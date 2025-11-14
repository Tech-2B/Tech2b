<?php

/**
 * Archivo AJAX para obtener el historial de archivos de un plan de acción
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

  // Consulta para obtener archivos del plan de acción
  $query =
    "SELECT 
      id_archivo,
      id_archivo_drive,
      nombre_archivo,
      nombre_archivo_original,
      tipo_archivo,
      tamano_archivo,
      comentario,
      url_drive,
      url_descarga,
      fecha_subida,
      id_usuario_subida,
      CONCAT(u.nombre, ' ', u.apellido_paterno) AS nombre_usuario
    FROM $tabla_archivos_drive AS ad
    INNER JOIN $tabla_usuarios AS u ON ad.id_usuario_subida = u.id_usuario
    WHERE id_cliente = ? AND id_plan_accion = ? AND estado_activo = 1
    ORDER BY fecha_subida DESC";

  $parametros = [$id_cliente, $id_plan_accion];
  $tipos = 'ii';
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, $parametros, $tipos);

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_encontrado,
      $resultado['datos']
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
