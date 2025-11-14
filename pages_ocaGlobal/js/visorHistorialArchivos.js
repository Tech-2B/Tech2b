/**
 * JavaScript para el visor de historial de archivos
 * Solo lectura - permite navegar por carpetas y ver archivos
 */

class VisorHistorialArchivos {
  constructor() {
    this.tabla = null;
    this.tablaArchivos = null;
    this.clienteActual = null;
    this.planAccionActual = null;
    this.estructuraCarpetas = [];
    this.carpetaActual = null;
    this.init();
  }

  init() {
    this.configurarEventos();
    this.verificarClienteSeleccionado();
    
  }

  /**
   * Configurar eventos
   */
  configurarEventos() {
    // Cerrar modal al hacer click fuera de él
    window.onclick = (event) => {
      const modal = document.getElementById('modal_carpetas');
      if (event.target === modal) {
        this.cerrarModalCarpetas();
      }
    };

    // Cerrar modal con tecla Escape
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        this.cerrarModalCarpetas();
      }
    });
  }

  /**
   * Verificar si hay un cliente seleccionado
   */
  verificarClienteSeleccionado() {
    // Si el rol es "Cliente", usar el cliente automático
    if (window.rolUsuario === 'Cliente' && window.clienteAutomatico) {
      this.clienteActual = {
        id: window.clienteAutomatico.id_cliente,
        nombre: window.clienteAutomatico.nombre_cliente,
        codigo: window.clienteAutomatico.codigo_cliente,
        tipo: window.clienteAutomatico.tipo_cliente
      };

      // Mostrar información del cliente
      this.mostrarInformacionCliente();
      
      // Cargar planes de acción del cliente
      this.cargarPlanesAccion();
      return;
    }

    // Para administradores, obtener datos del cliente desde sessionStorage
    const clienteId = sessionStorage.getItem('cliente_seleccionado_id');
    const clienteNombre = sessionStorage.getItem('cliente_seleccionado_nombre');
    const clienteCodigo = sessionStorage.getItem('cliente_seleccionado_codigo');
    const clienteTipo = sessionStorage.getItem('cliente_seleccionado_tipo');

    if (clienteId && clienteNombre) {
      this.clienteActual = {
        id: clienteId,
        nombre: clienteNombre,
        codigo: clienteCodigo,
        tipo: clienteTipo
      };

      // Mostrar información del cliente
      this.mostrarInformacionCliente();
      
      // Cargar planes de acción del cliente
      this.cargarPlanesAccion();
    } else {
      this.mostrarError('Sin cliente seleccionado', 'Debe seleccionar un cliente desde la página de planes de acción');
    }
  }

  /**
   * Mostrar información del cliente seleccionado
   */
  mostrarInformacionCliente() {
    const elemento = document.getElementById('nombre_cliente_seleccionado');
    if (elemento && this.clienteActual) {
      let info = this.clienteActual.nombre;
      if (this.clienteActual.codigo) {
        info += ` (${this.clienteActual.codigo})`;
      }
      if (this.clienteActual.tipo) {
        info += ` - ${this.clienteActual.tipo}`;
      }
      elemento.textContent = info;
    }
  }

  /**
   * Cargar planes de acción del cliente
   */
  async cargarPlanesAccion() {
    try {
      this.mostrarCargando(true, "Cargando planes de acción...", "Obteniendo información del cliente");

      const response = await fetch(`ajax/obtener_planes_accion_cliente.php?id_cliente=${this.clienteActual.id}`);
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
    console.log(`Manejando respuesta para operación: ${operacion}`, data);
    
    switch (data.code) {
      case 200:
        if (data.success) {
          if (operacion === 'cargar') {
            this.mostrarPlanesAccionEnTabla(data.data);
          } else if (operacion === 'estructura') {
            this.mostrarEstructuraCarpetas(data.data);
          } else if (operacion === 'archivos') {
            this.mostrarArchivosEnTabla(data.data);
          }
        } else {
          this.mostrarInfo('Sin información', data.message || 'No se encontraron datos');
        }
        break;
      case 201:
        this.mostrarExito('Operación exitosa', data.message);
        break;
      case 500:
        this.mostrarError('Error', data.message);
        break;
      default:
        this.mostrarError('Error inesperado', 'Código de respuesta no reconocido');
    }
  }

  /**
   * Mostrar planes de acción en la tabla
   */
  mostrarPlanesAccionEnTabla(planes) {
    const tbody = document.getElementById('tabla_planes_accion_body');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (planes.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center text-muted">
            <i class="fa fa-info-circle"></i> No hay planes de acción disponibles
          </td>
        </tr>
      `;
      return;
    }

    planes.forEach(plan => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${plan.id_registro}</td>
        <td>${plan.descripcion_area_oportunidad}</td>
        <td>${plan.descripcion_plan_accion}</td>
        <td>${plan.descripcion_topico || 'N/A'}</td>
        <td>${plan.descripcion_entregable || 'N/A'}</td>
        <td>${plan.descripcion_periodicidad || 'N/A'}</td>
        <td>
          <button type="button" class="btn btn-sm btn-primary btn-ver-estructura" 
                  onclick="visorHistorial.verEstructuraCarpetas(${plan.id_registro}, ${plan.id_plan_accion}, '${plan.descripcion_plan_accion}')" 
                  title="Ver Estructura de Carpetas">
            <i class="fa fa-folder-open"></i> Ver Estructura
          </button>
        </td>
      `;
      tbody.appendChild(row);
    });

    this.inicializarTabla();
  }

  /**
   * Inicializar DataTable
   */
  inicializarTabla() {
    if ($.fn.DataTable) {
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
            targets: -1, // Última columna (Ver Estructura)
            orderable: false,
            searchable: false,
          },
        ]
      });
    }
  }

  /**
   * Ver estructura de carpetas de un plan de acción
   */
  async verEstructuraCarpetas(idRegistro, idPlanAccion, nombrePlan) {
    console.log("idRegistro", idRegistro);
    console.log("idPlanAccion", idPlanAccion);
    console.log("nombrePlan", nombrePlan);
    try {
      this.planAccionActual = { 
        id_registro: idRegistro, 
        id_plan_accion: idPlanAccion,
        nombre: nombrePlan
      };
      
      // Mostrar nombre del plan en el modal
      this.mostrarNombrePlan(nombrePlan);
      
      // Abrir modal y cargar estructura
      this.abrirModalCarpetas();
      await this.cargarEstructuraCarpetas();
    } catch (error) {
      console.error("Error cargando estructura:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar la estructura de carpetas");
    }
  }

  /**
   * Mostrar nombre del plan en el modal
   */
  mostrarNombrePlan(nombrePlan) {
    const elemento = document.getElementById('nombre_plan_seleccionado');
    if (elemento) {
      elemento.textContent = nombrePlan;
    }
  }

  /**
   * Cargar estructura de carpetas en el modal
   */
  async cargarEstructuraCarpetas() {
    try {
      this.mostrarCargando(true, "Cargando estructura...", "Obteniendo carpetas del plan de acción");

      const response = await fetch(`ajax/obtener_estructura_carpetas.php?id_cliente=${this.clienteActual.id}&id_plan_accion=${this.planAccionActual.id_plan_accion}`);
      const data = await response.json();

      this.manejarRespuesta(data, 'estructura');
      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error cargando estructura:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar la estructura de carpetas");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar estructura de carpetas en el modal
   */
  mostrarEstructuraCarpetas(estructura) {
    const contenedor = document.getElementById('arbol_carpetas_modal');
    if (!contenedor) return;

    this.estructuraCarpetas = estructura;

    if (estructura.length === 0) {
      contenedor.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-folder-open fa-2x mb-2"></i>
          <p>Este plan de acción no tiene carpetas creadas</p>
        </div>
      `;
      return;
    }

    // Mostrar todas las carpetas en orden (lista plana como obtener_carpetas_plan_accion.php)
    this.mostrarListaCarpetas(estructura);
  }

  /**
   * Mostrar lista de carpetas (versión plana como obtener_carpetas_plan_accion.php)
   */
  mostrarListaCarpetas(carpetas) {
    const contenedor = document.getElementById('arbol_carpetas_modal');
    if (!contenedor) return;

    let html = '';
    carpetas.forEach((carpeta, index) => {
      // Determinar el nivel basado en el tipo de carpeta
      const nivel = carpeta.tipo_carpeta === 'plan_accion' ? 0 : 1;
      html += this.generarHTMLCarpetaLista(carpeta, nivel, index);
    });

    contenedor.innerHTML = html;
  }

  /**
   * Mostrar árbol de carpetas (versión jerárquica - mantenida para compatibilidad)
   */
  mostrarArbolCarpetas(estructura) {
    const contenedor = document.getElementById('arbol_carpetas_modal');
    if (!contenedor) return;

    let html = '';
    estructura.forEach(carpeta => {
      html += this.generarHTMLCarpetaArbol(carpeta, 0);
    });

    contenedor.innerHTML = html;
  }

  /**
   * Generar HTML para una carpeta en la lista (versión plana)
   */
  generarHTMLCarpetaLista(carpeta, nivel, index) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    const prefijo = carpeta.tipo_carpeta === 'plan_accion' ? '📁' : '📂';
    
    let html = `
      <div class="carpeta-item ${nivelClass}" 
           data-id-carpeta="${carpeta.id_carpeta_drive}" 
           data-tipo="${carpeta.tipo_carpeta}"
           onclick="visorHistorial.seleccionarCarpeta('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta}')">
        <!-- <i class="fa ${icono} icono-carpeta"></i> -->
        <span class="nombre-carpeta">${prefijo} ${carpeta.nombre_carpeta}</span>
        <span class="contador-archivos" id="contador-${carpeta.id_carpeta_drive}">0</span>
      </div>
    `;

    return html;
  }

  /**
   * Generar HTML para una carpeta en el árbol (versión jerárquica - mantenida para compatibilidad)
   */
  generarHTMLCarpetaArbol(carpeta, nivel) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    
    let html = `
      <div class="carpeta-item ${nivelClass}" 
           data-id-carpeta="${carpeta.id_carpeta_drive}" 
           data-tipo="${carpeta.tipo_carpeta}"
           onclick="visorHistorial.seleccionarCarpeta('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta}')">
        <i class="fa ${icono} icono-carpeta"></i>
        <span class="nombre-carpeta">${carpeta.nombre_carpeta}</span>
        <span class="contador-archivos" id="contador-${carpeta.id_carpeta_drive}">0</span>
      </div>
    `;

    // Agregar subcarpetas recursivamente
    if (carpeta.subcarpetas && carpeta.subcarpetas.length > 0) {
      carpeta.subcarpetas.forEach(subcarpeta => {
        html += this.generarHTMLCarpetaArbol(subcarpeta, nivel + 1);
      });
    }

    return html;
  }

  /**
   * Cargar archivos de la carpeta raíz del plan de acción
   */
  async cargarArchivosCarpetaRaiz() {
    try {
      // Obtener archivos de la carpeta raíz (sin id_carpeta_drive específico)
      const response = await fetch(`ajax/obtener_archivos_carpeta_raiz.php?id_cliente=${this.clienteActual.id}&id_plan_accion=${this.planAccionActual.id_plan_accion}`);
      const data = await response.json();

      if (data.success) {
        // Actualizar la estructura existente con los archivos de la carpeta raíz
        this.actualizarEstructuraConArchivosRaiz(data.data);
      }
    } catch (error) {
      console.error("Error cargando archivos de carpeta raíz:", error);
    }
  }

  /**
   * Actualizar la estructura existente con archivos de la carpeta raíz
   */
  actualizarEstructuraConArchivosRaiz(archivosRaiz) {
    const contenedor = document.getElementById('lista_carpetas_modal');
    if (!contenedor) return;

    let html = '';

    // Mostrar carpetas principales
    this.estructuraCarpetas.forEach(carpeta => {
      html += this.generarHTMLCarpetaModalCompleta(carpeta, 0, []);
    });

    // Mostrar archivos de la carpeta raíz
    if (archivosRaiz && archivosRaiz.length > 0) {
      archivosRaiz.forEach(archivo => {
        html += this.generarHTMLArchivoLista(archivo);
      });
    }

    contenedor.innerHTML = html;
  }

  /**
   * Mostrar estructura completa con carpetas y archivos
   */
  mostrarEstructuraCompleta(archivosRaiz) {
    const contenedor = document.getElementById('lista_carpetas_modal');
    if (!contenedor) return;

    let html = '';

    // Mostrar carpetas principales
    this.estructuraCarpetas.forEach(carpeta => {
      html += this.generarHTMLCarpetaModalCompleta(carpeta, 0, archivosRaiz);
    });

    // Mostrar archivos de la carpeta raíz
    if (archivosRaiz && archivosRaiz.length > 0) {
      archivosRaiz.forEach(archivo => {
        html += this.generarHTMLArchivoLista(archivo);
      });
    }

    contenedor.innerHTML = html;
  }

  /**
   * Generar HTML para una carpeta en el modal con funcionalidad de expandir/contraer
   */
  generarHTMLCarpetaModalCompleta(carpeta, nivel, archivosRaiz) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    const tieneSubcarpetas = carpeta.subcarpetas && carpeta.subcarpetas.length > 0;
    const expandirId = `expandir-${carpeta.id_carpeta_drive}`;
    const subcarpetasId = `subcarpetas-${carpeta.id_carpeta_drive}`;
    
    let html = `
      <li class="carpeta-modal-item ${nivelClass}" 
          data-id-carpeta="${carpeta.id_carpeta_drive}" 
          data-tipo="${carpeta.tipo_carpeta}">
        <button class="btn-expandir" id="${expandirId}" 
                onclick="visorHistorial.toggleSubcarpetas('${carpeta.id_carpeta_drive}')" 
                style="display: ${tieneSubcarpetas ? 'inline-block' : 'none'}">
          <i class="fa fa-chevron-right"></i>
        </button>
        <i class="fa ${icono} icono-carpeta"></i>
        <span class="nombre-carpeta" onclick="visorHistorial.seleccionarCarpetaModal('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta}')">${carpeta.nombre_carpeta}</span>
        <span class="contador-archivos" id="contador-${carpeta.id_carpeta_drive}">0</span>
      </li>
    `;

    // Contenedor de subcarpetas (inicialmente oculto)
    if (tieneSubcarpetas) {
      html += `<div class="subcarpetas-contenedor" id="${subcarpetasId}">`;
      carpeta.subcarpetas.forEach(subcarpeta => {
        html += this.generarHTMLCarpetaModalCompleta(subcarpeta, nivel + 1, []);
      });
      html += `</div>`;
    }

    return html;
  }


  /**
   * Generar HTML para una carpeta en el modal (versión simple para compatibilidad)
   */
  generarHTMLCarpetaModal(carpeta, nivel) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    
    let html = `
      <li class="carpeta-modal-item ${nivelClass}" 
          data-id-carpeta="${carpeta.id_carpeta_drive}" 
          data-tipo="${carpeta.tipo_carpeta}"
          onclick="visorHistorial.seleccionarCarpetaModal('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta}')">
        <i class="fa ${icono} icono-carpeta"></i>
        <span class="nombre-carpeta">${carpeta.nombre_carpeta}</span>
        <span class="contador-archivos" id="contador-${carpeta.id_carpeta_drive}">0</span>
      </li>
    `;

    // Agregar subcarpetas recursivamente
    if (carpeta.subcarpetas && carpeta.subcarpetas.length > 0) {
      carpeta.subcarpetas.forEach(subcarpeta => {
        html += this.generarHTMLCarpetaModal(subcarpeta, nivel + 1);
      });
    }

    return html;
  }

  /**
   * Generar HTML para una carpeta y sus subcarpetas (mantener para compatibilidad)
   */
  generarHTMLCarpeta(carpeta, nivel) {
    const nivelClass = nivel > 0 ? `nivel-${nivel}` : '';
    const icono = carpeta.tipo_carpeta === 'plan_accion' ? 'fa-folder' : 'fa-folder';
    
    let html = `
      <div class="carpeta-item ${nivelClass}" 
           data-id-carpeta="${carpeta.id_carpeta_drive}" 
           data-tipo="${carpeta.tipo_carpeta}"
           onclick="visorHistorial.seleccionarCarpeta('${carpeta.id_carpeta_drive}', '${carpeta.nombre_carpeta}')">
        <i class="fa ${icono} icono-carpeta"></i>
        <span class="nombre-carpeta">${carpeta.nombre_carpeta}</span>
        <span class="contador-archivos" id="contador-${carpeta.id_carpeta_drive}">0</span>
      </div>
    `;

    // Agregar subcarpetas recursivamente
    if (carpeta.subcarpetas && carpeta.subcarpetas.length > 0) {
      carpeta.subcarpetas.forEach(subcarpeta => {
        html += this.generarHTMLCarpeta(subcarpeta, nivel + 1);
      });
    }

    return html;
  }

  /**
   * Abrir modal de carpetas
   */
  abrirModalCarpetas() {
    const modal = document.getElementById('modal_carpetas');
    if (modal) {
      modal.style.display = 'block';
      // La estructura se carga desde verEstructuraCarpetas
    }
  }

  /**
   * Toggle expandir/contraer subcarpetas
   */
  toggleSubcarpetas(idCarpetaDrive) {
    const expandirBtn = document.getElementById(`expandir-${idCarpetaDrive}`);
    const subcarpetasContenedor = document.getElementById(`subcarpetas-${idCarpetaDrive}`);
    
    if (expandirBtn && subcarpetasContenedor) {
      const estaExpandido = subcarpetasContenedor.classList.contains('mostrar');
      
      if (estaExpandido) {
        // Contraer
        subcarpetasContenedor.classList.remove('mostrar');
        expandirBtn.classList.remove('expandido');
        expandirBtn.innerHTML = '<i class="fa fa-chevron-right"></i>';
      } else {
        // Expandir
        subcarpetasContenedor.classList.add('mostrar');
        expandirBtn.classList.add('expandido');
        expandirBtn.innerHTML = '<i class="fa fa-chevron-down"></i>';
        
        // Cargar archivos de la subcarpeta si no se han cargado
        this.cargarArchivosSubcarpeta(idCarpetaDrive, subcarpetasContenedor);
      }
    }
  }

  /**
   * Cargar archivos de una subcarpeta específica
   */
  async cargarArchivosSubcarpeta(idCarpetaDrive, contenedor) {
    try {
      // Verificar si ya se cargaron los archivos
      if (contenedor.querySelector('.archivos-cargados')) {
        return;
      }

      const response = await fetch(`ajax/obtener_archivos_carpeta.php?id_cliente=${this.clienteActual.id}&id_plan_accion=${this.planAccionActual.id_plan_accion}&id_carpeta_drive=${idCarpetaDrive}`);
      const data = await response.json();

      if (data.success && data.data.length > 0) {
        let htmlArchivos = '<div class="archivos-cargados">';
        data.data.forEach(archivo => {
          htmlArchivos += this.generarHTMLArchivoModal(archivo);
        });
        htmlArchivos += '</div>';
        
        contenedor.insertAdjacentHTML('beforeend', htmlArchivos);
      }
    } catch (error) {
      console.error("Error cargando archivos de subcarpeta:", error);
    }
  }

  /**
   * Navegar a una carpeta específica en el modal
   */
  navegarACarpetaModal(tipo) {
    if (tipo === 'root') {
      // Volver a la vista raíz
      this.mostrarEstructuraCompleta([]);
      this.actualizarBreadcrumbModal('Inicio');
    }
  }

  /**
   * Actualizar breadcrumb del modal
   */
  actualizarBreadcrumbModal(nombreCarpeta) {
    const breadcrumb = document.getElementById('breadcrumb_modal');
    if (!breadcrumb) return;

    breadcrumb.innerHTML = `
      <li class="breadcrumb-item">
        <a href="#" onclick="visorHistorial.navegarACarpetaModal('root')">
          <i class="fa fa-home"></i> Inicio
        </a>
      </li>
      <li class="breadcrumb-item active">
        <i class="fa fa-folder"></i> ${nombreCarpeta}
      </li>
    `;
  }

  /**
   * Seleccionar un archivo desde el modal
   */
  seleccionarArchivoModal(idArchivoDrive, nombreArchivo) {
    // Aquí puedes implementar la lógica para ver o descargar el archivo
    Swal.fire({
      icon: 'info',
      title: 'Archivo Seleccionado',
      text: `Has seleccionado: ${nombreArchivo}`,
      timer: 2000,
      showConfirmButton: false
    });
  }

  /**
   * Cerrar modal de carpetas
   */
  cerrarModalCarpetas() {
    const modal = document.getElementById('modal_carpetas');
    if (modal) {
      modal.style.display = 'none';
      // Limpiar selección y ocultar área de archivos
      this.mostrarAreaArchivos(false);
      document.querySelectorAll('.arbol-carpetas-modal .carpeta-item').forEach(item => {
        item.classList.remove('seleccionada');
      });
    }
  }

  /**
   * Seleccionar una carpeta desde el modal
   */
  async seleccionarCarpeta(idCarpetaDrive, nombreCarpeta) {
    try {
      console.log('=== SELECCIONANDO CARPETA ===');
      console.log('ID Carpeta Drive:', idCarpetaDrive);
      console.log('Nombre Carpeta:', nombreCarpeta);
      console.log('Cliente Actual:', this.clienteActual);
      console.log('Plan Acción Actual:', this.planAccionActual);
      
      // Actualizar estado visual en el árbol
      this.actualizarSeleccionCarpetaArbol(idCarpetaDrive);
      
      // Mostrar área de archivos
      this.mostrarAreaArchivos(true);
      
      // Mostrar indicador de carga
      this.mostrarCargandoArchivos(true, `Cargando archivos de "${nombreCarpeta}"...`);

      // Cargar archivos de la carpeta seleccionada
      const url = `ajax/obtener_archivos_carpeta.php?id_cliente=${this.clienteActual.id}&id_plan_accion=${this.planAccionActual.id_plan_accion}&id_carpeta_drive=${idCarpetaDrive}`;
      console.log('URL de la petición:', url);
      
      const response = await fetch(url);
      const data = await response.json();
      
      console.log('Respuesta del servidor:', data);

      this.mostrarCargandoArchivos(false);
      
      if (data.success) {
        console.log('Archivos encontrados:', data.data.length);
        this.mostrarArchivosEnModal(data.data, nombreCarpeta);
      } else {
        console.log('No se encontraron archivos o error en la respuesta');
        this.mostrarArchivosEnModal([], nombreCarpeta);
      }
    } catch (error) {
      console.error("Error cargando archivos:", error);
      this.mostrarCargandoArchivos(false);
      this.mostrarArchivosEnModal([], nombreCarpeta);
    }
  }

  /**
   * Actualizar selección visual en el árbol de carpetas
   */
  actualizarSeleccionCarpetaArbol(idCarpetaDrive) {
    // Remover selección anterior
    document.querySelectorAll('.arbol-carpetas-modal .carpeta-item').forEach(item => {
      item.classList.remove('seleccionada');
    });

    // Agregar selección a la carpeta actual
    const carpetaSeleccionada = document.querySelector(`.arbol-carpetas-modal [data-id-carpeta="${idCarpetaDrive}"]`);
    if (carpetaSeleccionada) {
      carpetaSeleccionada.classList.add('seleccionada');
    }
  }

  /**
   * Mostrar/ocultar área de archivos
   */
  mostrarAreaArchivos(mostrar) {
    const areaArchivos = document.getElementById('area_archivos');
    if (areaArchivos) {
      areaArchivos.style.display = mostrar ? 'block' : 'none';
    }
  }

  /**
   * Mostrar indicador de carga para archivos
   */
  mostrarCargandoArchivos(mostrar, mensaje = "Cargando archivos...") {
    const contenedor = document.getElementById('lista_archivos_modal');
    if (!contenedor) return;

    if (mostrar) {
      contenedor.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-spinner fa-spin fa-2x mb-2"></i>
          <p>${mensaje}</p>
        </div>
      `;
    } else {
      // No limpiar el contenido cuando mostrar es false
      // El contenido se actualizará con mostrarArchivosEnModal
    }
  }

  /**
   * Mostrar archivos en el modal
   */
  mostrarArchivosEnModal(archivos, nombreCarpeta) {
    console.log('=== MOSTRANDO ARCHIVOS EN MODAL ===');
    console.log('Archivos recibidos:', archivos);
    console.log('Nombre carpeta:', nombreCarpeta);
    
    const contenedor = document.getElementById('lista_archivos_modal');
    if (!contenedor) {
      console.error('❌ No se encontró el contenedor lista_archivos_modal');
      return;
    }
    
    console.log('✅ Contenedor encontrado:', contenedor);

    if (archivos.length === 0) {
      console.log('No hay archivos, mostrando mensaje de "sin archivos"');
      contenedor.innerHTML = `
        <div class="text-center text-muted">
          <i class="fa fa-file fa-2x mb-2"></i>
          <p>No hay archivos en "${nombreCarpeta}"</p>
        </div>
      `;
      return;
    }

    console.log(`Generando HTML para ${archivos.length} archivos`);
    
    // Crear estructura de tabla
    let html = `
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="thead-primary">
            <tr>
              <th><i class="fa fa-file"></i> Archivo</th>
              <th><i class="fa fa-comment"></i> Comentario</th>
              <th><i class="fa fa-weight"></i> Tamaño</th>
              <th><i class="fa fa-calendar"></i> Fecha</th>
              <th><i class="fa fa-cogs"></i> Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    archivos.forEach((archivo, index) => {
      console.log(`Generando HTML para archivo ${index + 1}:`, archivo.nombre_archivo_original);
      html += this.generarHTMLArchivoModal(archivo);
    });
    
    html += `
          </tbody>
        </table>
      </div>
    `;

    contenedor.innerHTML = html;
    console.log('✅ HTML generado e insertado en el contenedor');
  }

  /**
   * Generar HTML para un archivo en el modal (formato tabla)
   */
  generarHTMLArchivoModal(archivo) {
    return `
      <tr onclick="visorHistorial.seleccionarArchivo('${archivo.id_archivo_drive}', '${archivo.nombre_archivo_original}')" style="cursor: pointer;">
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
          <div class="btn-group" role="group">
            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); visorHistorial.verArchivo('${archivo.url_drive}')" title="Ver archivo">
              <i class="fa fa-eye"></i>
            </button>
            <button class="btn btn-sm btn-outline-success" onclick="event.stopPropagation(); visorHistorial.descargarArchivo('${archivo.url_descarga}', '${archivo.nombre_archivo_original}')" title="Descargar archivo">
              <i class="fa fa-download"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }

  /**
   * Generar HTML para un archivo en formato de lista (para otros contextos)
   */
  generarHTMLArchivoLista(archivo) {
    return `
      <div class="archivo-item" onclick="visorHistorial.seleccionarArchivo('${archivo.id_archivo_drive}', '${archivo.nombre_archivo_original}')">
        <i class="fa ${archivo.icono_tipo.icono} icono-archivo ${archivo.icono_tipo.clase}"></i>
        <span class="nombre-archivo">${archivo.nombre_archivo_original}</span>
        <span class="nombre-archivo">${archivo.comentario}</span>
        <span class="tamano-archivo">${archivo.tamano_formateado}</span>
        <span class="fecha-archivo">${archivo.fecha_formateada}</span>
        <div class="acciones-archivo">
          <button class="btn-accion-archivo" onclick="event.stopPropagation(); visorHistorial.verArchivo('${archivo.url_drive}')" title="Ver archivo">
            <i class="fa fa-eye"></i>
          </button>
          <button class="btn-accion-archivo" onclick="event.stopPropagation(); visorHistorial.descargarArchivo('${archivo.url_descarga}', '${archivo.nombre_archivo_original}')" title="Descargar archivo">
            <i class="fa fa-download"></i>
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Seleccionar un archivo
   */
  seleccionarArchivo(idArchivoDrive, nombreArchivo) {
    Swal.fire({
      icon: 'info',
      title: 'Archivo Seleccionado',
      text: `Has seleccionado: ${nombreArchivo}`,
      timer: 2000,
      showConfirmButton: false
    });
  }


  /**
   * Actualizar selección visual de carpeta
   */
  actualizarSeleccionCarpeta(idCarpetaDrive) {
    // Remover clase activa de todas las carpetas
    document.querySelectorAll('.carpeta-item').forEach(item => {
      item.classList.remove('activa');
    });

    // Agregar clase activa a la carpeta seleccionada
    const carpetaSeleccionada = document.querySelector(`[data-id-carpeta="${idCarpetaDrive}"]`);
    if (carpetaSeleccionada) {
      carpetaSeleccionada.classList.add('activa');
    }
  }

  /**
   * Actualizar breadcrumb de navegación
   */
  actualizarBreadcrumb(nombreCarpeta) {
    const breadcrumb = document.getElementById('breadcrumb_navegacion');
    if (!breadcrumb) return;

    breadcrumb.innerHTML = `
      <li class="breadcrumb-item">
        <a href="#" onclick="visorHistorial.navegarACarpeta('root')">
          <i class="fa fa-home"></i> Inicio
        </a>
      </li>
      <li class="breadcrumb-item active">
        <i class="fa fa-folder"></i> ${nombreCarpeta}
      </li>
    `;
  }

  /**
   * Navegar a una carpeta específica
   */
  navegarACarpeta(tipo) {
    if (tipo === 'root') {
      // Limpiar selección
      document.querySelectorAll('.carpeta-item').forEach(item => {
        item.classList.remove('activa');
      });
      
      // Ocultar sección de archivos
      const seccionArchivos = document.getElementById('seccion_archivos');
      if (seccionArchivos) {
        seccionArchivos.style.display = 'none';
      }
      
      // Resetear breadcrumb
      const breadcrumb = document.getElementById('breadcrumb_navegacion');
      if (breadcrumb) {
        breadcrumb.innerHTML = `
          <li class="breadcrumb-item">
            <a href="#" onclick="visorHistorial.navegarACarpeta('root')">
              <i class="fa fa-home"></i> Inicio
            </a>
          </li>
        `;
      }
      
      this.carpetaActual = null;
    }
  }

  /**
   * Mostrar archivos en la tabla
   */
  mostrarArchivosEnTabla(archivos) {
    const tbody = document.getElementById('tabla_archivos_carpeta_body');
    const seccionArchivos = document.getElementById('seccion_archivos');
    
    if (!tbody || !seccionArchivos) return;

    // Mostrar la sección de archivos
    seccionArchivos.style.display = 'block';

    tbody.innerHTML = '';

    if (archivos.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" class="text-center text-muted">
            <i class="fa fa-file"></i> No hay archivos en esta carpeta
          </td>
        </tr>
      `;
      return;
    }

    archivos.forEach(archivo => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="col-nombre">
          <i class="fa ${archivo.icono_tipo.icono} icono-tipo-archivo ${archivo.icono_tipo.clase}"></i>
          ${archivo.nombre_archivo_original}
        </td>
        <td class="col-tipo">
          <span class="badge badge-secondary">${archivo.tipo_archivo}</span>
        </td>
        <td class="col-tamano">
          <span class="tamano-archivo">${archivo.tamano_formateado}</span>
        </td>
        <td class="col-comentarios">${archivo.comentario || 'Sin comentarios'}</td>
        <td class="col-fecha">${archivo.fecha_formateada}</td>
        <td class="col-usuario">${archivo.nombre_usuario}</td>
        <td class="col-acciones">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-ver-archivo" 
                    onclick="visorHistorial.verArchivo('${archivo.url_drive}')" 
                    title="Ver archivo">
              <i class="fa fa-eye"></i> Ver
            </button>
            <button type="button" class="btn btn-sm btn-descargar-archivo" 
                    onclick="visorHistorial.descargarArchivo('${archivo.url_descarga}', '${archivo.nombre_archivo_original}')" 
                    title="Descargar archivo">
              <i class="fa fa-download"></i> Descargar
            </button>
          </div>
        </td>
      `;
      tbody.appendChild(row);
    });

    this.inicializarTablaArchivos();
  }

  /**
   * Inicializar DataTable para archivos
   */
  inicializarTablaArchivos() {
    if ($.fn.DataTable) {
      if ($.fn.DataTable.isDataTable('#tabla_archivos_carpeta')) {
        $('#tabla_archivos_carpeta').DataTable().destroy();
      }
      
      this.tablaArchivos = $("#tabla_archivos_carpeta").DataTable({
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
        order: [[4, "desc"]], // Ordenar por fecha de subida descendente
        // dom: 'lfrtip', // Configurar elementos del DOM sin cabecera adicional
        columnDefs: [
          {
            targets: -1, // Última columna (Acciones)
            orderable: false,
            searchable: false,
          },
        ]
      });
    }
  }

  /**
   * Ver archivo en nueva pestaña
   */
  verArchivo(urlDrive) {
    if (urlDrive) {
      window.open(urlDrive, '_blank');
    } else {
      this.mostrarError('Error', 'URL del archivo no disponible');
    }
  }

  /**
   * Descargar archivo
   */
  descargarArchivo(urlDescarga, nombreArchivo) {
    if (urlDescarga) {
      // Crear enlace temporal para descarga
      const link = document.createElement('a');
      link.href = urlDescarga;
      link.download = nombreArchivo;
      link.target = '_blank';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } else {
      this.mostrarError('Error', 'URL de descarga no disponible');
    }
  }

  /**
   * Mostrar/ocultar indicador de carga
   */
  mostrarCargando(mostrar, mensaje = "Procesando...", subMensaje = "Por favor espere") {
    if (mostrar) {
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
      }
    } else {
      const overlay = document.getElementById("loader-overlay");
      if (overlay) {
        overlay.remove();
      }
    }
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
   * Mostrar mensaje de información
   */
  mostrarInfo(titulo, mensaje) {
    Swal.fire({
      icon: "info",
      title: titulo,
      text: mensaje,
    });
  }

}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  window.visorHistorial = new VisorHistorialArchivos();
});
