<?php
/**
 * Archivo de prueba completo para simular la creación de carpetas de Google Drive
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';

// Incluir autoload de Google API
require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Incluir funciones de Google Drive
include 'includes/funcionesGoogleDrive.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  echo "<h2>Prueba Completa de Google Drive</h2>";
  
  // Simular los datos que se están enviando
  $id_cliente = 1;
  $area_oportunidad = "Área de prueba";
  $plan_accion = "Plan de prueba";
  $id_registro = 38; // El último registro creado
  
  echo "<h3>Datos de prueba:</h3>";
  echo "ID Cliente: $id_cliente<br>";
  echo "Área de Oportunidad: $area_oportunidad<br>";
  echo "Plan de Acción: $plan_accion<br>";
  echo "ID Registro: $id_registro<br>";
  
  // Verificar que el cliente existe
  echo "<h3>1. Verificando cliente:</h3>";
  $queryCliente = "SELECT id_cliente, nombre_cliente, activo FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  $resultadoCliente = $funciones->fnBuscarDatosRegistro($conn, $queryCliente, [$id_cliente], 'i');
  
  if ($resultadoCliente['success'] && !empty($resultadoCliente['datos'])) {
    echo "✓ Cliente encontrado: " . $resultadoCliente['datos'][0]['nombre_cliente'] . "<br>";
  } else {
    echo "✗ Cliente no encontrado<br>";
    echo "Resultado: " . json_encode($resultadoCliente) . "<br>";
    exit;
  }
  
  // Verificar que la clase FuncionesGoogleDrive existe
  echo "<h3>2. Verificando clase FuncionesGoogleDrive:</h3>";
  if (class_exists('FuncionesGoogleDrive')) {
    echo "✓ Clase FuncionesGoogleDrive disponible<br>";
  } else {
    echo "✗ Clase FuncionesGoogleDrive NO disponible<br>";
    exit;
  }
  
  // Intentar crear el objeto
  echo "<h3>3. Creando objeto FuncionesGoogleDrive:</h3>";
  try {
    $funcionesDrive = new FuncionesGoogleDrive($conn);
    echo "✓ Objeto FuncionesGoogleDrive creado exitosamente<br>";
  } catch (Exception $e) {
    echo "✗ Error creando objeto FuncionesGoogleDrive: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    exit;
  }
  
  // Intentar llamar a la función
  echo "<h3>4. Llamando a crearEstructuraCarpetasPlanManual:</h3>";
  try {
    $resultado = $funcionesDrive->crearEstructuraCarpetasPlanManual(
      $id_cliente,
      $area_oportunidad,
      $plan_accion,
      $id_registro
    );
    
    echo "<h4>Resultado:</h4>";
    echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($resultado['success']) {
      echo "✓ Estructura de carpetas creada exitosamente<br>";
    } else {
      echo "✗ Error creando estructura de carpetas: " . $resultado['error'] . "<br>";
    }
    
  } catch (Exception $e) {
    echo "✗ Error llamando a la función: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
  }
  
} catch (Exception $e) {
  echo "<h3>Error general:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
  echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
