<?php

/**
 * Funciones para manejo de Google Drive API
 * 
 * @author  Eduardo Lara
 * @version 1.0.0
 * @date    2025-01-27
 */

class FuncionesGoogleDrive
{
  public $service;
  private $conn;
  private $funciones;

  public function __construct($conn)
  {
    $this->conn = $conn;
    $this->funciones = new FuncionesGenerales();
    $this->inicializarServicio();
  }

  /**
   * Inicializar el servicio de Google Drive
   */
  private function inicializarServicio()
  {
    try {
      // Ruta al archivo de credenciales
      $credentialsPath = __DIR__ . '/../drive/credenciales/ocaconstruccion-b8ddbf846879.json';

      if (!file_exists($credentialsPath)) {
        throw new Exception("Archivo de credenciales no encontrado: $credentialsPath");
      }

      // Crear cliente de Google
      $client = new Google\Client();
      $client->setAuthConfig($credentialsPath);
      $client->addScope(Google\Service\Drive::DRIVE);
      $client->setAccessType('offline');

      // Crear servicio de Drive
      $this->service = new Google\Service\Drive($client);

      // Configurar para trabajar con Shared Drives (unidades compartidas)
      $this->service->getClient()->setUseBatch(false);
    } catch (Exception $e) {
      error_log("Error inicializando Google Drive: " . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Crear carpeta en Google Drive
   */
  public function crearCarpeta($nombreCarpeta, $idCarpetaPadre = null)
  {
    try {
      $fileMetadata = new Google\Service\Drive\DriveFile([
        'name' => $nombreCarpeta,
        'mimeType' => 'application/vnd.google-apps.folder'
      ]);

      if ($idCarpetaPadre) {
        $fileMetadata->setParents([$idCarpetaPadre]);
      }

      // Configurar parámetros para Shared Drives
      $params = [
        'fields' => 'id,name,parents',
        'supportsAllDrives' => true
      ];

      $carpeta = $this->service->files->create($fileMetadata, $params);

      return [
        'success' => true,
        'id_carpeta' => $carpeta->getId(),
        'nombre' => $carpeta->getName(),
        'id_padre' => $idCarpetaPadre
      ];
    } catch (Exception $e) {
      error_log("Error creando carpeta en Drive: " . $e->getMessage());
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Verificar y obtener la carpeta principal
   */
  private function verificarCarpetaPrincipal()
  {
    try {
      global $id_carpeta_principal_drive;

      // Intentar obtener información de la carpeta principal con soporte para Shared Drives
      $carpeta = $this->service->files->get($id_carpeta_principal_drive, [
        'supportsAllDrives' => true
      ]);

      if ($carpeta) {
        return [
          'success' => true,
          'id' => $carpeta->getId(),
          'nombre' => $carpeta->getName()
        ];
      }

      return ['success' => false, 'error' => 'Carpeta principal no encontrada'];
    } catch (Exception $e) {
      error_log("Error verificando carpeta principal: " . $e->getMessage());

      // Si no existe, crear una carpeta principal
      return $this->crearCarpetaPrincipal();
    }
  }

  /**
   * Crear carpeta principal si no existe
   */
  private function crearCarpetaPrincipal()
  {
    try {
      $nombreCarpetaPrincipal = "OCA Global - Planes de Acción";

      $resultado = $this->crearCarpeta($nombreCarpetaPrincipal, null);

      if ($resultado['success']) {
        // Actualizar la variable global con el nuevo ID
        global $id_carpeta_principal_drive;
        $id_carpeta_principal_drive = $resultado['id_carpeta'];

        error_log("Carpeta principal creada con ID: " . $resultado['id_carpeta']);

        return [
          'success' => true,
          'id' => $resultado['id_carpeta'],
          'nombre' => $resultado['nombre']
        ];
      }

      return ['success' => false, 'error' => $resultado['error']];
    } catch (Exception $e) {
      error_log("Error creando carpeta principal: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Crear estructura de carpetas para un cliente
   */
  public function crearEstructuraCarpetasCliente($idCliente, $nombreCliente, $planesAccion)
  {
    try {
      global $tabla_carpetas_drive;

      // 1. Verificar/crear carpeta principal
      $carpetaPrincipal = $this->verificarCarpetaPrincipal();

      if (!$carpetaPrincipal['success']) {
        throw new Exception("Error con carpeta principal: " . $carpetaPrincipal['error']);
      }

      $idCarpetaPrincipal = $carpetaPrincipal['id'];
      error_log("Usando carpeta principal ID: " . $idCarpetaPrincipal);

      // 2. Crear carpeta del cliente
      $resultadoCarpetaCliente = $this->crearCarpeta($nombreCliente, $idCarpetaPrincipal);

      if (!$resultadoCarpetaCliente['success']) {
        throw new Exception("Error creando carpeta del cliente: " . json_encode($resultadoCarpetaCliente));
      }

      $idCarpetaCliente = $resultadoCarpetaCliente['id_carpeta'];

      // Guardar carpeta del cliente en BD
      $this->guardarCarpetaEnBD($idCliente, $idCarpetaCliente, $nombreCliente, $idCarpetaPrincipal, 'cliente', $idCarpetaPrincipal . '/' . $nombreCliente);

      $carpetasCreadas = [];

      // 2. Crear carpetas por área de oportunidad y plan de acción
      foreach ($planesAccion as $plan) {
        // Crear carpeta de área de oportunidad
        $nombreAreaCarpeta = $plan['numero_area'] . " " . $plan['descripcion_area_oportunidad'];
        $resultadoArea = $this->crearCarpeta($nombreAreaCarpeta, $idCarpetaCliente);

        if ($resultadoArea['success']) {
          $idCarpetaArea = $resultadoArea['id_carpeta'];

          // Guardar carpeta de área en BD
          $this->guardarCarpetaEnBD($idCliente, $idCarpetaArea, $nombreAreaCarpeta, $idCarpetaCliente, 'area_oportunidad', $idCarpetaPrincipal . '/' . $nombreCliente . '/' . $nombreAreaCarpeta);

          // Crear carpeta de plan de acción
          $nombrePlanCarpeta = $plan['numero_plan_accion'] . " " . $plan['descripcion_plan_accion'];
          $resultadoPlan = $this->crearCarpeta($nombrePlanCarpeta, $idCarpetaArea);

          if ($resultadoPlan['success']) {
            $idCarpetaPlan = $resultadoPlan['id_carpeta'];

            // Guardar carpeta de plan en BD
            $this->guardarCarpetaEnBD($idCliente, $idCarpetaPlan, $nombrePlanCarpeta, $idCarpetaArea, 'plan_accion', $idCarpetaPrincipal . '/' . $nombreCliente . '/' . $nombreAreaCarpeta . '/' . $nombrePlanCarpeta, $plan['id_plan_accion']);

            $carpetasCreadas[] = [
              'id_plan_accion' => $plan['id_plan_accion'],
              'id_carpeta' => $idCarpetaPlan,
              'nombre' => $nombrePlanCarpeta
            ];
          }
        }
      }

      return [
        'success' => true,
        'id_carpeta_cliente' => $idCarpetaCliente,
        'carpetas_planes' => $carpetasCreadas
      ];
    } catch (Exception $e) {
      error_log("Error creando estructura de carpetas: " . $e->getMessage());
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Guardar información de carpeta en la base de datos
   */
  private function guardarCarpetaEnBD($idCliente, $idCarpetaDrive, $nombreCarpeta, $idCarpetaPadre, $tipoCarpeta, $rutaCompleta, $idPlanAccion = null)
  {
    try {
      global $tabla_carpetas_drive;

      $query = "INSERT INTO $tabla_carpetas_drive 
                     (id_carpeta_drive, nombre_carpeta, id_carpeta_padre, id_cliente, id_plan_accion, tipo_carpeta, ruta_completa, id_usuario_crea) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

      $idUsuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

      $params = [
        $idCarpetaDrive,
        $nombreCarpeta,
        $idCarpetaPadre,
        $idCliente,
        $idPlanAccion,
        $tipoCarpeta,
        $rutaCompleta,
        $idUsuario
      ];

      $types = 'ssiisssi';

      $resultado = $this->funciones->fnGuardarRegistro($this->conn, $query, $params, $types);

      if (!$resultado['success']) {
        error_log("Error guardando carpeta en BD: " . $resultado['response']);
      }

      return $resultado;
    } catch (Exception $e) {
      error_log("Error en guardarCarpetaEnBD: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Subir archivo a Google Drive
   */
  public function subirArchivo($archivoTemporal, $nombreArchivo, $idCarpetaDestino, $comentario = '')
  {
    try {
      // Obtener información del archivo
      $tamanoArchivo = filesize($archivoTemporal);
      $tipoArchivo = mime_content_type($archivoTemporal);
      $extension = pathinfo($nombreArchivo, PATHINFO_EXTENSION);

      // Crear metadata del archivo
      $fileMetadata = new Google\Service\Drive\DriveFile([
        'name' => $nombreArchivo,
        'parents' => [$idCarpetaDestino]
      ]);

      // Si hay comentario, agregarlo como descripción
      if (!empty($comentario)) {
        $fileMetadata->setDescription($comentario);
      }

      // Subir archivo con soporte para Shared Drives
      $resultado = $this->service->files->create($fileMetadata, [
        'data' => file_get_contents($archivoTemporal),
        'mimeType' => $tipoArchivo,
        'uploadType' => 'multipart',
        'fields' => 'id,name,webViewLink,webContentLink',
        'supportsAllDrives' => true
      ]);

      return [
        'success' => true,
        'id_archivo' => $resultado->getId(),
        'nombre' => $resultado->getName(),
        'url_vista' => $resultado->getWebViewLink(),
        'url_descarga' => $resultado->getWebContentLink(),
        'tamano' => $tamanoArchivo,
        'tipo' => $tipoArchivo,
        'extension' => $extension
      ];
    } catch (Exception $e) {
      error_log("Error subiendo archivo a Drive: " . $e->getMessage());
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Guardar información de archivo en la base de datos
   */
  public function guardarArchivoEnBD($idCliente, $idPlanAccion, $idCarpetaDrive, $datosArchivo, $comentario = '', $idUsuarioSubida = null)
  {
    try {
      global $tabla_archivos_drive;

      $query =
        "INSERT INTO $tabla_archivos_drive 
          (id_archivo_drive, nombre_archivo, nombre_archivo_original, tipo_archivo, tamano_archivo, 
          id_carpeta_drive, id_cliente, id_plan_accion, comentario, url_drive,
          url_descarga, id_usuario_subida) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

      $params = [
        $datosArchivo['id_archivo'],
        $datosArchivo['nombre'],
        $datosArchivo['nombre'], // nombre original
        $datosArchivo['tipo'],
        $datosArchivo['tamano'],
        $idCarpetaDrive,
        $idCliente,
        $idPlanAccion,
        $comentario,
        $datosArchivo['url_vista'],
        $datosArchivo['url_descarga'],
        $idUsuarioSubida
      ];

      $types = 'ssssisiisssi';

      $resultado = $this->funciones->fnGuardarRegistro($this->conn, $query, $params, $types);

      return $resultado;
    } catch (Exception $e) {
      error_log("Error guardando archivo en BD: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtener carpetas de un plan de acción específico
   */
  public function obtenerCarpetasPlanAccion($idCliente, $idPlanAccion)
  {
    try {
      global $tabla_carpetas_drive;

      $query = "SELECT id_carpeta_drive, nombre_carpeta, tipo_carpeta, ruta_completa 
                     FROM $tabla_carpetas_drive 
                     WHERE id_cliente = ? AND id_plan_accion = ? AND estado_activo = 1
                     ORDER BY fecha_creacion ASC";

      $params = [$idCliente, $idPlanAccion];
      $types = 'ii';

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, $params, $types);

      return $resultado;
    } catch (Exception $e) {
      error_log("Error obteniendo carpetas del plan: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Crear subcarpeta en un plan de acción
   */
  public function crearSubcarpeta($idCliente, $idPlanAccion, $nombreSubcarpeta, $idCarpetaPadre)
  {
    try {
      // Crear carpeta en Drive
      $resultado = $this->crearCarpeta($nombreSubcarpeta, $idCarpetaPadre);

      if (!$resultado['success']) {
        return $resultado;
      }

      // Obtener ruta completa
      $rutaCompleta = $this->obtenerRutaCompleta($idCarpetaPadre) . '/' . $nombreSubcarpeta;

      // Guardar en BD
      $this->guardarCarpetaEnBD($idCliente, $resultado['id_carpeta'], $nombreSubcarpeta, $idCarpetaPadre, 'subcarpeta', $rutaCompleta, $idPlanAccion);

      return $resultado;
    } catch (Exception $e) {
      error_log("Error creando subcarpeta: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Crear subcarpeta específicamente para un plan de acción
   * Busca automáticamente la carpeta raíz del plan de acción
   */
  public function crearSubcarpetaPlanAccion($idCliente, $idPlanAccion, $nombreSubcarpeta)
  {
    try {
      // Buscar la carpeta raíz del plan de acción
      $carpetaRaiz = $this->obtenerCarpetaRaizPlanAccion($idCliente, $idPlanAccion);
      
      if (!$carpetaRaiz['success']) {
        return $carpetaRaiz;
      }

      $idCarpetaPadre = $carpetaRaiz['datos']['id_carpeta_drive'];
      
      // Crear la subcarpeta
      $resultado = $this->crearSubcarpeta($idCliente, $idPlanAccion, $nombreSubcarpeta, $idCarpetaPadre);
      
      if ($resultado['success']) {
        return [
          'success' => true,
          'datos' => [
            'id_carpeta_drive' => $resultado['id_carpeta'],
            'nombre_carpeta' => $resultado['nombre'],
            'id_carpeta_padre' => $idCarpetaPadre,
            'ruta_completa' => $this->obtenerRutaCompleta($idCarpetaPadre) . '/' . $nombreSubcarpeta
          ]
        ];
      }
      
      return $resultado;
    } catch (Exception $e) {
      error_log("Error creando subcarpeta del plan de acción: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtener la carpeta raíz de un plan de acción específico
   */
  private function obtenerCarpetaRaizPlanAccion($idCliente, $idPlanAccion)
  {
    try {
      global $tabla_carpetas_drive;

      $query = "SELECT id_carpeta_drive, nombre_carpeta, ruta_completa 
                FROM $tabla_carpetas_drive 
                WHERE id_cliente = ? AND id_plan_accion = ?
                AND tipo_carpeta = 'plan_accion' AND estado_activo = 1
                LIMIT 1";

      $params = [$idCliente, $idPlanAccion];
      $types = 'ii';

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, $params, $types);

      if ($resultado['success'] && !empty($resultado['datos'])) {
        return [
          'success' => true,
          'datos' => $resultado['datos'][0]
        ];
      }

      return [
        'success' => false,
        'error' => 'No se encontró la carpeta raíz del plan de acción'
      ];
    } catch (Exception $e) {
      error_log("Error obteniendo carpeta raíz del plan: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtener ruta completa de una carpeta
   */
  private function obtenerRutaCompleta($idCarpetaDrive)
  {
    try {
      global $tabla_carpetas_drive;

      $query = "SELECT ruta_completa FROM $tabla_carpetas_drive WHERE id_carpeta_drive = ? AND estado_activo = 1";
      $params = [$idCarpetaDrive];
      $types = 's';

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, $params, $types);

      if ($resultado['success'] && !empty($resultado['datos'])) {
        return $resultado['datos'][0]['ruta_completa'];
      }

      return '';
    } catch (Exception $e) {
      error_log("Error obteniendo ruta completa: " . $e->getMessage());
      return '';
    }
  }

  /**
   * Eliminar archivo de Google Drive y base de datos
   */
  public function eliminarArchivo($idArchivo, $idUsuario)
  {
    try {
      global $tabla_archivos_drive;

      // Obtener información del archivo
      $query = "SELECT id_archivo_drive, nombre_archivo_original FROM $tabla_archivos_drive WHERE id_archivo = ? AND estado_activo = 1";
      $params = [$idArchivo];
      $types = 'i';

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, $params, $types);

      if (!$resultado['success'] || empty($resultado['datos'])) {
        return ['success' => false, 'error' => 'Archivo no encontrado'];
      }

      $archivoInfo = $resultado['datos'][0];
      $idArchivoDrive = $archivoInfo['id_archivo_drive'];
      $nombreArchivo = $archivoInfo['nombre_archivo_original'];

      // Eliminar archivo de Google Drive - DESHABILITADO TEMPORALMENTE
      try {
        // $this->service->files->delete($idArchivoDrive, [
        //   'supportsAllDrives' => true
        // ]); // Comentado para no eliminar archivos de Google Drive
        error_log("Archivo de Google Drive NO eliminado: " . $idArchivoDrive);
      } catch (Exception $e) {
        error_log("Error eliminando archivo de Drive: " . $e->getMessage());
        // Continuar con la eliminación lógica aunque falle en Drive
      }

      // Eliminar lógicamente de la base de datos
      $queryUpdate = "UPDATE $tabla_archivos_drive SET estado_activo = 0, id_usuario_actualizacion = ?, fecha_actualizacion = NOW() WHERE id_archivo = ?";
      $resultadoUpdate = $this->funciones->fnGuardarRegistro($this->conn, $queryUpdate, [$idUsuario, $idArchivo], 'ii');

      if ($resultadoUpdate['success']) {
        return [
          'success' => true,
          'mensaje' => "Archivo '{$nombreArchivo}' eliminado correctamente"
        ];
      } else {
        return [
          'success' => false,
          'error' => 'Error actualizando base de datos: ' . $resultadoUpdate['response']
        ];
      }

    } catch (Exception $e) {
      error_log("Error eliminando archivo: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Crear estructura de carpetas para un plan de acción manual
   */
  public function crearEstructuraCarpetasPlanManual($idCliente, $areaOportunidad, $planAccion, $idRegistro, $idAreaOportunidad = null, $idPlanAccion = null)
  {
    try {
      error_log("Iniciando crearEstructuraCarpetasPlanManual - ID Cliente: $idCliente, Área: $areaOportunidad, Plan: $planAccion, Registro: $idRegistro");
      error_log("IDs generados - ID Área: $idAreaOportunidad, ID Plan: $idPlanAccion");
      
      global $tabla_carpetas_drive, $id_carpeta_principal_drive;

      // Verificar que la carpeta principal existe
      $verificacion = $this->verificarCarpetaPrincipal();
      if (!$verificacion['success']) {
        return $verificacion;
      }

      $idCarpetaPrincipal = $verificacion['id'];

      // Obtener información del cliente
      $queryCliente = "SELECT nombre_cliente FROM wwappb_field_test.OCAGLOBAL_clientes WHERE id_cliente = ? AND activo = 1";
      $resultadoCliente = $this->funciones->fnBuscarDatosRegistro($this->conn, $queryCliente, [$idCliente], 'i');
      
      // Log para depuración
      error_log("Consulta cliente - ID: $idCliente, Resultado: " . json_encode($resultadoCliente));
      
      if (!$resultadoCliente['success']) {
        error_log("Error en consulta de cliente: " . $resultadoCliente['response']);
        return ['success' => false, 'error' => 'Error en consulta de cliente: ' . $resultadoCliente['response']];
      }
      
      if (empty($resultadoCliente['datos'])) {
        error_log("Cliente no encontrado - ID: $idCliente, Datos vacíos");
        return ['success' => false, 'error' => 'Cliente no encontrado - ID: ' . $idCliente];
      }

      $nombreCliente = $resultadoCliente['datos'][0]['nombre_cliente'];
      $carpetasCreadas = [];

      // 1. Crear carpeta del cliente (si no existe)
      $queryCarpetaCliente = "SELECT id_carpeta_drive FROM $tabla_carpetas_drive WHERE id_cliente = ? AND tipo_carpeta = 'cliente' AND estado_activo = 1";
      $resultadoCarpetaCliente = $this->funciones->fnBuscarDatosRegistro($this->conn, $queryCarpetaCliente, [$idCliente], 'i');
      // echo $this->funciones->fnConvertirConsulta($this->conn, $queryCarpetaCliente, [$idCliente]);

      if ($resultadoCarpetaCliente['success'] && !empty($resultadoCarpetaCliente['datos'])) {
        $idCarpetaCliente = $resultadoCarpetaCliente['datos'][0]['id_carpeta_drive'];
      } else {
        $resultadoCrearCliente = $this->crearCarpeta($nombreCliente, $idCarpetaPrincipal);
        if (!$resultadoCrearCliente['success']) {
          return $resultadoCrearCliente;
        }
        $idCarpetaCliente = $resultadoCrearCliente['id_carpeta'];

        $tipoCarpeta = 'cliente';
        // Guardar carpeta del cliente en BD
        $queryInsertCliente =
          "INSERT INTO $tabla_carpetas_drive 
            (id_carpeta_drive, nombre_carpeta, id_carpeta_padre, id_cliente,
            tipo_carpeta, ruta_completa, id_usuario_crea) 
          VALUES (?, ?, ?, ?, ?, ?, ?);
        ";
        $paramsCliente = [
          $idCarpetaCliente,
          $nombreCliente,
          $idCarpetaPrincipal,
          $idCliente,
          $tipoCarpeta,
          $nombreCliente,
          isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1
        ];
        $typesCliente = 'sssissi';
        // echo $this->funciones->fnConvertirConsulta($this->conn, $queryInsertCliente, $paramsCliente);
        $this->funciones->fnGuardarRegistro($this->conn, $queryInsertCliente, $paramsCliente, $typesCliente);
        $carpetasCreadas[] = ['tipo' => $tipoCarpeta, 'nombre' => $nombreCliente, 'id' => $idCarpetaCliente];
      }

      // 2. Crear carpeta del área de oportunidad
      $resultadoCrearArea = $this->crearCarpeta($areaOportunidad, $idCarpetaCliente);
      if (!$resultadoCrearArea['success']) {
        return $resultadoCrearArea;
      }
      $idCarpetaArea = $resultadoCrearArea['id_carpeta'];

      $tipoCarpetaArea = 'area_oportunidad';
      // Guardar carpeta del área en BD
      $queryInsertArea =
        "INSERT INTO $tabla_carpetas_drive 
          (id_carpeta_drive, nombre_carpeta, id_carpeta_padre, id_cliente,
          id_plan_accion, tipo_carpeta, ruta_completa, id_usuario_crea) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?);
      ";
      $paramsArea = [
        $idCarpetaArea,
        $areaOportunidad,
        $idCarpetaCliente,
        $idCliente,
        null,
        $tipoCarpetaArea,
        $nombreCliente . '/' . $areaOportunidad,
        isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1
      ];
      $typesArea = 'sssiissi';
      // echo $this->funciones->fnConvertirConsulta($this->conn, $queryInsertArea, $paramsArea);
      $this->funciones->fnGuardarRegistro($this->conn, $queryInsertArea, $paramsArea, $typesArea);
      $carpetasCreadas[] = ['tipo' => $tipoCarpetaArea, 'nombre' => $areaOportunidad, 'id' => $idCarpetaArea, 'id_area_oportunidad' => $idAreaOportunidad];

      // 3. Crear carpeta del plan de acción
      $resultadoCrearPlan = $this->crearCarpeta($planAccion, $idCarpetaArea);
      if (!$resultadoCrearPlan['success']) {
        return $resultadoCrearPlan;
      }
      $idCarpetaPlan = $resultadoCrearPlan['id_carpeta'];

      $tipoCarpetaPlan = 'plan_accion';
      // Guardar carpeta del plan en BD
      $queryInsertPlan =
        "INSERT INTO $tabla_carpetas_drive 
          (id_carpeta_drive, nombre_carpeta, id_carpeta_padre, id_cliente,
          id_plan_accion, tipo_carpeta, ruta_completa, id_usuario_crea) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?);
      ";
      $paramsPlan = [
        $idCarpetaPlan,
        $planAccion,
        $idCarpetaArea,
        $idCliente,
        $idPlanAccion,
        $tipoCarpetaPlan,
        $nombreCliente . '/' . $areaOportunidad . '/' . $planAccion,
        isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1
      ];
      $typesPlan = 'sssiissi';
      // echo $this->funciones->fnConvertirConsulta($this->conn, $queryInsertPlan, $paramsPlan);
      $this->funciones->fnGuardarRegistro($this->conn, $queryInsertPlan, $paramsPlan, $typesPlan);
      $carpetasCreadas[] = ['tipo' => $tipoCarpetaPlan, 'nombre' => $planAccion, 'id' => $idCarpetaPlan, 'id_area_oportunidad' => $idAreaOportunidad, 'id_plan_accion' => $idPlanAccion];

      return [
        'success' => true,
        'mensaje' => 'Estructura de carpetas creada correctamente',
        'carpetas_creadas' => $carpetasCreadas,
        'id_carpeta_plan' => $idCarpetaPlan
      ];

    } catch (Exception $e) {
      error_log("Error creando estructura de carpetas para plan manual: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }
}
