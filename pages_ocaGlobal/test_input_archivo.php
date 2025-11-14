<?php
/**
 * Prueba específica para el input de archivo mejorado
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Input de Archivo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/modalCargarArchivo.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Prueba del Input de Archivo Mejorado</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Versión Original (Problemática)</h3>
                <div class="form-group">
                    <label class="form-label">Seleccionar Archivo (Original):</label>
                    <div class="input-archivo-container" style="border: 2px dashed #00a7b5; padding: 20px; text-align: center; position: relative;">
                        <input type="file" id="archivo_original" name="archivo_original" accept=".pdf,.docx,.doc,.xlsx,.xls,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp,.txt,.csv,.log,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.m4v" style="position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer;">
                        <p style="margin: 0; font-weight: 600; color: #00a7b5;">Haz clic aquí para seleccionar un archivo</p>
                        <p style="font-size: 0.85rem; color: #6c757d; margin-top: 8px;">Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, archivos de texto, videos</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <h3>Versión Mejorada (Nueva)</h3>
                <div class="form-group">
                    <label for="archivo_mejorado" class="form-label">Seleccionar Archivo (Mejorado):</label>
                    <div class="input-archivo-container">
                        <input type="file" id="archivo_mejorado" name="archivo_mejorado" accept=".pdf,.docx,.doc,.xlsx,.xls,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp,.txt,.csv,.log,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.m4v" class="form-control-file">
                        <div class="input-archivo-info">
                            <i class="fa fa-info-circle"></i>
                            Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, archivos de texto, videos. Tamaño máximo: 100MB
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Resultados de las Pruebas:</h3>
                <div id="resultados" class="alert alert-info">
                    <strong>Instrucciones:</strong><br>
                    1. Prueba hacer clic en ambos inputs de archivo<br>
                    2. Observa cuál responde más rápido<br>
                    3. Verifica que ambos abran el diálogo de selección de archivos<br>
                    4. Selecciona un archivo y observa la respuesta
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar información del archivo seleccionado
        function mostrarInfoArchivo(inputId, containerId) {
            const input = document.getElementById(inputId);
            const container = document.getElementById(containerId);
            
            input.addEventListener('change', function(e) {
                const archivo = e.target.files[0];
                
                if (archivo) {
                    const tamanoMB = (archivo.size / (1024 * 1024)).toFixed(2);
                    container.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i>
                            <strong>Archivo seleccionado:</strong> ${archivo.name}<br>
                            <strong>Tamaño:</strong> ${tamanoMB} MB<br>
                            <strong>Tipo:</strong> ${archivo.type}<br>
                            <strong>Última modificación:</strong> ${new Date(archivo.lastModified).toLocaleString()}
                        </div>
                    `;
                } else {
                    container.innerHTML = '<div class="alert alert-warning">No se seleccionó ningún archivo</div>';
                }
            });
        }

        // Función para medir tiempo de respuesta
        function medirTiempoRespuesta(inputId, nombre) {
            const input = document.getElementById(inputId);
            let tiempoInicio;
            
            input.addEventListener('click', function() {
                tiempoInicio = performance.now();
                console.log(`Inicio de clic en ${nombre}:`, tiempoInicio);
            });
            
            input.addEventListener('focus', function() {
                if (tiempoInicio) {
                    const tiempoRespuesta = performance.now() - tiempoInicio;
                    console.log(`Tiempo de respuesta ${nombre}:`, tiempoRespuesta.toFixed(2), 'ms');
                    
                    // Mostrar en la página
                    const resultados = document.getElementById('resultados');
                    resultados.innerHTML += `<br><strong>${nombre}:</strong> ${tiempoRespuesta.toFixed(2)} ms de tiempo de respuesta`;
                }
            });
        }

        // Configurar eventos
        mostrarInfoArchivo('archivo_original', 'resultados');
        mostrarInfoArchivo('archivo_mejorado', 'resultados');
        
        medirTiempoRespuesta('archivo_original', 'Input Original');
        medirTiempoRespuesta('archivo_mejorado', 'Input Mejorado');

        // Mostrar información del navegador
        document.addEventListener('DOMContentLoaded', function() {
            const info = `
                <div class="alert alert-info">
                    <strong>Información del Navegador:</strong><br>
                    User Agent: ${navigator.userAgent}<br>
                    Plataforma: ${navigator.platform}<br>
                    Idioma: ${navigator.language}
                </div>
            `;
            document.getElementById('resultados').innerHTML += info;
        });
    </script>
</body>
</html>
