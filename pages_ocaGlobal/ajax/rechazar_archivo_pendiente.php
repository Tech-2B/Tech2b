<?php

/**
 * Archivo AJAX para rechazar un archivo pendiente de validación
 * Solo los Administradores pueden rechazar archivos
 */

session_start();

// Verificar permisos - Solo Administradores pueden rechazar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para rechazar archivos',
    'code' => 403
  ]);
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo json_encode([
    'success' => false,
    'message' => 'Error de conexión a la base de datos',
    'code' => 500
  ]);
  exit();
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener datos del POST
  $input = json_decode(file_get_contents('php://input'), true);
  $id_archivo_pendiente = isset($input['id_archivo_pendiente']) ? intval($input['id_archivo_pendiente']) : 0;
  $comentario_rechazo = isset($input['comentario_rechazo']) ? trim($input['comentario_rechazo']) : '';
  
  if ($id_archivo_pendiente <= 0) {
    echo json_encode([
      'success' => false,
      'message' => 'ID de archivo inválido',
      'code' => 400
    ]);
    exit();
  }
  
  if (empty($comentario_rechazo) || strlen($comentario_rechazo) < 10) {
    echo json_encode([
      'success' => false,
      'message' => 'El comentario de rechazo debe tener al menos 10 caracteres',
      'code' => 400
    ]);
    exit();
  }
  
  // Obtener información del archivo pendiente
  $query_archivo = "
    SELECT * FROM $tabla_archivos_pendientes_validacion 
    WHERE id_archivo_pendiente = ? AND activo = 1
  ";
  
  $stmt_archivo = $conn->prepare($query_archivo);
  $stmt_archivo->bind_param("i", $id_archivo_pendiente);
  $stmt_archivo->execute();
  $result_archivo = $stmt_archivo->get_result();
  
  if ($result_archivo->num_rows === 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Archivo no encontrado',
      'code' => 404
    ]);
    exit();
  }
  
  $archivo_pendiente = $result_archivo->fetch_assoc();
  $stmt_archivo->close();
  
  // Verificar que el archivo esté pendiente
  if ($archivo_pendiente['estatus_validacion'] !== 'pendiente') {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo ya ha sido procesado',
      'code' => 400
    ]);
    exit();
  }
  
  // Actualizar estatus del archivo pendiente
  $query_update = "
    UPDATE $tabla_archivos_pendientes_validacion 
    SET estatus_validacion = 'rechazado',
        comentario_rechazo = ?,
        fecha_validacion = NOW(),
        id_usuario_valido = ?
    WHERE id_archivo_pendiente = ?
  ";
  
  $stmt_update = $conn->prepare($query_update);
  $stmt_update->bind_param("sii", $comentario_rechazo, $_SESSION['id_usuario'], $id_archivo_pendiente);
  
  if (!$stmt_update->execute()) {
    throw new Exception("Error actualizando estatus del archivo: " . $stmt_update->error);
  }
  
  $stmt_update->close();
  
  // Registrar rechazo en historial de archivos
  registrarRechazoEnHistorial($conn, $archivo_pendiente, $comentario_rechazo);
  
  // Enviar notificación por correo al usuario que subió el archivo
  error_log("Llamando a función de envío de correo de rechazo");
  
  enviarNotificacionArchivoRechazado($conn, $archivo_pendiente, $comentario_rechazo);
  
  // Eliminar archivo temporal - DESHABILITADO TEMPORALMENTE
  $ruta_archivo_temporal = $archivo_pendiente['ruta_archivo_temporal'];
  if (file_exists($ruta_archivo_temporal)) {
    // unlink($ruta_archivo_temporal); // Comentado para no eliminar archivos del servidor
    error_log("Archivo temporal NO eliminado: " . $ruta_archivo_temporal);
  }
  
  echo json_encode([
    'success' => true,
    'message' => 'Archivo rechazado exitosamente',
    'data' => [
      'id_archivo_pendiente' => $id_archivo_pendiente,
      'comentario_rechazo' => $comentario_rechazo,
      'fecha_validacion' => date('Y-m-d H:i:s')
    ],
    'code' => 200
  ]);
  
} catch (Exception $e) {
  error_log("Error en rechazar_archivo_pendiente.php: " . $e->getMessage());
  
  echo json_encode([
    'success' => false,
    'message' => 'Error interno del servidor: ' . $e->getMessage(),
    'code' => 500
  ]);
} finally {
  if (isset($conn)) {
    $conn->close();
  }
}

/**
 * Función para enviar notificación por correo cuando un archivo es rechazado
 */
function enviarNotificacionArchivoRechazado($conn, $archivo_pendiente, $comentario_rechazo) {
  
  try {
    // Incluir PHPMailer
    include '../../phpLibraries/fwm_import_phpMailer.php';
    
    // Incluir variables globales
    include '../includes/variables.php';

    // Log para debugging
    error_log("Iniciando envío de notificación de rechazo para archivo ID: " . $archivo_pendiente['id_archivo_pendiente']);
    error_log("ID usuario que subió: " . $archivo_pendiente['id_usuario_subio']);
    
    // Obtener información del usuario que subió el archivo
    $query_usuario = "SELECT correo, nombre, apellido_paterno FROM $tabla_usuarios WHERE id_usuario = ?";
    
    $stmt_usuario = $conn->prepare($query_usuario);
    $stmt_usuario->bind_param("i", $archivo_pendiente['id_usuario_subio']);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    
    if ($result_usuario->num_rows === 0) {
      error_log("No se encontró el usuario que subió el archivo. ID: " . $archivo_pendiente['id_usuario_subio']);
      return;
    }
    
    $usuario = $result_usuario->fetch_assoc();
    $stmt_usuario->close();
    
    error_log("Usuario encontrado: " . $usuario['nombre'] . " " . $usuario['apellido_paterno'] . " - " . $usuario['correo']);
    
    // Obtener información del plan de acción
    $query_plan = "SELECT descripcion_plan_accion FROM $tabla_planes_accion_clientes WHERE id_plan_accion = ?";
    $stmt_plan = $conn->prepare($query_plan);
    $stmt_plan->bind_param("i", $archivo_pendiente['id_plan_accion']);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    
    $plan_info = "Plan de Acción #" . $archivo_pendiente['id_plan_accion'];
    if ($result_plan->num_rows > 0) {
      $plan_data = $result_plan->fetch_assoc();
      $plan_info = $plan_data['descripcion_plan_accion'];
    }
    $stmt_plan->close();

    // Obtener información del administrador que rechazó
    $query_admin = "SELECT nombre, apellido_paterno FROM $tabla_usuarios WHERE id_usuario = ?";
    $stmt_admin = $conn->prepare($query_admin);
    $stmt_admin->bind_param("i", $_SESSION['id_usuario']);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    
    $admin_nombre = "Administrador";
    if ($result_admin->num_rows > 0) {
      $admin_data = $result_admin->fetch_assoc();
      $admin_nombre = trim($admin_data['nombre'] . ' ' . $admin_data['apellido_paterno']);
    }
    $stmt_admin->close();
    
    // Configurar correo
    $mail->setFrom('field@tech2b.com.mx', 'Notificaciones Field');
    $mail->addBCC('eduardo.lara@tech2b.com.mx', 'Eduardo L');
    $mail->addAddress($usuario['correo'], trim($usuario['nombre'] . ' ' . $usuario['apellido_paterno']));
    
    $mail->Subject = "Archivo rechazado - Requiere corrección";
    $mail->AltBody = "Su archivo ha sido rechazado y requiere corrección";
    
    $fecha_actual = date('d/m/Y H:i:s');
    $nombre_archivo = basename($archivo_pendiente['ruta_archivo_temporal']);
    
    $msg = "
      <!DOCTYPE html>
      <html lang='es'>
      <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Archivo Rechazado</title>
      </head>
      <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
          
          <!-- Header -->
          <div style='background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;'>
              🚫 Archivo Rechazado
            </h1>
            <p style='color: #fed7d7; margin: 10px 0 0 0; font-size: 16px;'>
              Su archivo requiere corrección antes de ser aprobado
            </p>
          </div>
          
          <!-- Alert -->
          <div style='background-color: #fed7d7; border-left: 4px solid #e53e3e; padding: 20px; margin: 20px; border-radius: 4px;'>
            <div style='display: flex; align-items: center;'>
              <div style='font-size: 24px; margin-right: 15px;'>⚠️</div>
              <div>
                <h3 style='color: #742a2a; margin: 0 0 5px 0; font-size: 18px; font-weight: bold;'>
                  Acción Requerida
                </h3>
                <p style='color: #742a2a; margin: 0; font-size: 14px;'>
                  Su archivo ha sido revisado y rechazado. Por favor, revise los comentarios y corrija el archivo.
                </p>
              </div>
            </div>
          </div>
          
          <!-- Content -->
          <div style='padding: 0 30px 30px 30px;'>
            
            <!-- File Information -->
            <div style='background-color: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
              <h3 style='color: #2d3748; margin: 0 0 15px 0; font-size: 18px; font-weight: bold; display: flex; align-items: center;'>
                📄 Información del Archivo
              </h3>
              <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>
                <div>
                  <p style='margin: 0 0 5px 0; color: #4a5568; font-size: 14px; font-weight: bold;'>Nombre del Archivo:</p>
                  <p style='margin: 0; color: #2d3748; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;'>$nombre_archivo</p>
                </div>
                <div>
                  <p style='margin: 0 0 5px 0; color: #4a5568; font-size: 14px; font-weight: bold;'>Plan de Acción:</p>
                  <p style='margin: 0; color: #2d3748; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;'>$plan_info</p>
                </div>
                <div>
                  <p style='margin: 0 0 5px 0; color: #4a5568; font-size: 14px; font-weight: bold;'>Fecha de Rechazo:</p>
                  <p style='margin: 0; color: #2d3748; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;'>$fecha_actual</p>
                </div>
                <div>
                  <p style='margin: 0 0 5px 0; color: #4a5568; font-size: 14px; font-weight: bold;'>Rechazado por:</p>
                  <p style='margin: 0; color: #2d3748; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #e2e8f0;'>$admin_nombre</p>
                </div>
              </div>
            </div>
            
            <!-- Rejection Reason -->
            <div style='background-color: #fff5f5; border: 1px solid #fed7d7; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
              <h3 style='color: #742a2a; margin: 0 0 15px 0; font-size: 18px; font-weight: bold; display: flex; align-items: center;'>
                💬 Motivo del Rechazo
              </h3>
              <div style='background-color: #ffffff; border: 1px solid #fed7d7; border-radius: 6px; padding: 15px;'>
                <p style='margin: 0; color: #2d3748; font-size: 14px; line-height: 1.6; white-space: pre-wrap;'>$comentario_rechazo</p>
              </div>
            </div>
            
            <!-- Next Steps -->
            <div style='background-color: #f0fff4; border: 1px solid #9ae6b4; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
              <h3 style='color: #22543d; margin: 0 0 15px 0; font-size: 18px; font-weight: bold; display: flex; align-items: center;'>
                📋 Próximos Pasos
              </h3>
              <div style='color: #22543d; font-size: 14px; line-height: 1.6;'>
                <p style='margin: 0 0 10px 0;'>1. <strong>Revise los comentarios</strong> del administrador</p>
                <p style='margin: 0 0 10px 0;'>2. <strong>Corrija el archivo</strong> según las indicaciones</p>
                <p style='margin: 0 0 10px 0;'>3. <strong>Vuelva a subir</strong> el archivo corregido</p>
                <p style='margin: 0;'>4. <strong>Espere la nueva validación</strong> del administrador</p>
              </div>
            </div>
            
            <!-- Support -->
            <div style='text-align: center; padding: 20px; background-color: #f7fafc; border-radius: 8px; border: 1px solid #e2e8f0;'>
              <h3 style='color: #2d3748; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;'>
                ¿Necesita Ayuda?
              </h3>
              <p style='color: #4a5568; margin: 0; font-size: 14px; line-height: 1.5;'>
                Si tiene dudas sobre los comentarios o necesita asistencia,<br>
                contacte al administrador del sistema.
              </p>
            </div>
            
          </div>
          
          <!-- Footer -->
          <div style='background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 20px; text-align: center; border-radius: 0 0 8px 8px;'>
            <p style='color: #e2e8f0; font-size: 12px; margin: 0; line-height: 1.5;'>
              <strong>NOTA IMPORTANTE:</strong> Este correo se genera automáticamente.<br>
              Por favor no responda o reenvíe correos a esta cuenta de e-mail.<br>
            </p>
          </div>
          
        </div>
      </body>
      </html>
    ";
    
    $mail->MsgHTML($msg);
    
    error_log("Intentando enviar correo a: " . $usuario['correo']);
    $resultado_envio = $mail->send();
    
    if ($resultado_envio) {
      error_log("Notificación de archivo rechazado enviada exitosamente a: " . $usuario['correo']);
    } else {
      error_log("Error enviando correo: " . $mail->ErrorInfo);
    }
    
  } catch (Exception $e) {
    error_log("Error enviando notificación de archivo rechazado: " . $e->getMessage());
  }
}

/**
 * Función para registrar el rechazo en el historial de archivos
 */
function registrarRechazoEnHistorial($conn, $archivo_pendiente, $comentario_rechazo) {
  try {
    global $tabla_historialArchivos_planesAccion;
    
    // Actualizar el registro existente en el historial
    $query_update_historial = "
      UPDATE $tabla_historialArchivos_planesAccion 
      SET estatus_validacion = 'rechazado',
          comentario_rechazo = ?,
          fecha_validacion = NOW(),
          id_usuario_valido = ?
      WHERE id_registro = ? AND activo = 1
    ";
    
    $stmt_historial = $conn->prepare($query_update_historial);
    $stmt_historial->bind_param("sii", $comentario_rechazo, $_SESSION['id_usuario'], $archivo_pendiente['id_archivo_pendiente']);
    
    if ($stmt_historial->execute()) {
      error_log("Rechazo registrado en historial exitosamente");
    } else {
      error_log("Error registrando rechazo en historial: " . $stmt_historial->error);
    }
    
    $stmt_historial->close();
    
  } catch (Exception $e) {
    error_log("Error en registrarRechazoEnHistorial: " . $e->getMessage());
  }
}
?>
