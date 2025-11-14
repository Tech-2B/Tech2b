<?php

/**
 * Archivo para subir archivos a Google Drive
 * Operación: UPLOAD
 */

session_start();

// Verificar permisos de carga de archivos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para subir archivos',
    'code' => 403
  ]);
  exit();
}

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesGoogleDrive.php';

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

$id_usuario_subida = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Método no permitido'
  );
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  // Determinar el flujo según el rol
  $rol_usuario = $_SESSION['rol'];
  $subir_directamente = ($rol_usuario === 'Administrador');
  $funcionesDrive = new FuncionesGoogleDrive($conn);
  
  // Obtener datos del formulario
  $id_registro = $funciones->fnTrimDatosPost('id_registro');
  $id_cliente = $funciones->fnTrimDatosPost('id_cliente');
  $id_plan_accion = $funciones->fnTrimDatosPost('id_plan_accion');
  $carpeta_destino = $funciones->fnTrimDatosPost('carpeta_destino');
  $crear_nueva_carpeta = isset($_POST['crear_nueva_carpeta']) ? true : false;
  $nombre_nueva_carpeta = $funciones->fnTrimDatosPost('nombre_nueva_carpeta');
  $comentario = $funciones->fnTrimDatosPost('comentario_archivo');
  
  // Validaciones
  if (empty($id_cliente) || empty($id_plan_accion)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'ID de cliente o plan de acción no válido'
    );
    exit;
  }

  // Validar archivo
  if (!isset($_FILES['archivo_subir']) || $_FILES['archivo_subir']['error'] !== UPLOAD_ERR_OK) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'No se ha seleccionado un archivo válido'
    );
    exit;
  }

  $archivo = $_FILES['archivo_subir'];
  
  // Validar tamaño (100MB)
  if ($archivo['size'] > 100 * 1024 * 1024) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El archivo no puede ser mayor a 100MB'
    );
    exit;
  }

  // Validar tipo de archivo
  $tipos_permitidos = [
    // Documentos
    'pdf', 'docx', 'doc',
    // Hojas de cálculo
    'xlsx', 'xls',
    // Presentaciones
    'ppt', 'pptx',
    // Imágenes
    'png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp',
    // Archivos de texto
    'txt', 'csv', 'log',
    // Videos
    'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v'
  ];
  $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
  
  if (!in_array($extension, $tipos_permitidos)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Tipo de archivo no permitido. Formatos permitidos: ' . implode(', ', $tipos_permitidos)
    );
    exit;
  }

  // Determinar carpeta de destino
  $id_carpeta_destino = $carpeta_destino;
  
  // Validar que se haya seleccionado una carpeta
  if (empty($id_carpeta_destino) && !$crear_nueva_carpeta) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Debe seleccionar una carpeta de destino o crear una nueva'
    );
    exit;
  }
  
  if ($crear_nueva_carpeta) {
    if (empty($nombre_nueva_carpeta)) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error de validación',
        'Debe especificar el nombre de la nueva carpeta'
      );
      exit;
    }

    // Obtener la carpeta raíz del plan de acción
    $query_carpeta_raiz = "SELECT id_carpeta_drive FROM $tabla_carpetas_drive 
                          WHERE id_cliente = ? AND id_plan_accion = ? AND tipo_carpeta = 'plan_accion' 
                          AND estado_activo = 1 LIMIT 1";
    
    $resultado_carpeta_raiz = $funciones->fnBuscarDatosRegistro($conn, $query_carpeta_raiz, [$id_cliente, $id_plan_accion], 'ii');
    
    if (!$resultado_carpeta_raiz['success'] || empty($resultado_carpeta_raiz['datos'])) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error',
        'No se encontró la carpeta raíz del plan de acción'
      );
      exit;
    }

    $id_carpeta_raiz = $resultado_carpeta_raiz['datos'][0]['id_carpeta_drive'];

    // Crear nueva subcarpeta
    $resultado_nueva_carpeta = $funcionesDrive->crearSubcarpeta($id_cliente, $id_plan_accion, $nombre_nueva_carpeta, $id_carpeta_raiz);
    
    if (!$resultado_nueva_carpeta['success']) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error',
        'No se pudo crear la nueva carpeta: ' . $resultado_nueva_carpeta['error']
      );
      exit;
    }

    $id_carpeta_destino = $resultado_nueva_carpeta['id_carpeta'];
  } else {
    if (empty($carpeta_destino)) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error de validación',
        'Debe seleccionar una carpeta de destino'
      );
      exit;
    }
  }

  if ($subir_directamente) {
    // Flujo para Administradores: Subir directamente a Google Drive
    $resultado_subida = $funcionesDrive->subirArchivo(
      $archivo['tmp_name'], 
      $archivo['name'], 
      $id_carpeta_destino, 
      $comentario
    );

    if (!$resultado_subida['success']) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error',
        'No se pudo subir el archivo a Drive: ' . $resultado_subida['error']
      );
      exit;
    }

    // Guardar información del archivo en la base de datos
    $resultado_guardar = $funcionesDrive->guardarArchivoEnBD(
      $id_cliente, 
      $id_plan_accion, 
      $id_carpeta_destino, 
      $resultado_subida, 
      $comentario,
      $id_usuario_subida
    );
    
    // Registrar en historial de archivos (para administradores que suben directamente)
    if ($resultado_guardar['success']) {
      registrarEnHistorialArchivos($conn, null, $id_cliente, $id_plan_accion, $id_usuario_subida, $archivo, $comentario, 'aprobado', $resultado_subida);
    }
  } else {
    // Flujo para Colaboradores: Guardar en tabla de pendientes
    $directorio_temporal = '../../uploads/temp/';
    if (!is_dir($directorio_temporal)) {
      mkdir($directorio_temporal, 0755, true);
    }
    
    $nombre_archivo_sistema = uniqid() . '_' . $archivo['name'];
    $ruta_archivo_temporal = $directorio_temporal . $nombre_archivo_sistema;
    
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_archivo_temporal)) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error',
        'No se pudo guardar el archivo temporal'
      );
      exit;
    }
    
    // Insertar en tabla de archivos pendientes
    $query_insert = "
      INSERT INTO $tabla_archivos_pendientes_validacion (
        id_registro, id_cliente, id_plan_accion, id_carpeta_drive,
        nombre_archivo_original, nombre_archivo_sistema, ruta_archivo_temporal,
        tipo_archivo, tamano_archivo, comentario, estatus_validacion,
        fecha_subida, id_usuario_subio, activo
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW(), ?, 1)
    ";
    
    $stmt = $conn->prepare($query_insert);
    $stmt->bind_param(
      "iiisssssisi",
      $id_registro, $id_cliente, $id_plan_accion, $id_carpeta_destino,
      $archivo['name'], $nombre_archivo_sistema, $ruta_archivo_temporal,
      $extension, $archivo['size'], $comentario, $id_usuario_subida
    );
    
    if (!$stmt->execute()) {
      // Eliminar archivo temporal si falla la inserción - DESHABILITADO TEMPORALMENTE
      if (file_exists($ruta_archivo_temporal)) {
        // unlink($ruta_archivo_temporal); // Comentado para no eliminar archivos del servidor
        error_log("Archivo temporal NO eliminado tras error de inserción: " . $ruta_archivo_temporal);
      }
      
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_error,
        'Error',
        'No se pudo guardar el archivo pendiente: ' . $stmt->error
      );
      exit;
    }
    
    $id_archivo_pendiente = $conn->insert_id;
    $stmt->close();
    
    // Registrar en historial de archivos
    registrarEnHistorialArchivos($conn, $id_archivo_pendiente, $id_cliente, $id_plan_accion, $id_usuario_subida, $archivo, $comentario, 'pendiente');
    
    $resultado_guardar = [
      'success' => true,
      'message' => 'Archivo guardado pendiente de validación'
    ];
    
    // Enviar notificación por correo a administradores
    enviarNotificacionArchivoPendiente($conn, $id_cliente, $id_plan_accion, $archivo['name'], $comentario, $id_usuario_subida);
  }

  if (!$resultado_guardar['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error',
      'Archivo subido a Drive pero no se pudo guardar en la base de datos: ' . $resultado_guardar['error']
    );
    exit;
  }

  // Mensaje según el rol
  $mensaje_exito = $subir_directamente ? 
    'Archivo subido correctamente a Google Drive' : 
    'Archivo guardado pendiente de validación por un Administrador';
  
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_201,
    true,
    $icon_success,
    $titulo_exito,
    $mensaje_exito,
    ''
  );
  exit;

} catch (Exception $e) {
  error_log("Excepción capturada en subir_archivo_drive: " . $e->getMessage());
  error_log("Stack trace: " . $e->getTraceAsString());
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
  exit;
}

/**
 * Función para enviar notificación por correo cuando un colaborador sube un archivo pendiente
 */
function enviarNotificacionArchivoPendiente($conn, $id_cliente, $id_plan_accion, $nombre_archivo, $comentario, $id_usuario_subio) {
  try {
    // Incluir PHPMailer
    include '../../phpLibraries/fwm_import_phpMailer.php';
    global $tabla_usuarios;
    global $tabla_planes_accion_clientes;

    // Obtener información del colaborador que subió el archivo
    $query_usuario = "SELECT nombre, apellido_paterno FROM $tabla_usuarios WHERE id_usuario = ?";
    $stmt_usuario = $conn->prepare($query_usuario);
    $stmt_usuario->bind_param("i", $id_usuario_subio);
    $stmt_usuario->execute();
    $result_usuario = $stmt_usuario->get_result();
    $usuario_data = $result_usuario->fetch_assoc();
    $stmt_usuario->close();
    
    $nombre_colaborador = trim($usuario_data['nombre'] . ' ' . $usuario_data['apellido_paterno']);
    
    // Obtener información del plan de acción
    $query_plan = "SELECT descripcion_area_oportunidad, descripcion_plan_accion FROM $tabla_planes_accion_clientes WHERE id_plan_accion = ?";
    $stmt_plan = $conn->prepare($query_plan);
    $stmt_plan->bind_param("i", $id_plan_accion);
    $stmt_plan->execute();
    $result_plan = $stmt_plan->get_result();
    $plan_data = $result_plan->fetch_assoc();
    $stmt_plan->close();
    
    $area_oportunidad = $plan_data['descripcion_area_oportunidad'];
    $plan_accion = $plan_data['descripcion_plan_accion'];
    
    // Obtener correos de administradores
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
      
      $mail->Subject = "Nueva carga de archivo pendiente de validación";
      $mail->AltBody = "Nueva carga de archivo pendiente de validación";
      
      $fecha_actual = date('d/m/Y H:i:s');
      
      $msg = "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='UTF-8'>
          <meta name='viewport' content='width=device-width, initial-scale=1.0'>
          <title>Notificación OCA GLOBAL</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8f9fa;'>
          <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
            
            <!-- Header con logo y título -->
            <div style='background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0;'>
              <h1 style='color: #ffffff; font-size: 24px; font-weight: bold; margin: 0; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);'>
                📄 Nueva Carga de Archivo
              </h1>
              <p style='color: #e2e8f0; font-size: 16px; margin: 10px 0 0 0; font-weight: 300;'>
                Archivo pendiente de validación
              </p>
            </div>
            
            <!-- Contenido principal -->
            <div style='padding: 30px 20px;'>
              
              <!-- Alerta de acción requerida -->
              <div style='background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%); border-left: 5px solid #e53e3e; padding: 20px; margin-bottom: 25px; border-radius: 0 8px 8px 0;'>
                <div style='display: flex; align-items: center;'>
                  <span style='font-size: 24px; margin-right: 12px;'>⚠️</span>
                  <div>
                    <h3 style='color: #742a2a; font-size: 18px; font-weight: bold; margin: 0 0 5px 0;'>Acción Requerida</h3>
                    <p style='color: #742a2a; font-size: 14px; margin: 0; font-weight: 500;'>
                      Un colaborador ha subido un archivo que requiere su validación
                    </p>
                  </div>
                </div>
              </div>
              
              <!-- Información del archivo -->
              <div style='background-color: #f7fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px;'>
                <h2 style='color: #1a365d; font-size: 20px; font-weight: bold; margin: 0 0 20px 0; text-align: center; border-bottom: 2px solid #e53e3e; padding-bottom: 10px;'>
                  📋 Detalles del Archivo
                </h2>
                
                <div style='display: grid; gap: 15px;'>
                  <div style='display: flex; align-items: center; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #1a365d;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #1a365d;'>📁</span>
                    <div>
                      <strong style='color: #1a365d; font-size: 14px; display: block;'>Archivo:</strong>
                      <span style='color: #2d3748; font-size: 16px; font-weight: 600;'>$nombre_archivo</span>
                    </div>
                  </div>
                  
                  <div style='display: flex; align-items: center; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #e53e3e;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #e53e3e;'>👤</span>
                    <div>
                      <strong style='color: #1a365d; font-size: 14px; display: block;'>Colaborador:</strong>
                      <span style='color: #2d3748; font-size: 16px; font-weight: 600;'>$nombre_colaborador</span>
                    </div>
                  </div>
                  
                  <div style='display: flex; align-items: center; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #38a169;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #38a169;'>🏢</span>
                    <div>
                      <strong style='color: #1a365d; font-size: 14px; display: block;'>Área de Oportunidad:</strong>
                      <span style='color: #2d3748; font-size: 15px; font-weight: 500; line-height: 1.4;'>$area_oportunidad</span>
                    </div>
                  </div>
                  
                  <div style='display: flex; align-items: center; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #3182ce;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #3182ce;'>📋</span>
                    <div>
                      <strong style='color: #1a365d; font-size: 14px; display: block;'>Plan de Acción:</strong>
                      <span style='color: #2d3748; font-size: 15px; font-weight: 500; line-height: 1.4;'>$plan_accion</span>
                    </div>
                  </div>
                  
                  <div style='display: flex; align-items: center; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #805ad5;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #805ad5;'>⏰</span>
                    <div>
                      <strong style='color: #1a365d; font-size: 14px; display: block;'>Fecha y Hora:</strong>
                      <span style='color: #2d3748; font-size: 16px; font-weight: 600;'>$fecha_actual</span>
                    </div>
                  </div>
                  
                  <div style='display: flex; align-items: flex-start; background-color: #ffffff; padding: 12px; border-radius: 8px; border-left: 4px solid #d69e2e;'>
                    <span style='font-size: 18px; margin-right: 12px; color: #d69e2e; margin-top: 2px;'>💬</span>
                    <div style='flex: 1;'>
                      <strong style='color: #1a365d; font-size: 14px; display: block; margin-bottom: 5px;'>Comentarios:</strong>
                      <span style='color: #2d3748; font-size: 15px; font-weight: 500; line-height: 1.4; display: block;'>" . ($comentario ? $comentario : 'Sin comentarios') . "</span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Call to Action -->
              <div style='text-align: center; margin: 30px 0;'>
                <div style='background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); padding: 20px; border-radius: 12px; box-shadow: 0 4px 8px rgba(26, 54, 93, 0.3);'>
                  <h3 style='color: #ffffff; font-size: 18px; font-weight: bold; margin: 0 0 10px 0;'>
                    🚀 Acción Requerida
                  </h3>
                  <p style='color: #e2e8f0; font-size: 16px; margin: 0; font-weight: 500;'>
                    Por favor, revise y apruebe o rechace el archivo en el sistema
                  </p>
                </div>
              </div>
              
            </div>
            
            <!-- Footer -->
            <div style='padding: 20px; text-align: center; border-radius: 0 0 8px 8px;'>
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
      $mail->send();
      
      error_log("Notificación de archivo pendiente enviada exitosamente");
    } else {
      error_log("No se encontraron administradores para enviar notificación");
    }
    
  } catch (Exception $e) {
    error_log("Error al enviar notificación de archivo pendiente: " . $e->getMessage());
  }
}

/**
 * Función para registrar archivos en el historial
 */
function registrarEnHistorialArchivos($conn, $id_archivo_pendiente, $id_cliente, $id_plan_accion, $id_usuario_subio, $archivo, $comentario, $estatus, $resultado_drive = null) {
  try {
    global $tabla_historialArchivos_planesAccion;
    
    // Determinar la ruta del archivo
    $ruta_archivo = '';
    if ($estatus === 'pendiente') {
      $ruta_archivo = '../../uploads/temp/' . uniqid() . '_' . $archivo['name'];
    } elseif ($resultado_drive && isset($resultado_drive['ruta_archivo'])) {
      $ruta_archivo = $resultado_drive['ruta_archivo'];
    }
    
    $query_historial = "
      INSERT INTO $tabla_historialArchivos_planesAccion (
        id_registro, id_cliente, id_plan_accion, id_usuario_subio,
        nombre_archivo_original, ruta_archivo_temporal, tamano_archivo,
        tipo_archivo, comentario_subida, estatus_validacion,
        fecha_subida, activo
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)
    ";
    
    $stmt_historial = $conn->prepare($query_historial);
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    
    $stmt_historial->bind_param(
      "iiiissssss",
      $id_archivo_pendiente,
      $id_cliente,
      $id_plan_accion,
      $id_usuario_subio,
      $archivo['name'],
      $ruta_archivo,
      $archivo['size'],
      $extension,
      $comentario,
      $estatus
    );
    
    if ($stmt_historial->execute()) {
      error_log("Archivo registrado en historial exitosamente");
    } else {
      error_log("Error registrando en historial: " . $stmt_historial->error);
    }
    
    $stmt_historial->close();
    
  } catch (Exception $e) {
    error_log("Error en registrarEnHistorialArchivos: " . $e->getMessage());
  }
}
