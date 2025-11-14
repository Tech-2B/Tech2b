# Sistema de Planes de Acción de Clientes

## Descripción
Sistema completo para la gestión de planes de acción de clientes con integración a Google Drive para el almacenamiento de archivos.

## Características Principales

### 1. Gestión de Clientes
- **Alta de clientes** con información completa
- **Creación automática de planes de acción** al dar de alta un cliente
- **Estructura de carpetas en Google Drive** creada automáticamente

### 2. Planes de Acción
- **Áreas de Oportunidad**: Definidas en la tabla `OCAGLOBAL_lista_areasOportunidades`
- **Planes de Acción**: Relacionados con cada área de oportunidad
- **Tópicos**: Múltiples tópicos por plan de acción (almacenados como IDs separados por comas)
- **Entregables**: Un entregable por plan de acción
- **Periodicidades**: Múltiples periodicidades por plan de acción (almacenadas como IDs separados por comas)

### 3. Gestión de Archivos
- **Subida de archivos** a Google Drive
- **Creación de subcarpetas** dinámicas
- **Historial de archivos** por plan de acción
- **Validación de tipos de archivo** (PDF, Word, Excel, PowerPoint, imágenes, archivos de texto, videos)
- **Límite de tamaño**: 100MB por archivo

## Estructura de Base de Datos

### Tablas Principales

#### 1. OCAGLOBAL_planes_accion_clientes
Almacena los planes de acción asociados a cada cliente.

```sql
- id_registro (PK)
- id_cliente (FK)
- id_area_oportunidad
- descripcion_area_oportunidad
- id_plan_accion
- descripcion_plan_accion
- id_topico (IDs separados por comas: "1,2,3")
- descripcion_topico (Concatenado con <br><br>)
- id_entregable
- descripcion_entregable
- id_periodicidad (IDs separados por comas: "1,3")
- descripcion_periodicidad (Concatenado con <br><br>)
- fecha_creacion
- id_usuario_crea
- estado_activo
```

#### 2. OCAGLOBAL_carpetas_drive
Almacena información de las carpetas creadas en Google Drive.

```sql
- id_carpeta (PK)
- id_carpeta_drive (ID en Google Drive)
- nombre_carpeta
- id_carpeta_padre
- id_cliente (FK)
- id_plan_accion
- tipo_carpeta (cliente, area_oportunidad, plan_accion, subcarpeta)
- ruta_completa
- fecha_creacion
- id_usuario_crea
- estado_activo
```

#### 3. OCAGLOBAL_archivos_drive
Almacena información de los archivos subidos a Google Drive.

```sql
- id_archivo (PK)
- id_archivo_drive (ID en Google Drive)
- nombre_archivo
- nombre_archivo_original
- tipo_archivo
- tamano_archivo
- id_carpeta_drive
- id_cliente (FK)
- id_plan_accion
- comentario
- url_drive
- url_descarga
- fecha_subida
- id_usuario_subida
- estado_activo
```

## Estructura de Carpetas en Google Drive

```
CARPETA PRINCIPAL (ID: 1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi)
└── [NOMBRE_CLIENTE]
    └── [DESCRIPCION_AREA_OPORTUNIDAD]
        └── [DESCRIPCION_PLAN_ACCION]
            └── [SUBCARPETAS_CREADAS_DINAMICAMENTE]
```

## Archivos del Sistema

### Backend (PHP)
- `sql/guardar_cliente.php` - Modificado para incluir planes de acción
- `sql/subir_archivo_drive.php` - Subida de archivos a Google Drive
- `includes/funcionesPlanesAccion.php` - Funciones para manejo de planes de acción
- `includes/funcionesGoogleDrive.php` - Funciones para integración con Google Drive

### Frontend (HTML/JS)
- `planesAccionClientes.php` - Página principal de gestión
- `js/planesAccionClientes.js` - Lógica del frontend
- `css/administradorClientes.css` - Estilos (reutilizado)

### AJAX
- `ajax/obtener_planes_accion_cliente.php` - Obtener planes de un cliente
- `ajax/obtener_carpetas_plan_accion.php` - Obtener carpetas de un plan
- `ajax/obtener_historial_archivos.php` - Obtener historial de archivos

## Flujo de Trabajo

### 1. Alta de Cliente
1. Usuario llena formulario de cliente
2. Sistema valida datos y guarda cliente
3. Sistema obtiene todos los planes de acción disponibles
4. Sistema guarda planes de acción del cliente en BD
5. Sistema crea estructura de carpetas en Google Drive
6. Sistema guarda información de carpetas en BD

### 2. Gestión de Archivos
1. Usuario selecciona cliente
2. Sistema muestra planes de acción del cliente
3. Usuario hace clic en "Cargar Archivo"
4. Sistema muestra carpetas disponibles o permite crear nueva
5. Usuario selecciona archivo y comenta
6. Sistema sube archivo a Google Drive
7. Sistema guarda información del archivo en BD

### 3. Historial de Archivos
1. Usuario hace clic en "Historial de Archivos"
2. Sistema muestra todos los archivos del plan de acción
3. Usuario puede ver o descargar archivos

## Configuración Requerida

### Google Drive API
1. **Credenciales**: Archivo JSON en `drive/credenciales/ocaconstruccion-b8ddbf846879.json`
2. **Carpeta Principal**: ID `1X6a3VpVkasNg-7dxtukxu7ZQjfGSOcDi`
3. **Librería**: Google API Client v8.0 en `../../phpLibraries/googleApiClient_8_0/`

### Base de Datos
1. **Tablas de listas**: Deben estar pobladas con datos de referencia
2. **Permisos**: Usuario debe tener permisos para crear tablas y modificar datos
3. **Charset**: UTF-8 con collation `utf8_spanish2_ci`

## Validaciones Implementadas

### Cliente
- Nombre único
- Campos obligatorios
- Sanitización de datos

### Archivos
- Tipos permitidos: PDF, Word, Excel, PowerPoint, imágenes (PNG, JPG, GIF, BMP, WEBP), archivos de texto (TXT, CSV, LOG), videos (MP4, AVI, MOV, WMV, FLV, WEBM, MKV, M4V)
- Tamaño máximo: 100MB
- Validación de archivo seleccionado

### Carpetas
- Validación de permisos en Google Drive
- Verificación de existencia de carpeta padre
- Nombres únicos por carpeta padre

## Códigos de Respuesta

- **200**: Operación exitosa
- **201**: Recurso creado exitosamente
- **202**: Recurso actualizado exitosamente
- **203**: Recurso eliminado exitosamente
- **500**: Error general del servidor

## Consideraciones de Seguridad

1. **Consultas preparadas** para prevenir SQL injection
2. **Validación de tipos de archivo** para prevenir uploads maliciosos
3. **Límites de tamaño** para prevenir ataques DoS
4. **Sanitización de datos** de entrada
5. **Manejo de errores** sin exposición de información sensible

## Mantenimiento

### Logs
- Errores se registran en `error_log` de PHP
- Información de debug disponible en consola del navegador

### Backup
- Recomendado backup regular de la base de datos
- Google Drive mantiene versiones de archivos automáticamente

### Monitoreo
- Verificar logs de errores regularmente
- Monitorear uso de espacio en Google Drive
- Validar integridad de datos periódicamente
