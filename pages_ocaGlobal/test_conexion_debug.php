<?php

/**
 * Archivo de prueba para diagnosticar problemas de conexión
 */

echo "<h2>🔍 Diagnóstico de Conexión a Base de Datos</h2>";

// Paso 1: Verificar archivos de inclusión
echo "<h3>1. Verificando archivos de inclusión:</h3>";

$archivos = [
    'includes/funcionesGenerales.php',
    'includes/variables.php', 
    'includes/funcionesGoogleDrive.php',
    '../sql/conexionMysqliUTF8Dev2.php'
];

foreach ($archivos as $archivo) {
    if (file_exists($archivo)) {
        echo "✅ $archivo - Existe<br>";
    } else {
        echo "❌ $archivo - NO EXISTE<br>";
    }
}

echo "<hr>";

// Paso 2: Intentar incluir archivos
echo "<h3>2. Intentando incluir archivos:</h3>";

try {
    include '../includes/funcionesGenerales.php';
    echo "✅ funcionesGenerales.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error incluyendo funcionesGenerales.php: " . $e->getMessage() . "<br>";
}

try {
    include '../includes/variables.php';
    echo "✅ variables.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error incluyendo variables.php: " . $e->getMessage() . "<br>";
}

try {
    include '../includes/funcionesGoogleDrive.php';
    echo "✅ funcionesGoogleDrive.php incluido<br>";
} catch (Exception $e) {
    echo "❌ Error incluyendo funcionesGoogleDrive.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Paso 3: Intentar conexión a base de datos
echo "<h3>3. Intentando conexión a base de datos:</h3>";

try {
    include '../../sql/conexionMysqliUTF8Dev2.php';
    echo "✅ conexionMysqliUTF8Dev2.php incluido<br>";
    
    if (isset($conn)) {
        echo "✅ Variable \$conn está definida<br>";
        
        if ($conn->connect_error) {
            echo "❌ Error de conexión: " . $conn->connect_error . "<br>";
        } else {
            echo "✅ Conexión establecida correctamente<br>";
        }
    } else {
        echo "❌ Variable \$conn NO está definida<br>";
    }
} catch (Exception $e) {
    echo "❌ Error incluyendo conexionMysqliUTF8Dev2.php: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Paso 4: Intentar crear FuncionesGenerales
echo "<h3>4. Intentando crear FuncionesGenerales:</h3>";

try {
    $funciones = new FuncionesGenerales();
    echo "✅ FuncionesGenerales creado correctamente<br>";
} catch (Exception $e) {
    echo "❌ Error creando FuncionesGenerales: " . $e->getMessage() . "<br>";
}

echo "<hr>";

// Paso 5: Intentar crear FuncionesGoogleDrive
echo "<h3>5. Intentando crear FuncionesGoogleDrive:</h3>";

if (isset($conn) && !$conn->connect_error) {
    try {
        $funcionesDrive = new FuncionesGoogleDrive($conn);
        echo "✅ FuncionesGoogleDrive creado correctamente<br>";
        
        // Probar método obtenerCarpetasPlanAccion
        echo "<h4>Probando método obtenerCarpetasPlanAccion:</h4>";
        $resultado = $funcionesDrive->obtenerCarpetasPlanAccion(4, 1);
        
        if ($resultado['success']) {
            echo "✅ Método funcionando correctamente<br>";
            echo "📊 Datos devueltos: " . count($resultado['datos']) . " carpetas<br>";
        } else {
            echo "❌ Error en método: " . ($resultado['error'] ?? 'Error desconocido') . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creando FuncionesGoogleDrive: " . $e->getMessage() . "<br>";
        echo "📋 Stack trace: " . $e->getTraceAsString() . "<br>";
    }
} else {
    echo "❌ No se puede crear FuncionesGoogleDrive - conexión no disponible<br>";
}

echo "<hr>";

// Paso 6: Información del sistema
echo "<h3>6. Información del sistema:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Directorio actual: " . getcwd() . "<br>";
echo "Archivo actual: " . __FILE__ . "<br>";

// Verificar si hay errores de PHP
if (error_get_last()) {
    echo "<h4>Último error de PHP:</h4>";
    $error = error_get_last();
    echo "Tipo: " . $error['type'] . "<br>";
    echo "Mensaje: " . $error['message'] . "<br>";
    echo "Archivo: " . $error['file'] . "<br>";
    echo "Línea: " . $error['line'] . "<br>";
}

echo "<hr>";
echo "<h3>✅ Diagnóstico completado</h3>";

?>

