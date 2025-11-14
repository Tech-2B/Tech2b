<?php

/**
 * Archivo AJAX para descargar archivos temporales
 * Solo para usuarios con rol "Administrador"
 */

session_start();

// Verificar permisos - Solo Administradores pueden descargar archivos temporales
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
  http_response_code(403);
  echo "No tienes permisos para descargar archivos temporales";
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev.php';
if ($conn->connect_error) {
  http_response_code(500);
  echo "Error de conexión a la base de datos";
  exit();
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener ID del archivo
  $id_archivo_pendiente = $funciones->fnLimpiarCadena($_GET['id'] ?? '');
  
  if (empty($id_archivo_pendiente)) {
    http_response_code(400);
    echo "ID de archivo requerido";
    exit();
  }
  
  // Obtener información del archivo pendiente
  $query = "SELECT * FROM $tabla_archivos_pendientes WHERE id_archivo_pendiente = ? AND activo = 1";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('i', $id_archivo_pendiente);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit();
  }
  
  $archivo = $result->fetch_assoc();
  $stmt->close();
  
  // Verificar que el archivo temporal existe
  if (!file_exists($archivo['ruta_archivo_temporal'])) {
    http_response_code(404);
    echo "El archivo temporal no existe";
    exit();
  }
  
  // Configurar headers para descarga
  $nombre_archivo = $archivo['nombre_archivo_original'];
  $ruta_archivo = $archivo['ruta_archivo_temporal'];
  $tipo_mime = mime_content_type($ruta_archivo);
  
  // Headers para forzar descarga
  header('Content-Type: ' . $tipo_mime);
  header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
  header('Content-Length: ' . filesize($ruta_archivo));
  header('Cache-Control: must-revalidate');
  header('Pragma: public');
  
  // Limpiar buffer de salida
  ob_clean();
  flush();
  
  // Leer y enviar el archivo
  readfile($ruta_archivo);
  
} catch (Exception $e) {
  http_response_code(500);
  echo "Error interno del servidor: " . $e->getMessage();
}

$conn->close();
?>
