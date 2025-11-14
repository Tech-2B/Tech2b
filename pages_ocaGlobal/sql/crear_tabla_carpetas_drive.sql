-- Script para crear la tabla de carpetas de Google Drive
-- Base de datos: wwappb_field_test
-- Tabla: OCAGLOBAL_carpetas_drive

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_carpetas_drive` (
  `id_carpeta` int(11) NOT NULL AUTO_INCREMENT,
  `id_carpeta_drive` varchar(255) COLLATE utf8_spanish2_ci NOT NULL COMMENT 'ID de la carpeta en Google Drive',
  `nombre_carpeta` varchar(500) COLLATE utf8_spanish2_ci NOT NULL,
  `id_carpeta_padre` varchar(255) COLLATE utf8_spanish2_ci DEFAULT NULL COMMENT 'ID de la carpeta padre en Drive',
  `id_cliente` int(11) NOT NULL,
  `id_plan_accion` int(11) DEFAULT NULL COMMENT 'NULL para carpetas de cliente, ID del plan para subcarpetas',
  `tipo_carpeta` enum('cliente','area_oportunidad','plan_accion','subcarpeta') COLLATE utf8_spanish2_ci NOT NULL DEFAULT 'subcarpeta',
  `ruta_completa` text COLLATE utf8_spanish2_ci NOT NULL COMMENT 'Ruta completa de la carpeta',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_crea` int(11) DEFAULT NULL,
  `estado_activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo (borrado lógico)',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_carpeta`),
  UNIQUE KEY `unique_id_carpeta_drive` (`id_carpeta_drive`),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_plan_accion` (`id_plan_accion`),
  KEY `idx_id_carpeta_padre` (`id_carpeta_padre`),
  KEY `idx_tipo_carpeta` (`tipo_carpeta`),
  KEY `idx_estado_activo` (`estado_activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;
