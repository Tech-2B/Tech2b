<?php
  include 'menu.php';

  $fecha_js = date('Y-m-d H:i:s');
  
  // Verificar que solo Administradores puedan acceder
  if ($rol !== "Administrador") {
    header("Location: inicio.php");
    exit();
  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Field | Validador de Archivos</title>
  <link href="css/administradorClientes.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link href="css/validadorArchivos.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
</head>
<style>
  label span {
    color: red;
  }
</style>

<body class="ms-body ms-aside-left-open ms-settings-open">
  <!-- Main Content -->
  <main class="body-content">
    <!-- Body Content Wrapper -->
    <div class="ms-content-wrapper">
      <div class="row print-title" style="margin-left: 5px;">
        <div class="col-md-8">
          <span style="font-size: 40px;">Validador de Archivos</span>
        </div>
        <div class="col-md-4 text-right">
          <div class="badge badge-warning" id="contador_pendientes">
            <i class="fa fa-clock"></i> <span id="numero_pendientes">0</span> Pendientes
          </div>
        </div>
      </div>
      <br />

      <!-- Filtros -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6><i class="fa fa-filter"></i> Filtros</h6>
            </div>
            <div class="ms-panel-body">
              <div class="row">
                <div class="col-md-3">
                  <label for="filtro_estado">Estado:</label>
                  <select id="filtro_estado" class="form-control">
                    <option value="pendiente">Pendientes</option>
                    <option value="aprobado">Aprobados</option>
                    <option value="rechazado">Rechazados</option>
                    <option value="">Todos</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="filtro_cliente">Cliente:</label>
                  <select id="filtro_cliente" class="form-control">
                    <option value="">Todos los clientes</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label for="filtro_fecha_desde">Desde:</label>
                  <input type="date" id="filtro_fecha_desde" class="form-control">
                </div>
                <div class="col-md-3">
                  <label for="filtro_fecha_hasta">Hasta:</label>
                  <input type="date" id="filtro_fecha_hasta" class="form-control">
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12 text-right">
                  <button type="button" class="btn btn-primary" onclick="validador.filtrarArchivos()">
                    <i class="fa fa-search"></i> Filtrar
                  </button>
                  <button type="button" class="btn btn-secondary" onclick="validador.limpiarFiltros()">
                    <i class="fa fa-eraser"></i> Limpiar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de archivos pendientes -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6><i class="fa fa-file-upload"></i> Archivos para Validar</h6>
            </div>
            <div class="ms-panel-body">
              <div class="table-responsive">
                <table id="tabla_archivos_pendientes" class="table w-100 thead-primary">
                  <thead>
                    <tr>
                      <th>Archivo</th>
                      <th>Cliente</th>
                      <th>Plan de Acción</th>
                      <th>Subido por</th>
                      <th>Fecha</th>
                      <th>Tamaño</th>
                      <th>Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tabla_archivos_pendientes_body">
                    <tr>
                      <td colspan="8" class="text-center">
                        <i class="fa fa-spinner fa-spin"></i> Cargando archivos...
                      </td>
                    </tr>
                  </tbody>
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

    </div>
  </main>

  <!-- Modal para validar archivo -->
  <div class="modal fade" id="modal_validar_archivo" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Validar Archivo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="info_archivo_validar">
            <!-- Información del archivo se cargará aquí -->
          </div>
          
          <div class="form-group">
            <label for="comentario_validacion">Comentario de validación:</label>
            <textarea id="comentario_validacion" class="form-control" rows="3" 
                      placeholder="Opcional: Agregar comentario sobre la validación"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-danger" onclick="validador.rechazarArchivo()">
            <i class="fa fa-times"></i> Rechazar
          </button>
          <button type="button" class="btn btn-success" onclick="validador.aprobarArchivo()">
            <i class="fa fa-check"></i> Aprobar
          </button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        </div>
      </div>
    </div>
  </div>

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

  <!-- Page Specific Scripts Finish -->
  <!-- mylo core JavaScript -->
  <script src="../assets/js/framework.js"></script>
  <!-- Settings -->
  <script src="../assets/js/settings.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <script src="js/validadorArchivos.js?id=<?php echo $fecha_js; ?>"></script>
</body>

</html>
