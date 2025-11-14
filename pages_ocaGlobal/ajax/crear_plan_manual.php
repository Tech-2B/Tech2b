<?php

/**
 * Archivo AJAX para crear un plan de acción manual
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

// Incluir autoload de Google API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

include '../includes/funcionesGoogleDrive.php';

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

/**
 * Generar ID único para un cliente específico
 */
function generarIdUnicoCliente($conn, $idCliente, $tipo) {
  $funciones = new FuncionesGenerales();
  
  // Obtener el máximo ID existente para este cliente y tipo
  $query = "SELECT MAX(CAST(id_$tipo AS UNSIGNED)) as max_id 
            FROM wwappb_field_test.OCAGLOBAL_planes_accion_clientes 
            WHERE id_cliente = ? AND id_$tipo REGEXP '^[0-9]+$'";
  
  $resultado = $funciones->fnBuscarDatosRegistro($conn, $query, [$idCliente], 'i');
  
  $maxId = 0;
  if ($resultado['success'] && !empty($resultado['datos'])) {
    $maxId = (int)$resultado['datos'][0]['max_id'];
  }
  
  // Generar nuevo ID (máximo + 1, pero mínimo 1)
  $nuevoId = max(1, $maxId + 1);
  
  // Verificar que el ID no existe (por si acaso)
  $queryVerificar = "SELECT COUNT(*) as existe 
                     FROM wwappb_field_test.OCAGLOBAL_planes_accion_clientes 
                     WHERE id_cliente = ? AND id_$tipo = ?";
  
  $resultadoVerificar = $funciones->fnBuscarDatosRegistro($conn, $queryVerificar, [$idCliente, $nuevoId], 'ii');
  
  if ($resultadoVerificar['success'] && $resultadoVerificar['datos'][0]['existe'] > 0) {
    // Si existe, buscar el siguiente disponible
    $nuevoId = $maxId + 2;
  }
  
  return $nuevoId;
}

try {
  $funciones = new FuncionesGenerales();
  
  // Log para depuración
  error_log("Datos POST recibidos: " . print_r($_POST, true));
  error_log("Session data: " . print_r($_SESSION, true));
  
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
  
  // Verificar que el cliente existe antes de continuar
  $queryVerificarCliente = "SELECT id_cliente, nombre_cliente FROM $tabla_clientes WHERE id_cliente = ? AND activo = 1";
  
  $resultadoVerificarCliente = $funciones->fnBuscarDatosRegistro($conn, $queryVerificarCliente, [$id_cliente], 'i');
  error_log("Verificación de cliente - ID: $id_cliente, Resultado: " . json_encode($resultadoVerificarCliente));
  
  if (!$resultadoVerificarCliente['success'] || empty($resultadoVerificarCliente['datos'])) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El cliente con ID ' . $id_cliente . ' no existe o no está activo'
    );
    exit;
  }
  
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
  
  // Generar IDs únicos para el plan manual (números enteros mayores a 0)
  $id_area_oportunidad_manual = generarIdUnicoCliente($conn, $id_cliente, 'area_oportunidad');
  $id_plan_accion_manual = generarIdUnicoCliente($conn, $id_cliente, 'plan_accion');
  $id_topico_manual = generarIdUnicoCliente($conn, $id_cliente, 'topico');
  $id_entregable_manual = generarIdUnicoCliente($conn, $id_cliente, 'entregable');
  $id_periodicidad_manual = generarIdUnicoCliente($conn, $id_cliente, 'periodicidad');
  
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
  // $types = 'iisisssisssi';
  $resultado = $funciones->fnGuardarRegistro($conn, $query, $params, $types);
  
  if ($resultado['success']) {
    $id_registro_nuevo = $resultado['id_insertado'];
    
    // Crear estructura de carpetas en Drive
    try {
      error_log("Intentando crear objeto FuncionesGoogleDrive...");
      
      // Verificar que la clase existe
      if (!class_exists('FuncionesGoogleDrive')) {
        throw new Exception("La clase FuncionesGoogleDrive no está disponible");
      }
      
      $funcionesDrive = new FuncionesGoogleDrive($conn);
      error_log("Objeto FuncionesGoogleDrive creado exitosamente");
      
      error_log("Llamando a crearEstructuraCarpetasPlanManual con parámetros: ID Cliente: $id_cliente, Área: $area_oportunidad, Plan: $plan_accion, Registro: $id_registro_nuevo");
      error_log("IDs generados - Área: $id_area_oportunidad_manual, Plan: $id_plan_accion_manual");
      
      $resultadoDrive = $funcionesDrive->crearEstructuraCarpetasPlanManual(
        $id_cliente, 
        $area_oportunidad, 
        $plan_accion, 
        $id_registro_nuevo,
        $id_area_oportunidad_manual,
        $id_plan_accion_manual
      );
      
      error_log("Resultado de crearEstructuraCarpetasPlanManual: " . json_encode($resultadoDrive));
      
      if ($resultadoDrive['success']) {
        $funciones->fnRegresarRespuestaJsonEncode(
          $code_201,
          true,
          $icon_success,
          $titulo_exito,
          'Plan de acción creado correctamente y estructura de carpetas en Drive generada',
          [
            'id_registro' => $id_registro_nuevo,
            'carpetas_creadas' => $resultadoDrive['carpetas_creadas']
          ]
        );
      } else {
        $funciones->fnRegresarRespuestaJsonEncode(
          $code_201,
          true,
          $icon_success,
          $titulo_exito,
          'Plan de acción creado correctamente, pero hubo un error creando las carpetas en Drive: ' . $resultadoDrive['error'],
          ['id_registro' => $id_registro_nuevo]
        );
      }
    } catch (Exception $e) {
      error_log("Error creando carpetas en Drive: " . $e->getMessage());
      $funciones->fnRegresarRespuestaJsonEncode(
        $code_201,
        true,
        $icon_success,
        $titulo_exito,
        'Plan de acción creado correctamente, pero hubo un error creando las carpetas en Drive',
        ['id_registro' => $id_registro_nuevo]
      );
    }
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
  error_log("Excepción en crear_plan_manual.php: " . $e->getMessage());
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
