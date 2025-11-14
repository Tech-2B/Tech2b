<?php
/**
 * Prueba del modal de cargar archivos con funcionalidad de crear carpetas
 */

// Incluir archivos necesarios
include 'includes/funcionesGenerales.php';
include 'includes/variables.php';
include 'includes/funcionesGoogleDrive.php';

// Incluir conexión a la base de datos
include '../sql/conexionMysqliUTF8Dev2.php';

// Incluir Google Drive API
require_once '../phpLibraries/googleApiClient_8_0/vendor/autoload.php';

// Declaraciones use para Google Drive API
use Google\Client;
use Google\Service\Drive;

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Modal Cargar Archivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/modalCargarArchivo.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Prueba del Modal de Cargar Archivos</h1>
        
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal_cargar_archivo">
            Abrir Modal de Cargar Archivo
        </button>

        <!-- Modal para cargar archivos -->
        <div class="modal modal-cargar-archivo" id="modal_cargar_archivo" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Cargar Archivo</h2>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="formulario_cargar_archivo" enctype="multipart/form-data" autocomplete="off">
                            <input type="hidden" id="id_registro_archivo" name="id_registro" value="1">
                            <input type="hidden" id="id_cliente_archivo" name="id_cliente" value="1">
                            <input type="hidden" id="id_plan_accion_archivo" name="id_plan_accion" value="1">
                            
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
                                        <button type="button" class="btn btn-success" id="btn_crear_carpeta" onclick="crearNuevaCarpeta()">
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
                                    <option value="carpeta1">Carpeta de Prueba 1</option>
                                    <option value="carpeta2">Carpeta de Prueba 2</option>
                                </select>
                            </div>

                            <!-- Input de archivo -->
                            <div class="form-group">
                                <label class="form-label">Seleccionar Archivo:</label>
                                <div class="input-archivo-container">
                                    <input type="file" id="archivo_subir" name="archivo_subir" accept=".pdf,.docx,.doc,.xlsx,.xls,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp,.txt,.csv,.log,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.m4v" required>
                                    <p class="input-archivo-label">Haz clic aquí para seleccionar un archivo</p>
                                    <p class="input-archivo-info">Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, archivos de texto, videos. Tamaño máximo: 100MB</p>
                                </div>
                            </div>

                            <!-- Textarea de comentarios -->
                            <div class="form-group textarea-comentario">
                                <label for="comentario_archivo" class="form-label">Comentario (opcional):</label>
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
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para toggle de nueva carpeta
        function toggleNuevaCarpeta(mostrar) {
            const divNuevaCarpeta = document.getElementById('div_nombre_nueva_carpeta');
            const selectCarpeta = document.getElementById('select_carpeta_destino');
            const inputNombreCarpeta = document.getElementById('nombre_nueva_carpeta');
            
            if (mostrar) {
                divNuevaCarpeta.style.display = 'block';
                selectCarpeta.required = false;
                inputNombreCarpeta.required = true;
            } else {
                divNuevaCarpeta.style.display = 'none';
                selectCarpeta.required = true;
                inputNombreCarpeta.required = false;
                inputNombreCarpeta.value = '';
            }
        }

        // Función para crear nueva carpeta (simulada)
        function crearNuevaCarpeta() {
            const nombreCarpeta = document.getElementById('nombre_nueva_carpeta').value.trim();
            
            if (!nombreCarpeta) {
                alert('Debe ingresar un nombre para la carpeta');
                return;
            }

            // Simular creación exitosa
            alert('Carpeta "' + nombreCarpeta + '" creada exitosamente');
            
            // Agregar la nueva carpeta al select
            const selectCarpeta = document.getElementById('select_carpeta_destino');
            const option = document.createElement('option');
            option.value = 'nueva_' + Date.now();
            option.textContent = nombreCarpeta;
            selectCarpeta.appendChild(option);
            selectCarpeta.value = option.value;
            
            // Limpiar y ocultar
            document.getElementById('nombre_nueva_carpeta').value = '';
            document.getElementById('crear_nueva_carpeta').checked = false;
            toggleNuevaCarpeta(false);
        }

        // Event listeners
        document.getElementById('crear_nueva_carpeta').addEventListener('change', function(e) {
            toggleNuevaCarpeta(e.target.checked);
        });

        // Mostrar nombre del archivo seleccionado
        document.getElementById('archivo_subir').addEventListener('change', function(e) {
            const archivo = e.target.files[0];
            if (archivo) {
                const label = document.querySelector('.input-archivo-label');
                label.textContent = 'Archivo seleccionado: ' + archivo.name;
                label.style.color = '#28a745';
            }
        });
    </script>
</body>
</html>
