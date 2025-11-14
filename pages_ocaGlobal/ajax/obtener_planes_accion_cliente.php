<?php

/**
 * Archivo AJAX para obtener los planes de acción de un cliente específico
 * Solo consulta, no modifica datos
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesPlanesAccion.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
}

try {
  $funciones = new FuncionesGenerales();
  $funcionesPlanes = new FuncionesPlanesAccion($conn);

  // Obtener ID del cliente
  $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

  if ($id_cliente <= 0) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'ID de cliente no válido'
    );
    exit;
  }

  // Obtener planes de acción del cliente
  $resultado = $funcionesPlanes->obtenerPlanesAccionCliente($id_cliente);

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
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
}
