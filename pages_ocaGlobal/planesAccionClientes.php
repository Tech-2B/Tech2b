<?php
  include 'menu.php';

  $fecha_js = date('Y-m-d H:i:s');
  
  // Definir permisos según el rol
  $puede_eliminar_archivos = ($rol === "Administrador");
  $puede_cargar_archivos = ($rol === "Administrador" || $rol === "Colaborador");
  $puede_ver_historial = true; // Todos los roles pueden ver historial
  $puede_crear_planes = ($rol === "Administrador" || $rol === "Colaborador");
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Field | Planes de Acción de Clientes</title>
  <link href="css/administradorClientes.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link href="css/modalCargarArchivo.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link href="css/modalHistorialArchivos.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link href="css/planesAccionClientes.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">


</head>
<style>
  label span {
    color: red;
  }

  .btn-text {
    color: #fff;
  }
</style>

<body class="ms-body ms-aside-left-open ms-settings-open">
  <!-- Main Content -->
  <main class="body-content">
    <!-- Body Content Wrapper -->
    <div class="ms-content-wrapper">
      <div class="row print-title" style="margin-left: 5px;">
        <div class="col-md-8">
          <span style="font-size: 40px;">Planes de Acción de Clientes</span>
        </div>
      </div>
      <br />

      <!-- Información del cliente seleccionado -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6>Cliente Seleccionado</h6>
            </div>
            <div class="ms-panel-body">
              <div id="cliente_seleccionado_info" class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                <strong>Cliente:</strong> <span id="nombre_cliente_seleccionado">Cargando...</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Formulario para crear plan de acción manual -->
      <div class="row" id="formulario_nuevo_plan" style="display: none;">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header bg-primary text-white">
              <h6><i class="fa fa-plus-circle"></i> Crear Nuevo Plan de Acción</h6>
            </div>
            <div class="ms-panel-body">
              <form id="formulario_crear_plan" autocomplete="off">
                <input type="hidden" id="id_cliente_formulario" name="id_cliente">
                
                <div class="row">
                  <!-- Área de Oportunidad -->
                  <div class="col-md-6 mb-3">
                    <label for="area_oportunidad_manual" class="form-label">
                      <i class="fa fa-bullseye"></i> Área de Oportunidad <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="area_oportunidad_manual" name="area_oportunidad" 
                           placeholder="Escribe el área de oportunidad" required>
                  </div>

                  <!-- Plan de Acción -->
                  <div class="col-md-6 mb-3">
                    <label for="plan_accion_manual" class="form-label">
                      <i class="fa fa-tasks"></i> Plan de Acción <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="plan_accion_manual" name="plan_accion" 
                           placeholder="Escribe el plan de acción" required>
                  </div>
                </div>

                <div class="row">
                  <!-- Tópicos -->
                  <div class="col-md-6 mb-3">
                    <label for="topicos_manual" class="form-label">
                      <i class="fa fa-tags"></i> Tópicos <span class="text-danger">*</span>
                    </label>
                    <select class="form-control" id="topicos_manual" name="topicos" required>
                      <option value="">-- Seleccionar Tópico --</option>
                    </select>
                    <div id="div_topico_otro" class="mt-2" style="display: none;">
                      <input type="text" class="form-control" id="topico_otro" name="topico_otro" 
                             placeholder="Escribe el tópico personalizado">
                    </div>
                  </div>

                  <!-- Entregables -->
                  <div class="col-md-6 mb-3">
                    <label for="entregables_manual" class="form-label">
                      <i class="fa fa-box"></i> Entregables <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="entregables_manual" name="entregables" 
                           placeholder="Escribe los entregables" required>
                  </div>
                </div>

                <div class="row">
                  <!-- Periodicidad -->
                  <div class="col-md-6 mb-3">
                    <label for="periodicidad_manual" class="form-label">
                      <i class="fa fa-calendar"></i> Periodicidad <span class="text-danger">*</span>
                    </label>
                    <select class="form-control" id="periodicidad_manual" name="periodicidad" required>
                      <option value="">-- Seleccionar Periodicidad --</option>
                    </select>
                    <div id="div_periodicidad_otro" class="mt-2" style="display: none;">
                      <input type="text" class="form-control" id="periodicidad_otro" name="periodicidad_otro" 
                             placeholder="Escribe la periodicidad personalizada">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12">
                    <button type="submit" class="btn btn-success btn-lg">
                      <i class="fa fa-save"></i> Crear Plan de Acción
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg ml-2" id="btn_cancelar_plan">
                      <i class="fa fa-times"></i> Cancelar
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Botón para mostrar formulario -->
      <div class="row mb-3" id="btn_mostrar_formulario" style="display: none;">
        <div class="col-md-12 text-center">
          <button type="button" class="btn btn-primary btn-lg" id="btn_nuevo_plan">
            <i class="fa fa-plus"></i> Crear Nuevo Plan de Acción
          </button>
        </div>
      </div>

      <!-- Tabla de planes de acción -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-body"> 
              <div class="table-responsive">
                <table id="tabla_planes_accion" class="table thead-primary">
                  <thead>
                    <tr>
                      <th>ID Registro</th>
                      <th>Área de Oportunidad</th>
                      <th>Plan de Acción</th>
                      <th>Tópicos</th>
                      <th>Entregables</th>
                      <th>Periodicidades</th>
                      <th>Acciones</th>
                      <?php if ($puede_cargar_archivos): ?>
                      <th>Cargar Archivo</th>
                      <?php endif; ?>
                      <?php if ($puede_cargar_archivos): ?>
                      <th>Pendientes de Validación</th>
                      <?php endif; ?>
                      <th>Historial de Archivos</th>
                    </tr>
                  </thead>
                  <tbody id="tabla_planes_accion_body"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer logo -->
      <div class="row logo-field">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-body" style="display: flex;flex-direction: column;justify-content: center;align-content: center;align-items: center;">
              <h3>Field © <?php echo date("Y") ?> Todos los Derechos Reservados.</h3>
              <img src="../images/logoblue.png" width="45px" alt="">
            </div>
          </div>
        </div>
      </div>

      <!-- Modal para cargar archivos -->
      <div class="modal modal-cargar-archivo" id="modal_cargar_archivo" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h2 class="modal-title">Cargar Archivo</h2>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span style="color: #fff;" aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="formulario_cargar_archivo" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" id="id_registro_archivo" name="id_registro">
                <input type="hidden" id="id_cliente_archivo" name="id_cliente">
                <input type="hidden" id="id_plan_accion_archivo" name="id_plan_accion">
                
                <!-- Opción para crear nueva carpeta -->
                <div class="form-group">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="crear_nueva_carpeta" name="crear_nueva_carpeta">
                    <label class="form-check-label" for="crear_nueva_carpeta">
                      Crear nueva carpeta
                    </label>
                  </div>
                </div>

                <!-- Input para nombre de nueva carpeta -->
                <div class="form-group input-nueva-carpeta" id="div_nombre_nueva_carpeta" style="display: none;">
                  <label for="nombre_nueva_carpeta" class="form-label">Nombre de la Nueva Carpeta:</label>
                  <div class="input-group">
                    <input class="form-control" type="text" id="nombre_nueva_carpeta" name="nombre_nueva_carpeta" placeholder="Escribe el nombre de la nueva carpeta">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-success" id="btn_crear_carpeta" onclick="planesAccion.crearNuevaCarpeta()">
                        <i class="fa fa-plus"></i> Crear
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Select de carpeta de destino -->
                <div class="form-group select-carpeta-container">
                  <label for="select_carpeta_destino" class="form-label">Carpeta de Destino:</label>
                  <select class="form-control" id="select_carpeta_destino" name="carpeta_destino" required>
                    <option value="">-- Seleccionar Carpeta --</option>
                  </select>
                </div>

                <!-- Input de archivo -->
                <div class="form-group">
                  <label for="archivo_subir" class="form-label">Seleccionar Archivo:</label>
                  <div class="input-archivo-container">
                    <input type="file" id="archivo_subir" name="archivo_subir" accept=".pdf,.docx,.doc,.xlsx,.xls,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp,.txt,.csv,.log,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.m4v" required class="form-control-file">
                    <div class="input-archivo-info">
                      <i class="fa fa-info-circle"></i>
                      Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes (PNG, JPG, GIF, BMP, WEBP), archivos de texto (TXT, CSV, LOG), videos (MP4, AVI, MOV, WMV, FLV, WEBM, MKV, M4V). Tamaño máximo: 100MB
                    </div>
                  </div>
                </div>

                <!-- Textarea de comentarios -->
                <div class="form-group textarea-comentario">
                  <label for="comentario_archivo" class="form-label">Comentario sobre la actividad relacionada al archivo (opcional):</label>
                  <textarea class="form-control" id="comentario_archivo" name="comentario_archivo" rows="3" placeholder="Escribe un comentario sobre el archivo"></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-primary" id="btn_subir_archivo">Subir Archivo</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal para historial de archivos -->
      <div class="modal modal-historial-archivos" id="modal_historial_archivos" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h2 class="modal-title">Historial de Archivos</h2>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span style="color: #fff;" aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <!-- Información del plan de acción -->
              <div class="alert alert-info mb-3" id="info_plan_historial">
                <i class="fa fa-info-circle"></i>
                <span id="descripcion_plan_historial"></span>
              </div>
              
              <!-- Navegación breadcrumb -->
              <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb" id="breadcrumb_historial">
                  <li class="breadcrumb-item active" id="breadcrumb_inicio">Inicio</li>
                </ol>
              </nav>
              
              <!-- Estructura de carpetas -->
              <div class="row">
                <div class="col-12">
                  <h6><i class="fa fa-folder text-primary"></i> Estructura de Carpetas</h6>
                  <div class="carpetas-container" style="max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px; margin-bottom: 20px;">
                    <div id="lista_carpetas_historial">
                      <div class="text-center text-muted">
                        <i class="fa fa-spinner fa-spin"></i> Cargando estructura...
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Archivos de la carpeta -->
              <div class="row">
                <div class="col-12">
                  <h6><i class="fa fa-file text-success"></i> Archivos de la Carpeta</h6>
                  <div class="archivos-container" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;">
                    <div id="lista_archivos_historial">
                      <div class="text-center text-muted">
                        <i class="fa fa-folder-open"></i> Selecciona una carpeta para ver sus archivos
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal para validación de archivos pendientes -->
      <div class="modal modal-validacion-archivos" id="modal_validacion_archivos" tabindex="-1">
        <div class="modal-dialog modal-xl">
          <div class="modal-content">
            <div class="modal-header">
              <h2 class="modal-title">Validación de Archivos Pendientes</h2>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span style="color: #fff;" aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <!-- Información del plan de acción -->
              <div class="alert alert-info mb-3" id="info_plan_validacion">
                <i class="fa fa-info-circle"></i>
                <span id="descripcion_plan_validacion"></span>
              </div>
              
              <!-- Lista de archivos pendientes -->
              <div class="row">
                <div class="col-12">
                  <h6><i class="fa fa-clock text-warning"></i> Archivos Pendientes de Validación</h6>
                  <div class="archivos-pendientes-container" style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 5px; padding: 10px;">
                    <div id="lista_archivos_pendientes">
                      <div class="text-center text-muted">
                        <i class="fa fa-spinner fa-spin"></i> Cargando archivos pendientes...
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>

  <!-- Quick bar -->
  <aside id="ms-quick-bar" class="ms-quick-bar fixed ms-d-block-lg">
    <!-- Quick bar Content -->
    <div class="ms-quick-bar-content">
      <div class="ms-quick-bar-body tab-content">
      </div>
    </div>
  </aside>
  <!-- SCRIPTS -->
  <!-- Global Required Scripts Start -->
  <script src="../assets/js/jquery-3.3.1.min.js"></script>
  <script src="../assets/js/popper.min.js"></script>
  <script src="../assets/js/bootstrap.min.js"></script>
  <script src="../assets/js/perfect-scrollbar.js"> </script>
  <script src="../assets/js/jquery-ui.min.js"> </script>
  <!-- Global Required Scripts End -->
  <!-- Page Specific Scripts Start -->
  <script src="../assets/js/slick.min.js"> </script>
  <script src="../assets/js/moment.js"> </script>
  <script src="../assets/js/jquery.webticker.min.js"> </script>
  <script src="../assets/js/Chart.bundle.min.js"> </script>
  <script src="../assets/js/cryptocurrency.js"> </script>
  <script src="../assets/js/datatables.min.js"> </script>
  <script src="../assets/js/data-tables.js"> </script>
  <!-- jQuery ya está cargado arriba, no cargar duplicado -->
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

  <!-- Page Specific Scripts Finish -->
  <!-- mylo core JavaScript -->
  <script src="../assets/js/framework.js"></script>
  <!-- Settings -->
  <script src="../assets/js/settings.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Pasar permisos al JavaScript -->
  <script>
    window.permisosUsuario = {
      puedeEliminarArchivos: <?php echo $puede_eliminar_archivos ? 'true' : 'false'; ?>,
      puedeCargarArchivos: <?php echo $puede_cargar_archivos ? 'true' : 'false'; ?>,
      puedeVerHistorial: <?php echo $puede_ver_historial ? 'true' : 'false'; ?>,
      puedeCrearPlanes: <?php echo $puede_crear_planes ? 'true' : 'false'; ?>,
      rol: '<?php echo $rol; ?>'
    };
  </script>
  
  <script src="js/planesAccionClientes.js?id=<?php echo $fecha_js; ?>"></script>
</body>

</html>
