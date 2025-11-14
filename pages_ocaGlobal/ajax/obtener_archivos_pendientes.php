<?php

/**
 * Archivo AJAX para obtener archivos pendientes de validación
 * Solo para usuarios con rol "Administrador"
 */

session_start();

// Verificar permisos - Solo Administradores pueden ver archivos pendientes
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
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
include '../../sql/conexionMysqliUTF8Dev.php';
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
  
  // Obtener parámetros opcionales
  $estado = $funciones->fnLimpiarCadena($_GET['estado'] ?? 'pendiente');
  $id_cliente = $funciones->fnLimpiarCadena($_GET['id_cliente'] ?? '');
  $id_plan_accion = $funciones->fnLimpiarCadena($_GET['id_plan_accion'] ?? '');
  $limit = intval($_GET['limit'] ?? 50);
  $offset = intval($_GET['offset'] ?? 0);
  
  // Construir consulta base
  $query = "SELECT 
    ap.*,
    c.nombre_cliente,
    pac.descripcion_plan_accion
  FROM $tabla_archivos_pendientes ap
  LEFT JOIN $tabla_clientes c ON ap.id_cliente = c.id_cliente
  LEFT JOIN $tabla_planes_accion_clientes pac ON ap.id_plan_accion = pac.id_plan_accion
  WHERE ap.activo = 1";
  
  $params = [];
  $types = '';
  
  // Agregar filtros
  if (!empty($estado)) {
    $query .= " AND ap.estado_validacion = ?";
    $params[] = $estado;
    $types .= 's';
  }
  
  if (!empty($id_cliente)) {
    $query .= " AND ap.id_cliente = ?";
    $params[] = $id_cliente;
    $types .= 'i';
  }
  
  if (!empty($id_plan_accion)) {
    $query .= " AND ap.id_plan_accion = ?";
    $params[] = $id_plan_accion;
    $types .= 'i';
  }
  
  // Ordenar por fecha de subida (más recientes primero)
  $query .= " ORDER BY ap.fecha_subida DESC";
  
  // Agregar límite y offset
  $query .= " LIMIT ? OFFSET ?";
  $params[] = $limit;
  $params[] = $offset;
  $types .= 'ii';
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    echo json_encode([
      'success' => false,
      'message' => 'Error al preparar la consulta',
      'code' => 500
    ]);
    exit();
  }
  
  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }
  
  $stmt->execute();
  $result = $stmt->get_result();
  
  $archivos = [];
  while ($row = $result->fetch_assoc()) {
    // Formatear datos
    $archivo = [
      'id_archivo_pendiente' => $row['id_archivo_pendiente'],
      'id_cliente' => $row['id_cliente'],
      'id_plan_accion' => $row['id_plan_accion'],
      'id_carpeta_drive' => $row['id_carpeta_drive'],
      'nombre_archivo_original' => $row['nombre_archivo_original'],
      'nombre_archivo_sistema' => $row['nombre_archivo_sistema'],
      'tipo_archivo' => $row['tipo_archivo'],
      'tamano_archivo' => $row['tamano_archivo'],
      'tamano_formateado' => $this->formatearTamano($row['tamano_archivo']),
      'comentario' => $row['comentario'],
      'id_usuario_subio' => $row['id_usuario_subio'],
      'nombre_usuario_subio' => $row['nombre_usuario_subio'],
      'fecha_subida' => $row['fecha_subida'],
      'fecha_subida_formateada' => date('d/m/Y H:i', strtotime($row['fecha_subida'])),
      'estado_validacion' => $row['estado_validacion'],
      'id_usuario_valido' => $row['id_usuario_valido'],
      'nombre_usuario_valido' => $row['nombre_usuario_valido'],
      'fecha_validacion' => $row['fecha_validacion'],
      'fecha_validacion_formateada' => $row['fecha_validacion'] ? date('d/m/Y H:i', strtotime($row['fecha_validacion'])) : null,
      'comentario_validacion' => $row['comentario_validacion'],
      'nombre_cliente' => $row['nombre_cliente'],
      'descripcion_plan_accion' => $row['descripcion_plan_accion'],
      'icono_tipo' => $this->obtenerIconoTipoArchivo($row['tipo_archivo'])
    ];
    
    $archivos[] = $archivo;
  }
  
  // Obtener total de registros para paginación
  $query_count = "SELECT COUNT(*) as total FROM $tabla_archivos_pendientes ap WHERE ap.activo = 1";
  $params_count = [];
  $types_count = '';
  
  if (!empty($estado)) {
    $query_count .= " AND ap.estado_validacion = ?";
    $params_count[] = $estado;
    $types_count .= 's';
  }
  
  if (!empty($id_cliente)) {
    $query_count .= " AND ap.id_cliente = ?";
    $params_count[] = $id_cliente;
    $types_count .= 'i';
  }
  
  if (!empty($id_plan_accion)) {
    $query_count .= " AND ap.id_plan_accion = ?";
    $params_count[] = $id_plan_accion;
    $types_count .= 'i';
  }
  
  $stmt_count = $conn->prepare($query_count);
  if ($stmt_count) {
    if (!empty($params_count)) {
      $stmt_count->bind_param($types_count, ...$params_count);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total = $result_count->fetch_assoc()['total'];
    $stmt_count->close();
  } else {
    $total = count($archivos);
  }
  
  $stmt->close();
  
  echo json_encode([
    'success' => true,
    'message' => 'Archivos pendientes obtenidos correctamente',
    'data' => $archivos,
    'pagination' => [
      'total' => $total,
      'limit' => $limit,
      'offset' => $offset,
      'has_more' => ($offset + $limit) < $total
    ],
    'code' => 200
  ]);
  
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error interno del servidor: ' . $e->getMessage(),
    'code' => 500
  ]);
}

$conn->close();

/**
 * Función para formatear tamaño de archivo
 */
function formatearTamano($bytes) {
  if ($bytes >= 1073741824) {
    return number_format($bytes / 1073741824, 2) . ' GB';
  } elseif ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 2) . ' MB';
  } elseif ($bytes >= 1024) {
    return number_format($bytes / 1024, 2) . ' KB';
  } else {
    return $bytes . ' bytes';
  }
}

/**
 * Función para obtener icono según tipo de archivo
 */
function obtenerIconoTipoArchivo($tipo) {
  $iconos = [
    'pdf' => ['icono' => 'fa-file-pdf', 'clase' => 'text-danger'],
    'doc' => ['icono' => 'fa-file-word', 'clase' => 'text-primary'],
    'docx' => ['icono' => 'fa-file-word', 'clase' => 'text-primary'],
    'xls' => ['icono' => 'fa-file-excel', 'clase' => 'text-success'],
    'xlsx' => ['icono' => 'fa-file-excel', 'clase' => 'text-success'],
    'ppt' => ['icono' => 'fa-file-powerpoint', 'clase' => 'text-warning'],
    'pptx' => ['icono' => 'fa-file-powerpoint', 'clase' => 'text-warning'],
    'jpg' => ['icono' => 'fa-file-image', 'clase' => 'text-info'],
    'jpeg' => ['icono' => 'fa-file-image', 'clase' => 'text-info'],
    'png' => ['icono' => 'fa-file-image', 'clase' => 'text-info'],
    'gif' => ['icono' => 'fa-file-image', 'clase' => 'text-info'],
    'txt' => ['icono' => 'fa-file-alt', 'clase' => 'text-secondary'],
    'zip' => ['icono' => 'fa-file-archive', 'clase' => 'text-dark'],
    'rar' => ['icono' => 'fa-file-archive', 'clase' => 'text-dark']
  ];
  
  return $iconos[$tipo] ?? ['icono' => 'fa-file', 'clase' => 'text-muted'];
}
?>
