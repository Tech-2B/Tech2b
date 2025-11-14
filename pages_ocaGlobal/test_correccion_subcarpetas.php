<?php
/**
 * Test específico para verificar la corrección de subcarpetas
 * Simula exactamente los datos de WALMART
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

  // Parámetros específicos de WALMART
  $id_cliente = 4; // WALMART
  $id_plan_accion = 1; // Plan de acción 1

  echo "<h2>Test de Corrección - Subcarpetas WALMART</h2>";
  echo "<p><strong>Cliente ID:</strong> $id_cliente (WALMART)</p>";
  echo "<p><strong>Plan de Acción ID:</strong> $id_plan_accion</p>";

  // Simular exactamente los datos que reportaste
  $datos_simulados = [
    [
      'id_carpeta' => 84,
      'id_carpeta_drive' => '11ArND6Xhh918eaZl3NjPZtvqha16HVtJ',
      'nombre_carpeta' => '1. Reforzamiento de la estructura del departamento de Servicios HS, asegurando que cada Coordinador HS tenga a su cargo máximo entre 10 y 15 cascos rojos independientes (dependiendo la duración de los proyectos y la zona de los mismos)',
      'id_carpeta_padre' => 1,
      'tipo_carpeta' => 'plan_accion',
      'ruta_completa' => '1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi/WALMART/ 1. Incrementar la presencia de Coordinadores HS en los sitios de trabajo./ 1. Reforzamiento de la estructura del departamento de Servicios HS, asegurando que cada Coordinador HS tenga a su cargo máximo entre 10 y 15 cascos rojos independientes (dependiendo la duración de los proyectos y la zona de los mismos)',
      'fecha_creacion' => '2025-09-16 14:21:07'
    ],
    [
      'id_carpeta' => 110,
      'id_carpeta_drive' => '10RhCxKYvwjm0DIYXtOCHaaHMhNEB-4Tg',
      'nombre_carpeta' => 'subcarpeta walmart plan 1.1',
      'id_carpeta_padre' => 11, // ¡Aquí está el problema! Debería ser 84
      'tipo_carpeta' => 'subcarpeta',
      'ruta_completa' => 'ruta_subcarpeta_1',
      'fecha_creacion' => '2025-09-16 16:02:50'
    ],
    [
      'id_carpeta' => 111,
      'id_carpeta_drive' => '1xyUHRFmp1MheOarH8JgwPTK_BFUrfgtG',
      'nombre_carpeta' => 'subcarpeta walmart plan 1.2',
      'id_carpeta_padre' => 11, // ¡Aquí está el problema! Debería ser 84
      'tipo_carpeta' => 'subcarpeta',
      'ruta_completa' => 'ruta_subcarpeta_2',
      'fecha_creacion' => '2025-09-16 16:03:31'
    ]
  ];

  echo "<h3>1. Datos Simulados (exactamente como los reportaste):</h3>";
  echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
  echo "<tr><th>ID Carpeta</th><th>ID Drive</th><th>Nombre</th><th>ID Padre</th><th>Tipo</th></tr>";
  foreach ($datos_simulados as $carpeta) {
    echo "<tr>";
    echo "<td>" . $carpeta['id_carpeta'] . "</td>";
    echo "<td>" . $carpeta['id_carpeta_drive'] . "</td>";
    echo "<td>" . htmlspecialchars($carpeta['nombre_carpeta']) . "</td>";
    echo "<td>" . $carpeta['id_carpeta_padre'] . "</td>";
    echo "<td>" . $carpeta['tipo_carpeta'] . "</td>";
    echo "</tr>";
  }
  echo "</table>";

  echo "<h3>2. Análisis del Problema:</h3>";
  echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0;'>";
  echo "<h4>🔍 Problema Identificado:</h4>";
  echo "<ul>";
  echo "<li><strong>Carpeta Principal:</strong> ID = 84, ID Padre = 1</li>";
  echo "<li><strong>Subcarpeta 1:</strong> ID = 110, ID Padre = 11 (❌ Debería ser 84)</li>";
  echo "<li><strong>Subcarpeta 2:</strong> ID = 111, ID Padre = 11 (❌ Debería ser 84)</li>";
  echo "</ul>";
  echo "<p><strong>Conclusión:</strong> Las subcarpetas tienen un <code>id_carpeta_padre</code> incorrecto en la base de datos.</p>";
  echo "</div>";

  echo "<h3>3. Solución Temporal (Corrección en el Código):</h3>";
  echo "<p>Voy a modificar la lógica para buscar subcarpetas que tengan como padre el <code>id_carpeta</code> de la carpeta principal:</p>";

  // Aplicar la corrección
  $estructura_corregida = organizarCarpetasJerarquicamenteCorregida($datos_simulados);
  
  echo "<h4>Estructura Corregida:</h4>";
  echo "<pre>";
  print_r($estructura_corregida);
  echo "</pre>";

  echo "<h4>Vista de Árbol Corregida:</h4>";
  mostrarArbolCarpetasCorregido($estructura_corregida);

  echo "<h3>4. Recomendación:</h3>";
  echo "<div style='background-color: #d1ecf1; padding: 15px; border: 1px solid #bee5eb; border-radius: 5px; margin: 10px 0;'>";
  echo "<h4>💡 Solución Permanente:</h4>";
  echo "<p>Actualizar la base de datos para corregir los <code>id_carpeta_padre</code> de las subcarpetas:</p>";
  echo "<pre>";
  echo "UPDATE $tabla_carpetas_drive SET id_carpeta_padre = 84 WHERE id_carpeta IN (110, 111);";
  echo "</pre>";
  echo "</div>";

} catch (Exception $e) {
  echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
  echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

/**
 * Función corregida para organizar carpetas jerárquicamente
 */
function organizarCarpetasJerarquicamenteCorregida($carpetas) {
  $estructura = [];
  $carpetas_por_id = [];
  
  // Crear mapa de carpetas por ID
  foreach ($carpetas as $carpeta) {
    $carpetas_por_id[$carpeta['id_carpeta']] = $carpeta;
    $carpetas_por_id[$carpeta['id_carpeta_drive']] = $carpeta;
  }
  
  // Encontrar carpetas raíz (plan_accion)
  foreach ($carpetas as $carpeta) {
    if ($carpeta['tipo_carpeta'] === 'plan_accion') {
      $carpeta['nivel'] = 0;
      $carpeta['subcarpetas'] = [];
      $estructura[] = $carpeta;
    }
  }
  
  // Agregar subcarpetas recursivamente
  foreach ($estructura as &$carpeta_raiz) {
    agregarSubcarpetasCorregida($carpeta_raiz, $carpetas_por_id, 1);
  }
  
  return $estructura;
}

/**
 * Función corregida para agregar subcarpetas
 */
function agregarSubcarpetasCorregida(&$carpeta, $carpetas_por_id, $nivel) {
  foreach ($carpetas_por_id as $carpeta_info) {
    // Buscar subcarpetas que tengan como padre el id_carpeta de la carpeta actual
    if ($carpeta_info['id_carpeta_padre'] == $carpeta['id_carpeta'] && 
        $carpeta_info['tipo_carpeta'] === 'subcarpeta') {
      $carpeta_info['nivel'] = $nivel;
      $carpeta_info['subcarpetas'] = [];
      $carpeta['subcarpetas'][] = $carpeta_info;
      
      // Recursivamente agregar subcarpetas de esta subcarpeta
      $ultima_subcarpeta = &$carpeta['subcarpetas'][count($carpeta['subcarpetas']) - 1];
      agregarSubcarpetasCorregida($ultima_subcarpeta, $carpetas_por_id, $nivel + 1);
    }
  }
}

/**
 * Mostrar estructura en formato de árbol (versión corregida)
 */
function mostrarArbolCarpetasCorregido($carpetas, $nivel = 0) {
  foreach ($carpetas as $carpeta) {
    $indentacion = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $nivel);
    $icono = $carpeta['tipo_carpeta'] === 'plan_accion' ? '📁' : '📂';
    
    echo "<div style='margin-left: " . ($nivel * 20) . "px; padding: 5px; border: 1px solid #ddd; margin: 2px 0; background-color: " . ($nivel === 0 ? '#e3f2fd' : '#f8f9fa') . ";'>";
    echo $indentacion . $icono . " " . htmlspecialchars($carpeta['nombre_carpeta']);
    echo " <small>(" . $carpeta['tipo_carpeta'] . ")</small>";
    echo " <strong>ID:</strong> " . $carpeta['id_carpeta'];
    echo " <strong>ID Drive:</strong> " . $carpeta['id_carpeta_drive'];
    echo "</div>";
    
    if (!empty($carpeta['subcarpetas'])) {
      mostrarArbolCarpetasCorregido($carpeta['subcarpetas'], $nivel + 1);
    }
  }
}
?>
