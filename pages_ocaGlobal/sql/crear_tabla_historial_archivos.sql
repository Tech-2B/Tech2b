-- Tabla para mantener historial completo de archivos subidos
CREATE TABLE IF NOT EXISTS `OCAGLOBAL_historialArchivos_planesAccion` (
  `id_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_registro` int(11) NOT NULL COMMENT 'ID del registro en la tabla principal',
  `id_cliente` int(11) NOT NULL COMMENT 'ID del cliente',
  `id_plan_accion` int(11) NOT NULL COMMENT 'ID del plan de acción',
  `id_usuario_subio` int(11) NOT NULL COMMENT 'ID del usuario que subió el archivo',
  `nombre_archivo_original` varchar(255) NOT NULL COMMENT 'Nombre original del archivo',
  `ruta_archivo_temporal` text NOT NULL COMMENT 'Ruta temporal del archivo',
  `tamano_archivo` bigint(20) NOT NULL COMMENT 'Tamaño en bytes',
  `tipo_archivo` varchar(50) NOT NULL COMMENT 'Tipo/extensión del archivo',
  `comentario_subida` text COMMENT 'Comentario al subir el archivo',
  `estatus_validacion` enum('pendiente','aprobado','rechazado','reemplazado') NOT NULL DEFAULT 'pendiente' COMMENT 'Estatus de validación',
  `comentario_rechazo` text COMMENT 'Comentario de rechazo si aplica',
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora de subida',
  `fecha_validacion` datetime NULL COMMENT 'Fecha y hora de validación',
  `id_usuario_valido` int(11) NULL COMMENT 'ID del usuario que validó (aprobó/rechazó)',
  `id_archivo_reemplazado` int(11) NULL COMMENT 'ID del archivo que fue reemplazado',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indica si el registro está activo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_historial`),
  KEY `idx_cliente_plan` (`id_cliente`, `id_plan_accion`),
  KEY `idx_usuario_subio` (`id_usuario_subio`),
  KEY `idx_estatus` (`estatus_validacion`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historial completo de archivos subidos por área de oportunidad y plan de acción';
