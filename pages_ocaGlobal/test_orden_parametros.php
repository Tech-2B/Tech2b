<?php
/**
 * Archivo de prueba para verificar el orden correcto de parámetros
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
  
  // Simular datos de prueba
  $id_registro = 1; // Cambiar por un ID real
  $cambios = [
    'descripcion_area_oportunidad' => 'Área de prueba corregida',
    'descripcion_plan_accion' => 'Plan de prueba corregido'
  ];
  
  echo "<h2>Prueba de Orden de Parámetros</h2>";
  echo "<p><strong>ID Registro:</strong> $id_registro</p>";
  echo "<p><strong>Cambios:</strong></p>";
  echo "<pre>" . print_r($cambios, true) . "</pre>";
  
  // Validar que los campos sean válidos
  $camposPermitidos = [
    'descripcion_area_oportunidad',
    'descripcion_plan_accion', 
    'descripcion_topico',
    'descripcion_entregable',
    'descripcion_periodicidad'
  ];
  
  $cambiosValidos = [];
  foreach ($cambios as $campo => $valor) {
    if (in_array($campo, $camposPermitidos)) {
      $cambiosValidos[$campo] = trim($valor);
    }
  }
  
  if (empty($cambiosValidos)) {
    echo "<p style='color: red;'><strong>Error:</strong> No se proporcionaron campos válidos para actualizar</p>";
    exit;
  }
  
  // Construir la consulta UPDATE dinámicamente
  $setParts = [];
  $params = [];
  $types = '';
  
  foreach ($cambiosValidos as $campo => $valor) {
    $setParts[] = "`$campo` = ?";
    $params[] = $valor;
    $types .= 's'; // string
  }
  
  // CORRECCIÓN: Orden correcto de parámetros
  $params[] = 1; // id_usuario_actualiza (primero)
  $params[] = $id_registro; // id_registro para WHERE (segundo)
  $types .= 'ii'; // id_usuario (int), id_registro (int)
  
  $query = "UPDATE $tabla_planes_accion_clientes 
            SET " . implode(', ', $setParts) . ", 
                fecha_actualizacion = NOW(), 
                id_usuario_actualiza = ?
            WHERE id_registro = ? AND estado_activo = 1";
  
  echo "<p><strong>Query SQL:</strong></p>";
  echo "<pre>$query</pre>";
  
  echo "<p><strong>Parámetros (ORDEN CORRECTO):</strong></p>";
  echo "<pre>" . print_r($params, true) . "</pre>";
  
  echo "<p><strong>Tipos:</strong> $types</p>";
  
  // Mostrar la consulta convertida para verificar
  echo "<p><strong>Consulta Convertida:</strong></p>";
  echo "<pre>" . $funciones->fnConvertirConsulta($conn, $query, $params) . "</pre>";
  
  $resultado = $funciones->fnGuardarRegistro($conn, $query, $params, $types);
  
  echo "<p><strong>Resultado:</strong></p>";
  echo "<pre>" . print_r($resultado, true) . "</pre>";
  
  if ($resultado['success']) {
    echo "<p style='color: green;'><strong>Éxito:</strong> Los parámetros están en el orden correcto</p>";
  } else {
    echo "<p style='color: red;'><strong>Error:</strong> " . $resultado['response'] . "</p>";
  }
  
} catch (Exception $e) {
  echo "<p style='color: red;'><strong>Excepción:</strong> " . $e->getMessage() . "</p>";
  error_log("Excepción en test_orden_parametros.php: " . $e->getMessage());
}
?>
