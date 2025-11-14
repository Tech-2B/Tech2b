<?php

/**
 * Archivo para descargar archivos pendientes (usado por Google Docs Viewer)
 */

session_start();

// Verificar permisos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  http_response_code(403);
  echo "No tienes permisos para acceder a archivos";
  exit();
}

// Obtener parámetros
$id_archivo_pendiente = isset($_GET['id_archivo']) ? intval($_GET['id_archivo']) : 0;

if ($id_archivo_pendiente <= 0) {
  http_response_code(400);
  echo "ID de archivo inválido";
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

try {
  // Obtener información del archivo
  $query = "SELECT nombre_archivo_original, ruta_archivo_temporal, tipo_archivo FROM $tabla_archivos_pendientes_validacion WHERE id_archivo_pendiente = ? AND activo = 1";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    throw new Exception("Error preparando consulta: " . $conn->error);
  }
  
  $stmt->bind_param("i", $id_archivo_pendiente);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit();
  }
  
  $archivo = $result->fetch_assoc();
  $stmt->close();
  
  $ruta_archivo = $archivo['ruta_archivo_temporal'];
  $nombre_archivo = $archivo['nombre_archivo_original'];
  $tipo_archivo = $archivo['tipo_archivo'];
  
  // Verificar que el archivo existe
  if (!file_exists($ruta_archivo)) {
    http_response_code(404);
    echo "El archivo no existe en el servidor";
    exit();
  }
  
  // Obtener información del archivo
  $tamano_archivo = filesize($ruta_archivo);
  $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
  
  // Determinar el Content-Type según la extensión
  $content_types = [
    // Documentos
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    
    // Hojas de cálculo
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    
    // Presentaciones
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    
    // Imágenes
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    
    // Archivos de texto
    'txt' => 'text/plain',
    'csv' => 'text/csv',
    'log' => 'text/plain',
    
    // Videos
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'wmv' => 'video/x-ms-wmv',
    'flv' => 'video/x-flv',
    'webm' => 'video/webm',
    'mkv' => 'video/x-matroska',
    'm4v' => 'video/x-m4v'
  ];
  
  $content_type = isset($content_types[$extension]) ? $content_types[$extension] : 'application/octet-stream';
  
  // Configurar headers para descarga (necesario para Google Docs Viewer)
  header('Content-Type: ' . $content_type);
  header('Content-Length: ' . $tamano_archivo);
  header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
  header('Cache-Control: private, max-age=3600');
  header('Pragma: cache');
  
  // Headers de seguridad para HTTPS
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  
  // Para archivos de Office, agregar headers adicionales
  if (in_array($extension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) {
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
  }
  
  // Limpiar buffer de salida
  if (ob_get_level()) {
    ob_end_clean();
  }
  
  // Leer y enviar el archivo
  $handle = fopen($ruta_archivo, 'rb');
  if ($handle === false) {
    http_response_code(500);
    echo "Error al abrir el archivo";
    exit();
  }
  
  // Enviar el archivo en chunks para archivos grandes
  $chunk_size = 8192; // 8KB por chunk
  while (!feof($handle)) {
    $chunk = fread($handle, $chunk_size);
    if ($chunk === false) {
      break;
    }
    echo $chunk;
    flush();
  }
  
  fclose($handle);
  
} catch (Exception $e) {
  error_log("Error en descargar_archivo.php: " . $e->getMessage());
  http_response_code(500);
  echo "Error interno del servidor";
} finally {
  if (isset($conn)) {
    $conn->close();
  }
}
?>
