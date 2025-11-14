<?php

/**
 * Archivo para eliminar un cliente (borrado lógico)
 * Operación: UPDATE (cambiar activo = 0)
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

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
}


try {
  $funciones = new FuncionesGenerales();

  // Obtener y limpiar datos del POST
  $id_cliente = $funciones->fnTrimDatosPost('id_cliente');
  
  // Log para depuración
  error_log("Intentando eliminar cliente ID: " . $id_cliente);

  // Validaciones
  if (empty($id_cliente) || !is_numeric($id_cliente)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'ID de cliente inválido'
    );
  }

  $id_cliente = (int)$id_cliente;

  // Validar que el cliente exista y esté activo
  $query_validar = "SELECT id_cliente, nombre_cliente FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  $cliente_existe = $funciones->fnValidarExisteRegistro($conn, $query_validar, [$id_cliente], 'i');

  if (!$cliente_existe) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El cliente no existe o ya ha sido eliminado'
    );
  }

  // Obtener ID del usuario de la sesión
  $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

  // Preparar consulta de borrado lógico
  $query_eliminar = "UPDATE $tabla_clientes SET 
        activo = 0, 
        id_usuario_actualizacion = ?,
        fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_cliente = ? AND activo = 1";

  $params = [$id_usuario, $id_cliente];
  $types = 'ii';

  // Ejecutar borrado lógico
  $resultado = $funciones->fnActualizarRegistro($conn, $query_eliminar, $params, $types);
  
  // Log del resultado
  error_log("Resultado de eliminación: " . json_encode($resultado));

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_203,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_eliminado,
      ''
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      $titulo_ocurrio_error,
      $mensaje_ocurrio_error,
      ''
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
