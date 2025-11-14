<?php
/**
 * Prueba de funcionalidad de edición de planes de acción
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Edición Planes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/modalHistorialArchivos.css" rel="stylesheet">
    <style>
        .test-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 20px;
        }
        .test-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-button {
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="text-center mb-4">Prueba de Edición de Planes de Acción</h1>
        
        <div class="test-section">
            <h3>Simulación de Datos de Prueba</h3>
            <p>Esta página simula la funcionalidad de edición inline para las descripciones de planes de acción.</p>
            
            <div class="row">
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary test-button" onclick="simularDatos()">
                        <i class="fa fa-database"></i> Cargar Datos de Prueba
                    </button>
                    <button type="button" class="btn btn-success test-button" onclick="probarEdicion()">
                        <i class="fa fa-edit"></i> Probar Edición
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-warning test-button" onclick="probarGuardado()">
                        <i class="fa fa-save"></i> Probar Guardado
                    </button>
                    <button type="button" class="btn btn-info test-button" onclick="probarCancelacion()">
                        <i class="fa fa-times"></i> Probar Cancelación
                    </button>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h3>Tabla de Prueba</h3>
            <div class="table-responsive">
                <table id="tabla_prueba" class="table table-striped table-bordered" style="table-layout: fixed; width: 100%; min-width: 1200px; border-collapse: separate; border-spacing: 0;">
                    <thead class="thead-dark">
                        <tr>
                            <th style="width: 7%;">ID</th>
                            <th style="width: 16%;">Área de Oportunidad</th>
                            <th style="width: 16%;">Plan de Acción</th>
                            <th style="width: 16%;">Tópicos</th>
                            <th style="width: 15%;">Entregables</th>
                            <th style="width: 12%;">Periodicidades</th>
                            <th style="width: 10%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tabla_prueba_body">
                        <!-- Los datos se cargarán dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="test-section">
            <h3>Log de Eventos</h3>
            <div id="log_eventos" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; max-height: 300px; overflow-y: auto;">
                <p class="text-muted">Los eventos de edición aparecerán aquí...</p>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Datos de prueba
        const datosPrueba = [
            {
                id_registro: 1,
                descripcion_area_oportunidad: "Gestión de Proyectos",
                descripcion_plan_accion: "Implementar metodología ágil",
                descripcion_topico: "Scrum\nKanban\nRetrospectivas",
                descripcion_entregable: "Manual de procedimientos",
                descripcion_periodicidad: "Semanal\nMensual"
            },
            {
                id_registro: 2,
                descripcion_area_oportunidad: "Recursos Humanos",
                descripcion_plan_accion: "Capacitación del personal",
                descripcion_topico: "Liderazgo\nComunicación\nTrabajo en equipo",
                descripcion_entregable: "Programa de capacitación",
                descripcion_periodicidad: "Trimestral"
            }
        ];

        function logEvento(mensaje) {
            const log = document.getElementById('log_eventos');
            const timestamp = new Date().toLocaleTimeString();
            log.innerHTML += `<div><strong>[${timestamp}]</strong> ${mensaje}</div>`;
            log.scrollTop = log.scrollHeight;
        }

        function simularDatos() {
            const tbody = document.getElementById('tabla_prueba_body');
            tbody.innerHTML = '';

            datosPrueba.forEach(dato => {
                const row = document.createElement('tr');
                row.setAttribute('data-id', dato.id_registro);
                row.innerHTML = `
                    <td>${dato.id_registro}</td>
                    <td>
                        <div class="campo-editable" data-campo="descripcion_area_oportunidad" data-id="${dato.id_registro}">
                            <span class="valor-campo">${dato.descripcion_area_oportunidad}</span>
                            <textarea class="input-editable form-control" style="display: none;" rows="2">${dato.descripcion_area_oportunidad}</textarea>
                        </div>
                    </td>
                    <td>
                        <div class="campo-editable" data-campo="descripcion_plan_accion" data-id="${dato.id_registro}">
                            <span class="valor-campo">${dato.descripcion_plan_accion}</span>
                            <textarea class="input-editable form-control" style="display: none;" rows="2">${dato.descripcion_plan_accion}</textarea>
                        </div>
                    </td>
                    <td>
                        <div class="campo-editable" data-campo="descripcion_topico" data-id="${dato.id_registro}">
                            <span class="valor-campo">${dato.descripcion_topico}</span>
                            <textarea class="input-editable form-control" style="display: none;" rows="3">${dato.descripcion_topico}</textarea>
                        </div>
                    </td>
                    <td>
                        <div class="campo-editable" data-campo="descripcion_entregable" data-id="${dato.id_registro}">
                            <span class="valor-campo">${dato.descripcion_entregable}</span>
                            <textarea class="input-editable form-control" style="display: none;" rows="2">${dato.descripcion_entregable}</textarea>
                        </div>
                    </td>
                    <td>
                        <div class="campo-editable" data-campo="descripcion_periodicidad" data-id="${dato.id_registro}">
                            <span class="valor-campo">${dato.descripcion_periodicidad}</span>
                            <textarea class="input-editable form-control" style="display: none;" rows="2">${dato.descripcion_periodicidad}</textarea>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-accion btn-editar" 
                                onclick="toggleEdicion(${dato.id_registro})" 
                                title="Editar Descripciones">
                            <i class="fa fa-edit"></i>
                            <span class="btn-text">Editar</span>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            logEvento('Datos de prueba cargados en la tabla');
        }

        function toggleEdicion(idRegistro) {
            const fila = document.querySelector(`tr[data-id="${idRegistro}"]`);
            if (!fila) return;

            const botonEditar = fila.querySelector('.btn-editar');
            const camposEditables = fila.querySelectorAll('.campo-editable');
            
            if (botonEditar.classList.contains('editando')) {
                // Guardar cambios
                guardarCambios(idRegistro, camposEditables);
            } else {
                // Activar edición
                activarEdicion(idRegistro, camposEditables, botonEditar);
            }
        }

        function activarEdicion(idRegistro, camposEditables, botonEditar) {
            camposEditables.forEach(campo => {
                const span = campo.querySelector('.valor-campo');
                const textarea = campo.querySelector('.input-editable');
                
                if (span && textarea) {
                    span.style.display = 'none';
                    textarea.style.display = 'block';
                    
                    // Ajustar altura del textarea al contenido
                    ajustarAlturaTextarea(textarea);
                    
                    // Agregar evento para ajustar altura mientras se escribe
                    textarea.addEventListener('input', () => ajustarAlturaTextarea(textarea));
                    
                    textarea.focus();
                }
            });

            // Cambiar botón a modo guardar
            botonEditar.classList.add('editando');
            botonEditar.innerHTML = '<i class="fa fa-save"></i><span class="btn-text">Guardar</span>';
            botonEditar.title = 'Guardar Cambios';

            logEvento(`Modo edición activado para registro ${idRegistro}`);
        }

        function guardarCambios(idRegistro, camposEditables) {
            const cambios = {};
            let hayCambios = false;

            // Recopilar cambios
            camposEditables.forEach(campo => {
                const nombreCampo = campo.dataset.campo;
                const textarea = campo.querySelector('.input-editable');
                const valorOriginal = campo.querySelector('.valor-campo').textContent;
                const valorNuevo = textarea.value.trim();

                if (valorNuevo !== valorOriginal) {
                    cambios[nombreCampo] = valorNuevo;
                    hayCambios = true;
                }
            });

            if (!hayCambios) {
                Swal.fire({
                    icon: "info",
                    title: "Sin cambios",
                    text: "No se detectaron cambios para guardar",
                    timer: 2000,
                    showConfirmButton: false,
                });
                cancelarEdicion(idRegistro);
                return;
            }

            // Simular guardado exitoso
            Swal.fire({
                icon: "success",
                title: "Cambios guardados",
                text: "Las descripciones se actualizaron correctamente",
                timer: 2000,
                showConfirmButton: false,
            });

            // Actualizar valores en la interfaz
            camposEditables.forEach(campo => {
                const nombreCampo = campo.dataset.campo;
                const span = campo.querySelector('.valor-campo');
                const textarea = campo.querySelector('.input-editable');
                
                if (cambios[nombreCampo] !== undefined) {
                    span.textContent = cambios[nombreCampo];
                }
                
                span.style.display = 'block';
                textarea.style.display = 'none';
            });

            cancelarEdicion(idRegistro);
            logEvento(`Cambios guardados para registro ${idRegistro}: ${JSON.stringify(cambios)}`);
        }

        function cancelarEdicion(idRegistro) {
            const fila = document.querySelector(`tr[data-id="${idRegistro}"]`);
            if (!fila) return;

            const botonEditar = fila.querySelector('.btn-editar');
            const camposEditables = fila.querySelectorAll('.campo-editable');
            
            camposEditables.forEach(campo => {
                const span = campo.querySelector('.valor-campo');
                const textarea = campo.querySelector('.input-editable');
                
                if (span && textarea) {
                    // Restaurar valor original
                    textarea.value = span.textContent;
                    span.style.display = 'block';
                    textarea.style.display = 'none';
                }
            });

            // Restaurar botón
            botonEditar.classList.remove('editando');
            botonEditar.innerHTML = '<i class="fa fa-edit"></i><span class="btn-text">Editar</span>';
            botonEditar.title = 'Editar Descripciones';

            logEvento(`Edición cancelada para registro ${idRegistro}`);
        }

        function probarEdicion() {
            logEvento('Iniciando prueba de edición...');
            simularDatos();
            setTimeout(() => {
                toggleEdicion(1);
                logEvento('Prueba de edición completada');
            }, 500);
        }

        function probarGuardado() {
            logEvento('Iniciando prueba de guardado...');
            simularDatos();
            setTimeout(() => {
                toggleEdicion(1);
                setTimeout(() => {
                    const textarea = document.querySelector('tr[data-id="1"] .campo-editable textarea');
                    if (textarea) {
                        textarea.value = 'Valor modificado para prueba';
                        toggleEdicion(1);
                    }
                }, 1000);
            }, 500);
        }

        function probarCancelacion() {
            logEvento('Iniciando prueba de cancelación...');
            simularDatos();
            setTimeout(() => {
                toggleEdicion(1);
                setTimeout(() => {
                    const textarea = document.querySelector('tr[data-id="1"] .campo-editable textarea');
                    if (textarea) {
                        textarea.value = 'Valor que se cancelará';
                        setTimeout(() => {
                            cancelarEdicion(1);
                            logEvento('Prueba de cancelación completada');
                        }, 1000);
                    }
                }, 1000);
            }, 500);
        }

        // Función para ajustar altura del textarea automáticamente
        function ajustarAlturaTextarea(textarea) {
            // Resetear altura para obtener el scrollHeight correcto
            textarea.style.height = 'auto';
            
            // Calcular la altura necesaria basada en el contenido
            const scrollHeight = textarea.scrollHeight;
            const minHeight = 60; // Altura mínima
            const maxHeight = 200; // Altura máxima para evitar textareas muy grandes
            
            // Establecer la altura calculada, respetando los límites
            const nuevaAltura = Math.max(minHeight, Math.min(scrollHeight, maxHeight));
            textarea.style.height = nuevaAltura + 'px';
            
            // Si el contenido excede la altura máxima, permitir scroll solo en ese caso
            if (scrollHeight > maxHeight) {
                textarea.style.overflowY = 'auto';
            } else {
                textarea.style.overflowY = 'hidden';
            }
        }

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            logEvento('Página de prueba cargada');
        });
    </script>
</body>
</html>
