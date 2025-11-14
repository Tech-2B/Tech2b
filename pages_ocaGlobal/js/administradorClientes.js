/**
 * JavaScript para el administrador de clientes
 * Utiliza Fetch API para peticiones AJAX
 * Maneja formularios, tablas y validaciones
 */

class AdministradorClientes {
  constructor() {
    this.tabla = null;
    this.init();
  }

  init() {
    this.configurarEventos();
    this.cargarClientes();
  }

  /**
   * Configurar eventos del formulario y botones
   */
  configurarEventos() {
    // Evento para el formulario de guardar cliente
    const formCliente = document.getElementById("form_cliente");
    if (formCliente) {
      formCliente.addEventListener("submit", (e) => this.guardarCliente(e));
    }

    // Evento para validar nombre en tiempo real
    const nombreCliente = document.getElementById("nombre_cliente");
    if (nombreCliente) {
      nombreCliente.addEventListener("blur", () => this.validarNombreCliente());
    }
  }

  /**
   * Inicializar DataTable
   */
  inicializarTabla() {
    if ($.fn.DataTable) {
      // Verificar si ya existe una instancia de DataTable
      if ($.fn.DataTable.isDataTable('#tabla_clientes')) {
        console.log("DataTable ya existe, destruyendo...");
        $('#tabla_clientes').DataTable().destroy();
      }
      
      this.tabla = $("#tabla_clientes").DataTable({
        language: {
          url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json",
        },
        responsive: true,
        pageLength: 10,
        order: [[0, "asc"]],
        columnDefs: [
          {
            targets: -1, // Última columna (acciones)
            orderable: false,
            searchable: false,
          },
        ],
        columns: [
          // { data: "id_cliente", visible: false },
          { data: "id_cliente" },
          { data: "nombre_cliente" },
          { data: "codigo_cliente" },
          { data: "tipo_cliente" },
          { data: "nombre_contacto" },
          { data: "ciudad_estado" },
          {
            data: null,
            render: (data, type, row) => this.renderizarAcciones(row),
          },
        ],
      });
    }
  }

  /**
   * Renderizar botones de acciones en la tabla
   */
  renderizarAcciones(row) {
    return `
      <div class="btn-group" role="group" style="gap: 5px;">
        <button type="button" class="btn btn-sm btn-success btn-accion" 
              onclick="adminClientes.verPlanesAccion(${row.id_cliente}, '${row.nombre_cliente}', '${row.codigo_cliente || ''}', '${row.tipo_cliente || ''}')" 
              title="Ver Planes de Acción">
          <i class="fa fa-th-list"></i>
        </button>
        <button type="button" class="btn btn-sm btn-warning btn-accion" 
              onclick="adminClientes.editarCliente(${row.id_cliente})" 
              title="Editar">
          <i class="fa fa-edit"></i>
        </button>
        <button type="button" class="btn btn-sm btn-danger btn-accion" 
              onclick="adminClientes.eliminarCliente(${row.id_cliente}, '${row.nombre_cliente}')" 
              title="Eliminar">
          <i class="fa fa-trash"></i>
        </button>
      </div>
    `;
  }

  /**
   * Cargar lista de clientes
   */
  async cargarClientes() {
    try {
      console.log("Iniciando carga de clientes...");
      this.mostrarCargando(true, "Cargando clientes...", "Obteniendo información de la base de datos");

      const response = await fetch("ajax/obtener_clientes.php");
      console.log("Response status:", response.status);
      const data = await response.json();
      console.log("Datos recibidos:", data);

      // Manejar respuesta según código
      this.manejarRespuesta(data, 'cargar');
      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error en cargarClientes:", error);
      this.mostrarError(
        "Error de conexión",
        "No se pudo conectar con el servidor"
      );
      this.mostrarCargando(false);
    }
  }

  /**
   * Manejar respuesta del servidor según código
   */
  manejarRespuesta(data, operacion) {
    console.log(`Manejando respuesta para operación: ${operacion}`, data);
    
    switch (data.code) {
      case 200: // Encontrado/Éxito
        if (data.success) {
          console.log("Operación exitosa, actualizando tabla...");
          this.actualizarTablaCompleta(data.data);
        } else {
          console.log("Sin datos encontrados");
          this.actualizarTablaCompleta([]);
        }
        break;
        
      case 201: // Creado/Insertado
        if (data.success) {
          this.mostrarExito(data.title, data.message);
          this.actualizarTablaCompleta();
        } else {
          this.mostrarError(data.title, data.message);
        }
        break;
        
      case 202: // Actualizado
        if (data.success) {
          this.mostrarExito(data.title, data.message);
          this.actualizarTablaCompleta();
        } else {
          this.mostrarError(data.title, data.message);
        }
        break;
        
      case 203: // Eliminado
        if (data.success) {
          this.mostrarExito(data.title, data.message);
          this.actualizarTablaCompleta();
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
   * Actualizar tabla completa (crear/destruir)
   */
  async actualizarTablaCompleta(datos = null) {
    try {
      console.log("Actualizando tabla completa...");
      
      // Destruir tabla existente si existe
      if (this.tabla) {
        console.log("Destruyendo tabla existente...");
        this.tabla.destroy();
        this.tabla = null;
      }
      
      // Si no se proporcionan datos, obtenerlos del servidor
      if (datos === null) {
        console.log("Obteniendo datos del servidor...");
        const response = await fetch("ajax/obtener_clientes.php");
        const data = await response.json();
        
        if (data.success) {
          datos = data.data;
        } else {
          datos = [];
        }
      }
      
      // Limpiar tbody
      const tbody = document.querySelector('#tabla_clientes tbody');
      if (tbody) {
        tbody.innerHTML = '';
      }
      
      // Recrear tabla
      this.inicializarTabla();
      
      // Llenar tabla con datos
      if (this.tabla && datos) {
        console.log("Agregando", datos.length, "filas a la tabla...");
        this.tabla.rows.add(datos);
        this.tabla.draw();
        console.log("Tabla actualizada correctamente");
      }
      
    } catch (error) {
      console.error("Error al actualizar tabla completa:", error);
    }
  }

  /**
   * Guardar nuevo cliente
   */
  async guardarCliente(event) {
    event.preventDefault();

    try {
      this.mostrarCargando(true, "Guardando cliente...", "Creando registro y estructura de carpetas");

      const formData = new FormData(event.target);

      const response = await fetch("sql/guardar_cliente.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      console.log("Respuesta del servidor al guardar:", data);

      // Manejar respuesta según código
      this.manejarRespuesta(data, 'guardar');
      
      if (data.success) {
        this.limpiarFormulario();
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error en guardarCliente:", error);
      this.mostrarError(
        "Error de conexión",
        "No se pudo conectar con el servidor"
      );
      this.mostrarCargando(false);
    }
  }

  /**
   * Validar nombre de cliente en tiempo real
   */
  async validarNombreCliente() {
    const nombreCliente = document
      .getElementById("nombre_cliente")
      .value.trim();

    if (nombreCliente.length < 3) return;

    try {
      const response = await fetch(
        `ajax/validar_nombre_cliente.php?nombre_cliente=${encodeURIComponent(
          nombreCliente
        )}`
      );
      const data = await response.json();

      if (data.data && data.data.existe) {
        this.mostrarValidacion(
          "nombre_cliente",
          "Este nombre ya está en uso",
          "error"
        );
      } else {
        this.limpiarValidacion("nombre_cliente");
      }
    } catch (error) {
      console.error("Error validando nombre:", error);
    }
  }

  /**
   * Editar cliente
   */
  async editarCliente(idCliente) {
    try {
      this.mostrarCargando(true, "Cargando cliente...", "Obteniendo información para edición");

      const response = await fetch(
        `ajax/obtener_cliente_por_id.php?id_cliente=${idCliente}`
      );
      const data = await response.json();
      console.log("Respuesta del servidor:", data);
      
      if (data.success) {
        console.log("Abriendo modal de edición...");
        this.mostrarModalEdicion(data.data);
      } else {
        console.log("Error al obtener cliente:", data.message);
        this.mostrarError("Error", data.message);
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error en editarCliente:", error);
      this.mostrarError("Error de conexión", "No se pudo cargar el cliente");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar modal de edición
   */
  mostrarModalEdicion(cliente) {
    console.log("Datos del cliente para edición:", cliente);
    const modal = document.getElementById("modal_editar_cliente");
    const form = document.getElementById("formulario_editar_cliente");

    console.log("Modal encontrado:", modal);
    console.log("Form encontrado:", form);

    if (!modal) {
      console.error("No se encontró el modal con ID 'modal_editar_cliente'");
      return;
    }

    if (!form) {
      console.error("No se encontró el formulario con ID 'formulario_editar_cliente'");
      return;
    }

    // Cambiar título del modal
    const modalTitle = modal.querySelector(".modal-title");
    if (modalTitle) {
      modalTitle.textContent = "Editar cliente";
      console.log("Título del modal actualizado");
    } else {
      console.warn("No se encontró el título del modal");
    }

    // Llenar formulario con datos del cliente
    form.innerHTML = `
      <input type="hidden" name="id_cliente" value="${cliente.id_cliente}">
      <div class="form-group">
        <div class="form-row">
          <div class="col-md-6 mb-3">
              <label for="edit_nombre_cliente">Nombre del cliente:<span>&nbsp;*</span></label>
              <input class="form-control" type="text" id="edit_nombre_cliente" name="nombre_cliente" 
                      value="${cliente.nombre_cliente}" required>
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_codigo_cliente">Código del cliente:</label>
              <input class="form-control" type="text" id="edit_codigo_cliente" name="codigo_cliente" 
                      value="${cliente.codigo_cliente || ""}">
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_tipo_cliente">Tipo de cliente:</label>
              <input class="form-control" type="text" id="edit_tipo_cliente" name="tipo_cliente" 
                      value="${cliente.tipo_cliente || ""}">
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_nombre_contacto">Nombre contacto:</label>
              <input class="form-control" type="text" id="edit_nombre_contacto" name="nombre_contacto" 
                      value="${cliente.nombre_contacto || ""}">
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_telefono_cliente">Teléfono:</label>
              <input class="form-control" type="text" id="edit_telefono_cliente" name="telefono_cliente" 
                      value="${cliente.telefono_cliente || ""}">
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_correo_electronico">Correo electrónico:</label>
              <input class="form-control" type="email" id="edit_correo_electronico" name="correo_electronico" 
                      value="${cliente.correo_electronico || ""}">
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_direccion_cliente">Dirección:</label>
              <textarea class="form-control" id="edit_direccion_cliente" name="direccion_cliente" rows="3">${
                cliente.direccion_cliente || ""
              }</textarea>
          </div>
          <div class="col-md-6 mb-3">
              <label for="edit_ciudad_estado">Ciudad/Estado:</label>
              <input class="form-control" type="text" id="edit_ciudad_estado" name="ciudad_estado" 
                      value="${cliente.ciudad_estado || ""}">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar cambios</button>
      </div>
    `;

    // Agregar evento de envío
    form.onsubmit = (e) => this.actualizarCliente(e);

    // Mostrar modal
    console.log("Intentando mostrar el modal...");
    $(modal).modal("show");
    console.log("Modal mostrado");
  }

  /**
   * Actualizar cliente
   */
  async actualizarCliente(event) {
    event.preventDefault();

    try {
      this.mostrarCargando(true, "Actualizando cliente...", "Guardando cambios en la base de datos");

      const formData = new FormData(event.target);

      const response = await fetch("sql/actualizar_cliente.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      console.log("Respuesta del servidor al actualizar:", data);

      // Manejar respuesta según código
      this.manejarRespuesta(data, 'actualizar');
      
      if (data.success) {
        $("#modal_editar_cliente").modal("hide");
      }

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error en actualizarCliente:", error);
      this.mostrarError(
        "Error de conexión",
        "No se pudo conectar con el servidor"
      );
      this.mostrarCargando(false);
    }
  }

  /**
   * Ver detalles del cliente
   */
  async verDetalles(idCliente) {
    try {
      this.mostrarCargando(true);

      const response = await fetch(
        `ajax/obtener_cliente_por_id.php?id_cliente=${idCliente}`
      );
      const data = await response.json();

      if (data.success) {
        this.mostrarModalDetalles(data.data);
      } else {
        this.mostrarError("Error", data.message);
      }

      this.mostrarCargando(false);
    } catch (error) {
      this.mostrarError("Error de conexión", "No se pudo cargar el cliente");
      this.mostrarCargando(false);
    }
  }

  /**
   * Mostrar modal de detalles
   */
  mostrarModalDetalles(cliente) {
    const modal = document.getElementById("modal_editar_cliente");
    const form = document.getElementById("formulario_editar_cliente");

    // Cambiar título del modal
    const modalTitle = modal.querySelector(".modal-title");
    if (modalTitle) {
      modalTitle.textContent = "Detalles del Cliente";
    }

    form.innerHTML = `
      <div class="row">
        <div class="col-md-12">
          <h5 class="text-primary mb-3">Información del Cliente</h5>
        </div>
      </div>
      <div class="form-group">
        <div class="form-row">
          <div class="col-md-6 mb-3">
            <label><strong>Nombre del cliente:</strong></label>
            <p class="form-control-plaintext">${cliente.nombre_cliente}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Código del cliente:</strong></label>
            <p class="form-control-plaintext">${cliente.codigo_cliente || "No especificado"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Tipo de cliente:</strong></label>
            <p class="form-control-plaintext">${cliente.tipo_cliente || "No especificado"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Nombre contacto:</strong></label>
            <p class="form-control-plaintext">${cliente.nombre_contacto || "No especificado"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Teléfono:</strong></label>
            <p class="form-control-plaintext">${cliente.telefono_cliente || "No especificado"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Correo electrónico:</strong></label>
            <p class="form-control-plaintext">${cliente.correo_electronico || "No especificado"}</p>
          </div>
          <div class="col-md-12 mb-3">
            <label><strong>Dirección:</strong></label>
            <p class="form-control-plaintext">${cliente.direccion_cliente || "No especificada"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Ciudad/Estado:</strong></label>
            <p class="form-control-plaintext">${cliente.ciudad_estado || "No especificado"}</p>
          </div>
          <div class="col-md-6 mb-3">
            <label><strong>Fecha de creación:</strong></label>
            <p class="form-control-plaintext">${new Date(cliente.fecha_creacion).toLocaleDateString()}</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    `;

    // Mostrar modal
    $(modal).modal("show");
  }

  /**
   * Eliminar cliente
   */
  eliminarCliente(idCliente, nombreCliente) {
    Swal.fire({
      title: "¿Estás seguro?",
      text: `¿Deseas eliminar el cliente "${nombreCliente}"?`,
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#d33",
      cancelButtonColor: "#3085d6",
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
    }).then(async (result) => {
      if (result.isConfirmed) {
        await this.confirmarEliminacion(idCliente, nombreCliente);
      }
    });
  }

  /**
   * Confirmar eliminación
   */
  async confirmarEliminacion(idCliente, nombreCliente) {
    try {
      this.mostrarCargando(true, "Eliminando cliente...", "Procesando eliminación lógica");

      const formData = new FormData();
      formData.append("id_cliente", idCliente);

      console.log("Enviando petición de eliminación para cliente ID:", idCliente);

      const response = await fetch("sql/eliminar_cliente.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();
      console.log("Respuesta del servidor al eliminar:", data);

      // Manejar respuesta según código
      this.manejarRespuesta(data, 'eliminar');

      this.mostrarCargando(false);
    } catch (error) {
      console.error("Error en confirmarEliminacion:", error);
      this.mostrarError(
        "Error de conexión",
        "No se pudo conectar con el servidor"
      );
      this.mostrarCargando(false);
    }
  }

  /**
   * Limpiar formulario
   */
  limpiarFormulario() {
    const form = document.getElementById("form_cliente");
    if (form) {
      form.reset();
      this.limpiarTodasLasValidaciones();
    }
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
    // Botón de guardar cliente
    const btnGuardar = document.querySelector('input[type="submit"][name="btn_guardar"]');
    if (btnGuardar) {
      if (deshabilitar) {
        btnGuardar.disabled = true;
        btnGuardar.classList.add('btn-loading');
        btnGuardar.value = 'Guardando...';
      } else {
        btnGuardar.disabled = false;
        btnGuardar.classList.remove('btn-loading');
        btnGuardar.value = 'Guardar cliente';
      }
    }

    // Botón de actualizar cliente
    const btnActualizar = document.querySelector('button[type="submit"][id="btn_actualizar_cliente"]');
    if (btnActualizar) {
      if (deshabilitar) {
        btnActualizar.disabled = true;
        btnActualizar.classList.add('btn-loading');
      } else {
        btnActualizar.disabled = false;
        btnActualizar.classList.remove('btn-loading');
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
   * Mostrar validación de campo
   */
  mostrarValidacion(campoId, mensaje, tipo) {
    const campo = document.getElementById(campoId);
    if (campo) {
      campo.classList.add("is-invalid");

      // Remover mensaje anterior si existe
      const mensajeAnterior = campo.parentNode.querySelector(
        ".mensaje-validacion"
      );
      if (mensajeAnterior) {
        mensajeAnterior.remove();
      }

      // Agregar nuevo mensaje
      const divMensaje = document.createElement("div");
      divMensaje.className = `mensaje-validacion mensaje-${tipo}`;
      divMensaje.textContent = mensaje;
      campo.parentNode.appendChild(divMensaje);
    }
  }

  /**
   * Limpiar validación de campo
   */
  limpiarValidacion(campoId) {
    const campo = document.getElementById(campoId);
    if (campo) {
      campo.classList.remove("is-invalid");
      const mensaje = campo.parentNode.querySelector(".mensaje-validacion");
      if (mensaje) {
        mensaje.remove();
      }
    }
  }

  /**
   * Limpiar todas las validaciones
   */
  limpiarTodasLasValidaciones() {
    const campos = document.querySelectorAll(".is-invalid");
    campos.forEach((campo) => {
      campo.classList.remove("is-invalid");
    });

    const mensajes = document.querySelectorAll(".mensaje-validacion");
    mensajes.forEach((mensaje) => {
      mensaje.remove();
    });
  }

  /**
   * Recargar tabla completamente
   */
  async recargarTablaCompleta() {
    try {
      console.log("Recargando tabla completamente...");
      
      // Destruir tabla existente si existe
      if (this.tabla) {
        this.tabla.destroy();
        this.tabla = null;
      }
      
      // Recargar datos
      await this.cargarClientes();
      
    } catch (error) {
      console.error("Error al recargar tabla:", error);
    }
  }

  /**
   * Ver planes de acción del cliente
   */
  verPlanesAccion(idCliente, nombreCliente, codigoCliente = '', tipoCliente = '') {
    // Guardar información del cliente en sessionStorage para que la página de destino lo pueda usar
    sessionStorage.setItem('cliente_seleccionado_id', idCliente);
    sessionStorage.setItem('cliente_seleccionado_nombre', nombreCliente);
    sessionStorage.setItem('cliente_seleccionado_codigo', codigoCliente);
    sessionStorage.setItem('cliente_seleccionado_tipo', tipoCliente);
    
    // Redirigir a la página de planes de acción
    window.location.href = 'planesAccionClientes.php';
  }

}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", function () {
  window.adminClientes = new AdministradorClientes();
});
