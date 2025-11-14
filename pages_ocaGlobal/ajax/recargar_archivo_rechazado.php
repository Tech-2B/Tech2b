<?php

/**
 * Archivo AJAX para recargar un archivo rechazado
 * Permite a los colaboradores subir un nuevo archivo para reemplazar uno rechazado
 */

session_start();

// Verificar permisos - Solo Colaboradores pueden recargar archivos rechazados
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Colaborador') {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para recargar archivos',
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
  
  // Obtener datos del POST (FormData)
  $id_archivo_rechazado = isset($_POST['id_archivo_rechazado']) ? intval($_POST['id_archivo_rechazado']) : 0;
  $comentario_nuevo = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
  
  // Debug temporal
  error_log("Datos recibidos en recargar_archivo_rechazado.php:");
  error_log("POST data: " . print_r($_POST, true));
  error_log("FILES data: " . print_r($_FILES, true));
  error_log("ID archivo rechazado: " . $id_archivo_rechazado);
  
  if ($id_archivo_rechazado <= 0) {
    echo json_encode([
      'success' => false,
      'message' => 'ID de archivo inválido. Recibido: ' . $id_archivo_rechazado,
      'code' => 400,
      'debug' => [
        'post_data' => $_POST,
        'files_data' => $_FILES
      ]
    ]);
    exit();
  }
  
  // Verificar que se haya enviado un archivo
  if (!isset($_FILES['archivo_nuevo']) || $_FILES['archivo_nuevo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
      'success' => false,
      'message' => 'Debe seleccionar un archivo',
      'code' => 400
    ]);
    exit();
  }
  
  $archivo_nuevo = $_FILES['archivo_nuevo'];
  
  // Validar tamaño (100MB)
  if ($archivo_nuevo['size'] > 100 * 1024 * 1024) {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo no puede ser mayor a 100MB',
      'code' => 400
    ]);
    exit();
  }
  
  // Validar tipo de archivo
  $tipos_permitidos = [
    'pdf', 'docx', 'doc', 'xlsx', 'xls', 'ppt', 'pptx',
    'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp',
    'txt', 'csv', 'log', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v'
  ];
  $extension = strtolower(pathinfo($archivo_nuevo['name'], PATHINFO_EXTENSION));
  
  if (!in_array($extension, $tipos_permitidos)) {
    echo json_encode([
      'success' => false,
      'message' => 'Tipo de archivo no permitido',
      'code' => 400
    ]);
    exit();
  }
  
  // Obtener información del archivo rechazado
  $query_archivo = "
    SELECT * FROM $tabla_archivos_pendientes_validacion 
    WHERE id_archivo_pendiente = ? AND estatus_validacion = 'rechazado' AND activo = 1
  ";
  
  $stmt_archivo = $conn->prepare($query_archivo);
  $stmt_archivo->bind_param("i", $id_archivo_rechazado);
  $stmt_archivo->execute();
  $result_archivo = $stmt_archivo->get_result();
  
  if ($result_archivo->num_rows === 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Archivo rechazado no encontrado',
      'code' => 404
    ]);
    exit();
  }
  
  $archivo_rechazado = $result_archivo->fetch_assoc();
  $stmt_archivo->close();
  
  // Verificar que el usuario actual sea el que subió el archivo original
  if ($archivo_rechazado['id_usuario_subio'] != $_SESSION['id_usuario']) {
    echo json_encode([
      'success' => false,
      'message' => 'No tienes permisos para recargar este archivo',
      'code' => 403
    ]);
    exit();
  }
  
  // Guardar el nuevo archivo temporalmente
  $directorio_temporal = '../../uploads/temp/';
  if (!is_dir($directorio_temporal)) {
    mkdir($directorio_temporal, 0755, true);
  }
  
  $nombre_archivo_sistema = uniqid() . '_' . $archivo_nuevo['name'];
  $ruta_archivo_temporal = $directorio_temporal . $nombre_archivo_sistema;
  
  if (!move_uploaded_file($archivo_nuevo['tmp_name'], $ruta_archivo_temporal)) {
    echo json_encode([
      'success' => false,
      'message' => 'Error al guardar el archivo temporalmente',
      'code' => 500
    ]);
    exit();
  }
  
  // Actualizar el archivo rechazado con el nuevo archivo
  $query_update =
    "UPDATE $tabla_archivos_pendientes_validacion 
    SET nombre_archivo_original = ?,
        nombre_archivo_sistema = ?,
        ruta_archivo_temporal = ?,
        tipo_archivo = ?,
        tamano_archivo = ?,
        comentario = ?,
        estatus_validacion = 'pendiente',
        comentario_rechazo = NULL,
        fecha_validacion = NULL,
        id_usuario_valido = NULL,
        fecha_subida = NOW()
    WHERE id_archivo_pendiente = ?
  ";
  
  $stmt_update = $conn->prepare($query_update);
  $stmt_update->bind_param(
    "ssssisi",
    $archivo_nuevo['name'],
    $nombre_archivo_sistema,
    $ruta_archivo_temporal,
    $extension,
    $archivo_nuevo['size'],
    $comentario_nuevo,
    $id_archivo_rechazado
  );
  
  if (!$stmt_update->execute()) {
    // Eliminar archivo temporal si falla la actualización - DESHABILITADO TEMPORALMENTE
    // unlink($ruta_archivo_temporal); // Comentado para no eliminar archivos del servidor
    error_log("Archivo temporal NO eliminado tras error: " . $ruta_archivo_temporal);
    throw new Exception("Error actualizando archivo: " . $stmt_update->error);
  }
  
  $stmt_update->close();
  
  // Registrar en historial de archivos
  registrarRecargaEnHistorial($conn, $archivo_rechazado, $archivo_nuevo, $comentario_nuevo);
  
  // Enviar notificación por correo a administradores
  enviarNotificacionArchivoPendiente($conn, $archivo_rechazado['id_cliente'], $archivo_rechazado['id_plan_accion'], $archivo_nuevo['name'], $comentario_nuevo, $_SESSION['id_usuario']);
  
  echo json_encode([
    'success' => true,
    'message' => 'Archivo recargado exitosamente y pendiente de validación',
    'data' => [
      'id_archivo_pendiente' => $id_archivo_rechazado,
      'nombre_archivo' => $archivo_nuevo['name'],
      'fecha_subida' => date('Y-m-d H:i:s')
    ],
    'code' => 200
  ]);
  
} catch (Exception $e) {
  error_log("Error en recargar_archivo_rechazado.php: " . $e->getMessage());
  
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
 * Función para registrar la recarga en el historial de archivos
 */
function registrarRecargaEnHistorial($conn, $archivo_rechazado, $archivo_nuevo, $comentario_nuevo) {
  try {
    global $tabla_historialArchivos_planesAccion;
    
    // Insertar nuevo registro en el historial
    $query_historial = "
      INSERT INTO $tabla_historialArchivos_planesAccion (
        id_registro, id_cliente, id_plan_accion, id_usuario_subio,
        nombre_archivo_original, ruta_archivo_temporal, tamano_archivo,
        tipo_archivo, comentario_subida, estatus_validacion,
        fecha_subida, id_archivo_reemplazado, activo
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW(), ?, 1)
    ";
    
    $stmt_historial = $conn->prepare($query_historial);
    $extension = strtolower(pathinfo($archivo_nuevo['name'], PATHINFO_EXTENSION));
    $ruta_archivo = '../../uploads/temp/' . uniqid() . '_' . $archivo_nuevo['name'];
    
    $stmt_historial->bind_param(
      "iiiisssssi",
      $archivo_rechazado['id_archivo_pendiente'],
      $archivo_rechazado['id_cliente'],
      $archivo_rechazado['id_plan_accion'],
      $archivo_rechazado['id_usuario_subio'],
      $archivo_nuevo['name'],
      $ruta_archivo,
      $archivo_nuevo['size'],
      $extension,
      $comentario_nuevo,
      $archivo_rechazado['id_archivo_pendiente']
    );
    
    if ($stmt_historial->execute()) {
      error_log("Recarga registrada en historial exitosamente");
    } else {
      error_log("Error registrando recarga en historial: " . $stmt_historial->error);
    }
    
    $stmt_historial->close();
    
  } catch (Exception $e) {
    error_log("Error en registrarRecargaEnHistorial: " . $e->getMessage());
  }
}

/**
 * Función para enviar notificación por correo cuando se recarga un archivo
 */
function enviarNotificacionArchivoPendiente($conn, $id_cliente, $id_plan_accion, $nombre_archivo, $comentario, $id_usuario_subio) {
  try {
    // Incluir PHPMailer
    include '../../phpLibraries/fwm_import_phpMailer.php';
    include '../includes/variables.php';
    global $tabla_usuarios;
    global $tabla_planes_accion_clientes;

    // Obtener información del colaborador que subió el archivo
    $query_usuario = "SELECT nombre, apellido_paterno FROM $tabla_usuarios WHERE id_usuario = ?";
    $stmt_usuario = $conn->prepare($query_usuario);
    $stmt_usuario->bind_param("i", $id_usuario_subio);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    
    if ($result_usuario->num_rows === 0) {
      error_log("No se encontró el usuario que subió el archivo");
      return;
    }
    
    $usuario = $result_usuario->fetch_assoc();
    $stmt_usuario->close();
    $nombre_colaborador = trim($usuario['nombre'] . ' ' . $usuario['apellido_paterno']);
    
    // Obtener información del plan de acción
    $query_plan = "SELECT descripcion_plan_accion FROM $tabla_planes_accion_clientes WHERE id_plan_accion = ?";
    $stmt_plan = $conn->prepare($query_plan);
    $stmt_plan->bind_param("i", $id_plan_accion);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    
    $plan_info = "Plan de Acción #" . $id_plan_accion;
    if ($result_plan->num_rows > 0) {
      $plan_data = $result_plan->fetch_assoc();
      $plan_info = $plan_data['descripcion_plan_accion'];
    }
    $stmt_plan->close();
    
    // Obtener administradores
    $query_administradores = "SELECT correo, nombre, apellido_paterno FROM $tabla_usuarios WHERE rol = 'Administrador' AND id_empresa = 44 AND activo = 1";
    $result_administradores = $conn->query($query_administradores);
    
    if ($result_administradores->num_rows > 0) {
      // Configurar correo
      $mail->setFrom('field@tech2b.com.mx', 'Notificaciones Field');
      $mail->addBCC('eduardo.lara@tech2b.com.mx', 'Eduardo L');
      
      // Agregar administradores como destinatarios
      while ($admin = $result_administradores->fetch_assoc()) {
        $nombre_admin = trim($admin['nombre'] . ' ' . $admin['apellido_paterno']);
        $mail->addAddress($admin['correo'], $nombre_admin);
      }
      
      $mail->Subject = "Archivo recargado - Nueva validación requerida";
      $mail->AltBody = "Un colaborador ha recargado un archivo que requiere validación";
      
      $fecha_actual = date('d/m/Y H:i:s');
      
      $msg = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
          <meta charset='UTF-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1.0'>
          <title>Archivo Recargado</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
          <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #00a7b5 0%, #008a96 100%); padding: 30px; text-align: center;'>
              <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;'>
                🔄 Archivo Recargado
              </h1>
              <p style='color: #e6fffa; margin: 10px 0 0 0; font-size: 16px;'>
                Un colaborador ha recargado un archivo que requiere validación
              </p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
              <div style='background-color: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                <h3 style='color: #0369a1; margin: 0 0 15px 0; font-size: 18px; font-weight: bold;'>
                  📄 Información del Archivo Recargado
                </h3>
                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>
                  <div>
                    <p style='margin: 0 0 5px 0; color: #0369a1; font-size: 14px; font-weight: bold;'>Nombre del Archivo:</p>
                    <p style='margin: 0; color: #0c4a6e; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #bae6fd;'>$nombre_archivo</p>
                  </div>
                  <div>
                    <p style='margin: 0 0 5px 0; color: #0369a1; font-size: 14px; font-weight: bold;'>Plan de Acción:</p>
                    <p style='margin: 0; color: #0c4a6e; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #bae6fd;'>$plan_info</p>
                  </div>
                  <div>
                    <p style='margin: 0 0 5px 0; color: #0369a1; font-size: 14px; font-weight: bold;'>Colaborador:</p>
                    <p style='margin: 0; color: #0c4a6e; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #bae6fd;'>$nombre_colaborador</p>
                  </div>
                  <div>
                    <p style='margin: 0 0 5px 0; color: #0369a1; font-size: 14px; font-weight: bold;'>Fecha y Hora:</p>
                    <p style='margin: 0; color: #0c4a6e; font-size: 14px; background-color: #ffffff; padding: 8px; border-radius: 4px; border: 1px solid #bae6fd;'>$fecha_actual</p>
                  </div>
                </div>
              </div>
              
              <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;'>
                <h3 style='color: #374151; margin: 0 0 15px 0; font-size: 18px; font-weight: bold;'>
                  💬 Comentarios del Colaborador
                </h3>
                <div style='background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px;'>
                  <p style='margin: 0; color: #374151; font-size: 14px; line-height: 1.6; white-space: pre-wrap;'>$comentario</p>
                </div>
              </div>
              
              <div style='text-align: center; padding: 20px; background-color: #f0f9ff; border-radius: 8px; border: 1px solid #bae6fd;'>
                <h3 style='color: #0369a1; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;'>
                  ⚡ Acción Requerida
                </h3>
                <p style='color: #0369a1; margin: 0; font-size: 14px; line-height: 1.5;'>
                  Por favor, revise y valide el archivo recargado en el sistema.
                </p>
              </div>
              
            </div>
            
            <!-- Footer -->
            <div style='background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 20px; text-align: center; border-radius: 0 0 8px 8px;'>
              <p style='color: #e2e8f0; font-size: 12px; margin: 0; line-height: 1.5;'>
                <strong>NOTA IMPORTANTE:</strong> Este correo se genera automáticamente.<br>
                Por favor no responda o reenvíe correos a esta cuenta de e-mail.<br>
                <strong>OCA GLOBAL</strong> - Sistema de Gestión de Archivos
              </p>
            </div>
            
          </div>
        </body>
        </html>
      ";
      
      $mail->MsgHTML($msg);
      $mail->send();
      
      error_log("Notificación de archivo recargado enviada exitosamente");
    } else {
      error_log("No se encontraron administradores para enviar notificación");
    }
    
  } catch (Exception $e) {
    error_log("Error enviando notificación de archivo recargado: " . $e->getMessage());
  }
}
?>
