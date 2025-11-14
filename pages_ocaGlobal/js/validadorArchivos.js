/**
 * JavaScript para el Validador de Archivos
 * Permite a los Administradores validar archivos subidos por Colaboradores
 */

class ValidadorArchivos {
  constructor() {
    this.archivoActual = null;
    this.tabla = null;
    this.init();
  }

  init() {
    this.configurarEventos();
    this.cargarClientes();
    this.cargarArchivosPendientes();
    // DataTable se inicializará cuando se carguen los datos
  }

  /**
   * Configurar eventos del formulario y botones
   */
  configurarEventos() {
    // Evento para el filtro de estado
    const filtroEstado = document.getElementById('filtro_estado');
    if (filtroEstado) {
      filtroEstado.addEventListener('change', () => this.cargarArchivosPendientes());
    }

    // Evento para el filtro de cliente
    const filtroCliente = document.getElementById('filtro_cliente');
    if (filtroCliente) {
      filtroCliente.addEventListener('change', () => this.cargarArchivosPendientes());
    }

    // Evento para el modal de validación
    $('#modal_validar_archivo').on('hidden.bs.modal', () => {
      this.archivoActual = null;
      document.getElementById('comentario_validacion').value = '';
    });
  }

  /**
   * Configurar DataTable
   */
  configurarDataTable() {
    try {
      if ($.fn.DataTable && $('#tabla_archivos_pendientes').length > 0) {
        this.tabla = $('#tabla_archivos_pendientes').DataTable({
          language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
              "sFirst": "Primero",
              "sLast": "Último",
              "sNext": "Siguiente",
              "sPrevious": "Anterior"
            },
            "oAria": {
              "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
              "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
          },
          responsive: true,
          pageLength: 25,
          order: [[4, 'desc']], // Ordenar por fecha descendente
          columnDefs: [
            {
              targets: -1, // Columna de acciones
              orderable: false,
              searchable: false,
            }
          ]
        });
      }
    } catch (error) {
      console.error('Error inicializando DataTable:', error);
      // Si DataTables falla, continuar sin él
      this.tabla = null;
    }
  }

  /**
   * Cargar lista de clientes para el filtro
   */
  async cargarClientes() {
    try {
      const response = await fetch('ajax/obtener_clientes.php');
      const data = await response.json();

      if (data.success) {
        const selectCliente = document.getElementById('filtro_cliente');
        selectCliente.innerHTML = '<option value="">Todos los clientes</option>';
        
        data.data.forEach(cliente => {
          const option = document.createElement('option');
          option.value = cliente.id_cliente;
          option.textContent = cliente.nombre_cliente;
          selectCliente.appendChild(option);
        });
      }
    } catch (error) {
      console.error('Error cargando clientes:', error);
    }
  }

  /**
   * Cargar archivos pendientes
   */
  async cargarArchivosPendientes() {
    try {
      this.mostrarCargando(true, "Cargando archivos...", "Obteniendo lista de archivos pendientes");

      const filtroEstado = document.getElementById('filtro_estado').value;
      const filtroCliente = document.getElementById('filtro_cliente').value;
      const filtroFechaDesde = document.getElementById('filtro_fecha_desde').value;
      const filtroFechaHasta = document.getElementById('filtro_fecha_hasta').value;

      let url = `ajax/obtener_archivos_pendientes.php?estado=${filtroEstado}`;
      if (filtroCliente) url += `&id_cliente=${filtroCliente}`;
      if (filtroFechaDesde) url += `&fecha_desde=${filtroFechaDesde}`;
      if (filtroFechaHasta) url += `&fecha_hasta=${filtroFechaHasta}`;

      const response = await fetch(url);
      const data = await response.json();

      if (data.success) {
        this.mostrarArchivos(data.data);
        this.actualizarContadorPendientes(data.data);
      } else {
        this.mostrarError("Error", data.message || "No se pudieron cargar los archivos");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error cargando archivos:", error);
      this.mostrarError("Error de conexión", "No se pudieron cargar los archivos");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar archivos en la tabla
   */
  mostrarArchivos(archivos) {
    const tbody = document.getElementById('tabla_archivos_pendientes_body');
    
    if (archivos.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-muted">
            <i class="fa fa-folder-open fa-2x mb-2"></i>
            <p>No hay archivos para mostrar</p>
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = '';
    archivos.forEach(archivo => {
      const row = this.generarFilaArchivo(archivo);
      tbody.appendChild(row);
    });

    // Inicializar DataTable solo si no existe
    if (!this.tabla) {
      this.configurarDataTable();
    } else if (this.tabla) {
      // Si ya existe, solo limpiar y recargar datos
      try {
        this.tabla.clear();
        this.tabla.rows.add($(tbody).find('tr'));
        this.tabla.draw();
      } catch (error) {
        console.error('Error actualizando DataTable:', error);
        // Si hay error, reinicializar
        this.tabla.destroy();
        this.tabla = null;
        this.configurarDataTable();
      }
    }
  }

  /**
   * Generar fila de archivo para la tabla
   */
  generarFilaArchivo(archivo) {
    const row = document.createElement('tr');
    row.className = 'fade-in';
    
    const estadoClass = `estado-${archivo.estado_validacion}`;
    const estadoText = archivo.estado_validacion.charAt(0).toUpperCase() + archivo.estado_validacion.slice(1);
    
    row.innerHTML = `
      <td>
        <div class="info-archivo">
          <i class="fa ${archivo.icono_tipo.icono} ${archivo.icono_tipo.clase} icono-tipo-archivo"></i>
          <div class="detalles-archivo">
            <div class="nombre-archivo">${archivo.nombre_archivo_original}</div>
            ${archivo.comentario ? `<div class="comentario-archivo">"${archivo.comentario}"</div>` : ''}
          </div>
        </div>
      </td>
      <td>${archivo.nombre_cliente || 'N/A'}</td>
      <td>${archivo.descripcion_plan_accion || 'N/A'}</td>
      <td>${archivo.nombre_usuario_subio}</td>
      <td>${archivo.fecha_subida_formateada}</td>
      <td><span class="badge badge-info">${archivo.tamano_formateado}</span></td>
      <td><span class="badge-estado ${archivo.estado_validacion}">${estadoText}</span></td>
      <td>
        <div class="btn-group" role="group">
          ${this.generarBotonesAccion(archivo)}
        </div>
      </td>
    `;
    
    return row;
  }

  /**
   * Generar botones de acción según el estado del archivo
   */
  generarBotonesAccion(archivo) {
    let botones = '';
    
    if (archivo.estado_validacion === 'pendiente') {
      botones += `
        <button type="button" class="btn btn-sm btn-outline-primary btn-validar" 
                onclick="validador.verArchivo(${archivo.id_archivo_pendiente})" 
                title="Ver archivo">
          <i class="fa fa-eye"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-success btn-validar" 
                onclick="validador.abrirModalValidacion(${archivo.id_archivo_pendiente})" 
                title="Validar archivo">
          <i class="fa fa-check"></i>
        </button>
      `;
    } else {
      botones += `
        <button type="button" class="btn btn-sm btn-outline-info btn-validar" 
                onclick="validador.verDetallesValidacion(${archivo.id_archivo_pendiente})" 
                title="Ver detalles">
          <i class="fa fa-info-circle"></i>
        </button>
      `;
    }
    
    return botones;
  }

  /**
   * Actualizar contador de archivos pendientes
   */
  actualizarContadorPendientes(archivos) {
    const pendientes = archivos.filter(archivo => archivo.estado_validacion === 'pendiente');
    const contador = document.getElementById('numero_pendientes');
    if (contador) {
      contador.textContent = pendientes.length;
    }
  }

  /**
   * Abrir modal de validación
   */
  async abrirModalValidacion(idArchivoPendiente) {
    try {
      this.mostrarCargando(true, "Cargando archivo...", "Obteniendo información del archivo");

      // Buscar el archivo en la tabla actual
      const response = await fetch(`ajax/obtener_archivos_pendientes.php?estado=pendiente`);
      const data = await response.json();

      if (data.success) {
        const archivo = data.data.find(a => a.id_archivo_pendiente == idArchivoPendiente);
        if (archivo) {
          this.archivoActual = archivo;
          this.mostrarInfoArchivo(archivo);
          $('#modal_validar_archivo').modal('show');
        } else {
          this.mostrarError("Error", "Archivo no encontrado");
        }
      } else {
        this.mostrarError("Error", data.message || "No se pudo cargar la información del archivo");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error abriendo modal:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar la información del archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar información del archivo en el modal
   */
  mostrarInfoArchivo(archivo) {
    const container = document.getElementById('info_archivo_validar');
    
    container.innerHTML = `
      <div class="archivo-info">
        <i class="fa ${archivo.icono_tipo.icono} ${archivo.icono_tipo.clase} icono"></i>
        <div class="detalles">
          <h6>${archivo.nombre_archivo_original}</h6>
          <div class="meta">
            <strong>Cliente:</strong> ${archivo.nombre_cliente || 'N/A'}<br>
            <strong>Plan de Acción:</strong> ${archivo.descripcion_plan_accion || 'N/A'}<br>
            <strong>Subido por:</strong> ${archivo.nombre_usuario_subio}<br>
            <strong>Fecha:</strong> ${archivo.fecha_subida_formateada}<br>
            <strong>Tamaño:</strong> ${archivo.tamano_formateado}
          </div>
          ${archivo.comentario ? `
            <div class="comentario">
              <strong>Comentario del colaborador:</strong><br>
              "${archivo.comentario}"
            </div>
          ` : ''}
        </div>
      </div>
    `;
  }

  /**
   * Aprobar archivo
   */
  async aprobarArchivo() {
    if (!this.archivoActual) return;

    const comentario = document.getElementById('comentario_validacion').value;

    try {
      this.mostrarCargando(true, "Aprobando archivo...", "Subiendo archivo a Google Drive");

      const formData = new FormData();
      formData.append('id_archivo_pendiente', this.archivoActual.id_archivo_pendiente);
      formData.append('accion', 'aprobar');
      formData.append('comentario_validacion', comentario);

      const response = await fetch('ajax/validar_archivo_pendiente.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        this.mostrarExito("Archivo Aprobado", "El archivo ha sido aprobado y subido a Google Drive exitosamente");
        $('#modal_validar_archivo').modal('hide');
        this.cargarArchivosPendientes();
      } else {
        this.mostrarError("Error", data.message || "No se pudo aprobar el archivo");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error aprobando archivo:", error);
      this.mostrarError("Error de conexión", "No se pudo aprobar el archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Rechazar archivo
   */
  async rechazarArchivo() {
    if (!this.archivoActual) return;

    const comentario = document.getElementById('comentario_validacion').value;

    // Confirmar rechazo
    const confirmacion = await Swal.fire({
      title: '¿Rechazar archivo?',
      text: '¿Estás seguro de que quieres rechazar este archivo? Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, rechazar',
      cancelButtonText: 'Cancelar'
    });

    if (!confirmacion.isConfirmed) return;

    try {
      this.mostrarCargando(true, "Rechazando archivo...", "Procesando rechazo del archivo");

      const formData = new FormData();
      formData.append('id_archivo_pendiente', this.archivoActual.id_archivo_pendiente);
      formData.append('accion', 'rechazar');
      formData.append('comentario_validacion', comentario);

      const response = await fetch('ajax/validar_archivo_pendiente.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        this.mostrarExito("Archivo Rechazado", "El archivo ha sido rechazado exitosamente");
        $('#modal_validar_archivo').modal('hide');
        this.cargarArchivosPendientes();
      } else {
        this.mostrarError("Error", data.message || "No se pudo rechazar el archivo");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error rechazando archivo:", error);
      this.mostrarError("Error de conexión", "No se pudo rechazar el archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Ver archivo (descargar temporal)
   */
  async verArchivo(idArchivoPendiente) {
    try {
      // Implementar descarga del archivo temporal
      window.open(`ajax/descargar_archivo_temporal.php?id=${idArchivoPendiente}`, '_blank');
    } catch (error) {
      console.error("Error viendo archivo:", error);
      this.mostrarError("Error", "No se pudo abrir el archivo");
    }
  }

  /**
   * Ver detalles de validación
   */
  verDetallesValidacion(idArchivoPendiente) {
    // Implementar modal de detalles
    this.mostrarInfo("Detalles de Validación", "Funcionalidad en desarrollo");
  }

  /**
   * Filtrar archivos
   */
  filtrarArchivos() {
    this.cargarArchivosPendientes();
  }

  /**
   * Limpiar filtros
   */
  limpiarFiltros() {
    document.getElementById('filtro_estado').value = 'pendiente';
    document.getElementById('filtro_cliente').value = '';
    document.getElementById('filtro_fecha_desde').value = '';
    document.getElementById('filtro_fecha_hasta').value = '';
    this.cargarArchivosPendientes();
  }

  /**
   * Mostrar mensaje de carga
   */
  mostrarCargando(mostrar, titulo = "Cargando...", mensaje = "Por favor espera") {
    if (mostrar) {
      Swal.fire({
        title: titulo,
        text: mensaje,
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    } else {
      Swal.close();
    }
  }

  /**
   * Mostrar mensaje de éxito
   */
  mostrarExito(titulo, mensaje) {
    Swal.fire({
      icon: 'success',
      title: titulo,
      text: mensaje,
      confirmButtonText: 'Aceptar'
    });
  }

  /**
   * Mostrar mensaje de error
   */
  mostrarError(titulo, mensaje) {
    Swal.fire({
      icon: 'error',
      title: titulo,
      text: mensaje,
      confirmButtonText: 'Aceptar'
    });
  }

  /**
   * Mostrar mensaje informativo
   */
  mostrarInfo(titulo, mensaje) {
    Swal.fire({
      icon: 'info',
      title: titulo,
      text: mensaje,
      confirmButtonText: 'Aceptar'
    });
  }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  window.validador = new ValidadorArchivos();
});
