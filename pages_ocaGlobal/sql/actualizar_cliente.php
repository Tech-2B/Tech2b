<?php

/**
 * Archivo para actualizar un cliente existente
 * Operación: UPDATE
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
  $nombre_cliente = $funciones->fnTrimDatosPost('nombre_cliente');
  $codigo_cliente = $funciones->fnTrimDatosPost('codigo_cliente');
  $tipo_cliente = $funciones->fnTrimDatosPost('tipo_cliente');
  $nombre_contacto = $funciones->fnTrimDatosPost('nombre_contacto');
  $telefono_cliente = $funciones->fnTrimDatosPost('telefono_cliente');
  $correo_electronico = $funciones->fnTrimDatosPost('correo_electronico');
  $direccion_cliente = $funciones->fnTrimDatosPost('direccion_cliente');
  $ciudad_estado = $funciones->fnTrimDatosPost('ciudad_estado');

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

  if (empty($nombre_cliente)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El nombre del cliente es obligatorio'
    );
  }

  // Limpiar datos
  $id_cliente = (int)$id_cliente;
  $nombre_cliente = $funciones->fnLimpiarCadena($nombre_cliente);
  $codigo_cliente = $funciones->fnLimpiarCadena($codigo_cliente);
  $tipo_cliente = $funciones->fnLimpiarCadena($tipo_cliente);
  $nombre_contacto = $funciones->fnLimpiarCadena($nombre_contacto);
  $telefono_cliente = $funciones->fnLimpiarCadena($telefono_cliente);
  $correo_electronico = $funciones->fnLimpiarCadena($correo_electronico);
  $direccion_cliente = $funciones->fnLimpiarCadena($direccion_cliente);
  $ciudad_estado = $funciones->fnLimpiarCadena($ciudad_estado);

  // Validar que el cliente exista
  $query_validar_existe = "SELECT id_cliente FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  $cliente_existe = $funciones->fnValidarExisteRegistro($conn, $query_validar_existe, [$id_cliente], 'i');

  if (!$cliente_existe) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El cliente no existe o ha sido eliminado'
    );
  }

  // Validar que el nombre del cliente no exista en otro registro
  $query_validar_nombre = "SELECT id_cliente FROM $tabla_clientes WHERE nombre_cliente = ? AND id_cliente != ? AND activo = 1";
  $nombre_existe = $funciones->fnValidarExisteRegistro($conn, $query_validar_nombre, [$nombre_cliente, $id_cliente], 'si');

  if ($nombre_existe) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Ya existe otro cliente con el nombre: ' . $nombre_cliente
    );
  }

  // Obtener ID del usuario de la sesión
  $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

  // Preparar consulta de actualización
  $query_actualizar = "UPDATE $tabla_clientes SET 
        nombre_cliente = ?, 
        codigo_cliente = ?, 
        tipo_cliente = ?, 
        nombre_contacto = ?, 
        telefono_cliente = ?, 
        correo_electronico = ?, 
        direccion_cliente = ?, 
        ciudad_estado = ?, 
        id_usuario_actualizacion = ?,
        fecha_actualizacion = CURRENT_TIMESTAMP
        WHERE id_cliente = ? AND activo = 1";

  $params = [
    $nombre_cliente,
    $codigo_cliente,
    $tipo_cliente,
    $nombre_contacto,
    $telefono_cliente,
    $correo_electronico,
    $direccion_cliente,
    $ciudad_estado,
    $id_usuario,
    $id_cliente
  ];

  $types = 'ssssssssii';

  // Ejecutar actualización
  $resultado = $funciones->fnActualizarRegistro($conn, $query_actualizar, $params, $types);

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_202,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_actualizado,
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
