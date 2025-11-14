<?php

/**
 * Archivo AJAX para eliminar un archivo de Google Drive - DESHABILITADO TEMPORALMENTE
 */

session_start();

// FUNCIONALIDAD DE ELIMINACIÓN DESHABILITADA TEMPORALMENTE
echo json_encode([
  'success' => false,
  'message' => 'La eliminación de archivos está deshabilitada temporalmente',
  'code' => 503
]);
exit();

// Código original comentado:
// // Verificar permisos de eliminación
// if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
//   echo json_encode([
//     'success' => false,
//     'message' => 'No tienes permisos para eliminar archivos',
//     'code' => 403
//   ]);
//   exit();
// }

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesGoogleDrive.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
}

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

$id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

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
  
  // Obtener datos del JSON
  $input = json_decode(file_get_contents('php://input'), true);
  $id_archivo = isset($input['id_archivo']) ? (int)$input['id_archivo'] : 0;

  if ($id_archivo <= 0) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'ID de archivo no válido'
    );
    exit;
  }

  // Crear instancia de FuncionesGoogleDrive
  $funcionesDrive = new FuncionesGoogleDrive($conn);

  // Eliminar archivo
  $resultado = $funcionesDrive->eliminarArchivo($id_archivo, $id_usuario);

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      'Archivo eliminado',
      'El archivo se eliminó correctamente'
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error eliminando archivo',
      $resultado['error']
    );
  }

} catch (Exception $e) {
  error_log("Error en eliminar_archivo_drive.php: " . $e->getMessage());
  error_log("Stack trace: " . $e->getTraceAsString());
  
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
}
?>
