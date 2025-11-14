<?php

/**
 * Archivo para limpiar tokens temporales
 */

session_start();

// Obtener token
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!empty($token)) {
  $session_key = 'archivo_temp_' . $token;
  if (isset($_SESSION[$session_key])) {
    unset($_SESSION[$session_key]);
  }
}

// Limpiar tokens expirados
$current_time = time();
foreach ($_SESSION as $key => $value) {
  if (strpos($key, 'archivo_temp_') === 0 && is_array($value) && isset($value['timestamp'])) {
    if ($current_time - $value['timestamp'] > 3600) { // 1 hora
      unset($_SESSION[$key]);
    }
  }
}

http_response_code(200);
echo "OK";
?>
