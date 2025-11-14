<?php
/**
 * Archivo de prueba para verificar el envío de correo de rechazo
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';

if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Datos de prueba
$archivo_pendiente_test = [
  'id_archivo_pendiente' => 1,
  'id_usuario_subio' => 1, // Cambiar por un ID de usuario real
  'id_plan_accion' => 1,   // Cambiar por un ID de plan real
  'ruta_archivo_temporal' => 'test_archivo.pdf'
];

$comentario_rechazo_test = "Este es un comentario de prueba para verificar el envío de correo.";

echo "<h2>Prueba de Envío de Correo de Rechazo</h2>";

try {
  // Llamar a la función de envío
  enviarNotificacionArchivoRechazado($conn, $archivo_pendiente_test, $comentario_rechazo_test);
  echo "<p style='color: green;'>✅ Función ejecutada. Revisar logs para detalles.</p>";
} catch (Exception $e) {
  echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();

/**
 * Función para enviar notificación por correo cuando un archivo es rechazado
 */
function enviarNotificacionArchivoRechazado($conn, $archivo_pendiente, $comentario_rechazo) {
  try {
    // Incluir PHPMailer
    include '../phpLibraries/fwm_import_phpMailer.php';
    
    // Incluir variables globales
    include 'includes/variables.php';

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
      echo "<p style='color: orange;'>⚠️ No se encontró el usuario con ID: " . $archivo_pendiente['id_usuario_subio'] . "</p>";
      return;
    }
    
    $usuario = $result_usuario->fetch_assoc();
    $stmt_usuario->close();
    
    error_log("Usuario encontrado: " . $usuario['nombre'] . " " . $usuario['apellido_paterno'] . " - " . $usuario['correo']);
    echo "<p style='color: blue;'>👤 Usuario encontrado: " . $usuario['nombre'] . " " . $usuario['apellido_paterno'] . " - " . $usuario['correo'] . "</p>";
    
    // Obtener información del plan de acción
    $query_plan = "SELECT descripcion_plan FROM $tabla_planes_accion_clientes WHERE id_registro = ?";
    $stmt_plan = $conn->prepare($query_plan);
    $stmt_plan->bind_param("i", $archivo_pendiente['id_plan_accion']);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    
    $plan_info = "Plan de Acción #" . $archivo_pendiente['id_plan_accion'];
    if ($result_plan->num_rows > 0) {
      $plan_data = $result_plan->fetch_assoc();
      $plan_info = $plan_data['descripcion_plan'];
    }
    $stmt_plan->close();
    
    echo "<p style='color: blue;'>📋 Plan: " . $plan_info . "</p>";
    
    // Obtener información del administrador que rechazó
    $query_admin = "SELECT nombre, apellido_paterno FROM $tabla_usuarios WHERE id_usuario = ?";
    $stmt_admin = $conn->prepare($query_admin);
    $stmt_admin->bind_param("i", 1); // ID de prueba
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    
    $admin_nombre = "Administrador";
    if ($result_admin->num_rows > 0) {
      $admin_data = $result_admin->fetch_assoc();
      $admin_nombre = trim($admin_data['nombre'] . ' ' . $admin_data['apellido_paterno']);
    }
    $stmt_admin->close();
    
    echo "<p style='color: blue;'>👨‍💼 Admin: " . $admin_nombre . "</p>";
    
    // Configurar correo
    $mail->setFrom('field@tech2b.com.mx', 'Notificaciones Field');
    $mail->addBCC('eduardo.lara@tech2b.com.mx', 'Eduardo L');
    $mail->addAddress($usuario['correo'], trim($usuario['nombre'] . ' ' . $usuario['apellido_paterno']));
    
    $mail->Subject = "Archivo rechazado - Requiere corrección (PRUEBA)";
    $mail->AltBody = "Su archivo ha sido rechazado y requiere corrección";
    
    $fecha_actual = date('d/m/Y H:i:s');
    $nombre_archivo = basename($archivo_pendiente['ruta_archivo_temporal']);
    
    $msg = "
      <!DOCTYPE html>
      <html lang='es'>
      <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Archivo Rechazado - PRUEBA</title>
      </head>
      <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
          
          <!-- Header -->
          <div style='background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%); padding: 30px; text-align: center;'>
            <h1 style='color: #ffffff; margin: 0; font-size: 24px; font-weight: bold;'>
              🚫 Archivo Rechazado - PRUEBA
            </h1>
            <p style='color: #fed7d7; margin: 10px 0 0 0; font-size: 16px;'>
              Su archivo requiere corrección antes de ser aprobado
            </p>
          </div>
          
          <!-- Content -->
          <div style='padding: 30px;'>
            <p><strong>Nombre del Archivo:</strong> $nombre_archivo</p>
            <p><strong>Plan de Acción:</strong> $plan_info</p>
            <p><strong>Fecha de Rechazo:</strong> $fecha_actual</p>
            <p><strong>Rechazado por:</strong> $admin_nombre</p>
            <p><strong>Motivo del Rechazo:</strong></p>
            <div style='background-color: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 15px;'>
              <p style='margin: 0; color: #2d3748; font-size: 14px; line-height: 1.6; white-space: pre-wrap;'>$comentario_rechazo</p>
            </div>
          </div>
          
          <!-- Footer -->
          <div style='background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%); padding: 20px; text-align: center; border-radius: 0 0 8px 8px;'>
            <p style='color: #e2e8f0; font-size: 12px; margin: 0; line-height: 1.5;'>
              <strong>NOTA IMPORTANTE:</strong> Este es un correo de PRUEBA.<br>
              <strong>OCA GLOBAL</strong> - Sistema de Gestión de Archivos
            </p>
          </div>
          
        </div>
      </body>
      </html>
    ";
    
    $mail->MsgHTML($msg);
    
    error_log("Intentando enviar correo a: " . $usuario['correo']);
    echo "<p style='color: blue;'>📧 Intentando enviar correo a: " . $usuario['correo'] . "</p>";
    
    $resultado_envio = $mail->send();
    
    if ($resultado_envio) {
      error_log("Notificación de archivo rechazado enviada exitosamente a: " . $usuario['correo']);
      echo "<p style='color: green;'>✅ Correo enviado exitosamente a: " . $usuario['correo'] . "</p>";
    } else {
      error_log("Error enviando correo: " . $mail->ErrorInfo);
      echo "<p style='color: red;'>❌ Error enviando correo: " . $mail->ErrorInfo . "</p>";
    }
    
  } catch (Exception $e) {
    error_log("Error enviando notificación de archivo rechazado: " . $e->getMessage());
    echo "<p style='color: red;'>❌ Excepción: " . $e->getMessage() . "</p>";
  }
}
?>
