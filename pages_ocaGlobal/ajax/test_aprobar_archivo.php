<?php

/**
 * Archivo de prueba para debuggear el problema de aprobación
 */

session_start();

// Verificar permisos
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

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo json_encode([
    'success' => false,
    'message' => 'Error de conexión a la base de datos: ' . $conn->connect_error,
    'code' => 500
  ]);
  exit();
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener datos del POST
  $input = json_decode(file_get_contents('php://input'), true);
  $id_archivo_pendiente = isset($input['id_archivo_pendiente']) ? intval($input['id_archivo_pendiente']) : 0;
  
  echo json_encode([
    'success' => true,
    'message' => 'Prueba exitosa',
    'data' => [
      'id_archivo_pendiente' => $id_archivo_pendiente,
      'session_rol' => $_SESSION['rol'],
      'session_id_usuario' => $_SESSION['id_usuario'] ?? 'no_set',
      'tabla_archivos_pendientes' => $tabla_archivos_pendientes_validacion
    ],
    'code' => 200
  ]);
  
} catch (Exception $e) {
  error_log("Error en test_aprobar_archivo.php: " . $e->getMessage());
  
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
