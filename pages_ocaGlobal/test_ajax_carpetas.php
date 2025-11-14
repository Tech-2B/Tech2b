<?php
/**
 * Prueba específica para simular la llamada AJAX de obtener_carpetas_plan_accion.php
 */

// Simular parámetros GET
$_GET['id_cliente'] = 1;
$_GET['id_plan_accion'] = 1;

echo "<h2>Prueba de AJAX obtener_carpetas_plan_accion.php</h2>";

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
    echo "<h3>1. Verificando conexión a la base de datos:</h3>";
    if ($conn->connect_error) {
        echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
        exit;
    }
    echo "✅ Conexión a la base de datos exitosa<br>";
    
    echo "<h3>2. Creando instancia de FuncionesGenerales:</h3>";
    $funciones = new FuncionesGenerales();
    echo "✅ FuncionesGenerales creada exitosamente<br>";
    
    echo "<h3>3. Verificando parámetros:</h3>";
    $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
    $id_plan_accion = isset($_GET['id_plan_accion']) ? (int)$_GET['id_plan_accion'] : 0;
    echo "ID Cliente: $id_cliente<br>";
    echo "ID Plan Acción: $id_plan_accion<br>";
    
    if ($id_cliente <= 0 || $id_plan_accion <= 0) {
        echo "❌ Parámetros no válidos<br>";
        exit;
    }
    echo "✅ Parámetros válidos<br>";
    
    echo "<h3>4. Creando instancia de FuncionesGoogleDrive:</h3>";
    try {
        $funcionesDrive = new FuncionesGoogleDrive($conn);
        echo "✅ FuncionesGoogleDrive creada exitosamente<br>";
        
        echo "<h3>5. Llamando al método obtenerCarpetasPlanAccion:</h3>";
        $resultado = $funcionesDrive->obtenerCarpetasPlanAccion($id_cliente, $id_plan_accion);
        echo "✅ Método ejecutado exitosamente<br>";
        
        echo "<h3>6. Resultado:</h3>";
        echo "<pre>" . json_encode($resultado, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($resultado['success']) {
            echo "✅ Operación exitosa<br>";
            echo "Datos encontrados: " . count($resultado['datos']) . " registros<br>";
        } else {
            echo "⚠️ Operación sin éxito: " . $resultado['response'] . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creando FuncionesGoogleDrive: " . $e->getMessage() . "<br>";
        echo "Stack trace: " . $e->getTraceAsString() . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
