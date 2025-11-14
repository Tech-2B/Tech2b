<?php
/**
 * Archivo de prueba para verificar la inclusión de archivos
 */

echo "<h2>Prueba de Inclusión de Archivos</h2>";

// Verificar archivos uno por uno
echo "<h3>1. Verificando archivos:</h3>";

$archivos = [
  'includes/funcionesGenerales.php',
  'includes/variables.php',
  'includes/funcionesGoogleDrive.php'
];

foreach ($archivos as $archivo) {
  if (file_exists($archivo)) {
    echo "✓ $archivo existe<br>";
  } else {
    echo "✗ $archivo NO existe<br>";
  }
}

// Verificar autoload de Google API
echo "<h3>2. Verificando autoload de Google API:</h3>";
$autoload = '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';
if (file_exists($autoload)) {
  echo "✓ $autoload existe<br>";
} else {
  echo "✗ $autoload NO existe<br>";
}

// Incluir archivos uno por uno
echo "<h3>3. Incluyendo archivos:</h3>";

try {
  echo "Incluyendo funcionesGenerales.php...<br>";
  include 'includes/funcionesGenerales.php';
  echo "✓ funcionesGenerales.php incluido<br>";
} catch (Exception $e) {
  echo "✗ Error incluyendo funcionesGenerales.php: " . $e->getMessage() . "<br>";
}

try {
  echo "Incluyendo variables.php...<br>";
  include 'includes/variables.php';
  echo "✓ variables.php incluido<br>";
} catch (Exception $e) {
  echo "✗ Error incluyendo variables.php: " . $e->getMessage() . "<br>";
}

try {
  echo "Incluyendo autoload de Google API...<br>";
  require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';
  echo "✓ autoload de Google API incluido<br>";
} catch (Exception $e) {
  echo "✗ Error incluyendo autoload: " . $e->getMessage() . "<br>";
}

try {
  echo "Incluyendo funcionesGoogleDrive.php...<br>";
  include 'includes/funcionesGoogleDrive.php';
  echo "✓ funcionesGoogleDrive.php incluido<br>";
} catch (Exception $e) {
  echo "✗ Error incluyendo funcionesGoogleDrive.php: " . $e->getMessage() . "<br>";
} catch (ParseError $e) {
  echo "✗ Error de sintaxis en funcionesGoogleDrive.php: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}

// Verificar clases
echo "<h3>4. Verificando clases:</h3>";

if (class_exists('FuncionesGenerales')) {
  echo "✓ Clase FuncionesGenerales disponible<br>";
} else {
  echo "✗ Clase FuncionesGenerales NO disponible<br>";
}

if (class_exists('FuncionesGoogleDrive')) {
  echo "✓ Clase FuncionesGoogleDrive disponible<br>";
} else {
  echo "✗ Clase FuncionesGoogleDrive NO disponible<br>";
}

if (class_exists('Google\Client')) {
  echo "✓ Clase Google\Client disponible<br>";
} else {
  echo "✗ Clase Google\Client NO disponible<br>";
}

// Mostrar errores de PHP
echo "<h3>5. Errores de PHP:</h3>";
$errores = error_get_last();
if ($errores) {
  echo "✗ Error de PHP:<br>";
  echo "Tipo: " . $errores['type'] . "<br>";
  echo "Mensaje: " . $errores['message'] . "<br>";
  echo "Archivo: " . $errores['file'] . "<br>";
  echo "Línea: " . $errores['line'] . "<br>";
} else {
  echo "✓ No hay errores de PHP<br>";
}
?>
