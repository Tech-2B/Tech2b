<?php

/**
 * Archivo AJAX para actualizar descripciones de planes de acción
 */

session_start();

// Verificar permisos de edición
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  echo json_encode([
    'success' => false,
    'message' => 'No tienes permisos para editar planes de acción',
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
}

try {
  $funciones = new FuncionesGenerales();
  
  // Obtener el cuerpo de la petición JSON
  $input = file_get_contents('php://input');
  $data = json_decode($input, true);
  
  // Log para depuración
  error_log("Datos recibidos en actualizar_descripciones_plan.php: " . print_r($data, true));
  
  // Validar datos
  $id_registro = isset($data['id_registro']) ? (int)$data['id_registro'] : 0;
  $cambios = isset($data['cambios']) ? $data['cambios'] : [];
  
  if ($id_registro <= 0) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'ID de registro no válido'
    );
    exit;
  }
  
  if (empty($cambios)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'No se proporcionaron cambios para actualizar'
    );
    exit;
  }
  
  // Validar que los campos sean válidos
  $camposPermitidos = [
    'descripcion_area_oportunidad',
    'descripcion_plan_accion', 
    'descripcion_topico',
    'descripcion_entregable',
    'descripcion_periodicidad'
  ];
  
  $cambiosValidos = [];
  foreach ($cambios as $campo => $valor) {
    if (in_array($campo, $camposPermitidos)) {
      $cambiosValidos[$campo] = trim($valor);
    }
  }
  
  if (empty($cambiosValidos)) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'No se proporcionaron campos válidos para actualizar'
    );
    exit;
  }
  
  // Construir la consulta UPDATE dinámicamente
  $setParts = [];
  $params = [];
  $types = '';
  
  foreach ($cambiosValidos as $campo => $valor) {
    $setParts[] = "`$campo` = ?";
    $params[] = $valor;
    $types .= 's'; // string
  }
  
  // Agregar parámetros para fecha de actualización y WHERE
  $params[] = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1; // id_usuario_actualiza
  $params[] = $id_registro; // id_registro para WHERE
  $types .= 'ii'; // id_usuario (int), id_registro (int)
  
  $query = "UPDATE $tabla_planes_accion_clientes 
            SET " . implode(', ', $setParts) . ", 
                fecha_actualizacion = NOW(), 
                id_usuario_actualiza = ?
            WHERE id_registro = ? AND estado_activo = 1";

  // Log para depuración
  error_log("Query SQL: " . $query);
  error_log("Parámetros: " . print_r($params, true));
  error_log("Tipos: " . $types);
  
  $resultado = $funciones->fnGuardarRegistro($conn, $query, $params, $types);
  
  // Log del resultado
  error_log("Resultado de la actualización: " . print_r($resultado, true));

  if ($resultado['success']) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_200,
      true,
      $icon_success,
      $titulo_exito,
      'Descripciones actualizadas correctamente'
    );
  } else {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error al actualizar',
      'No se pudieron actualizar las descripciones: ' . $resultado['response']
    );
  }
  
} catch (Exception $e) {
  error_log("Excepción en actualizar_descripciones_plan.php: " . $e->getMessage());
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
}
