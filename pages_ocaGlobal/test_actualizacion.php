<?php
/**
 * Archivo de prueba para verificar la actualización de descripciones
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
    'descripcion_area_oportunidad' => 'Área de prueba actualizada',
    'descripcion_plan_accion' => 'Plan de prueba actualizado'
  ];
  
  echo "<h2>Prueba de Actualización de Descripciones</h2>";
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
  
  echo "<p><strong>Cambios válidos:</strong></p>";
  echo "<pre>" . print_r($cambiosValidos, true) . "</pre>";
  
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
  
  // Agregar parámetros para WHERE y fecha de actualización
  $params[] = $id_registro;
  $params[] = 1; // ID de usuario de prueba
  $types .= 'ii'; // id_registro (int), id_usuario (int)
  
  $query = "UPDATE $tabla_planes_accion_clientes 
            SET " . implode(', ', $setParts) . ", 
                fecha_actualizacion = NOW(), 
                id_usuario_actualiza = ?
            WHERE id_registro = ? AND estado_activo = 1";
  
  echo "<p><strong>Query SQL:</strong></p>";
  echo "<pre>$query</pre>";
  
  echo "<p><strong>Parámetros:</strong></p>";
  echo "<pre>" . print_r($params, true) . "</pre>";
  
  echo "<p><strong>Tipos:</strong> $types</p>";
  
  $resultado = $funciones->fnGuardarRegistro($conn, $query, $params, $types);
  
  echo "<p><strong>Resultado:</strong></p>";
  echo "<pre>" . print_r($resultado, true) . "</pre>";
  
  if ($resultado['success']) {
    echo "<p style='color: green;'><strong>Éxito:</strong> Las descripciones se actualizaron correctamente</p>";
  } else {
    echo "<p style='color: red;'><strong>Error:</strong> No se pudieron actualizar las descripciones: " . $resultado['response'] . "</p>";
  }
  
} catch (Exception $e) {
  echo "<p style='color: red;'><strong>Excepción:</strong> " . $e->getMessage() . "</p>";
  error_log("Excepción en test_actualizacion.php: " . $e->getMessage());
}
?>
