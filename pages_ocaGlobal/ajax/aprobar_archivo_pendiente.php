<?php

/**
 * Archivo AJAX para aprobar un archivo pendiente de validación
 * Solo los Administradores pueden aprobar archivos
 */

session_start();

// Verificar permisos - Solo Administradores pueden aprobar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para aprobar archivos',
    'code' => 403
  ]);
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesGoogleDrive.php';

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

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

use Google\Client;
use Google\Service\Drive;

try {
  $funciones = new FuncionesGenerales();
  
  // Log para debugging
  error_log("Iniciando proceso de aprobación de archivo");
  
  // Obtener datos del POST
  $input = json_decode(file_get_contents('php://input'), true);
  $id_archivo_pendiente = isset($input['id_archivo_pendiente']) ? intval($input['id_archivo_pendiente']) : 0;
  
  error_log("ID archivo pendiente: " . $id_archivo_pendiente);
  
  if ($id_archivo_pendiente <= 0) {
    echo json_encode([
      'success' => false,
      'message' => 'ID de archivo inválido',
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
  
  // Validar y corregir ID de carpeta de Drive
  if (empty($archivo_pendiente['id_carpeta_drive']) || $archivo_pendiente['id_carpeta_drive'] === '1') {
    // Usar la carpeta principal por defecto
    $archivo_pendiente['id_carpeta_drive'] = $id_carpeta_principal_drive;
    error_log("ID de carpeta inválido, usando carpeta principal: " . $archivo_pendiente['id_carpeta_drive']);
  }
  
  // Verificar que el archivo esté pendiente
  if ($archivo_pendiente['estatus_validacion'] !== 'pendiente') {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo ya ha sido procesado',
      'code' => 400
    ]);
    exit();
  }
  
  // Verificar que el archivo temporal existe
  $ruta_archivo_temporal = $archivo_pendiente['ruta_archivo_temporal'];
  if (!file_exists($ruta_archivo_temporal)) {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo temporal no existe',
      'code' => 404
    ]);
    exit();
  }
  
  // Inicializar Google Drive API
  try {
    $client = new Client();
    $credentials_path = __DIR__ . '/../drive/credenciales/ocaconstruccion-b8ddbf846879.json';
    
    if (!file_exists($credentials_path)) {
      throw new Exception("Archivo de credenciales no encontrado: " . $credentials_path);
    }
    
    $client->setAuthConfig($credentials_path);
    $client->addScope(Drive::DRIVE);
    $client->setAccessType('offline');
    
    $drive = new Drive($client);
    error_log("Google Drive API inicializada correctamente");
  } catch (Exception $e) {
    error_log("Error inicializando Google Drive API: " . $e->getMessage());
    throw new Exception("Error inicializando Google Drive API: " . $e->getMessage());
  }
  
  // Buscar la carpeta correcta del plan de acción
  $id_carpeta_correcta = null;
  
  // Primero intentar con el ID guardado
  if (!empty($archivo_pendiente['id_carpeta_drive']) && $archivo_pendiente['id_carpeta_drive'] !== '1') {
    try {
      error_log("Verificando existencia de carpeta guardada: " . $archivo_pendiente['id_carpeta_drive']);
      $carpeta = $drive->files->get($archivo_pendiente['id_carpeta_drive'], [
        'fields' => 'id,name,mimeType',
        'supportsAllDrives' => true
      ]);
      error_log("Carpeta encontrada: " . $carpeta->getName() . " (ID: " . $carpeta->getId() . ")");
      $id_carpeta_correcta = $archivo_pendiente['id_carpeta_drive'];
    } catch (Exception $e) {
      error_log("Error verificando carpeta guardada: " . $e->getMessage());
      error_log("Detalles del error: " . $e->getTraceAsString());
    }
  }
  
  
  // Si no se encontró la carpeta guardada, buscar en la base de datos
  if (!$id_carpeta_correcta) {
    error_log("Buscando carpeta del plan de acción en la base de datos");
    $query_carpeta = "
      SELECT id_carpeta_drive 
      FROM $tabla_carpetas_drive 
      WHERE id_plan_accion = ? AND tipo_carpeta = 'plan_accion' AND estado_activo = 1
      ORDER BY fecha_creacion DESC 
      LIMIT 1
    ";
    
    $stmt_carpeta = $conn->prepare($query_carpeta);
    $stmt_carpeta->bind_param("i", $archivo_pendiente['id_plan_accion']);
    $stmt_carpeta->execute();
    $result_carpeta = $stmt_carpeta->get_result();
    
    if ($result_carpeta->num_rows > 0) {
      $carpeta_data = $result_carpeta->fetch_assoc();
      $id_carpeta_correcta = $carpeta_data['id_carpeta_drive'];
      error_log("Carpeta encontrada en BD: " . $id_carpeta_correcta);
      
      // Verificar que existe en Google Drive
      try {
        $carpeta = $drive->files->get($id_carpeta_correcta, [
          'fields' => 'id,name,mimeType',
          'supportsAllDrives' => true
        ]);
        error_log("Carpeta de BD verificada en Drive: " . $carpeta->getName());
      } catch (Exception $e) {
        error_log("Carpeta de BD no existe en Drive: " . $e->getMessage());
        $id_carpeta_correcta = null;
      }
    }
    $stmt_carpeta->close();
  }
  
  // Si no se encontró ninguna carpeta válida, usar la carpeta principal
  if (!$id_carpeta_correcta) {
    $id_carpeta_correcta = $id_carpeta_principal_drive;
    error_log("Usando carpeta principal como fallback: " . $id_carpeta_correcta);
  }
  
  $archivo_pendiente['id_carpeta_drive'] = $id_carpeta_correcta;
  
  // Subir archivo a Google Drive
  try {
    error_log("Iniciando subida a Google Drive");
    error_log("Ruta archivo temporal: " . $ruta_archivo_temporal);
    error_log("ID carpeta Drive: " . $archivo_pendiente['id_carpeta_drive']);
    error_log("Datos del archivo pendiente: " . json_encode($archivo_pendiente));
    
    error_log("ID carpeta Drive final: " . $archivo_pendiente['id_carpeta_drive']);
    error_log("Nombre archivo original: " . $archivo_pendiente['nombre_archivo_original']);
    $fileMetadata = new Drive\DriveFile([
      'name' => $archivo_pendiente['nombre_archivo_original'],
      'parents' => [$archivo_pendiente['id_carpeta_drive']]
    ]);
    
    // Verificar que el archivo temporal existe y es legible
    if (!file_exists($ruta_archivo_temporal)) {
      throw new Exception("El archivo temporal no existe: " . $ruta_archivo_temporal);
    }
    
    if (!is_readable($ruta_archivo_temporal)) {
      throw new Exception("El archivo temporal no es legible: " . $ruta_archivo_temporal);
    }
    
    error_log("Archivo temporal existe y es legible: " . $ruta_archivo_temporal);
    error_log("Tamaño del archivo: " . filesize($ruta_archivo_temporal) . " bytes");
    
    $content = file_get_contents($ruta_archivo_temporal);
    if ($content === false) {
      throw new Exception("No se pudo leer el archivo temporal");
    }
    
    $mimeType = mime_content_type($ruta_archivo_temporal);
    if ($mimeType === false) {
      $mimeType = 'application/octet-stream';
    }
    
    error_log("MIME type: " . $mimeType);
    error_log("Tamaño archivo: " . strlen($content) . " bytes");
    
    // Debugging del metadata del archivo
    error_log("Metadata del archivo: " . json_encode([
      'name' => $fileMetadata->getName(),
      'parents' => $fileMetadata->getParents()
    ]));
    
    error_log("Iniciando creación del archivo en Google Drive...");
    $file = $drive->files->create($fileMetadata, [
      'data' => $content,
      'mimeType' => $mimeType,
      'uploadType' => 'multipart',
      'fields' => 'id,name,webViewLink,webContentLink',
      'supportsAllDrives' => true
    ]);
    
    error_log("Archivo subido exitosamente a Google Drive. ID: " . $file->getId());
  } catch (Exception $e) {
    error_log("Error subiendo archivo a Google Drive: " . $e->getMessage());
    throw new Exception("Error subiendo archivo a Google Drive: " . $e->getMessage());
  }
  
  $id_usuario_actual = isset($_SESSION['id_usuario']) ? intval($_SESSION['id_usuario']) : 1;

  $get_id = $file->getId();
  $get_name = $file->getName();
  $nombre_archivo_original = $archivo_pendiente['nombre_archivo_original'];
  $tipo_archivo = $archivo_pendiente['tipo_archivo'];
  $tamano_archivo = $archivo_pendiente['tamano_archivo'];
  $id_carpeta_drive = $archivo_pendiente['id_carpeta_drive'];
  $id_cliente = $archivo_pendiente['id_cliente'];
  $id_plan_accion = $archivo_pendiente['id_plan_accion'];
  $comentario = $archivo_pendiente['comentario'];
  $url_drive = $file->getWebViewLink();
  $url_descarga = $file->getWebContentLink();
  $id_usuario_subio = $archivo_pendiente['id_usuario_subio'];
  $fecha_subida = $archivo_pendiente['fecha_subida'];
  $estado_activo = 1;
  $id_usuario_valida = $id_usuario_actual;
  $fecha_valida = date('Y-m-d H:i:s');
  
  $query_insert =
    "INSERT INTO $tabla_archivos_drive (
      id_archivo_drive, nombre_archivo, nombre_archivo_original, tipo_archivo, tamano_archivo,
      id_carpeta_drive, id_cliente, id_plan_accion, comentario, url_drive,
      url_descarga, id_usuario_subida, fecha_subida, estado_activo, id_usuario_valida,
      fecha_valida
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ";
  
  $stmt_insert = $conn->prepare($query_insert);
  // echo "aqui 2<br>";
  if (!$stmt_insert) {
    // echo "aqui 3<br>";
    throw new Exception("Error preparando consulta de inserción: " . $conn->error);
  }
  
  $stmt_insert->bind_param(
    "ssssisiisssisiis",
    $get_id, $get_name, $nombre_archivo_original, $tipo_archivo, $tamano_archivo,
    $id_carpeta_drive, $id_cliente, $id_plan_accion, $comentario, $url_drive,
    $url_descarga, $id_usuario_subio, $fecha_subida, $estado_activo, $id_usuario_valida,
    $fecha_valida
  );
  
  error_log("Ejecutando consulta de inserción...");
  if (!$stmt_insert->execute()) {
    error_log("Error en execute: " . $stmt_insert->error);
    error_log("Código de error: " . $stmt_insert->errno);
    throw new Exception("Error insertando archivo en Drive: " . $stmt_insert->error);
  }
  error_log("Consulta de inserción ejecutada exitosamente");
  $id_archivo_drive = $conn->insert_id;
  
  $stmt_insert->close();
  
  // Actualizar estatus del archivo pendiente
  $query_update =
    "UPDATE $tabla_archivos_pendientes_validacion 
    SET estatus_validacion = 'aprobado',
      fecha_validacion = NOW(),
      id_usuario_valido = ?
    WHERE id_archivo_pendiente = ?
  ";
  
  $stmt_update = $conn->prepare($query_update);
  if (!$stmt_update) {
    throw new Exception("Error preparando consulta de actualización: " . $conn->error);
  }
  
  $stmt_update->bind_param("ii", $id_usuario_actual, $id_archivo_pendiente);
  
  if (!$stmt_update->execute()) {
    throw new Exception("Error actualizando estatus del archivo: " . $stmt_update->error);
  }
  
  $stmt_update->close();
  
  // Eliminar archivo temporal - DESHABILITADO TEMPORALMENTE
  if (file_exists($ruta_archivo_temporal)) {
    // unlink($ruta_archivo_temporal); // Comentado para no eliminar archivos del servidor
    error_log("Archivo temporal NO eliminado: " . $ruta_archivo_temporal);
  }
  
  echo json_encode([
    'success' => true,
    'message' => 'Archivo aprobado y subido a Google Drive exitosamente',
    'data' => [
      'id_archivo_drive' => $id_archivo_drive,
      'url_drive' => $file->getWebViewLink(),
      'url_descarga' => $file->getWebContentLink()
    ],
    'code' => 200
  ]);
  
} catch (Exception $e) {
  error_log("Error en aprobar_archivo_pendiente.php: " . $e->getMessage());
  
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
?>
