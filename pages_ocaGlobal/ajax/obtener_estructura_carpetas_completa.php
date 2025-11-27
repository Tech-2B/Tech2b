<?php

/**
 * Archivo AJAX para obtener la estructura completa de carpetas de forma recursiva
 * Retorna árbol jerárquico con todos los niveles
 */

// Iniciar sesión
session_start();

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
  $id_carpeta_padre = isset($_GET['id_carpeta_padre']) ? trim($_GET['id_carpeta_padre']) : null;

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

  // Obtener estructura completa de carpetas
  $resultado = $funcionesDrive->obtenerEstructuraCompletaCarpetas($id_cliente, $id_plan_accion, $id_carpeta_padre);

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
      $code_500,
      false,
      $icon_error,
      'Error',
      $resultado['error'] ?? 'Error obteniendo estructura de carpetas',
      []
    );
  }
} catch (Exception $e) {
  error_log("Error en obtener_estructura_carpetas_completa.php: " . $e->getMessage());
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

