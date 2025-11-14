<?php

/**
 * Archivo AJAX para obtener las listas de tópicos y periodicidades
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener tópicos
  $queryTopicos =
    "SELECT descripcion_topico,
      GROUP_CONCAT(DISTINCT numero_topico) AS numero_topico
    FROM $tabla_lista_topicos
    WHERE activo = ?
    GROUP BY descripcion_topico
    ORDER BY descripcion_topico;";
  
  $resultadoTopicos = $funciones->fnBuscarDatosRegistro($conn, $queryTopicos, [1], 'i');
  
  // Obtener periodicidades
  $queryPeriodicidades = 
    "SELECT descripcion_periodicidad,
      GROUP_CONCAT(DISTINCT numero_periodicidad) AS numero_periodicidad
    FROM $tabla_lista_periodicidades WHERE activo = ?
    GROUP BY descripcion_periodicidad
    ORDER BY descripcion_periodicidad";
  $resultadoPeriodicidades = $funciones->fnBuscarDatosRegistro($conn, $queryPeriodicidades, [1], 'i');
  
  if ($resultadoTopicos['success'] && $resultadoPeriodicidades['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      'Listas obtenidas correctamente',
      '',
      [
        'topicos' => $resultadoTopicos['datos'],
        'periodicidades' => $resultadoPeriodicidades['datos']
      ]
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error al obtener listas',
      'No se pudieron obtener las listas de tópicos y periodicidades'
    );
  }
  
} catch (Exception $e) {
  error_log("Excepción en obtener_listas_planes.php: " . $e->getMessage());
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
