<?php
/**
 * Archivo de prueba específico para verificar el cliente que está causando el error
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
  
  echo "<h2>Prueba Específica del Cliente</h2>";
  
  // Simular el ID del cliente que se está enviando (del registro 38)
  // Necesitamos verificar qué ID se está enviando realmente
  $id_cliente = 1; // Cambia este valor por el ID correcto
  
  echo "<h3>Verificando cliente con ID: $id_cliente</h3>";
  
  // Probar diferentes consultas
  echo "<h4>1. Consulta con 'activo':</h4>";
  $query1 = "SELECT id_cliente, nombre_cliente, activo FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  $resultado1 = $funciones->fnBuscarDatosRegistro($conn, $query1, [$id_cliente], 'i');
  echo "<pre>" . json_encode($resultado1, JSON_PRETTY_PRINT) . "</pre>";
  
  echo "<h4>2. Consulta sin filtro de activo:</h4>";
  $query2 = "SELECT id_cliente, nombre_cliente, activo FROM $tabla_clientes WHERE id_cliente = ?";
  $resultado2 = $funciones->fnBuscarDatosRegistro($conn, $query2, [$id_cliente], 'i');
  echo "<pre>" . json_encode($resultado2, JSON_PRETTY_PRINT) . "</pre>";
  
  echo "<h4>3. Consulta con 'estado_activo':</h4>";
  $query3 = "SELECT id_cliente, nombre_cliente, activo FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  $resultado3 = $funciones->fnBuscarDatosRegistro($conn, $query3, [$id_cliente], 'i');
  echo "<pre>" . json_encode($resultado3, JSON_PRETTY_PRINT) . "</pre>";
  
  // Mostrar estructura de la tabla
  echo "<h4>4. Estructura de la tabla:</h4>";
  $queryEstructura = "DESCRIBE $tabla_clientes";
  $resultadoEstructura = $funciones->fnBuscarDatosRegistro($conn, $queryEstructura, [], '');
  if ($resultadoEstructura['success']) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($resultadoEstructura['datos'] as $campo) {
      echo "<tr>";
      echo "<td>" . $campo['Field'] . "</td>";
      echo "<td>" . $campo['Type'] . "</td>";
      echo "<td>" . $campo['Null'] . "</td>";
      echo "<td>" . $campo['Key'] . "</td>";
      echo "<td>" . $campo['Default'] . "</td>";
      echo "<td>" . $campo['Extra'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
  
  // Mostrar todos los clientes
  echo "<h4>5. Todos los clientes en la tabla:</h4>";
  $queryTodos = "SELECT * FROM $tabla_clientes ORDER BY id_cliente ASC";
  $resultadoTodos = $funciones->fnBuscarDatosRegistro($conn, $queryTodos, [], '');
  if ($resultadoTodos['success'] && !empty($resultadoTodos['datos'])) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Código</th><th>Tipo</th><th>Activo</th><th>Estado_Activo</th></tr>";
    foreach ($resultadoTodos['datos'] as $cliente) {
      echo "<tr>";
      echo "<td>" . $cliente['id_cliente'] . "</td>";
      echo "<td>" . $cliente['nombre_cliente'] . "</td>";
      echo "<td>" . ($cliente['codigo_cliente'] ?? 'N/A') . "</td>";
      echo "<td>" . ($cliente['tipo_cliente'] ?? 'N/A') . "</td>";
      echo "<td>" . (isset($cliente['activo']) ? $cliente['activo'] : 'N/A') . "</td>";
      echo "<td>" . (isset($cliente['estado_activo']) ? $cliente['estado_activo'] : 'N/A') . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  }
  
} catch (Exception $e) {
  echo "<h3>Error:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}
?>
