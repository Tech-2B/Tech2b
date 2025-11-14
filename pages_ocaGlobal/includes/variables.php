<?php

  date_default_timezone_set('America/Mexico_City');
  /**
   * Variables que se usan en las pages
   *
   * Estas variables son para producción
  */

  # code = 200 => Encontrado/Exito
  # code = 201 => Creado/Insertado
  # code = 202 => Actualizado
  # code = 203 => Eliminado
  # code = 500 => Error general
  $code_200 = 200;
  $code_201 = 201;
  $code_202 = 202;
  $code_203 = 203;
  $code_500 = 500;

  # Iconos
  $icon_success = "success";
  $icon_info = "info";
  $icon_warning = "warning";
  $icon_error = "error";

  # Titulos
  $titulo_exito = "¡Éxito!";
  $titulo_sin_informacion = "Sin información";
  $titulo_ocurrio_error = "Ha ocurrido un error inesperado. Por favor intenta más tarde.";

  # Mensajes
  $mensaje_encontrado = "Los datos han sido encontrados correctamente.";
  $mensaje_no_encontrado = "No se encontraron datos.";
  $mensaje_insertado = "Los datos han sido guardados correctamente.";
  $mensaje_actualizado = "Los datos han sido actualizados correctamente.";
  $mensaje_eliminado = "Los datos han sido eliminados correctamente.";
  $mensaje_ocurrio_error = "Ha ocurrido un error inesperado.";
  
  # Tablas
  $tabla_usuarios = 'wwappb_field.usuarios';
  $tabla_clientes = 'wwappb_field_oca.OCAMEXICO_clientes';
  
  # Listas de los planes de acción
  $tabla_lista_areasOportunidades = 'wwappb_field_oca.OCAMEXICO_lista_areasOportunidades';
  $tabla_lista_planesAccion = 'wwappb_field_oca.OCAMEXICO_lista_planesAccion';
  $tabla_lista_topicos = 'wwappb_field_oca.OCAMEXICO_lista_topicos';
  $tabla_lista_entregables = 'wwappb_field_oca.OCAMEXICO_lista_entregables';
  $tabla_lista_periodicidades = 'wwappb_field_oca.OCAMEXICO_lista_periodicidades';
  
  # Tabla de planes de acción de clientes
  $tabla_planes_accion_clientes = 'wwappb_field_oca.OCAMEXICO_planes_accion_clientes';
  
  # Tabla de archivos de planes de acción
  $tabla_archivos_planes_accion = 'wwappb_field_oca.OCAMEXICO_archivos_planes_accion';
  
  # Tablas para Google Drive
  $tabla_carpetas_drive = 'wwappb_field_oca.OCAMEXICO_carpetas_drive';
  $tabla_archivos_drive = 'wwappb_field_oca.OCAMEXICO_archivos_drive';
  
  # Tabla para archivos pendientes de validación
  $tabla_archivos_pendientes_validacion = 'wwappb_field_oca.OCAMEXICO_archivos_pendientes_validacion';
  
  # Tabla para historial de archivos
  $tabla_historialArchivos_planesAccion = 'wwappb_field_oca.OCAMEXICO_historialArchivos_planesAccion';
  
  # ID de la carpeta principal en Google Drive
  $id_carpeta_principal_drive = '1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi';

