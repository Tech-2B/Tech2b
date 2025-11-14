<?php

/**
 * Archivo AJAX para subir archivos pendientes de validación
 * Solo para usuarios con rol "Colaborador"
 * Los archivos quedan pendientes hasta ser aprobados por un Administrador
 */

session_start();

// Verificar permisos - Solo Colaboradores pueden subir archivos pendientes
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Colaborador') {
  echo json_encode([
    'success' => false,
    'message' => 'Solo los Colaboradores pueden subir archivos pendientes de validación',
    'code' => 403
  ]);
  exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode([
    'success' => false,
    'message' => 'Método no permitido',
    'code' => 405
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
  
  // Obtener datos del formulario
  $id_cliente = $funciones->fnLimpiarCadena($_POST['id_cliente'] ?? '');
  $id_plan_accion = $funciones->fnLimpiarCadena($_POST['id_plan_accion'] ?? '');
  $id_carpeta_drive = $funciones->fnLimpiarCadena($_POST['carpeta_destino'] ?? '');
  $comentario = $funciones->fnLimpiarCadena($_POST['comentario'] ?? '');
  
  // Validar datos requeridos
  if (empty($id_cliente) || empty($id_plan_accion) || empty($id_carpeta_drive)) {
    echo json_encode([
      'success' => false,
      'message' => 'Faltan datos requeridos',
      'code' => 400
    ]);
    exit();
  }
  
  // Verificar que se haya subido un archivo
  if (!isset($_FILES['archivo_subir']) || $_FILES['archivo_subir']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
      'success' => false,
      'message' => 'Error al subir el archivo',
      'code' => 400
    ]);
    exit();
  }
  
  $archivo = $_FILES['archivo_subir'];
  
  // Validar tamaño del archivo (máximo 50MB)
  $tamano_maximo = 50 * 1024 * 1024; // 50MB
  if ($archivo['size'] > $tamano_maximo) {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo es demasiado grande. Máximo 50MB',
      'code' => 400
    ]);
    exit();
  }
  
  // Validar tipo de archivo
  $tipos_permitidos = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 
    'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'rar'
  ];
  
  $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
  if (!in_array($extension, $tipos_permitidos)) {
    echo json_encode([
      'success' => false,
      'message' => 'Tipo de archivo no permitido',
      'code' => 400
    ]);
    exit();
  }
  
  // Crear directorio temporal si no existe
  $directorio_temporal = '../../uploads/pendientes/';
  if (!is_dir($directorio_temporal)) {
    mkdir($directorio_temporal, 0755, true);
  }
  
  // Generar nombre único para el archivo
  $nombre_original = $archivo['name'];
  $nombre_sistema = uniqid() . '_' . time() . '.' . $extension;
  $ruta_temporal = $directorio_temporal . $nombre_sistema;
  
  // Mover archivo al directorio temporal
  if (!move_uploaded_file($archivo['tmp_name'], $ruta_temporal)) {
    echo json_encode([
      'success' => false,
      'message' => 'Error al guardar el archivo temporalmente',
      'code' => 500
    ]);
    exit();
  }
  
  // Obtener información del usuario
  $id_usuario = $_SESSION['id_usuario'];
  $nombre_usuario = $_SESSION['nombre'] . ' ' . $_SESSION['apellido_paterno'];
  
  // Insertar registro en la tabla de archivos pendientes
  $query = "INSERT INTO $tabla_archivos_pendientes (
    id_cliente, id_plan_accion, id_carpeta_drive, 
    nombre_archivo_original, nombre_archivo_sistema, ruta_archivo_temporal,
    tipo_archivo, tamano_archivo, comentario,
    id_usuario_subio, nombre_usuario_subio, estado_validacion
  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    // Eliminar archivo temporal si hay error en la consulta
    unlink($ruta_temporal);
    echo json_encode([
      'success' => false,
      'message' => 'Error al preparar la consulta',
      'code' => 500
    ]);
    exit();
  }
  
  $stmt->bind_param(
    'iisssssisss',
    $id_cliente, $id_plan_accion, $id_carpeta_drive,
    $nombre_original, $nombre_sistema, $ruta_temporal,
    $extension, $archivo['size'], $comentario,
    $id_usuario, $nombre_usuario
  );
  
  if ($stmt->execute()) {
    $id_archivo_pendiente = $conn->insert_id;
    
    echo json_encode([
      'success' => true,
      'message' => 'Archivo subido exitosamente. Pendiente de validación por un Administrador.',
      'data' => [
        'id_archivo_pendiente' => $id_archivo_pendiente,
        'nombre_archivo' => $nombre_original,
        'estado' => 'pendiente'
      ],
      'code' => 200
    ]);
  } else {
    // Eliminar archivo temporal si hay error en la inserción
    unlink($ruta_temporal);
    echo json_encode([
      'success' => false,
      'message' => 'Error al guardar el registro del archivo',
      'code' => 500
    ]);
  }
  
  $stmt->close();
  
} catch (Exception $e) {
  // Eliminar archivo temporal si hay excepción
  if (isset($ruta_temporal) && file_exists($ruta_temporal)) {
    unlink($ruta_temporal);
  }
  
  echo json_encode([
    'success' => false,
    'message' => 'Error interno del servidor: ' . $e->getMessage(),
    'code' => 500
  ]);
}

$conn->close();
?>
