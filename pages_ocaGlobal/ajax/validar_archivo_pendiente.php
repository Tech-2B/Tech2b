<?php

/**
 * Archivo AJAX para validar archivos pendientes
 * Solo para usuarios con rol "Administrador"
 * Permite aprobar o rechazar archivos subidos por Colaboradores
 */

session_start();

// Verificar permisos - Solo Administradores pueden validar archivos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para validar archivos',
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
include '../includes/funcionesGoogleDrive.php';

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

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener datos del formulario
  $id_archivo_pendiente = $funciones->fnLimpiarCadena($_POST['id_archivo_pendiente'] ?? '');
  $accion = $funciones->fnLimpiarCadena($_POST['accion'] ?? ''); // 'aprobar' o 'rechazar'
  $comentario_validacion = $funciones->fnLimpiarCadena($_POST['comentario_validacion'] ?? '');
  
  // Validar datos requeridos
  if (empty($id_archivo_pendiente) || empty($accion)) {
    echo json_encode([
      'success' => false,
      'message' => 'Faltan datos requeridos',
      'code' => 400
    ]);
    exit();
  }
  
  if (!in_array($accion, ['aprobar', 'rechazar'])) {
    echo json_encode([
      'success' => false,
      'message' => 'Acción no válida',
      'code' => 400
    ]);
    exit();
  }
  
  // Obtener información del archivo pendiente
  $query_archivo = "SELECT * FROM $tabla_archivos_pendientes WHERE id_archivo_pendiente = ? AND activo = 1";
  $stmt_archivo = $conn->prepare($query_archivo);
  $stmt_archivo->bind_param('i', $id_archivo_pendiente);
  $stmt_archivo->execute();
  $result_archivo = $stmt_archivo->get_result();
  
  if ($result_archivo->num_rows === 0) {
    echo json_encode([
      'success' => false,
      'message' => 'Archivo pendiente no encontrado',
      'code' => 404
    ]);
    exit();
  }
  
  $archivo_pendiente = $result_archivo->fetch_assoc();
  $stmt_archivo->close();
  
  // Verificar que el archivo esté en estado pendiente
  if ($archivo_pendiente['estado_validacion'] !== 'pendiente') {
    echo json_encode([
      'success' => false,
      'message' => 'El archivo ya ha sido validado',
      'code' => 400
    ]);
    exit();
  }
  
  // Obtener información del usuario que valida
  $id_usuario_valido = $_SESSION['id_usuario'];
  $nombre_usuario_valido = $_SESSION['nombre'] . ' ' . $_SESSION['apellido_paterno'];
  $fecha_validacion = date('Y-m-d H:i:s');
  
  // Iniciar transacción
  $conn->begin_transaction();
  
  try {
    if ($accion === 'aprobar') {
      // Proceso de aprobación: subir a Google Drive y crear registro
      
      // Verificar que el archivo temporal existe
      if (!file_exists($archivo_pendiente['ruta_archivo_temporal'])) {
        throw new Exception('El archivo temporal no existe');
      }
      
      // Subir archivo a Google Drive
      $funcionesGoogleDrive = new FuncionesGoogleDrive($conn);
      $resultado_subida = $funcionesGoogleDrive->subirArchivo(
        $archivo_pendiente['ruta_archivo_temporal'],
        $archivo_pendiente['nombre_archivo_original'],
        $archivo_pendiente['id_carpeta_drive'],
        $archivo_pendiente['comentario'] ?? ''
      );
      
      if (!$resultado_subida['success']) {
        throw new Exception('Error al subir archivo a Google Drive: ' . $resultado_subida['message']);
      }
      
      // Insertar registro en la tabla de archivos de Drive
      $query_insert = "INSERT INTO $tabla_archivos_drive (
        id_carpeta_drive, nombre_archivo_original, nombre_archivo_drive,
        id_archivo_drive, url_drive, url_descarga, tipo_archivo,
        tamano_archivo, comentario, id_usuario_subio, nombre_usuario_subio
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      
      $stmt_insert = $conn->prepare($query_insert);
      $stmt_insert->bind_param(
        'sssssssisss',
        $archivo_pendiente['id_carpeta_drive'],
        $archivo_pendiente['nombre_archivo_original'],
        $resultado_subida['data']['nombre_archivo_drive'],
        $resultado_subida['data']['id_archivo_drive'],
        $resultado_subida['data']['url_drive'],
        $resultado_subida['data']['url_descarga'],
        $archivo_pendiente['tipo_archivo'],
        $archivo_pendiente['tamano_archivo'],
        $archivo_pendiente['comentario'],
        $archivo_pendiente['id_usuario_subio'],
        $archivo_pendiente['nombre_usuario_subio']
      );
      
      if (!$stmt_insert->execute()) {
        throw new Exception('Error al crear registro del archivo en Drive');
      }
      
      $id_archivo_drive = $conn->insert_id;
      $stmt_insert->close();
      
      // Eliminar archivo temporal
      unlink($archivo_pendiente['ruta_archivo_temporal']);
      
      $mensaje = 'Archivo aprobado y subido a Google Drive exitosamente';
      
    } else {
      // Proceso de rechazo: solo marcar como rechazado
      $mensaje = 'Archivo rechazado';
      
      // Eliminar archivo temporal
      if (file_exists($archivo_pendiente['ruta_archivo_temporal'])) {
        unlink($archivo_pendiente['ruta_archivo_temporal']);
      }
    }
    
    // Actualizar estado del archivo pendiente
    $estado_final = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';
    
    $query_update = "UPDATE $tabla_archivos_pendientes SET 
      estado_validacion = ?,
      id_usuario_valido = ?,
      nombre_usuario_valido = ?,
      fecha_validacion = ?,
      comentario_validacion = ?
    WHERE id_archivo_pendiente = ?";
    
    $stmt_update = $conn->prepare($query_update);
    $stmt_update->bind_param(
      'sisssi',
      $estado_final,
      $id_usuario_valido,
      $nombre_usuario_valido,
      $fecha_validacion,
      $comentario_validacion,
      $id_archivo_pendiente
    );
    
    if (!$stmt_update->execute()) {
      throw new Exception('Error al actualizar el estado del archivo');
    }
    
    $stmt_update->close();
    
    // Confirmar transacción
    $conn->commit();
    
    echo json_encode([
      'success' => true,
      'message' => $mensaje,
      'data' => [
        'id_archivo_pendiente' => $id_archivo_pendiente,
        'estado' => $estado_final,
        'fecha_validacion' => $fecha_validacion,
        'usuario_valido' => $nombre_usuario_valido
      ],
      'code' => 200
    ]);
    
  } catch (Exception $e) {
    // Revertir transacción
    $conn->rollback();
    throw $e;
  }
  
} catch (Exception $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Error al validar archivo: ' . $e->getMessage(),
    'code' => 500
  ]);
}

$conn->close();
?>
