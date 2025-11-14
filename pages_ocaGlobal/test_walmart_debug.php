<?php
/**
 * Debug específico para WALMART (cliente ID 4) y plan de acción 1
 * Para verificar por qué no se muestran las subcarpetas
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

  echo "<h2>Debug WALMART - Cliente ID: $id_cliente, Plan: $id_plan_accion</h2>";

  // 1. Verificar datos raw de la base de datos
  echo "<h3>1. Datos Raw de la Base de Datos:</h3>";
  
  $query = "
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

  $parametros = [$id_cliente, $id_plan_accion];
  $tipos = 'ii';
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, $parametros, $tipos);

  if ($resultado['success']) {
    echo "<p style='color: green;'>✅ Encontradas " . count($resultado['datos']) . " carpetas en la base de datos:</p>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID Carpeta</th><th>ID Drive</th><th>Nombre</th><th>ID Padre</th><th>Tipo</th><th>Fecha</th></tr>";
    foreach ($resultado['datos'] as $carpeta) {
      echo "<tr>";
      echo "<td>" . $carpeta['id_carpeta'] . "</td>";
      echo "<td>" . $carpeta['id_carpeta_drive'] . "</td>";
      echo "<td>" . htmlspecialchars($carpeta['nombre_carpeta']) . "</td>";
      echo "<td>" . ($carpeta['id_carpeta_padre'] ?: 'NULL') . "</td>";
      echo "<td>" . $carpeta['tipo_carpeta'] . "</td>";
      echo "<td>" . $carpeta['fecha_creacion'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "<p style='color: red;'>❌ No se encontraron carpetas en la base de datos</p>";
  }

  // 2. Probar la función de organización jerárquica
  echo "<h3>2. Estructura Jerárquica:</h3>";
  
  if ($resultado['success']) {
    $carpetas = $resultado['datos'];
    $estructura = organizarCarpetasJerarquicamente($carpetas);
    
    echo "<h4>Estructura Organizada:</h4>";
    echo "<pre>";
    print_r($estructura);
    echo "</pre>";
    
    echo "<h4>Vista de Árbol:</h4>";
    mostrarArbolCarpetas($estructura);
  }

  // 3. Simular la respuesta del endpoint
  echo "<h3>3. Respuesta del Endpoint (JSON):</h3>";
  
  if ($resultado['success']) {
    $carpetas = $resultado['datos'];
    $estructura = organizarCarpetasJerarquicamente($carpetas);
    
    $respuesta = [
      'code' => 200,
      'success' => true,
      'icon' => 'success',
      'title' => '¡Éxito!',
      'message' => 'Los datos han sido encontrados correctamente.',
      'data' => $estructura
    ];
    
    echo "<pre>";
    echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
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
  
  // Crear mapa de carpetas por ID (tanto por id_carpeta como por id_carpeta_drive)
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
    agregarSubcarpetas($carpeta_raiz, $carpetas_por_id, 1);
  }
  
  return $estructura;
}

/**
 * Agregar subcarpetas recursivamente
 */
function agregarSubcarpetas(&$carpeta, $carpetas_por_id, $nivel) {
  foreach ($carpetas_por_id as $carpeta_info) {
    // Buscar subcarpetas que tengan como padre el id_carpeta de la carpeta actual
    if ($carpeta_info['id_carpeta_padre'] == $carpeta['id_carpeta'] && 
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
    
    echo "<div style='margin-left: " . ($nivel * 20) . "px; padding: 5px; border: 1px solid #ddd; margin: 2px 0;'>";
    echo $indentacion . $icono . " " . htmlspecialchars($carpeta['nombre_carpeta']);
    echo " <small>(" . $carpeta['tipo_carpeta'] . ")</small>";
    echo " <strong>ID Drive:</strong> " . $carpeta['id_carpeta_drive'];
    echo " <strong>ID Padre:</strong> " . ($carpeta['id_carpeta_padre'] ?: 'NULL');
    echo "</div>";
    
    if (!empty($carpeta['subcarpetas'])) {
      mostrarArbolCarpetas($carpeta['subcarpetas'], $nivel + 1);
    }
  }
}
?>
