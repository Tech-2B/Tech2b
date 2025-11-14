<?php
/**
 * Archivo de prueba para verificar que los IDs se guardan en la tabla de carpetas de Drive
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
  
  echo "<h2>Verificación de Carpetas en Drive</h2>";
  
  $id_cliente = 1; // Cliente de prueba
  
  echo "<h3>Cliente ID: $id_cliente</h3>";
  
  // Mostrar carpetas existentes para este cliente
  echo "<h4>Carpetas existentes en Drive para este cliente:</h4>";
  $queryCarpetas = "SELECT id_carpeta, id_carpeta_drive, nombre_carpeta, tipo_carpeta, id_area_oportunidad, id_plan_accion, ruta_completa, fecha_creacion 
                    FROM $tabla_carpetas_drive 
                    WHERE id_cliente = ? AND estado_activo = 1 
                    ORDER BY fecha_creacion DESC";
  
  $resultadoCarpetas = $funciones->fnBuscarDatosRegistro($conn, $queryCarpetas, [$id_cliente], 'i');
  
  if ($resultadoCarpetas['success'] && !empty($resultadoCarpetas['datos'])) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>";
    echo "<th>ID BD</th>";
    echo "<th>ID Drive</th>";
    echo "<th>Nombre</th>";
    echo "<th>Tipo</th>";
    echo "<th>ID Área</th>";
    echo "<th>ID Plan</th>";
    echo "<th>Ruta</th>";
    echo "<th>Fecha</th>";
    echo "</tr>";
    
    foreach ($resultadoCarpetas['datos'] as $carpeta) {
      echo "<tr>";
      echo "<td>" . $carpeta['id_carpeta'] . "</td>";
      echo "<td>" . $carpeta['id_carpeta_drive'] . "</td>";
      echo "<td>" . $carpeta['nombre_carpeta'] . "</td>";
      echo "<td>" . $carpeta['tipo_carpeta'] . "</td>";
      echo "<td>" . ($carpeta['id_area_oportunidad'] ?? 'N/A') . "</td>";
      echo "<td>" . ($carpeta['id_plan_accion'] ?? 'N/A') . "</td>";
      echo "<td>" . $carpeta['ruta_completa'] . "</td>";
      echo "<td>" . $carpeta['fecha_creacion'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "No hay carpetas en Drive para este cliente.<br>";
  }
  
  // Mostrar planes de acción del cliente
  echo "<h4>Planes de acción del cliente:</h4>";
  $queryPlanes = "SELECT id_registro, id_area_oportunidad, descripcion_area_oportunidad, id_plan_accion, descripcion_plan_accion, fecha_creacion 
                  FROM $tabla_planes_accion_clientes 
                  WHERE id_cliente = ? AND estado_activo = 1 
                  ORDER BY fecha_creacion DESC";
  
  $resultadoPlanes = $funciones->fnBuscarDatosRegistro($conn, $queryPlanes, [$id_cliente], 'i');
  
  if ($resultadoPlanes['success'] && !empty($resultadoPlanes['datos'])) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr>";
    echo "<th>ID Registro</th>";
    echo "<th>ID Área</th>";
    echo "<th>Descripción Área</th>";
    echo "<th>ID Plan</th>";
    echo "<th>Descripción Plan</th>";
    echo "<th>Fecha</th>";
    echo "</tr>";
    
    foreach ($resultadoPlanes['datos'] as $plan) {
      echo "<tr>";
      echo "<td>" . $plan['id_registro'] . "</td>";
      echo "<td>" . $plan['id_area_oportunidad'] . "</td>";
      echo "<td>" . $plan['descripcion_area_oportunidad'] . "</td>";
      echo "<td>" . $plan['id_plan_accion'] . "</td>";
      echo "<td>" . $plan['descripcion_plan_accion'] . "</td>";
      echo "<td>" . $plan['fecha_creacion'] . "</td>";
      echo "</tr>";
    }
    echo "</table>";
  } else {
    echo "No hay planes de acción para este cliente.<br>";
  }
  
  // Verificar correspondencia entre planes y carpetas
  echo "<h4>Verificación de correspondencia:</h4>";
  if ($resultadoPlanes['success'] && !empty($resultadoPlanes['datos']) && 
      $resultadoCarpetas['success'] && !empty($resultadoCarpetas['datos'])) {
    
    foreach ($resultadoPlanes['datos'] as $plan) {
      $idArea = $plan['id_area_oportunidad'];
      $idPlan = $plan['id_plan_accion'];
      
      echo "<strong>Plan ID: {$plan['id_registro']} - Área: $idArea, Plan: $idPlan</strong><br>";
      
      // Buscar carpetas correspondientes
      $carpetasArea = array_filter($resultadoCarpetas['datos'], function($c) use ($idArea) {
        return $c['id_area_oportunidad'] == $idArea;
      });
      
      $carpetasPlan = array_filter($resultadoCarpetas['datos'], function($c) use ($idPlan) {
        return $c['id_plan_accion'] == $idPlan;
      });
      
      if (!empty($carpetasArea)) {
        echo "✓ Carpetas de área encontradas: " . count($carpetasArea) . "<br>";
      } else {
        echo "✗ No se encontraron carpetas para el área $idArea<br>";
      }
      
      if (!empty($carpetasPlan)) {
        echo "✓ Carpetas de plan encontradas: " . count($carpetasPlan) . "<br>";
      } else {
        echo "✗ No se encontraron carpetas para el plan $idPlan<br>";
      }
      
      echo "<br>";
    }
  }
  
} catch (Exception $e) {
  echo "<h3>Error:</h3>";
  echo "Mensaje: " . $e->getMessage() . "<br>";
  echo "Archivo: " . $e->getFile() . "<br>";
  echo "Línea: " . $e->getLine() . "<br>";
}
?>
