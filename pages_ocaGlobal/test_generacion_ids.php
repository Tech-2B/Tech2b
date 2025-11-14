<?php
/**
 * Archivo de prueba para verificar la generación de IDs únicos
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

/**
 * Generar ID único para un cliente específico
 */
function generarIdUnicoCliente($conn, $idCliente, $tipo) {
  $funciones = new FuncionesGenerales();
  
  // Obtener el máximo ID existente para este cliente y tipo
  $query = "SELECT MAX(CAST(id_$tipo AS UNSIGNED)) as max_id 
            FROM wwappb_field_test.OCAGLOBAL_planes_accion_clientes 
            WHERE id_cliente = ? AND id_$tipo REGEXP '^[0-9]+$'";
  
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, [$idCliente], 'i');
  
  $maxId = 0;
  if ($resultado['success'] && !empty($resultado['datos'])) {
    $maxId = (int)$resultado['datos'][0]['max_id'];
  }
  
  // Generar nuevo ID (máximo + 1, pero mínimo 1)
  $nuevoId = max(1, $maxId + 1);
  
  // Verificar que el ID no existe (por si acaso)
  $queryVerificar = "SELECT COUNT(*) as existe 
                     FROM wwappb_field_test.OCAGLOBAL_planes_accion_clientes 
                     WHERE id_cliente = ? AND id_$tipo = ?";
  
  $resultadoVerificar = $funciones->fnBuscarDatosRegistro($conn, $queryVerificar, [$idCliente, $nuevoId], 'ii');
  
  if ($resultadoVerificar['success'] && $resultadoVerificar['datos'][0]['existe'] > 0) {
    // Si existe, buscar el siguiente disponible
    $nuevoId = $maxId + 2;
  }
  
  return $nuevoId;
}

try {
  $funciones = new FuncionesGenerales();
  
  echo "<h2>Prueba de Generación de IDs Únicos</h2>";
  
  $id_cliente = 1; // Cliente de prueba
  
  echo "<h3>Cliente ID: $id_cliente</h3>";
  
  // Mostrar IDs existentes para este cliente
  echo "<h4>IDs existentes para este cliente:</h4>";
  $queryExistentes = "SELECT id_area_oportunidad, id_plan_accion, id_topico, id_entregable, id_periodicidad 
                      FROM $tabla_planes_accion_clientes 
                      WHERE id_cliente = ? 
                      ORDER BY id_registro DESC 
                      LIMIT 5";
  
  $resultadoExistentes = $funciones->fnBuscarDatosRegistro($conn, $queryExistentes, [$id_cliente], 'i');
  
  if ($resultadoExistentes['success'] && !empty($resultadoExistentes['datos'])) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Área</th><th>Plan</th><th>Tópico</th><th>Entregable</th><th>Periodicidad</th></tr>";
    foreach ($resultadoExistentes['datos'] as $fila) {
      echo "<tr>";
      echo "<td>" . $fila['id_area_oportunidad'] . "</td>";
      echo "<td>" . $fila['id_plan_accion'] . "</td>";
      echo "<td>" . $fila['id_topico'] . "</td>";
      echo "<td>" . $fila['id_entregable'] . "</td>";
      echo "<td>" . $fila['id_periodicidad'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "No hay registros existentes para este cliente.<br>";
  }
  
  // Generar nuevos IDs
  echo "<h4>Nuevos IDs generados:</h4>";
  
  $tipos = ['area_oportunidad', 'plan_accion', 'topico', 'entregable', 'periodicidad'];
  
  foreach ($tipos as $tipo) {
    $nuevoId = generarIdUnicoCliente($conn, $id_cliente, $tipo);
    echo "<strong>$tipo:</strong> $nuevoId<br>";
  }
  
  // Probar generación múltiple para verificar unicidad
  echo "<h4>Prueba de unicidad (generando 5 IDs de cada tipo):</h4>";
  
  foreach ($tipos as $tipo) {
    echo "<strong>$tipo:</strong> ";
    $ids = [];
    for ($i = 0; $i < 5; $i++) {
      $id = generarIdUnicoCliente($conn, $id_cliente, $tipo);
      $ids[] = $id;
    }
    echo implode(', ', $ids) . "<br>";
  }
  
} catch (Exception $e) {
  echo "<h3>Error:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}
?>
