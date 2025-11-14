-- Script para crear la tabla de planes de acción de clientes
-- Base de datos: wwappb_field_test
-- Tabla: OCAGLOBAL_planes_accion_clientes

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_planes_accion_clientes` (
  `id_registro` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `id_area_oportunidad` int(11) NOT NULL,
  `descripcion_area_oportunidad` text COLLATE utf8_spanish2_ci NOT NULL,
  `id_plan_accion` int(11) NOT NULL,
  `descripcion_plan_accion` text COLLATE utf8_spanish2_ci NOT NULL,
  `id_topico` text COLLATE utf8_spanish2_ci DEFAULT NULL COMMENT 'IDs separados por comas: 1,2,3',
  `descripcion_topico` text COLLATE utf8_spanish2_ci DEFAULT NULL COMMENT 'Descripciones concatenadas con <br><br>',
  `id_entregable` int(11) NOT NULL,
  `descripcion_entregable` text COLLATE utf8_spanish2_ci NOT NULL,
  `id_periodicidad` text COLLATE utf8_spanish2_ci DEFAULT NULL COMMENT 'IDs separados por comas: 1,3',
  `descripcion_periodicidad` text COLLATE utf8_spanish2_ci DEFAULT NULL COMMENT 'Descripciones concatenadas con <br><br>',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario_crea` int(11) DEFAULT NULL,
  `estado_activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo (borrado lógico)',
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `id_usuario_actualiza` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_registro`),
  KEY `idx_id_cliente` (`id_cliente`),
  KEY `idx_id_area_oportunidad` (`id_area_oportunidad`),
  KEY `idx_id_plan_accion` (`id_plan_accion`),
  KEY `idx_id_entregable` (`id_entregable`),
  KEY `idx_estado_activo` (`estado_activo`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish2_ci;
