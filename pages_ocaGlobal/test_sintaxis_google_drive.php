<?php
/**
 * Archivo de prueba para verificar la sintaxis de funcionesGoogleDrive.php
 */

echo "<h2>Prueba de Sintaxis de FuncionesGoogleDrive</h2>";

// Verificar si hay errores de sintaxis
echo "<h3>1. Verificando sintaxis del archivo:</h3>";

$archivo = 'includes/funcionesGoogleDrive.php';
if (file_exists($archivo)) {
  echo "✓ Archivo $archivo existe<br>";
  
  // Verificar sintaxis
  $output = shell_exec("php -l $archivo 2>&1");
  if (strpos($output, 'No syntax errors') !== false) {
    echo "✓ Sintaxis correcta<br>";
  } else {
    echo "✗ Error de sintaxis:<br>";
    echo "<pre>$output</pre>";
  }
} else {
  echo "✗ Archivo $archivo NO existe<br>";
}

// Intentar incluir el archivo
echo "<h3>2. Intentando incluir el archivo:</h3>";
try {
  include 'includes/funcionesGoogleDrive.php';
  echo "✓ Archivo incluido exitosamente<br>";
} catch (Exception $e) {
  echo "✗ Error incluyendo archivo: " . $e->getMessage() . "<br>";
} catch (ParseError $e) {
  echo "✗ Error de sintaxis: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}

// Verificar si la clase existe
echo "<h3>3. Verificando si la clase existe:</h3>";
if (class_exists('FuncionesGoogleDrive')) {
  echo "✓ Clase FuncionesGoogleDrive disponible<br>";
} else {
  echo "✗ Clase FuncionesGoogleDrive NO disponible<br>";
  
  // Mostrar todas las clases disponibles
  echo "<h4>Clases disponibles:</h4>";
  $clases = get_declared_classes();
  foreach ($clases as $clase) {
    if (strpos($clase, 'Google') !== false || strpos($clase, 'Funciones') !== false) {
      echo "- $clase<br>";
    }
  }
}

// Verificar si hay errores de PHP
echo "<h3>4. Verificando errores de PHP:</h3>";
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
