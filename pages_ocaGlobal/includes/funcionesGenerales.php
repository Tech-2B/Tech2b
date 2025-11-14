<?php

  /**
   * Archivo principal para la gestión de consultas.
   *
   * Proporciona métodos para buscar, insertar, actualizar y eliminar registros (CRUD)
   * en una base de datos MySQL utilizando consultas preparadas.
   *
   * @author  Eduardo Lara
   * @version 1.0.0
   * @date    2025-07-22
   *
   * Archivos que lo consultan
  */

  class FuncionesGenerales
  {

    # Códigos estandar para esta clase
    # code = 200 => Encontrado/Exito
    # code = 201 => Creado/Insertado
    # code = 202 => Actualizado
    # code = 203 => Eliminado
    # code = 500 => Error general

    # Obtener datos limpios de POST
    public function fnTrimDatosPost($key)
    {
      return isset($_POST[$key]) ? trim($_POST[$key]) : null;
    }

    # Limpiar cadena
    public function fnLimpiarCadena($cadena)
    {
      // Eliminar cualquier caracter que no sea letra, número, punto, coma,
      // punto y coma, dos puntos, signos de admiración, signos de interrogación,
      // parentesis, guión medio, guión bajo, arroba, simbolo de gato, porcentaje,
      // ampersand, signo de igual, signo de suma y espacio
      $regex = "/[^a-zA-Z0-9áéíóúüñÁÉÍÓÚÜÑ.,;:!?()\-_@#\/%&=+ ]/u";

      return preg_replace($regex, '', $cadena);
    }

    # Validar si el registro existe
    public function fnValidarExisteRegistro($conn, $query, $params, $types)
    {
      if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
          $result = $stmt->get_result();
          $stmt->close();
          return $result->fetch_assoc();  // Retorna el registro si existe
        }
      }
      return null;
    }

    # Devolver datos de uno o varios registros
    public function fnBuscarDatosRegistro($conn, $query, $params, $types)
    {
      try {

        if ($stmt = $conn->prepare($query)) {
          $stmt->bind_param($types, ...$params);
          if ($stmt->execute()) {

            $result = $stmt->get_result();
            if ($result->num_rows > 0) {

              $datos = [];
  
              // Recorrer los resultados
              while ($row = $result->fetch_assoc()) {
                $datos[] = $row;
              }
  
              // Cerrar el statement
              $stmt->close();
  
              return [
                'code' => 200,
                'success' => true,
                'datos' => $datos, // Retorna el (los) registro(s) si existe(n)
                'response' => '',
              ];
            }

            return [
              'code' => 500,
              'success' => false,
              'datos' => [], // Retorna el (los) registro(s) si existe(n)
              'response' => 'No cuenta con registros',
            ];
          }
          return [
            'code' => 500,
            'success' => false,
            'datos' => [], // Retorna el (los) registro(s) si existe(n)
            'response' => 'No se pudo ejecutar la consulta',
          ];
        }
        return [
          'code' => 500,
          'success' => false,
          'datos' => [], // Retorna el (los) registro(s) si existe(n)
          'response' => 'No se pudo preparar la consulta',
        ];
        // return null;
      } catch (\Throwable $th) {
        $resultado_consulta = $this->fnConvertirConsulta($conn, $query, $params);
        $error =  "Error al consultar los datos: " . $th->getMessage() . "<br> Consulta => $resultado_consulta<br>";
        
        return [
          'code' => 500,
          'success' => false,
          'datos' => '',
          'response' => $error,
        ];
      }
    }

    # Insertar un nuevo registro
    public function fnGuardarRegistro($conn, $query, $params, $types)
    {

      try {
        // Iniciar una transacción
        // $conn->begin_transaction();

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
    
        if ($stmt->execute()) {
          $id_insertado = $conn->insert_id;
          $stmt->close();

          return [
            'code' => 201,
            'success' => true,
            'id_insertado' => $id_insertado,
            'response' => '',
          ];
        } else {
          $error_message = $stmt->error;
          $resultado_consulta = $this->fnConvertirConsulta($conn, $query, $params);
          return [
            'code' => 500,
            'success' => false,
            'id_insertado' => null,
            'response' => "Error al ejecutar la consulta: $error_message => resultado_consulta: $resultado_consulta",
          ];
        }
        
        
      } catch (\Throwable $th) {
        // $conn->rollback();
        // Para depuración, mostrar la consulta con valores
        $resultado_consulta = $this->fnConvertirConsulta($conn, $query, $params);

        return [
          'code' => 500,
          'success' => false,
          'id_insertado' => '', // Indica que hubo un error
          'response' => 'Error al guardar los datos: ' . $th->getMessage() . "<br> Consulta => $resultado_consulta<br>",
        ];
      }
    }

    # Actualiza datos de un registro
    public function fnActualizarRegistro($conn, $query, $params, $types)
    {
      try {
        // Habilitar excepciones en MySQLi
        $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;

        // Iniciar una transacción
        // $conn->begin_transaction();

        if ($stmt = $conn->prepare($query)) {
          $stmt->bind_param($types, ...$params);

          if ($stmt->execute()) {
            $filas_afectadas = $stmt->affected_rows;
            $stmt->close();

            // Confirmar la transacción solo si fue exitoso
            // $conn->commit();

            return [
              'code' => 202,
              'success' => true,
              'filas_afectadas' => $filas_afectadas,
              'response' => '',
            ];
          } else {
            throw new Exception("Error al ejecutar la consulta.");
          }
        } else {
          throw new Exception("Error al preparar la consulta.");
        }
      } catch (\Throwable $th) {
        // Hacer rollback en caso de errores
        // $conn->rollback();

        // Mostrar la consulta convertida para depuración
        $resultado_consulta = $this->fnConvertirConsulta($conn, $query, $params);

        return [
          'code' => 500,
          'success' => false,
          'filas_afectadas' => -1,
          'response' => 'Error al actualizar los datos: ' . $th->getMessage() . "<br> Consulta => $resultado_consulta<br>",
        ];
      }
    }

    # Eliminar datos de un registro
    public function fnEliminarRegistro($conn, $query, $params, $types)
    {
      try {

        // Iniciar una transacción
        $conn->begin_transaction();

        if ($stmt = $conn->prepare($query)) {
          $stmt->bind_param($types, ...$params);

          if ($stmt->execute()) {
            // Devuelve el número de filas afectadas
            $filas_eliminadas = $stmt->affected_rows;
            $stmt->close();

            // Confirmar la transacción
            $conn->commit();

            return [
              'code' => 203,
              'success' => true,
              'filas_eliminadas' => $filas_eliminadas,
              'response' => '',
            ];
          }
        } else {
          $conn->rollback();
          // Si no se ejecuta correctamente, retorna 0 filas afectadas
          return [
            'code' => 500,
            'success' => false,
            'filas_eliminadas' => 0,
            'response' => 0,
          ];
        }
      } catch (\Throwable $th) {
        $conn->rollback();
        // Para depuración, mostrar la consulta con valores
        $resultado_consulta = $this->fnConvertirConsulta($conn, $query, $params);

        return [
          'code' => 500,
          'success' => false,
          'filas_eliminadas' => -1, // Indica que hubo un error
          'response' => 'Error al eliminar los datos: ' . $th->getMessage() . "<br> Consulta => $resultado_consulta<br>",
        ];
        
      }
    }

    # Regresar/Enviar respuestas JSON
    public function fnRegresarRespuestaJsonEncode($code, $success, $icon, $title, $message, $data = "")
    {

      # code = 200 => Encontrado/Exitoso
      # code = 201 => Creado/Insertado
      # code = 202 => Actualizado
      # code = 203 => Eliminado
      # code = 500 => Error general

      echo json_encode([
        'code' => $code,
        'success' => $success,
        'icon' => $icon,
        'title' => $title,
        'message' => $message,
        'data' => $data,
      ]);
      exit;
    }

    # Convierte los datos a una consulta legible
    public function fnConvertirConsulta($conn, $query_original, $params)
    {
      try {

        // Construir consulta para depuración
        $consulta_imprimir = $query_original;
        foreach ($params as $param) {
          // Escapar adecuadamente las comillas para simular el SQL real
          $escapar_parametros = is_string($param) ? "'" . $conn->real_escape_string($param) . "'" : $param;
          $consulta_imprimir = preg_replace('/\?/', $escapar_parametros, $consulta_imprimir, 1);
        }
        return $consulta_imprimir;
      } catch (\Throwable $th) {
        //throw $th;
        echo $th->getMessage();
      }
    }

    # Agregar días a una fecha
    public function fnAgregarDiasAFecha($fecha, $numero_dias_agregar, $formato_fecha = 'Y-m-d') {
      // Definir la zona horaria de México
      $zona_horaria = new DateTimeZone('America/Mexico_City');
      
      // Crear objeto DateTime con la fecha proporcionada y la zona horaria
      $objeto_fecha = DateTime::createFromFormat($formato_fecha, $fecha, $zona_horaria);
      
      if (!$objeto_fecha) {
        return "Fecha no válida"; // Manejo de error en caso de que la fecha no sea válida
      }
  
      // Aplicar la zona horaria explícitamente
      $objeto_fecha->setTimezone($zona_horaria);
  
      // Modificar la fecha sumando o restando días
      $objeto_fecha->modify("+{$numero_dias_agregar} days");
  
      // Retornar la fecha en el formato especificado
      return $objeto_fecha->format($formato_fecha);
    }

    # Diferencia de dos fechas en número de días naturales, puede ser negativo o positivo
    public function fnDiferenciaNumeroDiasDosFechasPositivoNegativo($fecha_1, $fecha_2 = '') {
      date_default_timezone_set('America/Mexico_City');

      if (empty($fecha_2)) {
        $fecha_2 = date('Y-m-d');
      }

      // Crear objetos DateTime con solo fecha (sin hora)
      $fecha_uno = new DateTime(date('Y-m-d', strtotime($fecha_1)));
      $fecha_dos = new DateTime(date('Y-m-d', strtotime($fecha_2)));

      // Calcular diferencia
      $diferencia = $fecha_uno->diff($fecha_dos);
      $dias = $diferencia->days;

      // Determinar signo
      if ($fecha_uno > $fecha_dos) {
        $dias *= -1;
      }

      return $dias;
    }


  }
