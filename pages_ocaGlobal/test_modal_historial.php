<?php
/**
 * Prueba del modal de historial de archivos mejorado
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Modal Historial de Archivos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/modalHistorialArchivos.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Prueba del Modal de Historial de Archivos</h1>
        
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modal_historial_archivos">
            Abrir Modal de Historial
        </button>

        <!-- Modal para historial de archivos -->
        <div class="modal modal-historial-archivos" id="modal_historial_archivos" tabindex="-1">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Historial de Archivos</h2>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table id="tabla_historial_archivos" class="table w-100 thead-primary">
                                <thead>
                                    <tr>
                                        <th>Nombre del Archivo</th>
                                        <th>Comentarios</th>
                                        <th>Fecha de Subida</th>
                                        <th>Persona que Subió</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tabla_historial_archivos_body">
                                    <!-- Datos de prueba -->
                                    <tr>
                                        <td class="nombre-archivo">Documento_Contrato_2024.pdf</td>
                                        <td class="comentarios">Contrato de servicios para el proyecto principal</td>
                                        <td class="fecha-subida">15/01/2024 14:30</td>
                                        <td class="persona-subio">Juan Pérez</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-accion-historial btn-danger" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="nombre-archivo">Presentacion_Proyecto.pptx</td>
                                        <td class="comentarios">Presentación para la reunión del cliente</td>
                                        <td class="fecha-subida">14/01/2024 09:15</td>
                                        <td class="persona-subio">María García</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-accion-historial btn-danger" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="nombre-archivo">Plan_Trabajo_2024.xlsx</td>
                                        <td class="comentarios">Sin comentario</td>
                                        <td class="fecha-subida">13/01/2024 16:45</td>
                                        <td class="persona-subio">Carlos López</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-accion-historial btn-danger" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="nombre-archivo">Imagen_Logo_Empresa.png</td>
                                        <td class="comentarios">Logo actualizado para el proyecto</td>
                                        <td class="fecha-subida">12/01/2024 11:20</td>
                                        <td class="persona-subio">Ana Martínez</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-accion-historial btn-danger" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="nombre-archivo">Manual_Usuario_Final.docx</td>
                                        <td class="comentarios">Documentación completa para el usuario final del sistema</td>
                                        <td class="fecha-subida">11/01/2024 13:10</td>
                                        <td class="persona-subio">Roberto Silva</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
                                                    <i class="fa fa-external-link"></i>
                                                </a>
                                                <a href="#" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
                                                    <i class="fa fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-accion-historial btn-danger" title="Eliminar">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simular funcionalidad de los botones
        document.addEventListener('DOMContentLoaded', function() {
            // Botones de ver en Drive
            document.querySelectorAll('.btn-info').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Abriendo archivo en Google Drive...');
                });
            });

            // Botones de descarga
            document.querySelectorAll('.btn-success').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    alert('Iniciando descarga del archivo...');
                });
            });

            // Botones de eliminar
            document.querySelectorAll('.btn-danger').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('¿Estás seguro de que quieres eliminar este archivo?')) {
                        alert('Archivo eliminado (simulado)');
                        // Aquí se eliminaría la fila de la tabla
                        btn.closest('tr').remove();
                    }
                });
            });
        });
    </script>
</body>
</html>
