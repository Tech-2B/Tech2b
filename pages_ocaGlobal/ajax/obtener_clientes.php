<?php

/**
 * Archivo AJAX para obtener la lista de clientes
 * Solo consulta, no modifica datos
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
echo "Error de conexión: " . $conn->connect_error;
}


try {
  $funciones = new FuncionesGenerales();

  // Consulta para obtener todos los clientes activos
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
        WHERE activo = ?
        ORDER BY fecha_creacion ASC";

  $parametros = [1];
  $tipos = 'i';
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, $parametros, $tipos);

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
