# Visor de Historial de Archivos - Documentación

## 📋 Descripción

El **Visor de Historial de Archivos** es una página de solo lectura que permite navegar por la estructura de carpetas y subcarpetas de los planes de acción de los clientes, mostrando todos los archivos organizados jerárquicamente.

## 🎯 Características Principales

### ✅ **Solo Lectura**
- **Sin edición**: No se pueden modificar planes de acción
- **Sin subida**: No se pueden subir nuevos archivos
- **Sin creación**: No se pueden crear nuevas carpetas
- **Solo visualización**: Únicamente ver y descargar archivos existentes

### 📁 **Navegación por Carpetas**
- **Estructura jerárquica**: Muestra carpetas y subcarpetas por niveles
- **Navegación visual**: Click en carpetas para explorar su contenido
- **Breadcrumb**: Ruta de navegación clara
- **Indicadores visuales**: Iconos y contadores de archivos

### 📄 **Gestión de Archivos**
- **Lista detallada**: Nombre, tipo, tamaño, fecha, usuario
- **Iconos por tipo**: PDF, Excel, Word, imágenes, etc.
- **Acciones disponibles**: Ver y descargar archivos
- **Información completa**: Comentarios y metadatos

## 🚀 Cómo Usar

### **1. Acceso a la Página**
- Desde el menú lateral: **"Visor de Historial"**
- Desde planes de acción: Botón **"Visor de Historial"**
- Requiere cliente seleccionado previamente

### **2. Navegación**
1. **Seleccionar Plan**: Click en "Ver Estructura" de cualquier plan
2. **Explorar Carpetas**: Click en carpetas para ver su contenido
3. **Ver Archivos**: Los archivos aparecen en la tabla inferior
4. **Navegar**: Usar breadcrumb para volver a carpetas padre

### **3. Acciones con Archivos**
- **Ver**: Abre el archivo en Google Drive
- **Descargar**: Descarga el archivo localmente

## 🏗️ Arquitectura Técnica

### **Archivos Principales**
```
visorHistorialArchivos.php          # Página principal
js/visorHistorialArchivos.js        # Lógica JavaScript
css/visorHistorialArchivos.css      # Estilos específicos
```

### **Endpoints AJAX**
```
ajax/obtener_estructura_carpetas.php  # Obtener estructura jerárquica
ajax/obtener_archivos_carpeta.php     # Obtener archivos de carpeta
```

### **Base de Datos**
- **Tabla principal**: `OCAGLOBAL_carpetas_drive`
- **Tabla archivos**: `OCAGLOBAL_archivos_drive`
- **Relaciones**: Cliente → Plan → Carpetas → Archivos

## 🎨 Interfaz de Usuario

### **Secciones Principales**
1. **Información del Cliente**: Datos del cliente seleccionado
2. **Estructura de Carpetas**: Árbol jerárquico navegable
3. **Planes de Acción**: Lista de planes disponibles
4. **Archivos de Carpeta**: Tabla con archivos de la carpeta seleccionada

### **Elementos Visuales**
- **Iconos de carpetas**: Diferentes para cada tipo
- **Indentación**: Niveles visuales para subcarpetas
- **Contadores**: Número de archivos por carpeta
- **Breadcrumb**: Ruta de navegación actual
- **Tablas responsivas**: Con DataTables

## 📱 Responsive Design

### **Móviles**
- Árbol de carpetas compacto
- Tablas con scroll horizontal
- Botones de acción adaptados
- Navegación táctil optimizada

### **Desktop**
- Vista completa con todas las columnas
- Navegación con mouse
- Hover effects en elementos
- Tooltips informativos

## 🔧 Funcionalidades Técnicas

### **JavaScript**
- **Clase principal**: `VisorHistorialArchivos`
- **Navegación**: Sistema de breadcrumb dinámico
- **Carga asíncrona**: AJAX para obtener datos
- **Estado persistente**: Mantiene selección de cliente

### **CSS**
- **Estilos específicos**: Para árbol de carpetas
- **Animaciones**: Transiciones suaves
- **Responsive**: Media queries para móviles
- **Tema consistente**: Con el resto de la aplicación

### **PHP Backend**
- **Consultas optimizadas**: JOINs eficientes
- **Estructura jerárquica**: Algoritmo recursivo
- **Formateo de datos**: Tamaños, fechas, iconos
- **Validaciones**: Parámetros de entrada

## 🛡️ Seguridad

### **Validaciones**
- **Parámetros requeridos**: ID cliente y plan de acción
- **Sanitización**: Datos de entrada limpiados
- **Consultas preparadas**: Prevención de SQL injection
- **Permisos**: Solo lectura, sin modificaciones

### **Manejo de Errores**
- **Try-catch**: Captura de excepciones
- **Logging**: Registro de errores
- **Mensajes amigables**: Para el usuario
- **Fallbacks**: Estados de error manejados

## 📊 Rendimiento

### **Optimizaciones**
- **Carga bajo demanda**: Solo cuando se necesita
- **Cache de datos**: Evita consultas repetidas
- **Paginación**: Tablas con límite de registros
- **Índices**: Base de datos optimizada

### **Métricas**
- **Tiempo de carga**: < 2 segundos
- **Memoria**: Uso eficiente de recursos
- **Red**: Consultas mínimas necesarias
- **UX**: Feedback visual inmediato

## 🔄 Flujo de Trabajo

### **1. Inicialización**
```
Usuario accede → Verifica cliente seleccionado → Carga planes de acción
```

### **2. Exploración**
```
Selecciona plan → Carga estructura → Navega carpetas → Ve archivos
```

### **3. Acciones**
```
Click en archivo → Ver/Descargar → Regresa a navegación
```

## 🎯 Casos de Uso

### **Auditoría**
- Revisar archivos subidos por otros usuarios
- Verificar estructura de carpetas
- Consultar historial de cambios

### **Consulta**
- Buscar archivos específicos
- Navegar por organización de proyectos
- Ver metadatos de archivos

### **Descarga**
- Obtener archivos para uso local
- Backup de documentos importantes
- Compartir archivos fuera del sistema

## 🚀 Próximas Mejoras

### **Funcionalidades Futuras**
- **Búsqueda**: Filtro por nombre de archivo
- **Filtros**: Por tipo, fecha, usuario
- **Vista previa**: Imágenes y PDFs inline
- **Exportación**: Lista de archivos en Excel

### **Mejoras Técnicas**
- **Cache**: Redis para consultas frecuentes
- **CDN**: Archivos estáticos optimizados
- **PWA**: Funcionalidad offline
- **API**: Endpoints REST para integraciones

## 📞 Soporte

Para reportar problemas o solicitar mejoras, contactar al equipo de desarrollo con:
- Descripción del problema
- Pasos para reproducir
- Capturas de pantalla
- Información del navegador

---

**Versión**: 1.0.0  
**Última actualización**: Enero 2025  
**Autor**: Sistema OCA Global
