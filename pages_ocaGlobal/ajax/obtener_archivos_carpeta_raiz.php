<?php

/**
 * Archivo AJAX para obtener los archivos de la carpeta raíz de un plan de acción
 * Solo consulta, no modifica datos
 */

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
if ($conn->connect_error) {
  echo "Error de conexión: " . $conn->connect_error;
}

try {
  $funciones = new FuncionesGenerales();

  // Obtener parámetros
  $id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;
  $id_plan_accion = isset($_GET['id_plan_accion']) ? (int)$_GET['id_plan_accion'] : 0;

  if ($id_cliente <= 0 || $id_plan_accion <= 0) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Parámetros no válidos'
    );
    exit;
  }

  // Consulta para obtener archivos de la carpeta raíz del plan de acción
  // Los archivos de la carpeta raíz son aquellos que no tienen id_carpeta_drive específico
  // o que están asociados directamente al plan de acción
  $query =
    "SELECT 
      ad.id_archivo,
      ad.id_archivo_drive,
      ad.nombre_archivo,
      ad.nombre_archivo_original,
      ad.tipo_archivo,
      ad.tamano_archivo,
      ad.comentario,
      ad.url_drive,
      ad.url_descarga,
      ad.fecha_subida,
      ad.id_usuario_subida,
      CONCAT(u.nombre, ' ', u.apellido_paterno) AS nombre_usuario,
      'Carpeta Raíz' AS nombre_carpeta
    FROM $tabla_archivos_drive AS ad
    INNER JOIN $tabla_usuarios AS u ON ad.id_usuario_subida = u.id_usuario
    WHERE ad.id_cliente = ? 
    AND ad.id_plan_accion = ? 
    AND (ad.id_carpeta_drive IS NULL OR ad.id_carpeta_drive = '')
    AND ad.estado_activo = 1
    ORDER BY ad.fecha_subida DESC
  ";

  $parametros = [$id_cliente, $id_plan_accion];
  $tipos = 'ii';
  echo $funciones->fnConvertirConsulta($conn, $query, $parametros, $tipos);
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, $parametros, $tipos);

  if ($resultado['success']) {
    // Formatear los datos de archivos
    $archivos = array_map(function($archivo) {
      return [
        'id_archivo' => $archivo['id_archivo'],
        'id_archivo_drive' => $archivo['id_archivo_drive'],
        'nombre_archivo' => $archivo['nombre_archivo'],
        'nombre_archivo_original' => $archivo['nombre_archivo_original'],
        'tipo_archivo' => $archivo['tipo_archivo'],
        'tamano_archivo' => $archivo['tamano_archivo'],
        'tamano_formateado' => formatearTamanoArchivo($archivo['tamano_archivo']),
        'comentario' => $archivo['comentario'],
        'url_drive' => $archivo['url_drive'],
        'url_descarga' => $archivo['url_descarga'],
        'fecha_subida' => $archivo['fecha_subida'],
        'fecha_formateada' => formatearFecha($archivo['fecha_subida']),
        'id_usuario_subida' => $archivo['id_usuario_subida'],
        'nombre_usuario' => $archivo['nombre_usuario'],
        'nombre_carpeta' => $archivo['nombre_carpeta'],
        'icono_tipo' => obtenerIconoTipoArchivo($archivo['tipo_archivo'])
      ];
    }, $resultado['datos']);
    
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_encontrado,
      $archivos
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      false,
      $icon_info,
      $titulo_sin_informacion,
      $mensaje_no_encontrado,
      []
    );
  }
} catch (Exception $e) {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
}

/**
 * Formatear tamaño de archivo en formato legible
 */
function formatearTamanoArchivo($bytes) {
  if ($bytes == 0) return '0 B';
  
  $k = 1024;
  $sizes = ['B', 'KB', 'MB', 'GB'];
  $i = floor(log($bytes) / log($k));
  
  return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Formatear fecha en formato legible
 */
function formatearFecha($fecha) {
  $timestamp = strtotime($fecha);
  return date('d/m/Y H:i', $timestamp);
}

/**
 * Obtener icono según el tipo de archivo
 */
function obtenerIconoTipoArchivo($tipo) {
  $tipo_lower = strtolower($tipo);
  
  if (strpos($tipo_lower, 'pdf') !== false) {
    return ['icono' => 'fa-file-pdf', 'clase' => 'pdf'];
  } elseif (strpos($tipo_lower, 'excel') !== false || strpos($tipo_lower, 'spreadsheet') !== false) {
    return ['icono' => 'fa-file-excel', 'clase' => 'excel'];
  } elseif (strpos($tipo_lower, 'word') !== false || strpos($tipo_lower, 'document') !== false) {
    return ['icono' => 'fa-file-word', 'clase' => 'word'];
  } elseif (strpos($tipo_lower, 'image') !== false || strpos($tipo_lower, 'png') !== false || strpos($tipo_lower, 'jpg') !== false || strpos($tipo_lower, 'jpeg') !== false) {
    return ['icono' => 'fa-file-image', 'clase' => 'imagen'];
  } else {
    return ['icono' => 'fa-file', 'clase' => 'otro'];
  }
}
?>
