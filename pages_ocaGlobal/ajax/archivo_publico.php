<?php

/**
 * Archivo público temporal para servir archivos con token
 */

session_start();

// Obtener token
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
  http_response_code(400);
  echo "Token requerido";
  exit();
}

// Verificar que el token existe en la sesión
$session_key = 'archivo_temp_' . $token;
if (!isset($_SESSION[$session_key])) {
  http_response_code(404);
  echo "Token inválido o expirado";
  exit();
}

$archivo_info = $_SESSION[$session_key];

// Verificar que el token no haya expirado (1 hora)
if (time() - $archivo_info['timestamp'] > 3600) {
  unset($_SESSION[$session_key]);
  http_response_code(410);
  echo "Token expirado";
  exit();
}

$ruta_archivo = $archivo_info['ruta'];
$nombre_archivo = $archivo_info['nombre'];

// Verificar que el archivo existe
if (!file_exists($ruta_archivo)) {
  http_response_code(404);
  echo "Archivo no encontrado";
  exit();
}

// Obtener información del archivo
$tamano_archivo = filesize($ruta_archivo);
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

// Determinar el Content-Type según la extensión
$content_types = [
  'pdf' => 'application/pdf',
  'jpg' => 'image/jpeg',
  'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'gif' => 'image/gif',
  'bmp' => 'image/bmp',
  'webp' => 'image/webp',
  'txt' => 'text/plain',
  'csv' => 'text/csv',
  'log' => 'text/plain',
  'doc' => 'application/msword',
  'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'xls' => 'application/vnd.ms-excel',
  'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'ppt' => 'application/vnd.ms-powerpoint',
  'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
];

$content_type = isset($content_types[$extension]) ? $content_types[$extension] : 'application/octet-stream';

// Configurar headers para visualización
header('Content-Type: ' . $content_type);
header('Content-Length: ' . $tamano_archivo);
header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
header('Cache-Control: public, max-age=3600');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

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
?>
