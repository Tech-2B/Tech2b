<?php
/**
 * Archivo de prueba específico para WALMART (cliente ID 4) y plan de acción 1
 * Para verificar por qué no se muestran todas las carpetas en el visor
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

  echo "<h2>Prueba Específica - WALMART (Cliente ID: $id_cliente)</h2>";
  echo "<p><strong>Plan de Acción ID:</strong> $id_plan_accion</p>";

  // 1. Probar obtener_carpetas_plan_accion.php (el que funciona)
  echo "<h3>1. Resultado de obtener_carpetas_plan_accion.php:</h3>";
  
  // Simular la consulta que hace obtener_carpetas_plan_accion.php
  $query1 = "SELECT id_carpeta_drive, nombre_carpeta, tipo_carpeta, ruta_completa 
             FROM $tabla_carpetas_drive 
             WHERE id_cliente = ? AND id_plan_accion = ? AND estado_activo = 1
             ORDER BY fecha_creacion ASC";

  $parametros1 = [$id_cliente, $id_plan_accion];
  $tipos1 = 'ii';
  $resultado1 = $funciones->fnBuscarDatosRegistro($conn, $query1, $parametros1, $tipos1);

  if ($resultado1['success']) {
    echo "<p style='color: green;'>✅ Encontradas " . count($resultado1['datos']) . " carpetas:</p>";
    echo "<ul>";
    foreach ($resultado1['datos'] as $carpeta) {
      echo "<li><strong>" . $carpeta['nombre_carpeta'] . "</strong> (Tipo: " . $carpeta['tipo_carpeta'] . ")</li>";
    }
    echo "</ul>";
  } else {
    echo "<p style='color: red;'>❌ No se encontraron carpetas</p>";
  }

  // 2. Probar obtener_estructura_carpetas.php (el que no funciona bien)
  echo "<h3>2. Resultado de obtener_estructura_carpetas.php:</h3>";
  
  // Simular la consulta que hace obtener_estructura_carpetas.php
  $query2 = "
    SELECT 
      id_carpeta,
      id_carpeta_drive,
      nombre_carpeta,
      id_carpeta_padre,
      tipo_carpeta,
      ruta_completa,
      fecha_creacion
    FROM $tabla_carpetas_drive 
    WHERE id_cliente = ? 
    AND id_plan_accion = ? 
    AND estado_activo = 1
    ORDER BY 
      CASE tipo_carpeta 
        WHEN 'plan_accion' THEN 1
        WHEN 'subcarpeta' THEN 2
        ELSE 3
      END,
      fecha_creacion ASC
  ";

  $parametros2 = [$id_cliente, $id_plan_accion];
  $tipos2 = 'ii';
  $resultado2 = $funciones->fnBuscarDatosRegistro($conn, $query2, $parametros2, $tipos2);

  if ($resultado2['success']) {
    echo "<p style='color: green;'>✅ Encontradas " . count($resultado2['datos']) . " carpetas:</p>";
    echo "<h4>Datos Raw:</h4>";
    echo "<pre>";
    print_r($resultado2['datos']);
    echo "</pre>";
    
    // Organizar jerárquicamente
    $estructura = organizarCarpetasJerarquicamente($resultado2['datos']);
    
    echo "<h4>Estructura Jerárquica:</h4>";
    echo "<pre>";
    print_r($estructura);
    echo "</pre>";
    
    echo "<h4>Vista de Árbol:</h4>";
    mostrarArbolCarpetas($estructura);
    
  } else {
    echo "<p style='color: red;'>❌ No se encontraron carpetas</p>";
  }

  // 3. Comparar las diferencias
  echo "<h3>3. Comparación de Resultados:</h3>";
  if ($resultado1['success'] && $resultado2['success']) {
    $count1 = count($resultado1['datos']);
    $count2 = count($resultado2['datos']);
    
    if ($count1 === $count2) {
      echo "<p style='color: green;'>✅ Ambas consultas devuelven la misma cantidad de carpetas ($count1)</p>";
    } else {
      echo "<p style='color: orange;'>⚠️ Diferencia en cantidad: obtener_carpetas_plan_accion.php = $count1, obtener_estructura_carpetas.php = $count2</p>";
    }
  }

} catch (Exception $e) {
  echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
  echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

/**
 * Organizar carpetas en estructura jerárquica
 */
function organizarCarpetasJerarquicamente($carpetas) {
  $estructura = [];
  $carpetas_por_id = [];
  
  // Crear mapa de carpetas por ID
  foreach ($carpetas as $carpeta) {
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
    agregarSubcarpetas($carpeta_raiz, $carpetas_por_id, 1);
  }
  
  return $estructura;
}

/**
 * Agregar subcarpetas recursivamente
 */
function agregarSubcarpetas(&$carpeta, $carpetas_por_id, $nivel) {
  foreach ($carpetas_por_id as $carpeta_info) {
    if ($carpeta_info['id_carpeta_padre'] === $carpeta['id_carpeta_drive'] && 
        $carpeta_info['tipo_carpeta'] === 'subcarpeta') {
      $carpeta_info['nivel'] = $nivel;
      $carpeta_info['subcarpetas'] = [];
      $carpeta['subcarpetas'][] = $carpeta_info;
      
      // Recursivamente agregar subcarpetas de esta subcarpeta
      $ultima_subcarpeta = &$carpeta['subcarpetas'][count($carpeta['subcarpetas']) - 1];
      agregarSubcarpetas($ultima_subcarpeta, $carpetas_por_id, $nivel + 1);
    }
  }
}

/**
 * Mostrar estructura en formato de árbol
 */
function mostrarArbolCarpetas($carpetas, $nivel = 0) {
  foreach ($carpetas as $carpeta) {
    $indentacion = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $nivel);
    $icono = $carpeta['tipo_carpeta'] === 'plan_accion' ? '📁' : '📂';
    
    echo "<div style='margin-left: " . ($nivel * 20) . "px;'>";
    echo $indentacion . $icono . " " . $carpeta['nombre_carpeta'];
    echo " <small>(" . $carpeta['tipo_carpeta'] . ")</small>";
    echo "</div>";
    
    if (!empty($carpeta['subcarpetas'])) {
      mostrarArbolCarpetas($carpeta['subcarpetas'], $nivel + 1);
    }
  }
}
?>
