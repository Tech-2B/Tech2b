<?php

/**
 * Archivo AJAX para obtener las carpetas de un plan de acción específico
 * Solo consulta, no modifica datos
 */

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

  // Crear instancia de FuncionesGoogleDrive
  $funcionesDrive = new FuncionesGoogleDrive($conn);

  // Obtener carpetas del plan de acción
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
  error_log("Error en obtener_carpetas_plan_accion.php: " . $e->getMessage());
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
