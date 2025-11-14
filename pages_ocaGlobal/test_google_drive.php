<?php
/**
 * Archivo de prueba para verificar la clase FuncionesGoogleDrive
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';

// Incluir autoload de Google API
require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
  exit;
}

try {
  echo "<h2>Prueba de FuncionesGoogleDrive</h2>";
  
  // Verificar que la clase existe
  if (!class_exists('FuncionesGoogleDrive')) {
    echo "<h3>Error: La clase FuncionesGoogleDrive no está disponible</h3>";
    echo "Verificando archivos incluidos...<br>";
    
    // Verificar si el archivo existe
    $archivo = 'includes/funcionesGoogleDrive.php';
    if (file_exists($archivo)) {
      echo "✓ Archivo $archivo existe<br>";
    } else {
      echo "✗ Archivo $archivo NO existe<br>";
    }
    
    // Verificar si Google API está disponible
    if (class_exists('Google\Client')) {
      echo "✓ Google API Client está disponible<br>";
    } else {
      echo "✗ Google API Client NO está disponible<br>";
    }
    
    exit;
  }
  
  echo "<h3>✓ La clase FuncionesGoogleDrive está disponible</h3>";
  
  // Intentar crear el objeto
  echo "<h3>Intentando crear objeto FuncionesGoogleDrive...</h3>";
  
  try {
    $funcionesDrive = new FuncionesGoogleDrive($conn);
    echo "<h3>✓ Objeto FuncionesGoogleDrive creado exitosamente</h3>";
    
    // Verificar que el servicio está disponible
    if (isset($funcionesDrive->service)) {
      echo "<h3>✓ Servicio de Google Drive inicializado</h3>";
    } else {
      echo "<h3>✗ Servicio de Google Drive NO inicializado</h3>";
    }
    
  } catch (Exception $e) {
    echo "<h3>✗ Error creando objeto FuncionesGoogleDrive:</h3>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
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