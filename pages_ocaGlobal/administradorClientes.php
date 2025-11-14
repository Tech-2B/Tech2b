<?php
  include 'menu.php';

  $fecha_js = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <title>Field | Administrador de clientes</title>
  <link href="css/administradorClientes.css" rel="stylesheet">
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
        <span style="font-size: 40px;">Administrador de clientes</span>
      </div>
      <br />

      <!-- Alta de cliente -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6>Agregar Cliente</h6>
            </div>
            <div class="ms-panel-body">

              <!-- Formulario -->
              <form method="post" id="form_cliente" autocomplete="off" enctype="multipart/form-data">

                <div class="form-group">
                  <div class="form-row">

                    <div class="col-md-4 mb-3">
                      <label for="nombre_cliente">Nombre del cliente:<span>&nbsp;*</span></label>
                      <input class="form-control" type="text" id="nombre_cliente" name="nombre_cliente" required placeholder="Escribe el nombre del cliente" />
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="codigo_cliente">Código del cliente:<span>&nbsp;*</span></label>
                      <input class="form-control" type="text" id="codigo_cliente" name="codigo_cliente" required placeholder="Escribe el código del cliente" />
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="tipo_cliente">Tipo de cliente:</label>
                      <input class="form-control" type="text" id="tipo_cliente" name="tipo_cliente" placeholder="Escribe el tipo de cliente" />
                    </div>

                    <div class="col-md-4 mb-3">
                      <label for="nombre_contacto">Nombre contacto cliente:</label>
                      <input class="form-control" type="text" id="nombre_contacto" name="nombre_contacto" placeholder="Escribe el nombre del contacto" />
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="telefono_cliente">Teléfono del cliente:</label>
                      <input class="form-control" type="text" id="telefono_cliente" name="telefono_cliente" placeholder="Escribe el teléfono" />
                    </div>
                    <div class="col-md-4 mb-3">
                      <label for="correo_electronico">Correo electrónico del cliente:</label>
                      <input class="form-control" type="email" id="correo_electronico" name="correo_electronico" placeholder="Escribe el correo electrónico" />
                    </div>

                    <div class="col-md-6 mb-3">
                      <label for="direccion_cliente">Dirección del cliente:</label>
                      <textarea class="form-control" id="direccion_cliente" name="direccion_cliente" rows="3" placeholder="Escribe la dirección completa"></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label for="ciudad_estado">Ciudad/Estado del cliente:</label>
                      <input class="form-control" type="text" id="ciudad_estado" name="ciudad_estado" placeholder="Escribe la ciudad y estado" />
                    </div>
                    
                  </div>
                </div>

                <input type="submit" id="" class="btn btn-primary d-block w-25" name="btn_guardar" value="Guardar cliente" />
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6>Lista de clientes</h6>
            </div>
            <div class="ms-panel-body">
              <div class="table-responsive">
                <table id="tabla_clientes" class="table w-100 thead-primary">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Nombre del cliente</th>
                      <th>Código del cliente</th>
                      <th>Tipo de cliente</th>
                      <th>Nombre contacto</th>
                      <th>Ciudad/Estado</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody id="tabla_clientes_body"></tbody>
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

      <!-- Modal para editar -->
      <div class="modal" id="modal_editar_cliente" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header" style="background: #00a7b5;">
              <h2 style="color: white;" class="modal-title">Editar cliente</h2>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span style="color: white;" aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="formulario_editar_cliente" enctype="multipart/form-data" autocomplete="off">
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
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

  <!-- Page Specific Scripts Finish -->
  <!-- mylo core JavaScript -->
  <script src="../assets/js/framework.js"></script>
  <!-- Settings -->
  <script src="../assets/js/settings.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="js/administradorClientes.js?id=<?php echo $fecha_js; ?>"></script>
</body>

</html>
