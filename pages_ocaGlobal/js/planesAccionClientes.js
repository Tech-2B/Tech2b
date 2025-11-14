/**
 * JavaScript para la gestión de planes de acción de clientes
 * Utiliza Fetch API para peticiones AJAX
 * Maneja tablas, modales y carga de archivos
 */

class PlanesAccionClientes {
  constructor() {
    this.tabla = null;
    this.tablaHistorial = null;
    this.estructuraCarpetasHistorial = [];
    this.archivosRaizHistorial = [];
    this.clienteActualHistorial = null;
    this.planAccionActualHistorial = null;
    this.init();
  }

  init() {
    this.configurarEventos();
    this.verificarClienteSeleccionado();
  }

  /**
   * Configurar eventos del formulario y botones
   */
  configurarEventos() {

    // Evento para el checkbox de crear nueva carpeta
    const checkboxNuevaCarpeta = document.getElementById("crear_nueva_carpeta");
    if (checkboxNuevaCarpeta) {
      checkboxNuevaCarpeta.addEventListener("change", (e) => this.toggleNuevaCarpeta(e.target.checked));
    }

    // Evento para el botón de subir archivo
    const btnSubirArchivo = document.getElementById("btn_subir_archivo");
    if (btnSubirArchivo) {
      btnSubirArchivo.addEventListener("click", (e) => this.subirArchivo(e));
    }

    // Evento para mostrar nombre del archivo seleccionado
    const inputArchivo = document.getElementById("archivo_subir");
    if (inputArchivo) {
      inputArchivo.addEventListener("change", (e) => this.mostrarNombreArchivo(e));
    }

    // Eventos para el formulario de crear plan manual
    const btnNuevoPlan = document.getElementById("btn_nuevo_plan");
    if (btnNuevoPlan) {
      btnNuevoPlan.addEventListener("click", (e) => this.mostrarFormularioPlan());
    }

    const btnCancelarPlan = document.getElementById("btn_cancelar_plan");
    if (btnCancelarPlan) {
      btnCancelarPlan.addEventListener("click", (e) => this.ocultarFormularioPlan());
    }

    const formularioCrearPlan = document.getElementById("formulario_crear_plan");
    if (formularioCrearPlan) {
      formularioCrearPlan.addEventListener("submit", (e) => this.crearPlanManual(e));
    }

    // Eventos para los selects de tópicos y periodicidades
    const selectTopicos = document.getElementById("topicos_manual");
    if (selectTopicos) {
      selectTopicos.addEventListener("change", (e) => this.toggleCampoOtro(e.target.value, 'topico'));
    }

    const selectPeriodicidad = document.getElementById("periodicidad_manual");
    if (selectPeriodicidad) {
      selectPeriodicidad.addEventListener("change", (e) => this.toggleCampoOtro(e.target.value, 'periodicidad'));
    }
  }

  /**
   * Inicializar DataTable
   */
  inicializarTabla() {
    if ($.fn.DataTable) {
      // Verificar si ya existe una instancia de DataTable
      if ($.fn.DataTable.isDataTable('#tabla_planes_accion')) {
        
        $('#tabla_planes_accion').DataTable().destroy();
      }
      
      this.tabla = $("#tabla_planes_accion").DataTable({
        scrollX: true,         // Permite scroll horizontal si es necesario
        paging: false,         // Quita paginación (opcional)
        fixedHeader: {
          header: true,
          footer: false
        },
        language: {
          url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json",
        },
        // responsive: false, // Deshabilitar responsive para evitar cabecera duplicada
        pageLength: 10,
        order: [[0, "asc"]],
        // dom: 'lfrtip', // Configurar elementos del DOM sin cabecera adicional
        columnDefs: [
          {
            targets: -4, // Columna "Acciones"
            orderable: false,
            searchable: false,
          },
          {
            targets: -3, // Columna "Cargar Archivo"
            orderable: false,
            searchable: false,
          },
          {
            targets: -2, // Columna "Pendientes de Validación"
            orderable: false,
            searchable: false,
          },
          {
            targets: -1, // Columna "Historial de Archivos"
            orderable: false,
            searchable: false,
          },
        ],
        columns: [
          { data: "id_registro" },
          { 
            data: "descripcion_area_oportunidad",
            render: (data, type, row) => this.renderizarCampoEditable(data, 'descripcion_area_oportunidad', row.id_registro)
          },
          { 
            data: "descripcion_plan_accion",
            render: (data, type, row) => this.renderizarCampoEditable(data, 'descripcion_plan_accion', row.id_registro)
          },
          { 
            data: "descripcion_topico",
            render: (data, type, row) => this.renderizarCampoEditable(data, 'descripcion_topico', row.id_registro)
          },
          { 
            data: "descripcion_entregable",
            render: (data, type, row) => this.renderizarCampoEditable(data, 'descripcion_entregable', row.id_registro)
          },
          { 
            data: "descripcion_periodicidad",
            render: (data, type, row) => this.renderizarCampoEditable(data, 'descripcion_periodicidad', row.id_registro)
          },
          {
            data: null,
            render: (data, type, row) => this.renderizarBotonEditar(row),
          },
          {
            data: null,
            render: (data, type, row) => this.renderizarBotonCargarArchivo(row),
          },
          {
            data: null,
            render: (data, type, row) => this.renderizarBotonValidacion(row),
          },
          {
            data: null,
            render: (data, type, row) => this.renderizarBotonHistorial(row),
          },
        ],
      });
    }
  }

  /**
   * Renderizar campo editable
   */
  renderizarCampoEditable(data, campo, idRegistro) {
    const valor = data || '';
    return `
      <div class="campo-editable" data-campo="${campo}" data-id="${idRegistro}">
        <span class="valor-campo">${valor}</span>
        <textarea class="input-editable form-control" style="display: none;" rows="3">${valor}</textarea>
      </div>
    `;
  }

  /**
   * Renderizar botón de editar
   */
  renderizarBotonEditar(row) {
    return `
      <button type="button" class="btn btn-sm btn-accion btn-editar" 
              onclick="planesAccion.toggleEdicion(${row.id_registro})" 
              title="Editar Descripciones">
        <i class="fa fa-edit"></i>
        <span class="btn-text">Editar</span>
      </button>
    `;
  }

  /**
   * Renderizar botón de cargar archivo
   */
  renderizarBotonCargarArchivo(row) {
    // Verificar permisos
    if (!window.permisosUsuario || !window.permisosUsuario.puedeCargarArchivos) {
      return '<span class="text-muted">Sin permisos</span>';
    }
    
    return `
      <button type="button" class="btn btn-sm btn-accion btn-cargar-archivo" 
              onclick="planesAccion.abrirModalCargarArchivo(${row.id_registro}, ${row.id_cliente}, ${row.id_plan_accion})" 
              title="Cargar Archivo">
        <i class="fa fa-upload"></i>
        <span class="btn-text">Subir</span>
      </button>
    `;
  }

  /**
   * Generar botón de eliminar archivo según permisos - DESHABILITADO TEMPORALMENTE
   */
  generarBotonEliminarArchivo(archivo) {
    // FUNCIONALIDAD DE ELIMINACIÓN DESHABILITADA TEMPORALMENTE
    return '<span class="text-muted" title="Eliminación de archivos deshabilitada temporalmente">Eliminación deshabilitada</span>';
    
    // Código original comentado:
    // // Verificar permisos
    // if (!window.permisosUsuario || !window.permisosUsuario.puedeEliminarArchivos) {
    //   return '<span class="text-muted" title="Sin permisos para eliminar">Sin permisos</span>';
    // }
    // 
    // return `<button type="button" class="btn btn-sm btn-outline-danger" onclick="planesAccion.eliminarArchivo(${archivo.id_archivo}, '${archivo.nombre_archivo_original}')" title="Eliminar archivo">
    //           <i class="fa fa-trash"></i>
    //         </button>`;
  }

  /**
   * Renderizar botón de validación
   */
  renderizarBotonValidacion(row) {
    // Verificar permisos
    if (!window.permisosUsuario || !window.permisosUsuario.puedeCargarArchivos) {
      return '<span class="text-muted">Sin permisos</span>';
    }
    
    return `
      <button type="button" class="btn btn-sm btn-accion btn-validacion" 
              onclick="planesAccion.abrirModalValidacion(${row.id_registro}, ${row.id_cliente}, ${row.id_plan_accion})" 
              title="Validar Archivos Pendientes">
        <i class="fa fa-check-circle"></i>
        <span class="btn-text">Validar</span>
      </button>
    `;
  }

  /**
   * Renderizar botón de historial
   */
  renderizarBotonHistorial(row) {
    return `
      <button type="button" class="btn btn-sm btn-accion btn-historial" 
        onclick="planesAccion.abrirModalHistorial(${row.id_registro}, ${row.id_cliente}, ${row.id_plan_accion})" 
        title="Ver Historial">
        <i class="fa fa-history"></i>
        <span class="btn-text">Ver</span>
      </button>
    `;
  }

  /**
   * Cargar lista de clientes
   */
  async cargarClientes() {
    try {
      this.mostrarCargando(true);

      const response = await fetch("ajax/obtener_clientes.php");
      const data = await response.json();

      if (data.success) {
        const selectCliente = document.getElementById("select_cliente");
        if (selectCliente) {
          selectCliente.innerHTML = '<option value="">-- Seleccionar Cliente --</option>';
          
          data.data.forEach(cliente => {
            const option = document.createElement('option');
            option.value = cliente.id_cliente;
            option.textContent = cliente.nombre_cliente;
            selectCliente.appendChild(option);
          });
        }
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error cargando clientes:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar la lista de clientes");
      this.mostrarCargando(false);
    }
  }

  /**
   * Cargar planes de acción de un cliente
   */
  async cargarPlanesAccion(idCliente) {
    if (!idCliente) {
      this.limpiarTabla();
      return;
    }

    try {
      
      this.mostrarCargando(true);

      const response = await fetch(`ajax/obtener_planes_accion_cliente.php?id_cliente=${idCliente}`);
      const data = await response.json();

      this.manejarRespuesta(data, 'cargar');
      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error cargando planes de acción:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar los planes de acción");
      this.mostrarCargando(false);
    }
  }

  /**
   * Manejar respuesta del servidor
   */
  manejarRespuesta(data, operacion) {
    
    switch (data.code) {
      case 200: // Encontrado/Éxito
        if (data.success) {
          
          this.actualizarTablaCompleta(data.data);
        } else {
          
          this.actualizarTablaCompleta([]);
        }
        break;
        
      case 201: // Creado/Insertado
        if (data.success) {
          this.mostrarExito(data.title, data.message);
          // Recargar la tabla si es necesario
        } else {
          this.mostrarError(data.title, data.message);
        }
        break;
        
      case 500: // Error general
        this.mostrarError(data.title, data.message);
        break;
        
      default:
        console.error("Código de respuesta no reconocido:", data.code);
        this.mostrarError("Error", "Respuesta del servidor no válida");
    }
  }

  /**
   * Actualizar tabla completa
   */
  async actualizarTablaCompleta(datos = null) {
    try {
      
      // Destruir tabla existente si existe
      if (this.tabla) {
        this.tabla.destroy();
        this.tabla = null;
      }
      
      // Limpiar tbody
      const tbody = document.querySelector('#tabla_planes_accion tbody');
      if (tbody) {
        tbody.innerHTML = '';
      }
      
      // Asegurar que la tabla mantenga el ancho mínimo
      const tablaElement = document.querySelector('#tabla_planes_accion');
      if (tablaElement) {
        tablaElement.style.minWidth = '1600px';
        tablaElement.style.width = '100%';
      }
      
      // Recrear tabla
      this.inicializarTabla();
      
      // Llenar tabla con datos
      if (this.tabla && datos) {
        
        this.tabla.rows.add(datos);
        this.tabla.draw();
        
        // Agregar data-id a las filas para la edición
        setTimeout(() => {
          this.tabla.rows().every(function() {
            const data = this.data();
            const node = this.node();
            if (data && data.id_registro) {
              node.setAttribute('data-id', data.id_registro);
            }
          });
        }, 100);
        
      }
      
    } catch (error) {
      console.error("Error al actualizar tabla completa:", error);
    }
  }

  /**
   * Limpiar tabla
   */
  limpiarTabla() {
    if (this.tabla) {
      this.tabla.clear().draw();
    } else {
      const tbody = document.querySelector('#tabla_planes_accion tbody');
      if (tbody) {
        tbody.innerHTML = '';
      }
    }
  }

  /**
   * Abrir modal para validación de archivos
   */
  async abrirModalValidacion(idRegistro, idCliente, idPlanAccion) {
    try {
      // Guardar los parámetros actuales para uso posterior
      this.clienteActualHistorial = { id: idCliente };
      this.planAccionActualHistorial = { id_plan_accion: idPlanAccion };
      
      // Obtener información del plan de acción
      const planInfo = this.obtenerInfoPlanAccion(idRegistro);
      if (planInfo) {
        document.getElementById('descripcion_plan_validacion').textContent = planInfo.descripcion_plan_accion;
      }

      // Cargar archivos pendientes de validación
      await this.cargarArchivosPendientes(idCliente, idPlanAccion);

      // Mostrar modal
      $('#modal_validacion_archivos').modal('show');
    } catch (error) {
      console.error("Error abriendo modal de validación:", error);
      this.mostrarError("Error", "No se pudo abrir el modal de validación");
    }
  }

  /**
   * Abrir modal para cargar archivo
   */
  async abrirModalCargarArchivo(idRegistro, idCliente, idPlanAccion) {
    // Verificar permisos
    if (!window.permisosUsuario || !window.permisosUsuario.puedeCargarArchivos) {
      this.mostrarError("Sin permisos", "No tienes permisos para cargar archivos");
      return;
    }
    
    try {
      // Llenar campos ocultos
      document.getElementById('id_registro_archivo').value = idRegistro;
      document.getElementById('id_cliente_archivo').value = idCliente;
      document.getElementById('id_plan_accion_archivo').value = idPlanAccion;

      // Cargar carpetas disponibles
      await this.cargarCarpetasDisponibles(idCliente, idPlanAccion);

      // Mostrar modal
      $('#modal_cargar_archivo').modal('show');
    } catch (error) {
      console.error("Error abriendo modal de carga:", error);
      this.mostrarError("Error", "No se pudo abrir el modal de carga");
    }
  }

  /**
   * Cargar archivos pendientes de validación
   */
  async cargarArchivosPendientes(idCliente, idPlanAccion) {
    try {
      // Validar parámetros
      if (!idCliente || !idPlanAccion) {
        console.error("Parámetros inválidos para cargar archivos pendientes:", { idCliente, idPlanAccion });
        document.getElementById('lista_archivos_pendientes').innerHTML = `
          <div class="text-center text-muted">
            <i class="fa fa-exclamation-triangle"></i>
            <p>Error: Parámetros de cliente o plan de acción no válidos</p>
          </div>
        `;
        return;
      }

      const response = await fetch(`ajax/obtener_archivos_pendientes_validacion.php?id_cliente=${idCliente}&id_plan_accion=${idPlanAccion}`);
      const data = await response.json();

      if (data.success) {
        this.mostrarArchivosPendientes(data.data || []);
      } else {
        document.getElementById('lista_archivos_pendientes').innerHTML = `
          <div class="text-center text-muted">
            <i class="fa fa-exclamation-triangle"></i>
            <p>${data.message || 'No se pudieron cargar los archivos pendientes'}</p>
          </div>
        `;
      }
    } catch (error) {
      console.error("Error cargando archivos pendientes:", error);
      document.getElementById('lista_archivos_pendientes').innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-exclamation-triangle"></i>
          <p>Error al cargar los archivos pendientes</p>
        </div>
      `;
    }
  }

  /**
   * Mostrar archivos pendientes en el modal
   */
  mostrarArchivosPendientes(archivos) {
    const container = document.getElementById('lista_archivos_pendientes');
    
    if (!archivos || archivos.length === 0) {
      container.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-check-circle fa-2x mb-2"></i>
          <p>No hay archivos pendientes de validación</p>
        </div>
      `;
      return;
    }

    // Crear tabla de archivos pendientes
    let html = `
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="thead-light">
            <tr>
              <th>Archivo</th>
              <th>Comentario</th>
              <th>Tamaño</th>
              <th>Fecha de Subida</th>
              <th>Subido por</th>
              <th>Estatus</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;

    archivos.forEach(archivo => {
      html += this.generarFilaArchivoPendiente(archivo);
    });

    html += `
          </tbody>
        </table>
      </div>
    `;

    container.innerHTML = html;
  }

  /**
   * Generar fila de archivo pendiente
   */
  generarFilaArchivoPendiente(archivo) {
    const estatusClass = archivo.estatus_validacion === 'pendiente' ? 'warning' : 
                        archivo.estatus_validacion === 'aprobado' ? 'success' : 'danger';
    
    const estatusText = archivo.estatus_validacion === 'pendiente' ? 'Pendiente' : 
                       archivo.estatus_validacion === 'aprobado' ? 'Aprobado' : 'Rechazado';

    // Generar información de comentario según el estatus
    let comentarioInfo = '';
    if (archivo.estatus_validacion === 'rechazado' && archivo.comentario_rechazo) {
      comentarioInfo = `
        <div>
          <small class="text-danger font-weight-bold">
            <i class="fa fa-exclamation-triangle mr-1"></i>
            Motivo de rechazo:
          </small>
          <div class="alert alert-danger alert-sm mt-1 mb-0 p-2" style="font-size: 0.85rem;">
            <i class="fa fa-quote-left mr-1"></i>
            ${archivo.comentario_rechazo}
            <i class="fa fa-quote-right ml-1"></i>
          </div>
        </div>
      `;
    } else if (archivo.comentario) {
      comentarioInfo = `
        <div>
          <small class="text-muted">
            <i class="fa fa-comment mr-1"></i>
            Comentario:
          </small>
          <div class="text-muted mt-1" style="font-size: 0.85rem;">
            ${archivo.comentario}
          </div>
        </div>
      `;
    }

    return `
      <tr>
        <td>
          <i class="fa fa-file-pdf text-danger mr-2"></i>
          <strong>${archivo.nombre_archivo_original}</strong>
        </td>
        <td>
          ${comentarioInfo || '<span class="text-muted">Sin comentario</span>'}
        </td>
        <td>
          <span class="badge badge-info">${this.formatearTamano(archivo.tamano_archivo)}</span>
        </td>
        <td>
          <small class="text-muted">${this.formatearFecha(archivo.fecha_subida)}</small>
        </td>
        <td>
          <small class="text-muted">${archivo.nombre_usuario || 'N/A'}</small>
        </td>
        <td>
          <span class="badge badge-${estatusClass}">${estatusText}</span>
        </td>
        <td>
          ${this.generarAccionesArchivoPendiente(archivo)}
        </td>
      </tr>
    `;
  }

  /**
   * Generar acciones para archivo pendiente
   */
  generarAccionesArchivoPendiente(archivo) {
    if (archivo.estatus_validacion === 'pendiente') {
      // Solo los administradores pueden validar archivos pendientes
      if (!window.permisosUsuario || window.permisosUsuario.rol !== 'Administrador') {
        return '<span class="text-muted">Pendiente de validación</span>';
      }
      
      return `
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-xs btn-outline-primary" 
                  onclick="planesAccion.visualizarArchivo(${archivo.id_archivo_pendiente}, '${archivo.nombre_archivo_original}', '${archivo.ruta_archivo_temporal}')" 
                  title="Visualizar archivo">
            <i class="fa fa-eye"></i>
          </button>
          <button type="button" class="btn btn-xs btn-outline-success" 
                  onclick="planesAccion.aprobarArchivo(${archivo.id_archivo_pendiente})" 
                  title="Aprobar archivo">
            <i class="fa fa-check"></i>
          </button>
          <button type="button" class="btn btn-xs btn-outline-danger" 
                  onclick="planesAccion.rechazarArchivo(${archivo.id_archivo_pendiente})" 
                  title="Rechazar archivo">
            <i class="fa fa-times"></i>
          </button>
        </div>
      `;
    } else if (archivo.estatus_validacion === 'rechazado') {
      // Los colaboradores pueden recargar archivos rechazados, los administradores solo pueden visualizar
      if (!window.permisosUsuario || window.permisosUsuario.rol !== 'Colaborador') {
        // Administradores: Solo pueden visualizar archivos rechazados
        return `
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-xs btn-outline-primary" 
                    onclick="planesAccion.visualizarArchivo(${archivo.id_archivo_pendiente}, '${archivo.nombre_archivo_original}', '${archivo.ruta_archivo_temporal}')" 
                    title="Visualizar archivo">
              <i class="fa fa-eye"></i>
            </button>
          </div>
        `;
      }
      
      // Colaboradores: Pueden visualizar y recargar archivos rechazados
      return `
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-xs btn-outline-primary" 
                  onclick="planesAccion.visualizarArchivo(${archivo.id_archivo_pendiente}, '${archivo.nombre_archivo_original}', '${archivo.ruta_archivo_temporal}')" 
                  title="Visualizar archivo">
            <i class="fa fa-eye"></i>
          </button>
          <button type="button" class="btn btn-xs btn-outline-warning" 
                  onclick="planesAccion.recargarArchivo(${archivo.id_archivo_pendiente})" 
                  title="Cargar nuevamente">
            <i class="fa fa-upload"></i>
          </button>
        </div>
      `;
    } else {
      return `
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-xs btn-outline-info" 
                  onclick="planesAccion.verDetallesArchivo(${archivo.id_archivo_pendiente})" 
                  title="Ver detalles">
            <i class="fa fa-eye"></i>
          </button>
        </div>
      `;
    }
  }

  /**
   * Cargar carpetas disponibles
   */
  async cargarCarpetasDisponibles(idCliente, idPlanAccion) {
    try {
      const response = await fetch(`ajax/obtener_carpetas_plan_accion.php?id_cliente=${idCliente}&id_plan_accion=${idPlanAccion}`);
      const data = await response.json();

      const selectCarpeta = document.getElementById('select_carpeta_destino');
      if (selectCarpeta) {
        selectCarpeta.innerHTML = '<option value="">-- Seleccionar Carpeta --</option>';
        
        if (data.success && data.data) {
          data.data.forEach(carpeta => {
            const option = document.createElement('option');
            option.value = carpeta.id_carpeta_drive;
            option.textContent = carpeta.nombre_carpeta;
            selectCarpeta.appendChild(option);
          });
        }
      }
    } catch (error) {
      console.error("Error cargando carpetas:", error);
    }
  }

  /**
   * Toggle para mostrar/ocultar campo de nueva carpeta
   */
  toggleNuevaCarpeta(mostrar) {
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

  /**
   * Mostrar nombre del archivo seleccionado
   */
  mostrarNombreArchivo(event) {
    const archivo = event.target.files[0];
    const container = document.querySelector('.input-archivo-container');
    
    if (archivo) {
      // Crear o actualizar el elemento que muestra el nombre
      let nombreElement = container.querySelector('.archivo-seleccionado');
      if (!nombreElement) {
        nombreElement = document.createElement('div');
        nombreElement.className = 'archivo-seleccionado';
        container.appendChild(nombreElement);
      }
      
      // Mostrar información del archivo
      const tamanoMB = (archivo.size / (1024 * 1024)).toFixed(2);
      nombreElement.innerHTML = `
        <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 8px; margin-top: 8px;">
          <i class="fa fa-check-circle" style="color: #28a745;"></i>
          <strong>Archivo seleccionado:</strong> ${archivo.name}<br>
          <small style="color: #6c757d;">Tamaño: ${tamanoMB} MB</small>
        </div>
      `;
    } else {
      // Limpiar el elemento si no hay archivo
      const nombreElement = container.querySelector('.archivo-seleccionado');
      if (nombreElement) {
        nombreElement.remove();
      }
    }
  }

  /**
   * Crear nueva carpeta
   */
  async crearNuevaCarpeta() {
    try {
      const idCliente = document.getElementById('id_cliente_archivo').value;
      const idPlanAccion = document.getElementById('id_plan_accion_archivo').value;
      const nombreCarpeta = document.getElementById('nombre_nueva_carpeta').value.trim();

      if (!nombreCarpeta) {
        this.mostrarError("Error", "Debe ingresar un nombre para la carpeta");
        return;
      }

      this.mostrarCargando(true, "Creando carpeta...", "Creando nueva carpeta en Google Drive");

      const formData = new FormData();
      formData.append('id_cliente', idCliente);
      formData.append('id_plan_accion', idPlanAccion);
      formData.append('nombre_carpeta', nombreCarpeta);

      const response = await fetch('ajax/crear_carpeta_drive.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();
      this.mostrarCargando(false);

      if (data.success) {
        this.mostrarExito("Carpeta creada", "La carpeta se creó exitosamente");
        
        // Limpiar campo de nombre
        document.getElementById('nombre_nueva_carpeta').value = '';
        
        // Recargar carpetas disponibles
        await this.cargarCarpetasDisponibles(idCliente, idPlanAccion);
        
        // Seleccionar la nueva carpeta creada
        const selectCarpeta = document.getElementById('select_carpeta_destino');
        if (data.data && data.data.id_carpeta_drive) {
          selectCarpeta.value = data.data.id_carpeta_drive;
        }
        
        // Desmarcar checkbox de crear carpeta
        document.getElementById('crear_nueva_carpeta').checked = false;
        this.toggleNuevaCarpeta(false);
        
        return true;
        
      } else {
        this.mostrarError(data.title || "Error", data.message || "No se pudo crear la carpeta");
        return false;
      }

    } catch (error) {
      this.mostrarCargando(false);
      console.error("Error creando carpeta:", error);
      this.mostrarError("Error", "Error inesperado al crear la carpeta");
      return false;
    }
  }

  /**
   * Subir archivo
   */
  async subirArchivo(event) {
    event.preventDefault();

    try {
      this.mostrarCargando(true, "Subiendo archivo...", "Procesando archivo y subiendo a Google Drive");

      const form = document.getElementById('formulario_cargar_archivo');
      const formData = new FormData(form);

      // Validar archivo
      const archivo = formData.get('archivo_subir');
      if (!archivo || archivo.size === 0) {
        this.mostrarError("Error", "Debe seleccionar un archivo");
        this.mostrarCargando(false);
        return;
      }

      // Validar tamaño (100MB)
      if (archivo.size > 100 * 1024 * 1024) {
        this.mostrarError("Error", "El archivo no puede ser mayor a 100MB");
        this.mostrarCargando(false);
        return;
      }

      // Validar que se seleccione carpeta o se cree nueva
      const crearNueva = document.getElementById('crear_nueva_carpeta').checked;
      const carpetaDestino = formData.get('carpeta_destino');
      const nombreNuevaCarpeta = formData.get('nombre_nueva_carpeta');

      if (!crearNueva && !carpetaDestino) {
        this.mostrarError("Error", "Debe seleccionar una carpeta de destino");
        this.mostrarCargando(false);
        return;
      }

      if (crearNueva && !nombreNuevaCarpeta.trim()) {
        this.mostrarError("Error", "Debe especificar el nombre de la nueva carpeta");
        this.mostrarCargando(false);
        return;
      }

      // Si se está creando una nueva carpeta, primero crearla
      if (crearNueva) {
        const resultadoCrear = await this.crearNuevaCarpeta();
        if (!resultadoCrear) {
          this.mostrarCargando(false);
          return;
        }
        // Actualizar el formData con la nueva carpeta
        formData.set('carpeta_destino', document.getElementById('select_carpeta_destino').value);
      }

      const response = await fetch("sql/subir_archivo_drive.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      this.manejarRespuesta(data, 'subir');

      if (data.success) {
        $('#modal_cargar_archivo').modal('hide');
        form.reset();
        this.toggleNuevaCarpeta(false);
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error subiendo archivo:", error);
      this.mostrarError("Error de conexión", "No se pudo subir el archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Abrir modal de historial de archivos con estructura de carpetas
   */
  async abrirModalHistorial(idRegistro, idCliente, idPlanAccion) {
    try {
      this.mostrarCargando(true, "Cargando historial...", "Obteniendo estructura de carpetas del plan de acción");

      // Limpiar contenido previo del modal
      this.limpiarModalHistorial();

      // Obtener información del plan de acción
      const planInfo = this.obtenerInfoPlanAccion(idRegistro);
      if (planInfo) {
        document.getElementById('descripcion_plan_historial').textContent = planInfo.descripcion_plan_accion;
      }

      // Obtener estructura de carpetas
      const response = await fetch(`ajax/obtener_estructura_carpetas.php?id_cliente=${idCliente}&id_plan_accion=${idPlanAccion}`);
      const data = await response.json();

      if (data.success) {
        // El endpoint devuelve directamente el array de carpetas en data
        this.estructuraCarpetasHistorial = data.data || [];
        this.archivosRaizHistorial = []; // Por ahora no hay archivos raíz
        this.clienteActualHistorial = { id: idCliente };
        this.planAccionActualHistorial = { id_plan_accion: idPlanAccion };
        
        
        this.mostrarEstructuraCompletaHistorial(this.archivosRaizHistorial);
        this.actualizarBreadcrumbHistorial('Inicio');
        
        // Configurar evento de limpieza cuando se cierre el modal
        this.configurarEventosModalHistorial();
        
        $('#modal_historial_archivos').modal('show');
      } else {
        this.mostrarError("Error", data.message || "No se pudo cargar la estructura de carpetas");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error cargando historial:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar el historial");
      this.mostrarCargando(false);
    }
  }

  /**
   * Limpiar contenido del modal de historial
   */
  limpiarModalHistorial() {
    // Limpiar lista de carpetas
    const contenedorCarpetas = document.getElementById('lista_carpetas_historial');
    if (contenedorCarpetas) {
      contenedorCarpetas.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-spinner fa-spin"></i> Cargando estructura...
        </div>
      `;
    }

    // Limpiar lista de archivos
    const contenedorArchivos = document.getElementById('lista_archivos_historial');
    if (contenedorArchivos) {
      contenedorArchivos.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-folder-open"></i> Selecciona una carpeta para ver sus archivos
        </div>
      `;
    }

    // Limpiar breadcrumb
    const breadcrumb = document.getElementById('breadcrumb_historial');
    if (breadcrumb) {
      breadcrumb.innerHTML = '<li class="breadcrumb-item active" id="breadcrumb_inicio">Inicio</li>';
    }

    // Limpiar variables de estado
    this.estructuraCarpetasHistorial = [];
    this.archivosRaizHistorial = [];
    this.clienteActualHistorial = null;
    this.planAccionActualHistorial = null;
  }

  /**
   * Configurar eventos del modal de historial
   */
  configurarEventosModalHistorial() {
    // Limpiar eventos previos para evitar duplicados
    $('#modal_historial_archivos').off('hidden.bs.modal');
    
    // Configurar evento de limpieza cuando se cierre el modal
    $('#modal_historial_archivos').on('hidden.bs.modal', () => {
      this.limpiarModalHistorial();
    });
  }

  /**
   * Mostrar historial en el modal (función legacy - mantenida para compatibilidad)
   */
  mostrarHistorialEnModal(archivos) {
    const tbody = document.getElementById('tabla_historial_archivos_body');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (archivos.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="text-center" style="padding: 40px; color: #6c757d; font-style: italic;">No hay archivos subidos para este plan de acción</td></tr>';
      return;
    }

    archivos.forEach(archivo => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="nombre-archivo">${archivo.nombre_archivo_original}</td>
        <td class="comentarios">${archivo.comentario || 'Sin comentario'}</td>
        <td class="fecha-subida">${this.formatearFecha(archivo.fecha_subida)}</td>
        <td class="persona-subio">${archivo.nombre_usuario || 'Usuario no disponible'}</td>
        <td>
          <div class="btn-group" role="group">
            <a href="${archivo.url_drive}" target="_blank" class="btn btn-accion-historial btn-info" title="Ver en Drive">
              <i class="fa fa-external-link-alt"></i>
            </a>
            <a href="${archivo.url_descarga}" target="_blank" class="btn btn-accion-historial btn-success" title="Descargar">
              <i class="fa fa-download"></i>
            </a>
            ${this.generarBotonEliminarArchivo(archivo)}
          </div>
        </td>
      `;
      tbody.appendChild(row);
    });
  }

  /**
   * Mostrar estructura completa de carpetas en el modal de historial
   */
  mostrarEstructuraCompletaHistorial(archivosRaiz) {
    const contenedor = document.getElementById('lista_carpetas_historial');
    if (!contenedor) {
      console.error('No se encontró el contenedor lista_carpetas_historial');
      return;
    }


    let html = '';

    if (this.estructuraCarpetasHistorial.length === 0) {
      html = `
        <div class="text-center text-muted">
          <i class="fa fa-folder-open fa-2x mb-2"></i>
          <p>No hay carpetas disponibles</p>
        </div>
      `;
    } else {
      // Mostrar carpetas principales
      this.estructuraCarpetasHistorial.forEach((carpeta, index) => {
        html += this.generarHTMLCarpetaHistorial(carpeta, 0, archivosRaiz);
      });

      // Mostrar archivos de la carpeta raíz
      if (archivosRaiz && archivosRaiz.length > 0) {
        archivosRaiz.forEach(archivo => {
          html += this.generarHTMLArchivoHistorial(archivo);
        });
      }
    }

    contenedor.innerHTML = html;
  }

  /**
   * Generar HTML para una carpeta en el modal de historial
   */
  generarHTMLCarpetaHistorial(carpeta, nivel, archivosRaiz) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    
    // Determinar el nivel basado en la ruta completa
    const nivelCalculado = this.calcularNivelCarpeta(carpeta);
    const marginLeft = nivelCalculado * 20;
    
    let html = `
      <div class="carpeta-item ${nivelClass}" 
          data-id-carpeta="${carpeta.id_carpeta_drive}" 
          data-tipo="${carpeta.tipo_carpeta}"
          style="margin-left: ${marginLeft}px;">
        <i class="fa ${icono} text-warning mr-2"></i>
        <span class="nombre-carpeta" onclick="planesAccion.seleccionarCarpetaHistorial('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta.trim()}')" style="cursor: pointer;">
          ${carpeta.nombre_carpeta.trim()}
        </span>
        <span class="badge badge-primary ml-2">0</span>
      </div>
    `;

    return html;
  }

  /**
   * Calcular el nivel de una carpeta basado en su ruta completa
   */
  calcularNivelCarpeta(carpeta) {
    if (!carpeta.ruta_completa) return 0;
    
    // Contar el número de "/" en la ruta para determinar el nivel
    const partes = carpeta.ruta_completa.split('/');
    return Math.max(0, partes.length - 3); // -3 porque la estructura es: carpeta_principal/cliente/area_oportunidad/plan_accion/...
  }

  /**
   * Generar HTML para un archivo en el modal de historial
   */
  generarHTMLArchivoHistorial(archivo) {
    return `
      <div class="archivo-item" style="margin-left: 20px; padding: 5px; border-left: 2px solid #e9ecef;">
        <i class="fa fa-file text-info mr-2"></i>
        <span class="nombre-archivo">${archivo.nombre_archivo_original}</span>
        <small class="text-muted ml-2">${archivo.tamano_formateado}</small>
      </div>
    `;
  }

  /**
   * Toggle subcarpetas en el modal de historial
   */
  toggleSubcarpetasHistorial(idCarpetaDrive) {
    const expandirBtn = document.getElementById(`expandir-historial-${idCarpetaDrive}`);
    const subcarpetasDiv = document.getElementById(`subcarpetas-historial-${idCarpetaDrive}`);
    
    if (expandirBtn && subcarpetasDiv) {
      const icono = expandirBtn.querySelector('i');
      if (subcarpetasDiv.style.display === 'none') {
        subcarpetasDiv.style.display = 'block';
        icono.className = 'fa fa-chevron-down';
      } else {
        subcarpetasDiv.style.display = 'none';
        icono.className = 'fa fa-chevron-right';
      }
    }
  }

  /**
   * Seleccionar carpeta en el modal de historial
   */
  async seleccionarCarpetaHistorial(idCarpetaDrive, nombreCarpeta) {
    try {
      // Actualizar breadcrumb
      this.actualizarBreadcrumbHistorial(nombreCarpeta);
      
      // Obtener archivos de la carpeta
      const response = await fetch(`ajax/obtener_archivos_carpeta.php?id_cliente=${this.clienteActualHistorial.id}&id_plan_accion=${this.planAccionActualHistorial.id_plan_accion}&id_carpeta_drive=${idCarpetaDrive}`);
      const data = await response.json();

      if (data.success) {
        this.mostrarArchivosEnHistorial(data.data, nombreCarpeta);
      } else {
        this.mostrarArchivosEnHistorial([], nombreCarpeta);
      }
    } catch (error) {
      console.error("Error obteniendo archivos de carpeta:", error);
      this.mostrarArchivosEnHistorial([], nombreCarpeta);
    }
  }

  /**
   * Mostrar archivos en el modal de historial
   */
  mostrarArchivosEnHistorial(archivos, nombreCarpeta) {
    const contenedor = document.getElementById('lista_archivos_historial');
    if (!contenedor) return;

    if (archivos.length === 0) {
      contenedor.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-folder-open fa-2x mb-2"></i>
          <p>No hay archivos en "${nombreCarpeta}"</p>
        </div>
      `;
      return;
    }

    // Crear estructura de tabla
    let html = `
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead class="thead-primary">
            <tr>
              <th><i class="fa fa-file"></i> Archivo</th>
              <th><i class="fa fa-comment"></i> Comentario</th>
              <th><i class="fa fa-weight"></i> Tamaño</th>
              <th><i class="fa fa-calendar"></i> Fecha</th>
              <th><i class="fa fa-user"></i> Usuario</th>
              <th><i class="fa fa-cogs"></i> Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    archivos.forEach(archivo => {
      html += this.generarHTMLFilaArchivoHistorial(archivo);
    });
    
    html += `
          </tbody>
        </table>
      </div>
    `;

    contenedor.innerHTML = html;
  }

  /**
   * Generar HTML para una fila de archivo en el modal de historial
   */
  generarHTMLFilaArchivoHistorial(archivo) {
    return `
      <tr>
        <td>
          <i class="fa ${archivo.icono_tipo.icono} ${archivo.icono_tipo.clase} mr-2"></i>
          <strong>${archivo.nombre_archivo_original}</strong>
        </td>
        <td>
          <span class="text-muted">${archivo.comentario || 'Sin comentario'}</span>
        </td>
        <td>
          <span class="badge badge-info">${archivo.tamano_formateado}</span>
        </td>
        <td>
          <small class="text-muted">${archivo.fecha_formateada}</small>
        </td>
        <td>
          <small class="text-muted">${archivo.nombre_usuario || 'N/A'}</small>
        </td>
        <td>
          <div class="btn-group" role="group">
            <a href="${archivo.url_drive}" target="_blank" class="btn btn-sm btn-outline-primary" title="Ver archivo">
              <i class="fa fa-eye"></i>
            </a>
            <a href="${archivo.url_descarga}" target="_blank" class="btn btn-sm btn-outline-success" title="Descargar archivo">
              <i class="fa fa-download"></i>
            </a>
            ${this.generarBotonEliminarArchivo(archivo)}
          </div>
        </td>
      </tr>
    `;
  }

  /**
   * Actualizar breadcrumb del modal de historial
   */
  actualizarBreadcrumbHistorial(nombreCarpeta) {
    const breadcrumb = document.getElementById('breadcrumb_historial');
    if (!breadcrumb) return;

    if (nombreCarpeta === 'Inicio') {
      breadcrumb.innerHTML = '<li class="breadcrumb-item active" id="breadcrumb_inicio">Inicio</li>';
    } else {
      breadcrumb.innerHTML = `
        <li class="breadcrumb-item"><a href="#" onclick="planesAccion.navegarAInicioHistorial()">Inicio</a></li>
        <li class="breadcrumb-item active">${nombreCarpeta}</li>
      `;
    }
  }

  /**
   * Navegar al inicio en el modal de historial
   */
  navegarAInicioHistorial() {
    this.mostrarEstructuraCompletaHistorial(this.archivosRaizHistorial);
    this.actualizarBreadcrumbHistorial('Inicio');
  }

  /**
   * Obtener información del plan de acción por ID de registro
   */
  obtenerInfoPlanAccion(idRegistro) {
    if (!this.tabla) return null;
    
    const data = this.tabla.rows().data().toArray();
    const plan = data.find(row => row.id_registro == idRegistro);
    
    return plan || null;
  }

  /**
   * Formatear tamaño de archivo
   */
  formatearTamano(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * Formatear fecha para mostrar
   */
  formatearFecha(fecha) {
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  /**
   * Eliminar archivo
   */
  async eliminarArchivo(idArchivo, nombreArchivo) {
    // FUNCIONALIDAD DE ELIMINACIÓN DESHABILITADA TEMPORALMENTE
    this.mostrarError("Funcionalidad deshabilitada", "La eliminación de archivos está deshabilitada temporalmente");
    return;
    
    // Código original comentado:
    // // Verificar permisos
    // if (!window.permisosUsuario || !window.permisosUsuario.puedeEliminarArchivos) {
    //   this.mostrarError("Sin permisos", "No tienes permisos para eliminar archivos");
    //   return;
    // }
    // 
    // try {
    //   const confirmacion = await Swal.fire({
    //     title: '¿Eliminar archivo?',
    //     text: `¿Estás seguro de que quieres eliminar "${nombreArchivo}"?`,
    //     icon: 'warning',
    //     showCancelButton: true,
    //     confirmButtonColor: '#d33',
    //     cancelButtonColor: '#3085d6',
    //     confirmButtonText: 'Sí, eliminar',
    //     cancelButtonText: 'Cancelar'
    //   });
    //
    //   if (confirmacion.isConfirmed) {
    //     this.mostrarCargando(true, "Eliminando archivo...", "Procesando eliminación del archivo");
    //
    //     const response = await fetch('ajax/eliminar_archivo_drive.php', {
    //       method: 'POST',
    //       headers: {
    //         'Content-Type': 'application/json',
    //       },
    //       body: JSON.stringify({
    //         id_archivo: idArchivo
    //       })
    //     });
    //
    //     const data = await response.json();
    //     this.mostrarCargando(false);
    //
    //     if (data.success) {
    //       this.mostrarExito('Archivo eliminado', 'El archivo se eliminó correctamente');
    //       // Recargar el historial
    //       const idCliente = document.getElementById('id_cliente_archivo').value;
    //       const idPlanAccion = document.getElementById('id_plan_accion_archivo').value;
    //       if (idCliente && idPlanAccion) {
    //         await this.cargarHistorialArchivos(idCliente, idPlanAccion);
    //       }
    //     } else {
    //       this.mostrarError('Error', data.message || 'No se pudo eliminar el archivo');
    //     }
    //   }
    // } catch (error) {
    //   this.mostrarCargando(false);
    //   console.error('Error eliminando archivo:', error);
    //   this.mostrarError('Error', 'Error inesperado al eliminar el archivo');
    // }
  }

  /**
   * Mostrar/ocultar indicador de carga mejorado
   */
  mostrarCargando(mostrar, mensaje = "Procesando...", subMensaje = "Por favor espere") {
    if (mostrar) {
      // Evitar múltiples overlays
      if (!document.getElementById("loader-overlay")) {
        const overlay = document.createElement("div");
        overlay.id = "loader-overlay";
        overlay.className = "loader-overlay";
        overlay.innerHTML = `
          <div class="loader-container">
            <div class="loader-spinner"></div>
            <p class="loader-text">${mensaje}</p>
            <p class="loader-subtext">${subMensaje}</p>
          </div>
        `;
        document.body.appendChild(overlay);
        
        // Deshabilitar todos los botones de envío
        this.deshabilitarBotones(true);
      }
    } else {
      const overlay = document.getElementById("loader-overlay");
      if (overlay) {
        overlay.remove();
      }
      
      // Rehabilitar todos los botones de envío
      this.deshabilitarBotones(false);
    }
  }

  /**
   * Deshabilitar/habilitar botones para evitar duplicidad
   */
  deshabilitarBotones(deshabilitar) {
    // Botón de subir archivo
    const btnSubirArchivo = document.getElementById("btn_subir_archivo");
    if (btnSubirArchivo) {
      if (deshabilitar) {
        btnSubirArchivo.disabled = true;
        btnSubirArchivo.classList.add('btn-loading');
      } else {
        btnSubirArchivo.disabled = false;
        btnSubirArchivo.classList.remove('btn-loading');
      }
    }

    // Botón de crear plan manual
    const btnCrearPlan = document.querySelector('button[type="submit"]');
    if (btnCrearPlan) {
      if (deshabilitar) {
        btnCrearPlan.disabled = true;
        btnCrearPlan.classList.add('btn-loading');
      } else {
        btnCrearPlan.disabled = false;
        btnCrearPlan.classList.remove('btn-loading');
      }
    }

    // Botón de crear carpeta
    const btnCrearCarpeta = document.getElementById("btn_crear_carpeta");
    if (btnCrearCarpeta) {
      if (deshabilitar) {
        btnCrearCarpeta.disabled = true;
        btnCrearCarpeta.classList.add('btn-loading');
      } else {
        btnCrearCarpeta.disabled = false;
        btnCrearCarpeta.classList.remove('btn-loading');
      }
    }

    // Botones de acción en la tabla
    const botonesAccion = document.querySelectorAll('.btn-accion');
    botonesAccion.forEach(boton => {
      if (deshabilitar) {
        boton.disabled = true;
        boton.classList.add('btn-loading');
      } else {
        boton.disabled = false;
        boton.classList.remove('btn-loading');
      }
    });
  }

  /**
   * Mostrar mensaje de éxito
   */
  mostrarExito(titulo, mensaje) {
    Swal.fire({
      icon: "success",
      title: titulo,
      text: mensaje,
      timer: 3000,
      showConfirmButton: false,
    });
  }

  /**
   * Mostrar mensaje de error
   */
  mostrarError(titulo, mensaje) {
    Swal.fire({
      icon: "error",
      title: titulo,
      text: mensaje,
    });
  }

  /**
   * Verificar si hay un cliente seleccionado desde la página anterior
   */
  async verificarClienteSeleccionado() {
    const clienteId = sessionStorage.getItem('cliente_seleccionado_id');
    const clienteNombre = sessionStorage.getItem('cliente_seleccionado_nombre');
    
    if (clienteId && clienteNombre) {
      // Obtener información completa del cliente
      await this.obtenerInformacionCliente(clienteId);
      
      // Cargar los planes de acción del cliente seleccionado
      this.cargarPlanesAccion(clienteId);
      
      // Mostrar botón para crear plan manual
      this.mostrarBotonCrearPlan();
      
      // Mostrar mensaje informativo
      this.mostrarExito(
        'Cliente seleccionado',
        `Se cargaron los planes de acción para: ${clienteNombre}`
      );
      
      // NO limpiar el sessionStorage para mantener la selección
    } else {
      // Si no hay cliente seleccionado, redirigir a la página de clientes
      this.mostrarError(
        'Cliente no seleccionado',
        'No se ha seleccionado ningún cliente. Redirigiendo a la página de clientes...'
      );
      
      // Redirigir después de 2 segundos
      setTimeout(() => {
        window.location.href = 'administradorClientes.php';
      }, 2000);
    }
  }

  /**
   * Obtener información completa del cliente
   */
  async obtenerInformacionCliente(idCliente) {
    try {
      const response = await fetch(`ajax/obtener_informacion_cliente.php?id_cliente=${idCliente}`);
      const data = await response.json();

      if (data.success && data.data) {
        this.mostrarClienteSeleccionado(data.data);
      } else {
        // Si no se puede obtener la información, usar datos del sessionStorage
        const datosFallback = {
          nombre_cliente: sessionStorage.getItem('cliente_seleccionado_nombre'),
          codigo_cliente: sessionStorage.getItem('cliente_seleccionado_codigo'),
          tipo_cliente: sessionStorage.getItem('cliente_seleccionado_tipo')
        };
        this.mostrarClienteSeleccionado(datosFallback);
      }
    } catch (error) {
      console.error('Error obteniendo información del cliente:', error);
      // Si hay error, usar datos del sessionStorage
      const datosFallback = {
        nombre_cliente: sessionStorage.getItem('cliente_seleccionado_nombre'),
        codigo_cliente: sessionStorage.getItem('cliente_seleccionado_codigo'),
        tipo_cliente: sessionStorage.getItem('cliente_seleccionado_tipo')
      };
      this.mostrarClienteSeleccionado(datosFallback);
    }
  }

  /**
   * Mostrar indicador visual del cliente seleccionado
   */
  mostrarClienteSeleccionado(datosCliente) {
    const infoDiv = document.getElementById('cliente_seleccionado_info');
    
    if (infoDiv) {
      const nombreCliente = datosCliente.nombre_cliente || 'Cliente no disponible';
      const codigoCliente = datosCliente.codigo_cliente || 'N/A';
      const tipoCliente = datosCliente.tipo_cliente || 'N/A';
      
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

  /**
   * Mostrar botón para crear plan manual
   */
  mostrarBotonCrearPlan() {
    const botonMostrar = document.getElementById('btn_mostrar_formulario');
    if (botonMostrar) {
      botonMostrar.style.display = 'block';
    }
  }

  /**
   * Ocultar indicador visual del cliente seleccionado
   */
  ocultarClienteSeleccionado() {
    const infoDiv = document.getElementById('cliente_seleccionado_info');
    if (infoDiv) {
      infoDiv.style.display = 'none';
    }
  }

  /**
   * Toggle de edición para una fila
   */
  toggleEdicion(idRegistro) {
    const fila = document.querySelector(`tr[data-id="${idRegistro}"]`);
    if (!fila) return;

    const botonEditar = fila.querySelector('.btn-editar');
    const camposEditables = fila.querySelectorAll('.campo-editable');
    
    if (botonEditar.classList.contains('editando')) {
      // Guardar cambios
      this.guardarCambios(idRegistro, camposEditables);
    } else {
      // Activar edición
      this.activarEdicion(idRegistro, camposEditables, botonEditar);
    }
  }

  /**
   * Activar modo de edición
   */
  activarEdicion(idRegistro, camposEditables, botonEditar) {
    camposEditables.forEach(campo => {
      const span = campo.querySelector('.valor-campo');
      const textarea = campo.querySelector('.input-editable');
      
      if (span && textarea) {
        span.style.display = 'none';
        textarea.style.display = 'block';
        
        // Ajustar altura del textarea al contenido
        this.ajustarAlturaTextarea(textarea);
        
        // Agregar evento para ajustar altura mientras se escribe
        textarea.addEventListener('input', () => this.ajustarAlturaTextarea(textarea));
        
        textarea.focus();
      }
    });

    // Cambiar botón a modo guardar
    botonEditar.classList.add('editando');
    botonEditar.innerHTML = '<i class="fa fa-save"></i><span class="btn-text">Guardar</span>';
    botonEditar.title = 'Guardar Cambios';
  }

  /**
   * Guardar cambios
   */
  async guardarCambios(idRegistro, camposEditables) {
    try {
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
        this.mostrarInfo('Sin cambios', 'No se detectaron cambios para guardar');
        this.cancelarEdicion(idRegistro);
        return;
      }

      this.mostrarCargando(true, "Actualizando plan...", "Guardando cambios en la base de datos");

      const response = await fetch('ajax/actualizar_descripciones_plan.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          id_registro: idRegistro,
          cambios: cambios
        })
      });

      const data = await response.json();
      this.mostrarCargando(false);

      if (data.success) {
        this.mostrarExito('Cambios guardados', 'Las descripciones se actualizaron correctamente');
        
        // Recargar los datos del cliente para reflejar los cambios
        const clienteId = sessionStorage.getItem('cliente_seleccionado_id');
        if (clienteId) {
          await this.cargarPlanesAccion(clienteId);
        }
        
        this.cancelarEdicion(idRegistro);
      } else {
        this.mostrarError('Error', data.message || 'No se pudieron guardar los cambios');
      }

    } catch (error) {
      this.mostrarCargando(false);
      console.error('Error guardando cambios:', error);
      this.mostrarError('Error', 'Error inesperado al guardar los cambios');
    }
  }

  /**
   * Cancelar edición
   */
  cancelarEdicion(idRegistro) {
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
  }

  /**
   * Mostrar mensaje informativo
   */
  mostrarInfo(titulo, mensaje) {
    Swal.fire({
      icon: "info",
      title: titulo,
      text: mensaje,
      timer: 2000,
      showConfirmButton: false,
    });
  }

  /**
   * Ajustar altura del textarea automáticamente
   */
  ajustarAlturaTextarea(textarea) {
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

  /**
   * Mostrar formulario de crear plan manual
   */
  mostrarFormularioPlan() {
    const formulario = document.getElementById('formulario_nuevo_plan');
    const botonMostrar = document.getElementById('btn_mostrar_formulario');
    
    if (formulario && botonMostrar) {
      formulario.style.display = 'block';
      botonMostrar.style.display = 'none';
      
      // Cargar listas de tópicos y periodicidades
      this.cargarListasPlanes();
    }
  }

  /**
   * Ocultar formulario de crear plan manual
   */
  ocultarFormularioPlan() {
    const formulario = document.getElementById('formulario_nuevo_plan');
    const botonMostrar = document.getElementById('btn_mostrar_formulario');
    
    if (formulario && botonMostrar) {
      formulario.style.display = 'none';
      botonMostrar.style.display = 'block';
      
      // Limpiar formulario
      document.getElementById('formulario_crear_plan').reset();
      document.getElementById('div_topico_otro').style.display = 'none';
      document.getElementById('div_periodicidad_otro').style.display = 'none';
    }
  }

  /**
   * Cargar listas de tópicos y periodicidades
   */
  async cargarListasPlanes() {
    try {
      const response = await fetch('ajax/obtener_listas_planes.php');
      const data = await response.json();

      if (data.success) {
        this.llenarSelectTopicos(data.data.topicos);
        this.llenarSelectPeriodicidades(data.data.periodicidades);
      } else {
        this.mostrarError('Error', 'No se pudieron cargar las listas');
      }
    } catch (error) {
      console.error('Error cargando listas:', error);
      this.mostrarError('Error', 'Error de conexión al cargar las listas');
    }
  }

  /**
   * Llenar select de tópicos
   */
  llenarSelectTopicos(topicos) {
    const select = document.getElementById('topicos_manual');
    if (select) {
      select.innerHTML = '<option value="">-- Seleccionar Tópico --</option>';
      
      topicos.forEach(topico => {
        const option = document.createElement('option');
        option.value = topico.numero_topico;
        option.textContent = topico.descripcion_topico;
        select.appendChild(option);
      });
      
      // Agregar opción "Otro"
      const optionOtro = document.createElement('option');
      optionOtro.value = 'otro';
      optionOtro.textContent = 'Otro';
      select.appendChild(optionOtro);
    }
  }

  /**
   * Llenar select de periodicidades
   */
  llenarSelectPeriodicidades(periodicidades) {
    const select = document.getElementById('periodicidad_manual');
    if (select) {
      select.innerHTML = '<option value="">-- Seleccionar Periodicidad --</option>';
      
      periodicidades.forEach(periodicidad => {
        const option = document.createElement('option');
        option.value = periodicidad.numero_periodicidad;
        option.textContent = periodicidad.descripcion_periodicidad;
        select.appendChild(option);
      });
      
      // Agregar opción "Otro"
      const optionOtro = document.createElement('option');
      optionOtro.value = 'otro';
      optionOtro.textContent = 'Otro';
      select.appendChild(optionOtro);
    }
  }

  /**
   * Toggle campo "Otro" según selección
   */
  toggleCampoOtro(valor, tipo) {
    const divOtro = document.getElementById(`div_${tipo}_otro`);
    const inputOtro = document.getElementById(`${tipo}_otro`);
    
    if (valor === 'otro') {
      divOtro.style.display = 'block';
      inputOtro.required = true;
    } else {
      divOtro.style.display = 'none';
      inputOtro.required = false;
      inputOtro.value = '';
    }
  }

  /**
   * Crear plan de acción manual
   */
  async crearPlanManual(event) {
    event.preventDefault();

    try {
      this.mostrarCargando(true, "Creando plan de acción...", "Guardando plan y creando estructura de carpetas");

      const formData = new FormData(document.getElementById('formulario_crear_plan'));
      
      // Obtener ID del cliente del sessionStorage
      const clienteId = sessionStorage.getItem('cliente_seleccionado_id');
      formData.append('id_cliente', clienteId);

      const response = await fetch('ajax/crear_plan_manual.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();
      this.mostrarCargando(false);

      if (data.success) {
        this.mostrarExito('Plan creado', 'El plan de acción se creó correctamente');
        
        // Ocultar formulario
        this.ocultarFormularioPlan();
        
        // Recargar la tabla de planes
        if (clienteId) {
          await this.cargarPlanesAccion(clienteId);
        }
      } else {
        this.mostrarError('Error', data.message || 'No se pudo crear el plan de acción');
      }

    } catch (error) {
      this.mostrarCargando(false);
      console.error('Error creando plan manual:', error);
      this.mostrarError('Error', 'Error inesperado al crear el plan de acción');
    }
  }

  /**
   * Aprobar archivo pendiente
   */
  async aprobarArchivo(idArchivoPendiente) {
    try {
      const confirmacion = await Swal.fire({
        title: '¿Aprobar archivo?',
        text: '¿Estás seguro de que quieres aprobar este archivo? Se subirá a Google Drive.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, aprobar',
        cancelButtonText: 'Cancelar'
      });

      if (confirmacion.isConfirmed) {
        this.mostrarCargando(true, "Aprobando archivo...", "Subiendo archivo a Google Drive");

        const response = await fetch('ajax/aprobar_archivo_pendiente.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id_archivo_pendiente: idArchivoPendiente
          })
        });

        const data = await response.json();
        this.mostrarCargando(false);

        if (data.success) {
          this.mostrarExito("Archivo Aprobado", "El archivo ha sido aprobado y subido a Google Drive exitosamente");
          // Recargar la lista de archivos pendientes
          
          await this.cargarArchivosPendientes(this.clienteActualHistorial?.id, this.planAccionActualHistorial?.id_plan_accion);
        } else {
          this.mostrarError("Error", data.message || "No se pudo aprobar el archivo");
        }
      }
    } catch (error) {
      console.error("Error aprobando archivo:", error);
      this.mostrarError("Error de conexión", "No se pudo aprobar el archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar modal personalizado para rechazo de archivo
   */
  async mostrarModalRechazo() {
    return new Promise((resolve) => {
      // Crear modal HTML
      const modalHtml = `
        <div class="modal fade" id="modalRechazoArchivo" tabindex="-1" role="dialog" aria-labelledby="modalRechazoArchivoLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalRechazoArchivoLabel">Rechazar Archivo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                <p>¿Por qué motivo rechazas este archivo?</p>
                <div class="form-group">
                  <label for="comentarioRechazo">Motivo del rechazo:</label>
                  <textarea 
                    class="form-control" 
                    id="comentarioRechazo" 
                    rows="4" 
                    placeholder="Escribe el motivo del rechazo (mínimo 10 caracteres)..."
                    style="resize: vertical; min-height: 100px;"
                  ></textarea>
                  <small class="form-text text-muted">Mínimo 10 caracteres requeridos</small>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btnConfirmarRechazo">Rechazar</button>
              </div>
            </div>
          </div>
      </div>
    `;

      // Remover modal existente si existe
      const existingModal = document.getElementById('modalRechazoArchivo');
      if (existingModal) {
        existingModal.remove();
      }

      // Agregar modal al DOM
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      // Mostrar modal
      $('#modalRechazoArchivo').modal('show');

      // Configurar eventos
      const textarea = document.getElementById('comentarioRechazo');
      const btnConfirmar = document.getElementById('btnConfirmarRechazo');

      // Enfocar textarea al mostrar el modal
      $('#modalRechazoArchivo').on('shown.bs.modal', function () {
        textarea.focus();
      });

      // Validar textarea en tiempo real
      textarea.addEventListener('input', function() {
        const valor = this.value.trim();
        if (valor.length >= 10) {
          btnConfirmar.disabled = false;
          btnConfirmar.classList.remove('btn-secondary');
          btnConfirmar.classList.add('btn-danger');
        } else {
          btnConfirmar.disabled = true;
          btnConfirmar.classList.remove('btn-danger');
          btnConfirmar.classList.add('btn-secondary');
        }
      });

      // Botón confirmar
      btnConfirmar.addEventListener('click', function() {
        const comentario = textarea.value.trim();
        if (comentario.length >= 10) {
          $('#modalRechazoArchivo').modal('hide');
          resolve(comentario);
        }
      });

      // Botón cancelar o cerrar modal
      $('#modalRechazoArchivo').on('hidden.bs.modal', function () {
        $(this).remove();
        resolve(null);
      });

      // Enter para confirmar
      textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.ctrlKey) {
          const comentario = this.value.trim();
          if (comentario.length >= 10) {
            $('#modalRechazoArchivo').modal('hide');
            resolve(comentario);
          }
        }
      });
    });
  }

  /**
   * Rechazar archivo pendiente
   */
  async rechazarArchivo(idArchivoPendiente) {
    try {
      // Crear modal personalizado para el textarea
      const comentario = await this.mostrarModalRechazo();
      
      if (comentario && comentario.trim().length >= 10) {
        this.mostrarCargando(true, "Rechazando archivo...", "Procesando rechazo del archivo");

        const response = await fetch('ajax/rechazar_archivo_pendiente.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id_archivo_pendiente: idArchivoPendiente,
            comentario_rechazo: comentario.trim()
          })
        });

        const data = await response.json();
        this.mostrarCargando(false);

        if (data.success) {
          this.mostrarExito("Archivo Rechazado", "El archivo ha sido rechazado exitosamente");
          // Recargar la lista de archivos pendientes
          
          await this.cargarArchivosPendientes(this.clienteActualHistorial?.id, this.planAccionActualHistorial?.id_plan_accion);
        } else {
          this.mostrarError("Error", data.message || "No se pudo rechazar el archivo");
        }
      }
    } catch (error) {
      console.error("Error rechazando archivo:", error);
      this.mostrarError("Error de conexión", "No se pudo rechazar el archivo");
      this.mostrarCargando(false);
    }
  }

  /**
   * Ver detalles de archivo
   */
  verDetallesArchivo(idArchivoPendiente) {
    // Implementar modal de detalles si es necesario
    this.mostrarInfo("Detalles del Archivo", "Función de detalles en desarrollo");
  }

  /**
   * Visualizar archivo antes de aprobar
   */
  visualizarArchivo(idArchivoPendiente, nombreArchivo, rutaArchivo) {
    try {
      // Construir la URL del endpoint de visualización
      const urlVisualizacion = `ajax/visualizar_archivo.php?id_archivo=${idArchivoPendiente}`;
      
      // Abrir archivo en nueva pestaña usando el endpoint
      window.open(urlVisualizacion, '_blank');
    } catch (error) {
      console.error('Error al visualizar archivo:', error);
      this.mostrarError("Error", "No se pudo abrir el archivo. Intente descargarlo.");
    }
  }

  /**
   * Mostrar imagen en modal
   */
  mostrarImagenEnModal(nombreArchivo, urlArchivo) {
    const modalHtml = `
      <div class="modal fade" id="modalVisualizarImagen" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="fa fa-image mr-2"></i>${nombreArchivo}
              </h5>
              <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body text-center">
              <img src="${urlArchivo}" class="img-fluid" alt="${nombreArchivo}" style="max-height: 70vh;">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <a href="${urlArchivo}" class="btn btn-primary" download="${nombreArchivo}">
                <i class="fa fa-download mr-1"></i>Descargar
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Remover modal anterior si existe
    $('#modalVisualizarImagen').remove();
    
    // Agregar modal al body
    $('body').append(modalHtml);
    
    // Mostrar modal
    $('#modalVisualizarImagen').modal('show');
  }

  /**
   * Mostrar texto en modal
   */
  mostrarTextoEnModal(nombreArchivo, urlArchivo) {
    // Cargar contenido del archivo de texto
    fetch(urlArchivo)
      .then(response => response.text())
      .then(texto => {
        const modalHtml = `
          <div class="modal fade" id="modalVisualizarTexto" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">
                    <i class="fa fa-file-text mr-2"></i>${nombreArchivo}
                  </h5>
                  <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <pre style="max-height: 60vh; overflow-y: auto; background-color: #f8f9fa; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-wrap: break-word;">${texto}</pre>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                  <a href="${urlArchivo}" class="btn btn-primary" download="${nombreArchivo}">
                    <i class="fa fa-download mr-1"></i>Descargar
                  </a>
                </div>
              </div>
            </div>
          </div>
        `;
        
        // Remover modal anterior si existe
        $('#modalVisualizarTexto').remove();
        
        // Agregar modal al body
        $('body').append(modalHtml);
        
        // Mostrar modal
        $('#modalVisualizarTexto').modal('show');
      })
      .catch(error => {
        console.error('Error al cargar archivo de texto:', error);
        this.mostrarError("Error", "No se pudo cargar el contenido del archivo.");
      });
  }

  /**
   * Mostrar documento en modal con Google Docs Viewer
   */
  mostrarDocumentoEnModal(nombreArchivo, googleViewerUrl) {
    const modalHtml = `
      <div class="modal fade" id="modalVisualizarDocumento" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">
                <i class="fa fa-file mr-2"></i>${nombreArchivo}
              </h5>
              <button type="button" class="close" data-dismiss="modal">
                <span>&times;</span>
              </button>
            </div>
            <div class="modal-body p-0">
              <iframe src="${googleViewerUrl}" width="100%" height="600px" frameborder="0"></iframe>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
              <a href="${googleViewerUrl}" class="btn btn-primary" target="_blank">
                <i class="fa fa-external-link mr-1"></i>Abrir en nueva ventana
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Remover modal anterior si existe
    $('#modalVisualizarDocumento').remove();
    
    // Agregar modal al body
    $('body').append(modalHtml);
    
    // Mostrar modal
    $('#modalVisualizarDocumento').modal('show');
  }

  /**
   * Mostrar opciones de descarga para archivos no visualizables
   */
  mostrarOpcionesDescarga(nombreArchivo, urlArchivo) {
    const extension = nombreArchivo.split('.').pop().toLowerCase();
    
    Swal.fire({
      title: 'Archivo no visualizable',
      html: `
        <div class="text-center">
          <i class="fa fa-file-o fa-3x text-muted mb-3"></i>
          <p><strong>${nombreArchivo}</strong></p>
          <p class="text-muted">Este tipo de archivo (.${extension}) no se puede visualizar directamente.</p>
          <p>¿Desea descargarlo para revisarlo?</p>
        </div>
      `,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: '<i class="fa fa-download mr-1"></i>Descargar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#007bff'
    }).then((result) => {
      if (result.isConfirmed) {
        // Crear enlace de descarga
        const link = document.createElement('a');
        link.href = urlArchivo;
        link.download = nombreArchivo;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
    });
  }

  /**
   * Recargar archivo rechazado
   */
  async recargarArchivo(idArchivoPendiente) {
    try {
      // Mostrar modal personalizado de Bootstrap
      this.mostrarModalRecarga(idArchivoPendiente);

    } catch (error) {
      console.error('Error recargando archivo:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Ocurrió un error al recargar el archivo',
        confirmButtonText: 'Aceptar'
      });
    }
  }

  /**
   * Mostrar modal personalizado para recarga de archivo
   */
  mostrarModalRecarga(idArchivoPendiente) {
    // Crear modal dinámicamente
    const modalHTML = `
      <div class="modal fade" id="modalRecargarArchivo" tabindex="-1" role="dialog" aria-labelledby="modalRecargarArchivoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalRecargarArchivoLabel">
                <i class="fa fa-upload text-warning"></i> Cargar Nuevo Archivo
              </h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="formRecargarArchivo" enctype="multipart/form-data">
                <div class="form-group">
                  <label for="archivo_nuevo" class="form-label">
                    <i class="fa fa-file-o"></i> Seleccionar Nuevo Archivo:
                  </label>
                  <input type="file" id="archivo_nuevo" name="archivo_nuevo" 
                         accept=".pdf,.docx,.doc,.xlsx,.xls,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.bmp,.webp,.txt,.csv,.log,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.m4v" 
                         class="form-control-file" required>
                  <small class="form-text text-muted">
                    <i class="fa fa-info-circle"></i> 
                    Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, archivos de texto, videos. Tamaño máximo: 100MB
                  </small>
                </div>
                <div class="form-group">
                  <label for="comentario_recarga" class="form-label">
                    <i class="fa fa-comment-o"></i> Comentarios (opcional):
                  </label>
                  <textarea id="comentario_recarga" name="comentario_recarga" 
                            class="form-control" rows="4" 
                            placeholder="Explique los cambios realizados en el archivo..."></textarea>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">
                <i class="fa fa-times"></i> Cancelar
              </button>
              <button type="button" class="btn btn-warning" id="btnRecargarArchivo">
                <i class="fa fa-upload"></i> Cargar Archivo
              </button>
            </div>
          </div>
        </div>
      </div>
    `;

    // Remover modal existente si existe
    const existingModal = document.getElementById('modalRecargarArchivo');
    if (existingModal) {
      existingModal.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Mostrar modal
    $('#modalRecargarArchivo').modal('show');

    // Configurar eventos
    $('#btnRecargarArchivo').off('click').on('click', () => {
      this.procesarRecargaArchivo(idArchivoPendiente);
    });

    // Limpiar modal cuando se cierre
    $('#modalRecargarArchivo').off('hidden.bs.modal').on('hidden.bs.modal', () => {
      $('#modalRecargarArchivo').remove();
    });
  }

  /**
   * Procesar recarga de archivo
   */
  async procesarRecargaArchivo(idArchivoPendiente) {
    try {
      const archivo = document.getElementById('archivo_nuevo').files[0];
      const comentario = document.getElementById('comentario_recarga').value;

      // Validaciones
      if (!archivo) {
        Swal.fire({
          icon: 'warning',
          title: 'Archivo requerido',
          text: 'Debe seleccionar un archivo',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      // Validar tamaño
      if (archivo.size > 100 * 1024 * 1024) {
        Swal.fire({
          icon: 'error',
          title: 'Archivo muy grande',
          text: 'El archivo no puede ser mayor a 100MB',
          confirmButtonText: 'Aceptar'
        });
        return;
      }

      // Cerrar modal
      $('#modalRecargarArchivo').modal('hide');

      // Mostrar loading
      Swal.fire({
        title: 'Cargando archivo...',
        text: 'Por favor espere mientras se procesa el archivo',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
          Swal.showLoading();
        }
      });

      // Preparar FormData para envío
      const uploadData = new FormData();
      uploadData.append('archivo_nuevo', archivo);
      uploadData.append('comentario', comentario);
      uploadData.append('id_archivo_rechazado', idArchivoPendiente);

      // Enviar archivo
      const response = await fetch('ajax/recargar_archivo_rechazado.php', {
        method: 'POST',
        body: uploadData
      });

      const result = await response.json();

      if (result.success) {
        Swal.fire({
          icon: 'success',
          title: 'Archivo Recargado',
          text: result.message,
          confirmButtonText: 'Aceptar'
        });

        // Recargar la lista de archivos pendientes
        await this.cargarArchivosPendientes(this.clienteActualHistorial?.id, this.planAccionActualHistorial?.id_plan_accion);
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: result.message,
          confirmButtonText: 'Aceptar'
        });
      }

    } catch (error) {
      console.error('Error procesando recarga:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Ocurrió un error al procesar el archivo',
        confirmButtonText: 'Aceptar'
      });
    }
  }
}

// Inicializar la clase cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  window.planesAccion = new PlanesAccionClientes();
});
