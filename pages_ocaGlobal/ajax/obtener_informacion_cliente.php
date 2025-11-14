<?php

/**
 * Archivo AJAX para obtener información completa de un cliente
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
  
  // Obtener parámetros
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

  // Consultar información del cliente
  $query = "SELECT id_cliente, nombre_cliente, codigo_cliente, tipo_cliente 
            FROM $tabla_clientes 
            WHERE id_cliente = ? AND estado_activo = 1 
            LIMIT 1";

  $params = [$id_cliente];
  $types = 'i';

  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, $params, $types);

  if ($resultado['success'] && !empty($resultado['datos'])) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      'Información obtenida',
      'Información del cliente obtenida correctamente',
      $resultado['datos'][0]
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_404,
      false,
      $icon_info,
      'Cliente no encontrado',
      'No se encontró información del cliente'
    );
  }

} catch (Exception $e) {
  error_log("Error en obtener_informacion_cliente.php: " . $e->getMessage());
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
