<?php
/**
 * Archivo de prueba para verificar el formulario de plan manual
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
  echo "<p><strong>ID Cliente:</strong> $id_cliente</p>";
  echo "<p><strong>Área Oportunidad:</strong> $area_oportunidad</p>";
  echo "<p><strong>Plan Acción:</strong> $plan_accion</p>";
  echo "<p><strong>Tópico:</strong> $topico_seleccionado</p>";
  echo "<p><strong>Entregables:</strong> $entregables</p>";
  echo "<p><strong>Periodicidad:</strong> $periodicidad_seleccionada</p>";
  
  // Probar consulta de tópicos
  $queryTopico = "SELECT descripcion_topico FROM $tabla_lista_topicos WHERE numero_topico = ? AND activo = 1";
  $resultadoTopico = $funciones->fnBuscarDatosRegistro($conn, $queryTopico, [$topico_seleccionado], 'i');
  
  echo "<h3>Resultado consulta tópico:</h3>";
  echo "<pre>" . print_r($resultadoTopico, true) . "</pre>";
  
  // Probar consulta de periodicidades
  $queryPeriodicidad = "SELECT descripcion_periodicidad FROM $tabla_lista_periodicidades WHERE numero_periodicidad = ? AND activo = 1";
  $resultadoPeriodicidad = $funciones->fnBuscarDatosRegistro($conn, $queryPeriodicidad, [$periodicidad_seleccionada], 'i');
  
  echo "<h3>Resultado consulta periodicidad:</h3>";
  echo "<pre>" . print_r($resultadoPeriodicidad, true) . "</pre>";
  
} catch (Exception $e) {
  echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
  echo "<p><strong>Stack trace:</strong></p>";
  echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
