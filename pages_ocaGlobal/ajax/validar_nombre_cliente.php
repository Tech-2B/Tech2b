<?php

/**
 * Archivo AJAX para validar si un nombre de cliente ya existe
 * Solo consulta, no modifica datos
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

// Verificar que se haya enviado el nombre
if (!isset($_GET['nombre_cliente']) || empty($_GET['nombre_cliente'])) {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    'Error de validación',
    'Nombre de cliente requerido'
  );
}



try {
  $funciones = new FuncionesGenerales();

  $nombre_cliente = $funciones->fnLimpiarCadena(trim($_GET['nombre_cliente']));
  $id_cliente_excluir = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

  // Consulta para verificar si el nombre existe
  if ($id_cliente_excluir > 0) {
    // Para edición: excluir el cliente actual
    $query = "SELECT id_cliente, nombre_cliente FROM $tabla_clientes 
                  WHERE nombre_cliente = ? AND id_cliente != ? AND activo = 1";
    $params = [$nombre_cliente, $id_cliente_excluir];
    $types = 'si';
  } else {
    // Para nuevo cliente
    $query = "SELECT id_cliente, nombre_cliente FROM $tabla_clientes 
                  WHERE nombre_cliente = ? AND activo = 1";
    $params = [$nombre_cliente];
    $types = 's';
  }

  $cliente_existe = $funciones->fnValidarExisteRegistro($conn, $query, $params, $types);

  if ($cliente_existe) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_warning,
      'Nombre duplicado',
      'Ya existe un cliente con este nombre',
      ['existe' => true, 'cliente' => $cliente_existe]
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      'Nombre disponible',
      'El nombre está disponible',
      ['existe' => false]
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
