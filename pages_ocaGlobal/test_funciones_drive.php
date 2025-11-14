<?php
/**
 * Prueba específica para verificar la creación del objeto FuncionesGoogleDrive
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
    echo "<h2>Prueba de Creación de FuncionesGoogleDrive</h2>";
    
    echo "<h3>1. Verificando conexión a la base de datos:</h3>";
    if ($conn->connect_error) {
        echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
        exit;
    }
    echo "✅ Conexión a la base de datos exitosa<br>";
    
    echo "<h3>2. Verificando archivo de credenciales:</h3>";
    $credentialsPath = __DIR__ . '/drive/credenciales/ocaconstruccion-b8ddbf846879.json';
    if (!file_exists($credentialsPath)) {
        echo "❌ Archivo de credenciales no encontrado: $credentialsPath<br>";
        exit;
    }
    echo "✅ Archivo de credenciales encontrado<br>";
    
    echo "<h3>3. Verificando autoload de Google API:</h3>";
    if (!class_exists('Google\Client')) {
        echo "❌ Clase Google\Client no encontrada<br>";
        exit;
    }
    echo "✅ Clase Google\Client disponible<br>";
    
    if (!class_exists('Google\Service\Drive')) {
        echo "❌ Clase Google\Service\Drive no encontrada<br>";
        exit;
    }
    echo "✅ Clase Google\Service\Drive disponible<br>";
    
    echo "<h3>4. Creando instancia de FuncionesGenerales:</h3>";
    $funciones = new FuncionesGenerales();
    echo "✅ FuncionesGenerales creada exitosamente<br>";
    
    echo "<h3>5. Creando instancia de FuncionesGoogleDrive:</h3>";
    try {
        $funcionesDrive = new FuncionesGoogleDrive($conn);
        echo "✅ FuncionesGoogleDrive creada exitosamente<br>";
        
        echo "<h3>6. Verificando método obtenerCarpetasPlanAccion:</h3>";
        if (method_exists($funcionesDrive, 'obtenerCarpetasPlanAccion')) {
            echo "✅ Método obtenerCarpetasPlanAccion existe<br>";
            
            // Probar con datos de prueba
            echo "<h3>7. Probando método con datos de prueba:</h3>";
            $resultado = $funcionesDrive->obtenerCarpetasPlanAccion(1, 1);
            echo "✅ Método ejecutado sin errores<br>";
            echo "Resultado: " . json_encode($resultado) . "<br>";
            
        } else {
            echo "❌ Método obtenerCarpetasPlanAccion no existe<br>";
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
