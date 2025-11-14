<?php

/**
 * Archivo AJAX para obtener archivos pendientes de validación
 */

session_start();

// Verificar permisos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para ver archivos pendientes',
    'code' => 403
  ]);
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo json_encode([
    'success' => false,
    'message' => 'Error de conexión a la base de datos',
    'code' => 500
  ]);
  exit();
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener parámetros
  $id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : 0;
  $id_plan_accion = isset($_GET['id_plan_accion']) ? intval($_GET['id_plan_accion']) : 0;
  
  if ($id_cliente <= 0 || $id_plan_accion <= 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Parámetros inválidos',
      'code' => 400
    ]);
    exit();
  }
  
  // Consulta para obtener archivos pendientes de validación
  $query = "
    SELECT 
      apv.id_archivo_pendiente,
      apv.id_registro,
      apv.id_cliente,
      apv.id_plan_accion,
      apv.id_carpeta_drive,
      apv.nombre_archivo_original,
      apv.nombre_archivo_sistema,
      apv.ruta_archivo_temporal,
      apv.tipo_archivo,
      apv.tamano_archivo,
      apv.comentario,
      apv.estatus_validacion,
      apv.comentario_rechazo,
      apv.fecha_subida,
      apv.fecha_validacion,
      apv.id_usuario_subio,
      apv.id_usuario_valido,
      u.nombre as nombre_usuario,
      u.apellido_paterno as apellido_usuario
    FROM $tabla_archivos_pendientes_validacion apv
    LEFT JOIN $tabla_usuarios u ON apv.id_usuario_subio = u.id_usuario
    WHERE apv.id_cliente = ? 
      AND apv.id_plan_accion = ? 
      AND apv.activo = 1
      AND apv.estatus_validacion <> 'aprobado'
    ORDER BY apv.fecha_subida DESC
  ";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    throw new Exception("Error preparando consulta: " . $conn->error);
  }
  
  $stmt->bind_param("ii", $id_cliente, $id_plan_accion);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $archivos = [];
  while ($row = $result->fetch_assoc()) {
    // Formatear datos
    $archivo = [
      'id_archivo_pendiente' => $row['id_archivo_pendiente'],
      'id_registro' => $row['id_registro'],
      'id_cliente' => $row['id_cliente'],
      'id_plan_accion' => $row['id_plan_accion'],
      'id_carpeta_drive' => $row['id_carpeta_drive'],
      'nombre_archivo_original' => $row['nombre_archivo_original'],
      'nombre_archivo_sistema' => $row['nombre_archivo_sistema'],
      'ruta_archivo_temporal' => $row['ruta_archivo_temporal'],
      'tipo_archivo' => $row['tipo_archivo'],
      'tamano_archivo' => $row['tamano_archivo'],
      'comentario' => $row['comentario'],
      'estatus_validacion' => $row['estatus_validacion'],
      'comentario_rechazo' => $row['comentario_rechazo'],
      'fecha_subida' => $row['fecha_subida'],
      'fecha_validacion' => $row['fecha_validacion'],
      'id_usuario_subio' => $row['id_usuario_subio'],
      'id_usuario_valido' => $row['id_usuario_valido'],
      'nombre_usuario' => trim($row['nombre_usuario'] . ' ' . $row['apellido_usuario'])
    ];
    
    $archivos[] = $archivo;
  }
  
  $stmt->close();
  
  echo json_encode([
    'success' => true,
    'message' => 'Archivos pendientes obtenidos correctamente',
    'data' => $archivos,
    'code' => 200
  ]);
  
} catch (Exception $e) {
  error_log("Error en obtener_archivos_pendientes_validacion.php: " . $e->getMessage());
  
  echo json_encode([
    'success' => false,
    'message' => 'Error interno del servidor',
    'code' => 500
  ]);
} finally {
  if (isset($conn)) {
    $conn->close();
  }
}
?>
