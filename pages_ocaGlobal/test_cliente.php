<?php
/**
 * Archivo de prueba para verificar si el cliente existe en la base de datos
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
  
  echo "<h2>Prueba de Cliente en Base de Datos</h2>";
  
  // ID del cliente que se está intentando crear (del registro 37)
  $id_cliente = 1; // Cambia este valor por el ID del cliente que estás usando
  
  echo "<h3>Verificando cliente con ID: $id_cliente</h3>";
  
  // Consulta para verificar si el cliente existe
  $queryCliente = "SELECT id_cliente, nombre_cliente, codigo_cliente, tipo_cliente, estado_activo FROM $tabla_clientes WHERE id_cliente = ?";
  $resultadoCliente = $funciones->fnBuscarDatosRegistro($conn, $queryCliente, [$id_cliente], 'i');
  
  echo "<h3>Resultado de la consulta:</h3>";
  echo "<pre>" . json_encode($resultadoCliente, JSON_PRETTY_PRINT) . "</pre>";
  
  if ($resultadoCliente['success'] && !empty($resultadoCliente['datos'])) {
    echo "<h3>✓ Cliente encontrado:</h3>";
    $cliente = $resultadoCliente['datos'][0];
    echo "ID: " . $cliente['id_cliente'] . "<br>";
    echo "Nombre: " . $cliente['nombre_cliente'] . "<br>";
    echo "Código: " . $cliente['codigo_cliente'] . "<br>";
    echo "Tipo: " . $cliente['tipo_cliente'] . "<br>";
    echo "Estado: " . $cliente['estado_activo'] . "<br>";
  } else {
    echo "<h3>✗ Cliente NO encontrado</h3>";
    echo "Error: " . ($resultadoCliente['response'] ?? 'Sin respuesta') . "<br>";
  }
  
  // Mostrar todos los clientes disponibles
  echo "<h3>Clientes disponibles en la base de datos:</h3>";
  $queryTodos = "SELECT id_cliente, nombre_cliente, codigo_cliente, estado_activo FROM $tabla_clientes ORDER BY id_cliente ASC";
  $resultadoTodos = $funciones->fnBuscarDatosRegistro($conn, $queryTodos, [], '');
  
  if ($resultadoTodos['success'] && !empty($resultadoTodos['datos'])) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Nombre</th><th>Código</th><th>Estado</th></tr>";
    foreach ($resultadoTodos['datos'] as $cliente) {
      echo "<tr>";
      echo "<td>" . $cliente['id_cliente'] . "</td>";
      echo "<td>" . $cliente['nombre_cliente'] . "</td>";
      echo "<td>" . $cliente['codigo_cliente'] . "</td>";
      echo "<td>" . $cliente['estado_activo'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "No se encontraron clientes en la base de datos.<br>";
  }
  
} catch (Exception $e) {
  echo "<h3>Error:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}
?>
