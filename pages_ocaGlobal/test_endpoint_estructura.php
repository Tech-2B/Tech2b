<?php
/**
 * Archivo de prueba para verificar el endpoint obtener_estructura_carpetas.php
 * Específico para WALMART (cliente ID 4) y plan de acción 1
 */

// Simular la llamada AJAX
$id_cliente = 4; // WALMART
$id_plan_accion = 1; // Plan de acción 1

echo "<h2>Prueba del Endpoint obtener_estructura_carpetas.php</h2>";
echo "<p><strong>Cliente ID:</strong> $id_cliente (WALMART)</p>";
echo "<p><strong>Plan de Acción ID:</strong> $id_plan_accion</p>";

// Simular la URL del endpoint
$url = "ajax/obtener_estructura_carpetas.php?id_cliente=$id_cliente&id_plan_accion=$id_plan_accion";

echo "<h3>URL del Endpoint:</h3>";
echo "<p><code>$url</code></p>";

// Hacer la llamada al endpoint
echo "<h3>Respuesta del Endpoint:</h3>";

// Capturar la salida del endpoint
ob_start();
include 'ajax/obtener_estructura_carpetas.php';
$response = ob_get_clean();

echo "<h4>Respuesta Raw:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Intentar decodificar JSON
$data = json_decode($response, true);
if ($data) {
  echo "<h4>Datos Decodificados:</h4>";
  echo "<pre>";
  print_r($data);
  echo "</pre>";
  
  if (isset($data['success']) && $data['success']) {
    echo "<h4>Estructura de Carpetas:</h4>";
    if (isset($data['data']) && is_array($data['data'])) {
      echo "<p style='color: green;'>✅ Se encontraron " . count($data['data']) . " carpetas principales</p>";
      
      foreach ($data['data'] as $index => $carpeta) {
        echo "<div style='border: 1px solid #ccc; margin: 10px 0; padding: 10px;'>";
        echo "<h5>Carpeta " . ($index + 1) . ": " . $carpeta['nombre_carpeta'] . "</h5>";
        echo "<p><strong>Tipo:</strong> " . $carpeta['tipo_carpeta'] . "</p>";
        echo "<p><strong>ID Drive:</strong> " . $carpeta['id_carpeta_drive'] . "</p>";
        
        if (isset($carpeta['subcarpetas']) && !empty($carpeta['subcarpetas'])) {
          echo "<p><strong>Subcarpetas (" . count($carpeta['subcarpetas']) . "):</strong></p>";
          echo "<ul>";
          foreach ($carpeta['subcarpetas'] as $subcarpeta) {
            echo "<li>" . $subcarpeta['nombre_carpeta'] . " (Tipo: " . $subcarpeta['tipo_carpeta'] . ")</li>";
          }
          echo "</ul>";
        } else {
          echo "<p style='color: orange;'>⚠️ No hay subcarpetas</p>";
        }
        echo "</div>";
      }
    } else {
      echo "<p style='color: red;'>❌ No hay datos en la respuesta</p>";
    }
  } else {
    echo "<p style='color: red;'>❌ La respuesta indica error: " . (isset($data['message']) ? $data['message'] : 'Error desconocido') . "</p>";
  }
} else {
  echo "<p style='color: red;'>❌ No se pudo decodificar la respuesta JSON</p>";
}

echo "<h3>Instrucciones para Probar:</h3>";
echo "<ol>";
echo "<li>Ejecuta este archivo para ver la respuesta del endpoint</li>";
echo "<li>Verifica que aparezcan las 3 carpetas (principal + 2 subcarpetas)</li>";
echo "<li>Si no aparecen, revisa la base de datos para el cliente ID 4, plan de acción 1</li>";
echo "<li>Compara con el resultado de <code>test_walmart_carpetas.php</code></li>";
echo "</ol>";
?>
