<?php
  include 'menu.php';
  include '../sql/conexionMysqliUTF8Dev2.php';
  include 'includes/variables.php';

  $fecha_js = date('Y-m-d H:i:s');
  
  // Definir permisos según el rol
  $puede_eliminar_archivos = ($rol === "Administrador");
  $puede_cargar_archivos = ($rol === "Administrador" || $rol === "Colaborador");
  $puede_ver_historial = true; // Todos los roles pueden ver historial
  
  // Obtener información del cliente automáticamente si el rol es "Cliente"
  $cliente_automatico = null;
  if ($rol === "Cliente") {
    // Obtener el único cliente de la base de datos
    $query_cliente = "SELECT id_cliente, nombre_cliente, codigo_cliente, tipo_cliente FROM $tabla_clientes WHERE activo = 1 LIMIT 1";
    $result_cliente = mysqli_query($conn, $query_cliente);
    
    if ($result_cliente && mysqli_num_rows($result_cliente) > 0) {
      $cliente_automatico = mysqli_fetch_assoc($result_cliente);
    }
  }
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Field | Visor de Historial de Archivos</title>
  <link href="css/administradorClientes.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link href="css/visorHistorialArchivos.css?id=<?php echo $fecha_js; ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css">
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
          <span style="font-size: 40px;">Visor de Historial de Archivos</span>
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


      <!-- Lista de planes de acción -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            
            <div class="ms-panel-body">
              <div class="table-responsive">
                <table id="tabla_planes_accion" class="table w-100 thead-primary">
                  <thead>
                    <tr>
                      <th>ID Registro</th>
                      <th>Área de Oportunidad</th>
                      <th>Plan de Acción</th>
                      <th>Tópicos</th>
                      <th>Entregables</th>
                      <th>Periodicidades</th>
                      <th>Ver Estructura</th>
                    </tr>
                  </thead>
                  <tbody id="tabla_planes_accion_body"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Lista de archivos de la carpeta seleccionada -->
      <div class="row" id="seccion_archivos" style="display: none;">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6><i class="fa fa-file"></i> Archivos en la Carpeta Seleccionada</h6>
            </div>
            <div class="ms-panel-body">
              <div class="table-responsive">
                <table id="tabla_archivos_carpeta" class="tabla-archivos-carpeta table w-100">
                  <thead>
                    <tr>
                      <th class="col-nombre">Nombre del Archivo</th>
                      <th class="col-tipo">Tipo</th>
                      <th class="col-tamano">Tamaño</th>
                      <th class="col-comentarios">Comentarios</th>
                      <th class="col-fecha">Fecha de Subida</th>
                      <th class="col-usuario">Subido por</th>
                      <th class="col-acciones">Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tabla_archivos_carpeta_body"></tbody>
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

  <!-- Modal de Estructura de Carpetas -->
  <div id="modal_carpetas" class="modal modal-carpetas">
    <div class="modal-carpetas-contenido">
      <div class="modal-carpetas-header">
        <h5 style="color: #ffffff;"><i class="fa fa-folder"></i> Estructura de Carpetas</h5>
        <span class="modal-carpetas-cerrar" onclick="visorHistorial.cerrarModalCarpetas()">&times;</span>
      </div>
      <div class="modal-carpetas-body">
        <!-- Información del plan de acción seleccionado -->
        <div class="info-plan-seleccionado mb-3">
          <h6 id="nombre_plan_seleccionado">Cargando...</h6>
        </div>
        
        <!-- Área de carpetas (arriba) -->
        <div class="area-carpetas">
          <h6><i class="fa fa-folder"></i> Carpetas Disponibles</h6>
          <div class="arbol-carpetas-modal" id="arbol_carpetas_modal">
            <div class="text-center text-muted">
              <i class="fa fa-folder-open fa-2x mb-2"></i>
              <p>Cargando estructura de carpetas...</p>
            </div>
          </div>
        </div>
        
        <!-- Área de archivos (abajo) -->
        <div class="area-archivos" id="area_archivos" style="display: none;">
          <h6><i class="fa fa-file"></i> Archivos de la Carpeta Seleccionada</h6>
          <div class="lista-archivos-modal" id="lista_archivos_modal">
            <!-- Los archivos se cargarán aquí dinámicamente -->
          </div>
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
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

  <!-- Page Specific Scripts Finish -->
  <!-- mylo core JavaScript -->
  <script src="../assets/js/framework.js"></script>
  <!-- Settings -->
  <script src="../assets/js/settings.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Pasar información del cliente automático y permisos al JavaScript -->
  <script>
    window.clienteAutomatico = <?php echo json_encode($cliente_automatico); ?>;
    window.rolUsuario = '<?php echo $rol; ?>';
    window.permisosUsuario = {
      puedeEliminarArchivos: <?php echo $puede_eliminar_archivos ? 'true' : 'false'; ?>,
      puedeCargarArchivos: <?php echo $puede_cargar_archivos ? 'true' : 'false'; ?>,
      puedeVerHistorial: <?php echo $puede_ver_historial ? 'true' : 'false'; ?>,
      rol: '<?php echo $rol; ?>'
    };
  </script>
  
  <script src="js/visorHistorialArchivos.js?id=<?php echo $fecha_js; ?>"></script>
</body>

</html>
