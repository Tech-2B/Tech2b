<?php

/**
 * Archivo para visualizar archivos pendientes sin descarga
 */

session_start();

// Verificar permisos
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Colaborador'])) {
  http_response_code(403);
  echo "No tienes permisos para visualizar archivos";
  exit();
}

// Obtener parámetros
$id_archivo_pendiente = isset($_GET['id_archivo']) ? intval($_GET['id_archivo']) : 0;

if ($id_archivo_pendiente <= 0) {
  http_response_code(400);
  echo "ID de archivo inválido";
  exit();
}

// Incluir archivos necesarios
include '../includes/funcionesGenerales.php';
include '../includes/variables.php';

// Incluir conexión a la base de datos
include '../../sql/conexionMysqliUTF8Dev2.php';

try {
  // Obtener información del archivo
  $query = "SELECT nombre_archivo_original, ruta_archivo_temporal, tipo_archivo FROM $tabla_archivos_pendientes_validacion WHERE id_archivo_pendiente = ? AND activo = 1";
  
  $stmt = $conn->prepare($query);
  if (!$stmt) {
    throw new Exception("Error preparando consulta: " . $conn->error);
  }
  
  $stmt->bind_param("i", $id_archivo_pendiente);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows === 0) {
    http_response_code(404);
    echo "Archivo no encontrado";
    exit();
  }
  
  $archivo = $result->fetch_assoc();
  $stmt->close();
  
  $ruta_archivo = $archivo['ruta_archivo_temporal'];
  $nombre_archivo = $archivo['nombre_archivo_original'];
  $tipo_archivo = $archivo['tipo_archivo'];
  
  // Verificar que el archivo existe
  if (!file_exists($ruta_archivo)) {
    http_response_code(404);
    echo "El archivo no existe en el servidor";
    exit();
  }
  
  // Obtener información del archivo
  $tamano_archivo = filesize($ruta_archivo);
  $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
  
  // Determinar el Content-Type según la extensión
  $content_types = [
    // Documentos
    'pdf' => 'application/pdf',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    
    // Hojas de cálculo
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    
    // Presentaciones
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    
    // Imágenes
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    
    // Archivos de texto
    'txt' => 'text/plain',
    'csv' => 'text/csv',
    'log' => 'text/plain',
    
    // Videos
    'mp4' => 'video/mp4',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
    'wmv' => 'video/x-ms-wmv',
    'flv' => 'video/x-flv',
    'webm' => 'video/webm',
    'mkv' => 'video/x-matroska',
    'm4v' => 'video/x-m4v'
  ];
  
  $content_type = isset($content_types[$extension]) ? $content_types[$extension] : 'application/octet-stream';
  
  // Para archivos de Excel, mostrar página con opciones de visualización
  if (in_array($extension, ['xls', 'xlsx'])) {
    mostrarPaginaExcel($id_archivo_pendiente, $nombre_archivo, $ruta_archivo);
    exit();
  }
  
  // Para archivos de PowerPoint, mostrar página con opciones de visualización
  if (in_array($extension, ['ppt', 'pptx'])) {
    mostrarPaginaPowerPoint($id_archivo_pendiente, $nombre_archivo, $ruta_archivo);
    exit();
  }
  
  // Para archivos de texto, mostrar contenido en modal
  if (in_array($extension, ['txt', 'csv', 'log'])) {
    mostrarContenidoTexto($id_archivo_pendiente, $nombre_archivo, $ruta_archivo);
    exit();
  }
  
  // Para archivos de video, mostrar reproductor
  if (in_array($extension, ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'm4v'])) {
    // Si se solicita el video directamente (para el reproductor)
    if (isset($_GET['directo']) && $_GET['directo'] == '1') {
      // Configurar headers para streaming de video
      header('Content-Type: ' . $content_type);
      header('Content-Length: ' . $tamano_archivo);
      header('Accept-Ranges: bytes');
      header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
      
      // Leer y enviar el archivo
      $archivo_handle = fopen($ruta_archivo, 'rb');
      if ($archivo_handle) {
        while (!feof($archivo_handle)) {
          echo fread($archivo_handle, 8192);
          flush();
        }
        fclose($archivo_handle);
      }
      exit();
    } else {
      // Mostrar reproductor de video
      mostrarReproductorVideo($id_archivo_pendiente, $nombre_archivo, $ruta_archivo);
      exit();
    }
  }
  
  // Configurar headers para visualización
  header('Content-Type: ' . $content_type);
  header('Content-Length: ' . $tamano_archivo);
  header('Content-Disposition: inline; filename="' . $nombre_archivo . '"');
  header('Cache-Control: private, max-age=3600');
  header('Pragma: cache');
  
  // Headers de seguridad para HTTPS
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  
  // Para archivos PDF, agregar headers adicionales
  if ($extension === 'pdf') {
    header('Content-Transfer-Encoding: binary');
    header('Accept-Ranges: bytes');
  }
  
  // Para imágenes, agregar headers de cache
  if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
    header('Cache-Control: public, max-age=86400');
  }
  
  // Limpiar buffer de salida
  if (ob_get_level()) {
    ob_end_clean();
  }
  
  // Leer y enviar el archivo
  $handle = fopen($ruta_archivo, 'rb');
  if ($handle === false) {
    http_response_code(500);
    echo "Error al abrir el archivo";
    exit();
  }
  
  // Enviar el archivo en chunks para archivos grandes
  $chunk_size = 8192; // 8KB por chunk
  while (!feof($handle)) {
    $chunk = fread($handle, $chunk_size);
    if ($chunk === false) {
      break;
    }
    echo $chunk;
    flush();
  }
  
  fclose($handle);
  
} catch (Exception $e) {
  error_log("Error en visualizar_archivo.php: " . $e->getMessage());
  http_response_code(500);
  echo "Error interno del servidor";
} finally {
  if (isset($conn)) {
    $conn->close();
  }
}

/**
 * Mostrar página para archivos Excel
 */
function mostrarPaginaExcel($idArchivo, $nombreArchivo, $rutaArchivo) {
  // Forzar HTTPS para evitar problemas de Mixed Content
  $protocol = 'https';
  $host = $_SERVER['HTTP_HOST'];
  $script_path = dirname($_SERVER['SCRIPT_NAME']);
  $download_url = $protocol . '://' . $host . $script_path . '/descargar_archivo.php?id_archivo=' . $idArchivo;
  
  echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo Excel - ' . htmlspecialchars($nombreArchivo) . '</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1a365d;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            text-align: center;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .file-icon {
            font-size: 48px;
            color: #28a745;
            margin-bottom: 15px;
        }
        .file-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .file-size {
            color: #666;
            font-size: 14px;
        }
        .actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Archivo Excel</h1>
        </div>
        <div class="content">
            <div class="file-info">
                <div class="file-icon">📈</div>
                <div class="file-name">' . htmlspecialchars($nombreArchivo) . '</div>
                <div class="file-size">Archivo de Microsoft Excel</div>
            </div>
            
            <div class="note">
                <strong>Nota:</strong> Los archivos Excel no se pueden visualizar directamente en el navegador. 
                Puede descargar el archivo para abrirlo con Microsoft Excel o Google Sheets.
            </div>
            
            <div class="actions">
                <a href="' . $download_url . '" class="btn btn-success" download="' . htmlspecialchars($nombreArchivo) . '">
                    📥 Descargar Archivo
                </a>
                <button onclick="window.close()" class="btn btn-secondary">
                    ❌ Cerrar
                </button>
            </div>
        </div>
    </div>
</body>
</html>';
}

/**
 * Mostrar página para archivos PowerPoint
 */
function mostrarPaginaPowerPoint($idArchivo, $nombreArchivo, $rutaArchivo) {
  // Forzar HTTPS para evitar problemas de Mixed Content
  $protocol = 'https';
  $host = $_SERVER['HTTP_HOST'];
  $script_path = dirname($_SERVER['SCRIPT_NAME']);
  $download_url = $protocol . '://' . $host . $script_path . '/descargar_archivo.php?id_archivo=' . $idArchivo;
  
  echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo PowerPoint - ' . htmlspecialchars($nombreArchivo) . '</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1a365d;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
            text-align: center;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        .file-icon {
            font-size: 48px;
            color: #dc3545;
            margin-bottom: 15px;
        }
        .file-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .file-size {
            color: #666;
            font-size: 14px;
        }
        .actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Archivo PowerPoint</h1>
        </div>
        <div class="content">
            <div class="file-info">
                <div class="file-icon">📈</div>
                <div class="file-name">' . htmlspecialchars($nombreArchivo) . '</div>
                <div class="file-size">Archivo de Microsoft PowerPoint</div>
            </div>
            
            <div class="note">
                <strong>Nota:</strong> Los archivos PowerPoint no se pueden visualizar directamente en el navegador. 
                Puede descargar el archivo para abrirlo con Microsoft PowerPoint o Google Slides.
            </div>
            
            <div class="actions">
                <a href="' . $download_url . '" class="btn btn-success" download="' . htmlspecialchars($nombreArchivo) . '">
                    📥 Descargar Archivo
                </a>
                <button onclick="window.close()" class="btn btn-secondary">
                    ❌ Cerrar
                </button>
            </div>
        </div>
    </div>
</body>
</html>';
}

/**
 * Mostrar contenido de archivos de texto
 */
function mostrarContenidoTexto($idArchivo, $nombreArchivo, $rutaArchivo) {
  // Forzar HTTPS para evitar problemas de Mixed Content
  $protocol = 'https';
  $host = $_SERVER['HTTP_HOST'];
  $script_path = dirname($_SERVER['SCRIPT_NAME']);
  $download_url = $protocol . '://' . $host . $script_path . '/descargar_archivo.php?id_archivo=' . $idArchivo;
  
  // Leer el contenido del archivo
  $contenido = file_get_contents($rutaArchivo);
  if ($contenido === false) {
    http_response_code(500);
    echo "Error al leer el archivo";
    exit();
  }
  
  // Escapar el contenido para HTML
  $contenido_html = htmlspecialchars($contenido);
  
  echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo de Texto - ' . htmlspecialchars($nombreArchivo) . '</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1a365d;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .close-btn {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .close-btn:hover {
            background: #c53030;
        }
        .content {
            padding: 20px;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-name {
            font-weight: bold;
            color: #333;
        }
        .download-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .download-btn:hover {
            background: #1e7e34;
        }
        .text-content {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
            font-family: "Courier New", monospace;
            font-size: 14px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 ' . htmlspecialchars($nombreArchivo) . '</h1>
            <button class="close-btn" onclick="window.close()">Cerrar</button>
        </div>
        <div class="content">
            <div class="file-info">
                <div class="file-name">' . htmlspecialchars($nombreArchivo) . '</div>
                <a href="' . $download_url . '" class="download-btn" download="' . htmlspecialchars($nombreArchivo) . '">
                    📥 Descargar
                </a>
            </div>
            <div class="text-content">' . $contenido_html . '</div>
        </div>
    </div>
</body>
</html>';
}

/**
 * Mostrar reproductor de video
 */
function mostrarReproductorVideo($idArchivo, $nombreArchivo, $rutaArchivo) {
  // Forzar HTTPS para evitar problemas de Mixed Content
  $protocol = 'https';
  $host = $_SERVER['HTTP_HOST'];
  $script_path = dirname($_SERVER['SCRIPT_NAME']);
  $video_url = $protocol . '://' . $host . $script_path . '/visualizar_archivo.php?id_archivo=' . $idArchivo . '&directo=1';
  $download_url = $protocol . '://' . $host . $script_path . '/descargar_archivo.php?id_archivo=' . $idArchivo;
  
  // Obtener información del archivo
  $tamano_archivo = filesize($rutaArchivo);
  $tamano_mb = round($tamano_archivo / (1024 * 1024), 2);
  
  echo '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reproductor de Video - ' . htmlspecialchars($nombreArchivo) . '</title>
    <style>
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #1a365d;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .close-btn {
            background: #e53e3e;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .close-btn:hover {
            background: #c53030;
        }
        .content {
            padding: 20px;
        }
        .file-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-name {
            font-weight: bold;
            color: #333;
        }
        .file-size {
            color: #666;
            font-size: 14px;
        }
        .download-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        .download-btn:hover {
            background: #1e7e34;
        }
        .video-container {
            background: #000;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .video-player {
            width: 100%;
            height: auto;
            min-height: 400px;
            display: block;
        }
        .video-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .video-info h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
        .video-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
        .controls {
            text-align: center;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .note {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            .content {
                padding: 15px;
            }
            .video-player {
                min-height: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎬 ' . htmlspecialchars($nombreArchivo) . '</h1>
            <button class="close-btn" onclick="window.close()">Cerrar</button>
        </div>
        <div class="content">
            <div class="file-info">
                <div>
                    <div class="file-name">' . htmlspecialchars($nombreArchivo) . '</div>
                    <div class="file-size">Tamaño: ' . $tamano_mb . ' MB</div>
                </div>
                <a href="' . $download_url . '" class="download-btn" download="' . htmlspecialchars($nombreArchivo) . '">
                    📥 Descargar
                </a>
            </div>
            
            <div class="video-container">
                <video class="video-player" controls preload="metadata">
                    <source src="' . $video_url . '" type="video/mp4">
                    <source src="' . $video_url . '" type="video/webm">
                    <source src="' . $video_url . '" type="video/quicktime">
                    <source src="' . $video_url . '" type="video/x-msvideo">
                    <p>Tu navegador no soporta la reproducción de video HTML5.</p>
                </video>
            </div>
            
            <div class="video-info">
                <h3>📋 Información del Video</h3>
                <p><strong>Nombre:</strong> ' . htmlspecialchars($nombreArchivo) . '</p>
                <p><strong>Tamaño:</strong> ' . $tamano_mb . ' MB</p>
                <p><strong>Formato:</strong> ' . strtoupper(pathinfo($nombreArchivo, PATHINFO_EXTENSION)) . '</p>
            </div>
            
            <div class="note">
                <strong>Nota:</strong> Si el video no se reproduce correctamente, puede descargarlo para verlo con un reproductor externo.
                Algunos formatos de video pueden requerir códecs específicos en su navegador.
            </div>
            
            <div class="controls">
                <a href="' . $download_url . '" class="btn btn-primary" download="' . htmlspecialchars($nombreArchivo) . '">
                    📥 Descargar Video
                </a>
                <button onclick="window.close()" class="btn btn-secondary">
                    ❌ Cerrar
                </button>
            </div>
        </div>
    </div>
</body>
</html>';
}
?>
