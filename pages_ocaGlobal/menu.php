<?php
  session_start();
  include '../sql/conexionMysqliUTF8Dev2.php';
  include 'includes/variables.php';

  $id_usuario = $_SESSION["id_usuario"];
  $rol = $_SESSION["rol"];
  $nombre = $_SESSION["nombre"];
  $apellido_paterno = $_SESSION["apellido_paterno"];

  if (empty($_SESSION['nombre'])) {
    header("Location: ../index.php");
  }

  // Redirección automática para clientes al visor de historial
  if ($rol === "Cliente") {
    // Verificar si no estamos ya en el visor de historial
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'visorHistorialArchivos.php') {
      header("Location: visorHistorialArchivos.php");
      exit();
    }
  }
  
  // Validar acceso a páginas según el rol
  $current_page = basename($_SERVER['PHP_SELF']);
  $pages_allowed = [];
  
  switch ($rol) {
    case "Administrador":
      $pages_allowed = ['inicio.php', 'administradorClientes.php', 'planesAccionClientes.php', 'visorHistorialArchivos.php'];
      break;
    case "Colaborador":
      $pages_allowed = ['inicio.php', 'administradorClientes.php', 'planesAccionClientes.php', 'visorHistorialArchivos.php'];
      break;
    case "Cliente":
      $pages_allowed = ['visorHistorialArchivos.php'];
      break;
    default:
      $pages_allowed = ['visorHistorialArchivos.php'];
  }
  
  // Redirigir si la página actual no está permitida para el rol
  if (!in_array($current_page, $pages_allowed)) {
    if ($rol === "Cliente") {
      header("Location: visorHistorialArchivos.php");
    } else {
      header("Location: inicio.php");
    }
    exit();
  }


?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="../vendors/iconic-fonts/font-awesome/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../vendors/iconic-fonts/flat-icons/flaticon.css">
  <link rel="stylesheet" href="../vendors/iconic-fonts/cryptocoins/cryptocoins.css">
  <link rel="stylesheet" href="../vendors/iconic-fonts/cryptocoins/cryptocoins-colors.css">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href=" https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" rel="stylesheet">
  <link href="../assets/css/slick.css" rel="stylesheet">
  <link href="../assets/css/datatables.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <link href="../assets/css/sweetalert2.min.css" rel="stylesheet">
  <link rel="shortcut icon" type="image/png" href="../images/favicon.png" />


  <!-- Estilos personalizados -->
  <!-- <link rel="stylesheet" href="css/estilos.css"> -->

</head>
<!-- Preloader -->
<div id="preloader-wrap">
  <div class="spinner spinner-8">
    <div class="ms-circle1 ms-child"></div>
    <div class="ms-circle2 ms-child"></div>
    <div class="ms-circle3 ms-child"></div>
    <div class="ms-circle4 ms-child"></div>
    <div class="ms-circle5 ms-child"></div>
    <div class="ms-circle6 ms-child"></div>
    <div class="ms-circle7 ms-child"></div>
    <div class="ms-circle8 ms-child"></div>
    <div class="ms-circle9 ms-child"></div>
    <div class="ms-circle10 ms-child"></div>
    <div class="ms-circle11 ms-child"></div>
    <div class="ms-circle12 ms-child"></div>
  </div>
</div>
<!-- Overlays -->
<div class="ms-aside-overlay ms-overlay-left ms-toggler" data-target="#ms-side-nav" data-toggle="slideLeft"></div>
<!-- Sidebar Navigation Left -->
<aside style="background-color:#115498;" id="ms-side-nav" class="side-nav fixed ms-aside-scrollable ms-aside-left">
  <!-- Logo -->
  <div class="logo-sn ms-d-block-lg p-3" style="background-color: #125090; text-align: center;">
    <a class="pl-0 ml-0 text-center p-0" href="">
      <img src="../../reportes/images/logo_oca.png" alt="Field" width="100%" height="120px" style="background-color: #fff;border-radius: 5px;max-width: none;">
    </a>
  </div>
  <!-- Navigation -->
  <ul class="accordion ms-main-aside fs-14" id="side-nav-accordion">
    <?php if ($rol === "Administrador"): ?>
      <!-- Administrador: Acceso completo -->
      <li class="menu-item">
        <a href="inicio.php">
          <span><i class="material-icons fs-16">dashboard</i>Inicio</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="administradorClientes.php">
          <span><i class="fa fa-users"></i>Administrador de Clientes</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="visorHistorialArchivos.php">
          <span><i class="fa fa-folder-open"></i>Visor de Historial</span>
        </a>
      </li>
    <?php elseif ($rol === "Colaborador"): ?>
      <!-- Colaborador: Acceso completo excepto eliminar archivos -->
      <li class="menu-item">
        <a href="inicio.php">
          <span><i class="material-icons fs-16">dashboard</i>Inicio</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="administradorClientes.php">
          <span><i class="fa fa-users"></i>Administrador de Clientes</span>
        </a>
      </li>
      <li class="menu-item">
        <a href="visorHistorialArchivos.php">
          <span><i class="fa fa-folder-open"></i>Visor de Historial</span>
        </a>
      </li>
    <?php elseif ($rol === "Cliente"): ?>
      <!-- Cliente: Solo Visor de Historial -->
      <li class="menu-item">
        <a href="visorHistorialArchivos.php">
          <span><i class="fa fa-folder-open"></i>Visor de Historial</span>
        </a>
      </li>
    <?php endif; ?>
    
    <!-- Cerrar sesión siempre visible -->
    <li class="menu-item">
      <a href="../sql/logout.php">
        <span><i class="fas fa-door-open"></i>Cerrar sesión</span>
      </a>
    </li>
  </ul>
</aside>
<!-- Navigation Bar -->
<nav class="navbar ms-navbar" style="background-color: #fff;">
  <div class="ms-aside-toggler ms-toggler pl-0" data-target="#ms-side-nav" data-toggle="slideLeft">
    <span class="ms-toggler-bar bg-primary"></span>
    <span class="ms-toggler-bar bg-primary"></span>
    <span class="ms-toggler-bar bg-primary"></span>
  </div>
  <div class="logo-sn logo-sm ms-d-block-sm">
    <a class="pl-0 ml-0 text-center navbar-brand mr-0" href="inicio.php">
      <img src="../reportes/images/logo_oca.png" width="50%" alt="Field">
    </a>
  </div>
  <ul class="ms-nav-list ms-inline mb-0" id="ms-nav-options">
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">

          <span style="color: #125090; font-size: 20px;">Portal <?php echo $rol ?></span>
        </div>
      </form>
    </li>
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">

        </div>
      </form>
    </li>
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">

        </div>
      </form>
    </li>
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">

        </div>
      </form>
    </li>
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">

        </div>
      </form>
    </li>
    <li class="ms-nav-item dropdown">
      <a href="#" class="text-disabled ms-has-notification" id="notificationDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i style="color:#125090;" class="flaticon-bell"></i></a>
      <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="notificationDropdown">
        <li class="dropdown-menu-header">
          <h6 class="dropdown-header ms-inline m-0"><span class="text-disabled">Notificaciones</span></h6><span class="badge badge-pill badge-info">1 Nueva</span>
        </li>
        <li class="dropdown-divider"></li>
        <li class="ms-scrollable ms-dropdown-list">
          <a class="media p-2" href="#">
            <div class="media-body">
              <span>Nueva imagen al Portal Field</span>
              <p class="fs-10 my-1 text-disabled"><i class="material-icons">access_time</i> Hace un momento</p>
            </div>
          </a>
        </li>
        <li class="dropdown-divider"></li>
      </ul>
    </li>
    <li class="ms-nav-item ms-search-form pb-0 py-0">
      <form class="ms-form" method="post">
        <div class="ms-form-group my-0 mb-0 has-icon fs-14">
          <span style="color: #125090"><?php echo $_SESSION['nombre']; ?></span>
        </div>
      </form>
    </li>
</nav>

</html>
