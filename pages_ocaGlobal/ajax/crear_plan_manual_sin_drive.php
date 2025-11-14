<?php

/**
 * Archivo AJAX para crear un plan de acción manual SIN Google Drive
 */

session_start();

// Verificar permisos de creación de planes
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para crear planes de acción',
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
  echo "Error de conexión: " . $conn->connect_error;
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Método no permitido'
  );
  exit;
}

try {
  $funciones = new FuncionesGenerales();
  
  // Log para depuración
  error_log("Datos POST recibidos: " . print_r($_POST, true));
  
  // Obtener datos del formulario
  $id_cliente = $funciones->fnTrimDatosPost('id_cliente');
  $area_oportunidad = $funciones->fnTrimDatosPost('area_oportunidad');
  $plan_accion = $funciones->fnTrimDatosPost('plan_accion');
  $topico_seleccionado = $funciones->fnTrimDatosPost('topicos');
  $topico_otro = $funciones->fnTrimDatosPost('topico_otro');
  $entregables = $funciones->fnTrimDatosPost('entregables');
  $periodicidad_seleccionada = $funciones->fnTrimDatosPost('periodicidad');
  $periodicidad_otro = $funciones->fnTrimDatosPost('periodicidad_otro');
  
  // Log de datos procesados
  error_log("Datos procesados - ID Cliente: $id_cliente, Área: $area_oportunidad, Plan: $plan_accion, Tópico: $topico_seleccionado, Entregables: $entregables, Periodicidad: $periodicidad_seleccionada");
  
  // Validar datos requeridos
  if (empty($id_cliente) || empty($area_oportunidad) || empty($plan_accion) || 
      empty($topico_seleccionado) || empty($entregables) || empty($periodicidad_seleccionada)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Todos los campos son requeridos'
    );
    exit;
  }
  
  // Determinar tópico final
  $topico_final = $topico_seleccionado;
  $descripcion_topico = '';
  if ($topico_seleccionado === 'otro' && !empty($topico_otro)) {
    $topico_final = 'otro';
    $descripcion_topico = $topico_otro;
  } else {
    // Obtener descripción del tópico seleccionado
    $queryTopico = "SELECT descripcion_topico FROM $tabla_lista_topicos WHERE numero_topico = ? AND activo = 1";
    $resultadoTopico = $funciones->fnBuscarDatosRegistro($conn, $queryTopico, [$topico_seleccionado], 'i');
    if ($resultadoTopico['success'] && !empty($resultadoTopico['datos'])) {
      $descripcion_topico = $resultadoTopico['datos'][0]['descripcion_topico'];
    }
  }
  
  // Determinar periodicidad final
  $periodicidad_final = $periodicidad_seleccionada;
  $descripcion_periodicidad = '';
  if ($periodicidad_seleccionada === 'otro' && !empty($periodicidad_otro)) {
    $periodicidad_final = 'otro';
    $descripcion_periodicidad = $periodicidad_otro;
  } else {
    // Obtener descripción de la periodicidad seleccionada
    $queryPeriodicidad = "SELECT descripcion_periodicidad FROM $tabla_lista_periodicidades WHERE numero_periodicidad = ? AND activo = 1";
    $resultadoPeriodicidad = $funciones->fnBuscarDatosRegistro($conn, $queryPeriodicidad, [$periodicidad_seleccionada], 'i');
    if ($resultadoPeriodicidad['success'] && !empty($resultadoPeriodicidad['datos'])) {
      $descripcion_periodicidad = $resultadoPeriodicidad['datos'][0]['descripcion_periodicidad'];
    }
  }
  
  // Generar IDs únicos para el plan manual
  $id_area_oportunidad_manual = 'MANUAL_' . time() . '_' . rand(1000, 9999);
  $id_plan_accion_manual = 'MANUAL_' . time() . '_' . rand(1000, 9999);
  $id_topico_manual = 'MANUAL_' . time() . '_' . rand(1000, 9999);
  $id_entregable_manual = 'MANUAL_' . time() . '_' . rand(1000, 9999);
  $id_periodicidad_manual = 'MANUAL_' . time() . '_' . rand(1000, 9999);
  
  // Insertar el plan de acción en la tabla
  $query = "INSERT INTO $tabla_planes_accion_clientes 
            (id_cliente, id_area_oportunidad, descripcion_area_oportunidad, id_plan_accion, 
             descripcion_plan_accion, id_topico, descripcion_topico, id_entregable, 
             descripcion_entregable, id_periodicidad, descripcion_periodicidad, id_usuario_crea) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
  
  $params = [
    $id_cliente,
    $id_area_oportunidad_manual,
    $area_oportunidad,
    $id_plan_accion_manual,
    $plan_accion,
    $id_topico_manual,
    $descripcion_topico,
    $id_entregable_manual,
    $entregables,
    $id_periodicidad_manual,
    $descripcion_periodicidad,
    isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1
  ];
  
  $types = 'iisisssisssi';
  $resultado = $funciones->fnGuardarRegistro($conn, $query, $params, $types);
  
  if ($resultado['success']) {
    $id_registro_nuevo = $resultado['id_insertado'];
    
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_201,
      true,
      $icon_success,
      $titulo_exito,
      'Plan de acción creado correctamente (sin Google Drive)',
      [
        'id_registro' => $id_registro_nuevo,
        'mensaje' => 'Plan creado exitosamente. Las carpetas de Google Drive se crearán en una actualización futura.'
      ]
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error al crear plan',
      'No se pudo crear el plan de acción: ' . $resultado['response']
    );
  }
  
} catch (Exception $e) {
  error_log("Excepción en crear_plan_manual_sin_drive.php: " . $e->getMessage());
  error_log("Stack trace: " . $e->getTraceAsString());
  
  // Asegurar que no hay salida antes del JSON
  if (ob_get_level()) {
    ob_clean();
  }
  
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
}
?>
