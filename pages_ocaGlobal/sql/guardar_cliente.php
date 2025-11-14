<?php

/**
 * Archivo para guardar un nuevo cliente
 * Operación: INSERT
 */

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';
// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';
include '../includes/funcionesPlanesAccion.php';
include '../includes/funcionesGoogleDrive.php';

// Incluir Google Drive API
require_once '../../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Método no permitido'
  );
  exit; // Importante: salir después de enviar la respuesta
}

try {
  $funciones = new FuncionesGenerales();
  
  $nombre_cliente = $funciones->fnTrimDatosPost('nombre_cliente');
  $codigo_cliente = $funciones->fnTrimDatosPost('codigo_cliente');
  $tipo_cliente = $funciones->fnTrimDatosPost('tipo_cliente');
  $nombre_contacto = $funciones->fnTrimDatosPost('nombre_contacto');
  $telefono_cliente = $funciones->fnTrimDatosPost('telefono_cliente');
  $correo_electronico = $funciones->fnTrimDatosPost('correo_electronico');
  $direccion_cliente = $funciones->fnTrimDatosPost('direccion_cliente');
  $ciudad_estado = $funciones->fnTrimDatosPost('ciudad_estado');
  
  // Validaciones
  error_log("Iniciando validaciones");
  if (empty($nombre_cliente)) {
    error_log("Error: Nombre del cliente vacío");
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'El nombre del cliente es obligatorio'
    );
    exit;
  }

  // Validar que el nombre del cliente no exista
  $query_validar = "SELECT id_cliente FROM $tabla_clientes WHERE nombre_cliente = ? AND activo = 1";
  $existe_cliente = $funciones->fnValidarExisteRegistro($conn, $query_validar, [$nombre_cliente], 's');

  if ($existe_cliente) {
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      'Error de validación',
      'Ya existe un cliente con el nombre: ' . $nombre_cliente
    );
    exit;
  }

  // Obtener ID del usuario de la sesión
  $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

  // Preparar consulta de inserción
  $query_insertar = "INSERT INTO $tabla_clientes 
        (nombre_cliente, codigo_cliente, tipo_cliente, nombre_contacto, telefono_cliente, 
         correo_electronico, direccion_cliente, ciudad_estado, id_usuario_creacion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

  $params = [
    $nombre_cliente,
    $codigo_cliente,
    $tipo_cliente,
    $nombre_contacto,
    $telefono_cliente,
    $correo_electronico,
    $direccion_cliente,
    $ciudad_estado,
    $id_usuario
  ];

  $types = 'ssssssssi';

  // Ejecutar inserción
  error_log("Ejecutando inserción del cliente");
  $resultado = $funciones->fnGuardarRegistro($conn, $query_insertar, $params, $types);
  error_log("Resultado de inserción: " . json_encode($resultado));

  if ($resultado['success']) {
    $id_cliente_nuevo = $resultado['id_insertado'];
    
    // Obtener planes de acción completos
    $funcionesPlanes = new FuncionesPlanesAccion($conn);
    $planesAccion = $funcionesPlanes->obtenerPlanesAccionCompletos();
    // echo json_encode($planesAccion);
    
    if ($planesAccion['success']) {
      // Guardar planes de acción del cliente
      $resultadoPlanes = $funcionesPlanes->guardarPlanesAccionCliente($id_cliente_nuevo, $planesAccion['datos']);
      
      if ($resultadoPlanes['success']) {
        // Crear estructura de carpetas en Google Drive
        try {
          $funcionesDrive = new FuncionesGoogleDrive($conn);
          $resultadoDrive = $funcionesDrive->crearEstructuraCarpetasCliente($id_cliente_nuevo, $nombre_cliente, $planesAccion['datos']);
          
          if ($resultadoDrive['success']) {
            $mensaje_final = "Cliente guardado correctamente. Se crearon {$resultadoPlanes['planes_guardados']} planes de acción y la estructura de carpetas en Drive.";
          } else {
            $mensaje_final = "Cliente guardado correctamente. Se crearon {$resultadoPlanes['planes_guardados']} planes de acción, pero hubo un error creando las carpetas en Drive: " . $resultadoDrive['error'];
          }
        } catch (Exception $e) {
          error_log("Error creando carpetas en Drive: " . $e->getMessage());
          $mensaje_final = "Cliente guardado correctamente. Se crearon {$resultadoPlanes['planes_guardados']} planes de acción, pero hubo un error creando las carpetas en Drive.";
        }
      } else {
        $mensaje_final = "Cliente guardado correctamente, pero hubo un error guardando los planes de acción.";
      }
    } else {
      $mensaje_final = "Cliente guardado correctamente, pero no se pudieron obtener los planes de acción.";
    }
    
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_201,
      true,
      $icon_success,
      $titulo_exito,
      $mensaje_final,
      ''
    );
    exit;
  } else {
    error_log("Error en inserción: " . $resultado['response']);
    $funciones->fnRegresarRespuestaJsonEncode(
      $code_500,
      false,
      $icon_error,
      $titulo_ocurrio_error,
      $mensaje_ocurrio_error,
      ''
    );
    exit;
  }
} catch (Exception $e) {
  error_log("Excepción capturada: " . $e->getMessage());
  error_log("Stack trace: " . $e->getTraceAsString());
  $funciones = new FuncionesGenerales();
  $funciones->fnRegresarRespuestaJsonEncode(
    $code_500,
    false,
    $icon_error,
    $titulo_ocurrio_error,
    'Error inesperado: ' . $e->getMessage()
  );
  exit;
}
