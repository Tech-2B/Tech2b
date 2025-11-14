<?php
/**
 * Prueba específica para la función crearSubcarpetaPlanAccion
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';
include 'includes/funcionesGoogleDrive.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';

// Incluir Google Drive API
require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

try {
    echo "<h2>Prueba de crearSubcarpetaPlanAccion</h2>";
    
    echo "<h3>1. Verificando conexión a la base de datos:</h3>";
    if ($conn->connect_error) {
        echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
        exit;
    }
    echo "✅ Conexión a la base de datos exitosa<br>";
    
    echo "<h3>2. Creando instancia de FuncionesGoogleDrive:</h3>";
    $funcionesDrive = new FuncionesGoogleDrive($conn);
    echo "✅ FuncionesGoogleDrive creada exitosamente<br>";
    
    echo "<h3>3. Verificando que existe la función crearSubcarpetaPlanAccion:</h3>";
    if (method_exists($funcionesDrive, 'crearSubcarpetaPlanAccion')) {
        echo "✅ Método crearSubcarpetaPlanAccion existe<br>";
    } else {
        echo "❌ Método crearSubcarpetaPlanAccion NO existe<br>";
        exit;
    }
    
    echo "<h3>4. Verificando que existe la función obtenerCarpetaRaizPlanAccion:</h3>";
    $reflection = new ReflectionClass($funcionesDrive);
    if ($reflection->hasMethod('obtenerCarpetaRaizPlanAccion')) {
        echo "✅ Método obtenerCarpetaRaizPlanAccion existe (privado)<br>";
    } else {
        echo "❌ Método obtenerCarpetaRaizPlanAccion NO existe<br>";
    }
    
    echo "<h3>5. Probando con datos de prueba:</h3>";
    $idCliente = 1;
    $idPlanAccion = 1;
    $nombreCarpeta = "Carpeta de Prueba " . date('Y-m-d H:i:s');
    
    echo "ID Cliente: $idCliente<br>";
    echo "ID Plan Acción: $idPlanAccion<br>";
    echo "Nombre Carpeta: $nombreCarpeta<br>";
    
    $resultado = $funcionesDrive->crearSubcarpetaPlanAccion($idCliente, $idPlanAccion, $nombreCarpeta);
    
    echo "<h3>6. Resultado:</h3>";
    echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT) . "</pre>";
    
    if ($resultado['success']) {
        echo "✅ Función ejecutada exitosamente<br>";
        echo "ID Carpeta Drive: " . $resultado['datos']['id_carpeta_drive'] . "<br>";
        echo "Nombre Carpeta: " . $resultado['datos']['nombre_carpeta'] . "<br>";
        echo "ID Carpeta Padre: " . $resultado['datos']['id_carpeta_padre'] . "<br>";
        echo "Ruta Completa: " . $resultado['datos']['ruta_completa'] . "<br>";
    } else {
        echo "⚠️ Función ejecutada con error: " . $resultado['error'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
