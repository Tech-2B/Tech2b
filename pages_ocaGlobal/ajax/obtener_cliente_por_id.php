<?php

/**
 * Archivo AJAX para obtener un cliente específico por ID
 * Solo consulta, no modifica datos
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

// Verificar que se haya enviado el ID
if (!isset($_GET['id_cliente']) || empty($_GET['id_cliente'])) {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    'Error de validación',
    'ID de cliente requerido'
  );
}



try {
  $funciones = new FuncionesGenerales();

  $id_cliente = (int)$_GET['id_cliente'];

  // Consulta para obtener un cliente específico
  $query = "SELECT 
        id_cliente,
        nombre_cliente,
        codigo_cliente,
        tipo_cliente,
        nombre_contacto,
        telefono_cliente,
        correo_electronico,
        direccion_cliente,
        ciudad_estado,
        fecha_creacion,
        fecha_actualizacion
        FROM $tabla_clientes 
        WHERE id_cliente = ? AND activo = 1";

  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, [$id_cliente], 'i');

  if ($resultado['success']) {
    if (count($resultado['datos']) > 0) {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_200,
        true,
        $icon_success,
        'Consulta exitosa',
        'Cliente obtenido correctamente',
        $resultado['datos'][0] // Retornar solo el primer (y único) registro
      );
    } else {
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_500,
        false,
        $icon_info,
        'Cliente no encontrado',
        'No se encontró el cliente solicitado'
      );
    }
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      $titulo_ocurrio_error,
      $resultado['response']
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
