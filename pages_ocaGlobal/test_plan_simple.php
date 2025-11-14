<?php
/**
 * Archivo de prueba simple para verificar el formulario de plan manual
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  echo "<h2>Prueba de Formulario de Plan Manual</h2>";
  
  // Simular datos de prueba
  $_POST['id_cliente'] = '1';
  $_POST['area_oportunidad'] = 'Área de prueba';
  $_POST['plan_accion'] = 'Plan de prueba';
  $_POST['topicos'] = '1';
  $_POST['entregables'] = 'Entregable de prueba';
  $_POST['periodicidad'] = '1';
  
  echo "<h3>Datos de prueba:</h3>";
  echo "<pre>" . print_r($_POST, true) . "</pre>";
  
  // Probar obtención de datos
  $id_cliente = $funciones->fnTrimDatosPost('id_cliente');
  $area_oportunidad = $funciones->fnTrimDatosPost('area_oportunidad');
  $plan_accion = $funciones->fnTrimDatosPost('plan_accion');
  $topico_seleccionado = $funciones->fnTrimDatosPost('topicos');
  $entregables = $funciones->fnTrimDatosPost('entregables');
  $periodicidad_seleccionada = $funciones->fnTrimDatosPost('periodicidad');
  
  echo "<h3>Datos procesados:</h3>";
  echo "ID Cliente: $id_cliente<br>";
  echo "Área: $area_oportunidad<br>";
  echo "Plan: $plan_accion<br>";
  echo "Tópico: $topico_seleccionado<br>";
  echo "Entregables: $entregables<br>";
  echo "Periodicidad: $periodicidad_seleccionada<br>";
  
  // Probar consulta de tópicos
  echo "<h3>Probando consulta de tópicos:</h3>";
  $queryTopico = "SELECT descripcion_topico FROM $tabla_lista_topicos WHERE numero_topico = ? AND activo = 1";
  $resultadoTopico = $funciones->fnBuscarDatosRegistro($conn, $queryTopico, [$topico_seleccionado], 'i');
  
  if ($resultadoTopico['success'] && !empty($resultadoTopico['datos'])) {
    echo "Tópico encontrado: " . $resultadoTopico['datos'][0]['descripcion_topico'] . "<br>";
  } else {
    echo "Error en consulta de tópicos: " . ($resultadoTopico['response'] ?? 'Sin respuesta') . "<br>";
  }
  
  // Probar consulta de periodicidades
  echo "<h3>Probando consulta de periodicidades:</h3>";
  $queryPeriodicidad = "SELECT descripcion_periodicidad FROM $tabla_lista_periodicidades WHERE numero_periodicidad = ? AND activo = 1";
  $resultadoPeriodicidad = $funciones->fnBuscarDatosRegistro($conn, $queryPeriodicidad, [$periodicidad_seleccionada], 'i');
  
  if ($resultadoPeriodicidad['success'] && !empty($resultadoPeriodicidad['datos'])) {
    echo "Periodicidad encontrada: " . $resultadoPeriodicidad['datos'][0]['descripcion_periodicidad'] . "<br>";
  } else {
    echo "Error en consulta de periodicidades: " . ($resultadoPeriodicidad['response'] ?? 'Sin respuesta') . "<br>";
  }
  
  echo "<h3>Prueba completada</h3>";
  
} catch (Exception $e) {
  echo "<h3>Error:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
  echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
