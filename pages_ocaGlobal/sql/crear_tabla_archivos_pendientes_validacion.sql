-- Tabla para archivos pendientes de validación
-- Los archivos subidos por Colaboradores quedan aquí hasta ser validados por Administradores

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_archivos_pendientes_validacion` (
  `id_archivo_pendiente` int(11) NOT NULL AUTO_INCREMENT,
  `id_registro` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `id_plan_accion` int(11) NOT NULL,
  `id_carpeta_drive` varchar(255) NOT NULL,
  `nombre_archivo_original` varchar(500) NOT NULL,
  `nombre_archivo_sistema` varchar(500) NOT NULL,
  `ruta_archivo_temporal` varchar(1000) NOT NULL,
  `tipo_archivo` varchar(100) NOT NULL,
  `tamano_archivo` bigint(20) NOT NULL,
  `comentario` text,
  `estatus_validacion` enum('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
  `comentario_rechazo` text,
  `fecha_subida` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_validacion` datetime NULL,
  `id_usuario_subio` int(11) NOT NULL,
  `id_usuario_valido` int(11) NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_archivo_pendiente`),
  KEY `idx_id_registro` (`id_registro`),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_plan_accion` (`id_plan_accion`),
  KEY `idx_estatus_validacion` (`estatus_validacion`),
  KEY `idx_id_usuario_subio` (`id_usuario_subio`),
  KEY `idx_id_usuario_valido` (`id_usuario_valido`),
  KEY `idx_fecha_subida` (`fecha_subida`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comentarios de la tabla
ALTER TABLE `OCAGLOBAL_archivos_pendientes_validacion` 
COMMENT = 'Tabla para archivos pendientes de validación por Administradores';
