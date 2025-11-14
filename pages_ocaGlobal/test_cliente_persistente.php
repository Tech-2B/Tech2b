<?php
/**
 * Prueba de persistencia del cliente seleccionado
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba Cliente Persistente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="css/modalHistorialArchivos.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Prueba de Persistencia del Cliente</h1>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Simular Selección de Cliente</h3>
                <button type="button" class="btn btn-primary" onclick="simularSeleccionCliente()">
                    Simular Cliente Seleccionado
                </button>
                <button type="button" class="btn btn-secondary" onclick="limpiarSessionStorage()">
                    Limpiar SessionStorage
                </button>
            </div>
            
            <div class="col-md-6">
                <h3>Verificar SessionStorage</h3>
                <button type="button" class="btn btn-info" onclick="verificarSessionStorage()">
                    Ver SessionStorage
                </button>
                <button type="button" class="btn btn-success" onclick="simularCargaPagina()">
                    Simular Carga de Página
                </button>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Información del Cliente Seleccionado</h3>
                <div id="cliente_seleccionado_info" class="alert alert-info" style="display: none;">
                    <div class="row">
                        <div class="col-md-4">
                            <strong><i class="fa fa-user"></i> Nombre:</strong> <span id="nombre_cliente">Cargando...</span>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fa fa-barcode"></i> Código:</strong> <span id="codigo_cliente">Cargando...</span>
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fa fa-tag"></i> Tipo:</strong> <span id="tipo_cliente">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <h3>Log de Actividades</h3>
                <div id="log" class="alert alert-light" style="height: 200px; overflow-y: auto;">
                    <p>Log de actividades aparecerá aquí...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function log(mensaje) {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            logDiv.innerHTML += `<p>[${timestamp}] ${mensaje}</p>`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function simularSeleccionCliente() {
            // Simular datos de un cliente
            const clienteData = {
                id: 1,
                nombre: 'Empresa ABC S.A. de C.V.',
                codigo: 'CLI-001',
                tipo: 'Corporativo'
            };

            // Guardar en sessionStorage
            sessionStorage.setItem('cliente_seleccionado_id', clienteData.id);
            sessionStorage.setItem('cliente_seleccionado_nombre', clienteData.nombre);
            sessionStorage.setItem('cliente_seleccionado_codigo', clienteData.codigo);
            sessionStorage.setItem('cliente_seleccionado_tipo', clienteData.tipo);

            log(`Cliente seleccionado: ${clienteData.nombre} (${clienteData.codigo})`);
            mostrarClienteSeleccionado(clienteData);
        }

        function limpiarSessionStorage() {
            sessionStorage.removeItem('cliente_seleccionado_id');
            sessionStorage.removeItem('cliente_seleccionado_nombre');
            sessionStorage.removeItem('cliente_seleccionado_codigo');
            sessionStorage.removeItem('cliente_seleccionado_tipo');
            
            log('SessionStorage limpiado');
            document.getElementById('cliente_seleccionado_info').style.display = 'none';
        }

        function verificarSessionStorage() {
            const id = sessionStorage.getItem('cliente_seleccionado_id');
            const nombre = sessionStorage.getItem('cliente_seleccionado_nombre');
            const codigo = sessionStorage.getItem('cliente_seleccionado_codigo');
            const tipo = sessionStorage.getItem('cliente_seleccionado_tipo');

            log(`SessionStorage actual:`);
            log(`- ID: ${id || 'No definido'}`);
            log(`- Nombre: ${nombre || 'No definido'}`);
            log(`- Código: ${codigo || 'No definido'}`);
            log(`- Tipo: ${tipo || 'No definido'}`);
        }

        function simularCargaPagina() {
            log('Simulando carga de página...');
            
            const id = sessionStorage.getItem('cliente_seleccionado_id');
            const nombre = sessionStorage.getItem('cliente_seleccionado_nombre');
            const codigo = sessionStorage.getItem('cliente_seleccionado_codigo');
            const tipo = sessionStorage.getItem('cliente_seleccionado_tipo');

            if (id && nombre) {
                const clienteData = {
                    id: id,
                    nombre: nombre,
                    codigo: codigo || 'N/A',
                    tipo: tipo || 'N/A'
                };
                
                mostrarClienteSeleccionado(clienteData);
                log(`Cliente cargado desde sessionStorage: ${nombre}`);
            } else {
                log('No hay cliente seleccionado en sessionStorage');
                document.getElementById('cliente_seleccionado_info').style.display = 'none';
            }
        }

        function mostrarClienteSeleccionado(datosCliente) {
            const infoDiv = document.getElementById('cliente_seleccionado_info');
            
            if (infoDiv) {
                const nombreCliente = datosCliente.nombre || 'Cliente no disponible';
                const codigoCliente = datosCliente.codigo || 'N/A';
                const tipoCliente = datosCliente.tipo || 'N/A';
                
                infoDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            <strong><i class="fa fa-user"></i> Nombre:</strong> ${nombreCliente}
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fa fa-barcode"></i> Código:</strong> ${codigoCliente}
                        </div>
                        <div class="col-md-4">
                            <strong><i class="fa fa-tag"></i> Tipo:</strong> ${tipoCliente}
                        </div>
                    </div>
                `;
                infoDiv.style.display = 'block';
            }
        }

        // Verificar al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            log('Página cargada');
            simularCargaPagina();
        });
    </script>
</body>
</html>
