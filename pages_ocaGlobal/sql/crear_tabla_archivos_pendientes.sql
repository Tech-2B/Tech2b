-- Tabla para archivos pendientes de validación
-- Los archivos subidos por Colaboradores quedan aquí hasta ser aprobados por Administradores

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_archivos_pendientes` (
  `id_archivo_pendiente` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_plan_accion` int(11) NOT NULL,
  `id_carpeta_drive` varchar(255) NOT NULL,
  `nombre_archivo_original` varchar(255) NOT NULL,
  `nombre_archivo_sistema` varchar(255) NOT NULL,
  `ruta_archivo_temporal` varchar(500) NOT NULL,
  `tipo_archivo` varchar(50) NOT NULL,
  `tamano_archivo` bigint(20) NOT NULL,
  `comentario` text,
  `id_usuario_subio` int(11) NOT NULL,
  `nombre_usuario_subio` varchar(255) NOT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_validacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `id_usuario_valido` int(11) DEFAULT NULL,
  `nombre_usuario_valido` varchar(255) DEFAULT NULL,
  `fecha_validacion` datetime DEFAULT NULL,
  `comentario_validacion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archivo_pendiente`),
  KEY `idx_cliente_plan` (`id_cliente`, `id_plan_accion`),
  KEY `idx_estado_validacion` (`estado_validacion`),
  KEY `idx_usuario_subio` (`id_usuario_subio`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionales para optimizar consultas
CREATE INDEX `idx_estado_fecha` ON `OCAGLOBAL_archivos_pendientes` (`estado_validacion`, `fecha_subida`);
CREATE INDEX `idx_usuario_estado` ON `OCAGLOBAL_archivos_pendientes` (`id_usuario_subio`, `estado_validacion`);
