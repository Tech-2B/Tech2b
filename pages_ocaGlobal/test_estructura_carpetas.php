<?php
/**
 * Archivo de prueba para verificar la estructura de carpetas
 * Simula la consulta y muestra la estructura jerárquica
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

  // Parámetros de prueba (cambiar por valores reales)
  $id_cliente = 1; // Cambiar por un ID de cliente real
  $id_plan_accion = 1; // Cambiar por un ID de plan de acción real

  echo "<h2>Prueba de Estructura de Carpetas</h2>";
  echo "<p><strong>Cliente ID:</strong> $id_cliente</p>";
  echo "<p><strong>Plan de Acción ID:</strong> $id_plan_accion</p>";

  // Consulta para obtener la estructura de carpetas del plan de acción
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
    $carpetas = $resultado['datos'];
    
    echo "<h3>Datos Raw de la Base de Datos:</h3>";
    echo "<pre>";
    print_r($carpetas);
    echo "</pre>";
    
    // Organizar las carpetas en estructura jerárquica
    $estructura = organizarCarpetasJerarquicamente($carpetas);
    
    echo "<h3>Estructura Jerárquica Organizada:</h3>";
    echo "<pre>";
    print_r($estructura);
    echo "</pre>";
    
    echo "<h3>Vista de Árbol:</h3>";
    mostrarArbolCarpetas($estructura);
    
  } else {
    echo "<p style='color: red;'>No se encontraron carpetas para este plan de acción.</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>El cliente ID existe</li>";
    echo "<li>El plan de acción ID existe</li>";
    echo "<li>Hay carpetas creadas para este plan de acción</li>";
    echo "</ul>";
  }
} catch (Exception $e) {
  echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
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
