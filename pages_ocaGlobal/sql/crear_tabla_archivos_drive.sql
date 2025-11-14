-- Script para crear la tabla de archivos subidos a Google Drive
-- Base de datos: wwappb_field_test
-- Tabla: OCAGLOBAL_archivos_drive

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_archivos_drive` (
  `id_archivo` int(11) NOT NULL AUTO_INCREMENT,
  `id_archivo_drive` varchar(255) COLLATE utf8_spanish2_ci NOT NULL COMMENT 'ID del archivo en Google Drive',
  `nombre_archivo` varchar(500) COLLATE utf8_spanish2_ci NOT NULL,
  `nombre_archivo_original` varchar(500) COLLATE utf8_spanish2_ci NOT NULL COMMENT 'Nombre original del archivo subido',
  `tipo_archivo` varchar(100) COLLATE utf8_spanish2_ci NOT NULL COMMENT 'pdf, xlsx, docx, png, jpg, etc.',
  `tamano_archivo` bigint(20) NOT NULL COMMENT 'Tamaño en bytes',
  `id_carpeta_drive` varchar(255) COLLATE utf8_spanish2_ci NOT NULL COMMENT 'ID de la carpeta donde se subió',
  `id_cliente` int(11) NOT NULL,
  `id_plan_accion` int(11) DEFAULT NULL,
  `comentario` text COLLATE utf8_spanish2_ci DEFAULT NULL,
  `url_drive` text COLLATE utf8_spanish2_ci NOT NULL COMMENT 'URL de visualización en Drive',
  `url_descarga` text COLLATE utf8_spanish2_ci NOT NULL COMMENT 'URL de descarga directa',
  `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_subida` int(11) DEFAULT NULL,
  `estado_activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo (borrado lógico)',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archivo`),
  UNIQUE KEY `unique_id_archivo_drive` (`id_archivo_drive`),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_plan_accion` (`id_plan_accion`),
  KEY `idx_id_carpeta_drive` (`id_carpeta_drive`),
  KEY `idx_tipo_archivo` (`tipo_archivo`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  KEY `idx_estado_activo` (`estado_activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;
