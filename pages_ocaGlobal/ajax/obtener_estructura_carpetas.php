<?php

/**
 * Archivo AJAX para obtener la estructura de carpetas de un plan de acción
 * Usa la misma lógica que obtener_carpetas_plan_accion.php
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesGoogleDrive.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

// Verificar que la conexión se haya establecido correctamente
if (!isset($conn)) {
  error_log("Error: Variable \$conn no está definida");
  echo json_encode([
    'code' => 500,
    'success' => false,
    'icon' => 'error',
    'title' => 'Error de conexión',
    'message' => 'No se pudo establecer conexión a la base de datos'
  ]);
  exit;
}

if ($conn->connect_error) {
  error_log("Error de conexión a la base de datos: " . $conn->connect_error);
  echo json_encode([
    'code' => 500,
    'success' => false,
    'icon' => 'error',
    'title' => 'Error de conexión',
    'message' => 'Error de conexión: ' . $conn->connect_error
  ]);
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener parámetros
  $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
  $id_plan_accion = isset($_GET['id_plan_accion']) ? (int)$_GET['id_plan_accion'] : 0;

  if ($id_cliente <= 0 || $id_plan_accion <= 0) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Parámetros no válidos'
    );
    exit;
  }

  // Verificar que la conexión esté disponible antes de crear FuncionesGoogleDrive
  if (!$conn || $conn->connect_error) {
    error_log("Error: Conexión a la base de datos no válida");
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de conexión',
      'No se pudo establecer conexión a la base de datos'
    );
    exit;
  }

  // Crear instancia de FuncionesGoogleDrive
  try {
    $funcionesDrive = new FuncionesGoogleDrive($conn);
  } catch (Exception $e) {
    error_log("Error creando FuncionesGoogleDrive: " . $e->getMessage());
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de inicialización',
      'No se pudo inicializar FuncionesGoogleDrive: ' . $e->getMessage()
    );
    exit;
  }

  // Obtener carpetas del plan de acción (misma lógica que obtener_carpetas_plan_accion.php)
  $resultado = $funcionesDrive->obtenerCarpetasPlanAccion($id_cliente, $id_plan_accion);

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_encontrado,
      $resultado['datos']
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      false,
      $icon_info,
      $titulo_sin_informacion,
      $mensaje_no_encontrado,
      []
    );
  }
} catch (Exception $e) {
  error_log("Error en obtener_estructura_carpetas.php: " . $e->getMessage());
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