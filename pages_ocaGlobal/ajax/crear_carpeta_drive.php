<?php

/**
 * Archivo AJAX para crear una nueva carpeta en Google Drive
 */

session_start();

// Verificar permisos de carga de archivos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para crear carpetas',
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
  echo "Error de conexión: " . $conn->connect_error;
}

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener parámetros
  $id_cliente = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 0;
  $id_plan_accion = isset($_POST['id_plan_accion']) ? (int)$_POST['id_plan_accion'] : 0;
  $nombre_carpeta = isset($_POST['nombre_carpeta']) ? trim($_POST['nombre_carpeta']) : '';

  if ($id_cliente <= 0 || $id_plan_accion <= 0 || empty($nombre_carpeta)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Parámetros no válidos'
    );
    exit;
  }

  // Crear instancia de FuncionesGoogleDrive
  $funcionesDrive = new FuncionesGoogleDrive($conn);

  // Crear la carpeta
  $resultado = $funcionesDrive->crearSubcarpetaPlanAccion($id_cliente, $id_plan_accion, $nombre_carpeta);

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_201,
      true,
      $icon_success,
      'Carpeta creada',
      'La carpeta se creó exitosamente',
      $resultado['datos']
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error creando carpeta',
      $resultado['error']
    );
  }

} catch (Exception $e) {
  error_log("Error en crear_carpeta_drive.php: " . $e->getMessage());
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
