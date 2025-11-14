<?php

/**
 * Funciones para manejo de planes de acción
 * 
 * @author  Eduardo Lara
 * @version 1.0.0
 * @date    2025-01-27
 */

class FuncionesPlanesAccion
{
  private $conn;
  private $funciones;

  public function __construct($conn)
  {
    $this->conn = $conn;
    $this->funciones = new FuncionesGenerales();
  }

  /**
   * Obtener todos los planes de acción con sus relaciones
   */
  public function obtenerPlanesAccionCompletos()
  {
    try {
      global $tabla_lista_areasOportunidades, $tabla_lista_planesAccion,
        $tabla_lista_topicos, $tabla_lista_entregables, $tabla_lista_periodicidades;

      // Consulta para obtener áreas de oportunidad
      $queryAreas = "SELECT id_area_oportunidad, numero_area, descripcion_area 
                    FROM $tabla_lista_areasOportunidades 
                    WHERE activo = ? 
                    ORDER BY numero_area ASC";

      $resultadoAreas = $this->funciones->fnBuscarDatosRegistro($this->conn, $queryAreas, [1], 'i');

      if (!$resultadoAreas['success']) {
        return ['success' => false, 'error' => 'Error obteniendo áreas de oportunidad'];
      }

      $planesCompletos = [];

      foreach ($resultadoAreas['datos'] as $area) {
        $idArea = $area['id_area_oportunidad'];

        // Obtener planes de acción para esta área
        $queryPlanes = "SELECT id_plan_accion, numero_plan_accion, descripcion_plan_accion 
                               FROM $tabla_lista_planesAccion 
                               WHERE id_area_oportunidad = ? AND activo = ? 
                               ORDER BY numero_plan_accion ASC";

        $resultadoPlanes = $this->funciones->fnBuscarDatosRegistro($this->conn, $queryPlanes, [$idArea, 1], 'ii');

        if ($resultadoPlanes['success']) {
          foreach ($resultadoPlanes['datos'] as $plan) {
            $idPlan = $plan['id_plan_accion'];

            // Obtener tópicos para este plan
            $topicos = $this->obtenerTopicosPorPlan($idPlan);

            // Obtener entregables para este plan
            $entregables = $this->obtenerEntregablesPorPlan($idPlan);

            // Obtener periodicidades para este plan
            $periodicidades = $this->obtenerPeriodicidadesPorPlan($idPlan);

            $planesCompletos[] = [
              'id_area_oportunidad' => $idArea,
              'descripcion_area_oportunidad' => $area['descripcion_area'],
              'id_plan_accion' => $idPlan,
              'descripcion_plan_accion' => $plan['descripcion_plan_accion'],
              'topicos' => $topicos,
              'entregables' => $entregables,
              'periodicidades' => $periodicidades
            ];
          }
        }
      }

      return [
        'success' => true,
        'datos' => $planesCompletos
      ];
    } catch (Exception $e) {
      error_log("Error obteniendo planes de acción completos: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtener tópicos por plan de acción
   */
  private function obtenerTopicosPorPlan($idPlanAccion)
  {
    try {
      global $tabla_lista_topicos;

      $query = "SELECT id_topico, numero_topico, descripcion_topico 
                     FROM $tabla_lista_topicos 
                     WHERE id_plan_accion = ? AND activo = 1 
                     ORDER BY numero_topico ASC";

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, [$idPlanAccion], 'i');

      if ($resultado['success']) {
        $ids = [];
        $descripciones = [];

        foreach ($resultado['datos'] as $topico) {
          $ids[] = $topico['id_topico'];
          $descripciones[] = $topico['descripcion_topico'];
        }

        return [
          'ids' => implode(',', $ids),
          'descripciones' => implode('<br><br>', $descripciones),
          'datos' => $resultado['datos']
        ];
      }

      return ['ids' => '', 'descripciones' => '', 'datos' => []];
    } catch (Exception $e) {
      error_log("Error obteniendo tópicos: " . $e->getMessage());
      return ['ids' => '', 'descripciones' => '', 'datos' => []];
    }
  }

  /**
   * Obtener entregables por plan de acción
   */
  private function obtenerEntregablesPorPlan($idPlanAccion)
  {
    try {
      global $tabla_lista_entregables;

      $query = "SELECT id_entregable, numero_entregable, descripcion_entregable 
                     FROM $tabla_lista_entregables 
                     WHERE id_plan_accion = ? AND activo = 1 
                     ORDER BY numero_entregable ASC";

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, [$idPlanAccion], 'i');

      if ($resultado['success'] && !empty($resultado['datos'])) {
        return $resultado['datos'][0]; // Solo el primer entregable según el requerimiento
      }

      return null;
    } catch (Exception $e) {
      error_log("Error obteniendo entregables: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Obtener periodicidades por plan de acción
   */
  private function obtenerPeriodicidadesPorPlan($idPlanAccion)
  {
    try {
      global $tabla_lista_periodicidades;

      $query = "SELECT id_periodicidad, numero_periodicidad, descripcion_periodicidad 
                     FROM $tabla_lista_periodicidades 
                     WHERE id_plan_accion = ? AND activo = 1 
                     ORDER BY numero_periodicidad ASC";

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, [$idPlanAccion], 'i');

      if ($resultado['success']) {
        $ids = [];
        $descripciones = [];

        foreach ($resultado['datos'] as $periodicidad) {
          $ids[] = $periodicidad['id_periodicidad'];
          $descripciones[] = $periodicidad['descripcion_periodicidad'];
        }

        return [
          'ids' => implode(',', $ids),
          'descripciones' => implode('<br><br>', $descripciones),
          'datos' => $resultado['datos']
        ];
      }

      return ['ids' => '', 'descripciones' => '', 'datos' => []];
    } catch (Exception $e) {
      error_log("Error obteniendo periodicidades: " . $e->getMessage());
      return ['ids' => '', 'descripciones' => '', 'datos' => []];
    }
  }

  /**
   * Guardar planes de acción de un cliente
   */
  public function guardarPlanesAccionCliente($idCliente, $planesAccion)
  {
    try {
      global $tabla_planes_accion_clientes;

      $planesGuardados = 0;
      $errores = [];

      foreach ($planesAccion as $plan) {
        $query =
          "INSERT INTO $tabla_planes_accion_clientes 
            (id_cliente, id_area_oportunidad, descripcion_area_oportunidad, id_plan_accion, 
            descripcion_plan_accion, id_topico, descripcion_topico, id_entregable, 
            descripcion_entregable, id_periodicidad, descripcion_periodicidad, id_usuario_crea) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);
        ";

        $idUsuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;

        $params = [
          $idCliente,
          $plan['id_area_oportunidad'],
          $plan['descripcion_area_oportunidad'],
          $plan['id_plan_accion'],
          $plan['descripcion_plan_accion'],
          $plan['topicos']['ids'],
          $plan['topicos']['descripciones'],
          $plan['entregables']['id_entregable'],
          $plan['entregables']['descripcion_entregable'],
          $plan['periodicidades']['ids'],
          $plan['periodicidades']['descripciones'],
          $idUsuario
        ];

        $types = 'iisisssisssi';
        // echo "consulta => <br>";
        // echo $this->funciones->fnConvertirConsulta($this->conn, $query, $params);

        $resultado = $this->funciones->fnGuardarRegistro($this->conn, $query, $params, $types);

        if ($resultado['success']) {
          $planesGuardados++;
        } else {
          $errores[] = "Error guardando plan {$plan['id_plan_accion']}: " . $resultado['response'];
        }
      }

      return [
        'success' => $planesGuardados > 0,
        'planes_guardados' => $planesGuardados,
        'total_planes' => count($planesAccion),
        'errores' => $errores
      ];
    } catch (Exception $e) {
      error_log("Error guardando planes de acción del cliente: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtener planes de acción de un cliente específico
   */
  public function obtenerPlanesAccionCliente($idCliente)
  {
    try {
      global $tabla_planes_accion_clientes;

      $query = "SELECT * FROM $tabla_planes_accion_clientes 
                     WHERE id_cliente = ? AND estado_activo = 1 
                     ORDER BY fecha_creacion ASC";

      $params = [$idCliente];
      $types = 'i';

      $resultado = $this->funciones->fnBuscarDatosRegistro($this->conn, $query, $params, $types);

      return $resultado;
    } catch (Exception $e) {
      error_log("Error obteniendo planes de acción del cliente: " . $e->getMessage());
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }
}
