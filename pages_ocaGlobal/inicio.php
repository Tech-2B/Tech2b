<?php
  include 'menu.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <title>Field | Inicio</title>
  <link href="css/administradorClientes.css" rel="stylesheet">
</head>
<body class="ms-body ms-aside-left-open ms-settings-open">
  <main class="body-content">
    <div class="ms-content-wrapper">
      <div class="row print-title" style="margin-left: 5px;">
        <span style="font-size: 40px;">Inicio - Portal Field</span>
      </div>
      <br />

      <div class="row">
        <div class="col-md-12">
          <div class="ms-panel">
            <div class="ms-panel-header">
              <h6>Bienvenido al Portal Field</h6>
            </div>
            <div class="ms-panel-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="card text-center">
                    <div class="card-body">
                      <i class="fa fa-users fa-3x text-primary mb-3"></i>
                      <h5 class="card-title">Administrador de Clientes</h5>
                      <p class="card-text">Gestiona la información de tus clientes</p>
                      <a href="administradorClientes.php" class="btn btn-primary">Acceder</a>
                    </div>
                  </div>
                </div>
                <!-- <div class="col-md-4">
                  <div class="card text-center">
                    <div class="card-body">
                      <i class="fa fa-project-diagram fa-3x text-success mb-3"></i>
                      <h5 class="card-title">Administrador de Proyectos</h5>
                      <p class="card-text">Administra tus proyectos y recursos</p>
                      <a href="administradorProyectos.php" class="btn btn-success">Acceder</a>
                    </div>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="card text-center">
                    <div class="card-body">
                      <i class="fa fa-th-list fa-3x text-info mb-3"></i>
                      <h5 class="card-title">Plan de Acciones</h5>
                      <p class="card-text">Crea y gestiona planes de acción</p>
                      <a href="planesAccion.php" class="btn btn-info">Acceder</a>
                    </div>
                  </div>
                </div> -->
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

  <!-- Quick bar -->
  <aside id="ms-quick-bar" class="ms-quick-bar fixed ms-d-block-lg">
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
</body>
</html>