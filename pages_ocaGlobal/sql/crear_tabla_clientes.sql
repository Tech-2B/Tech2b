-- Script para crear la tabla de clientes
-- Base de datos: wwappb_field_test
-- Tabla: OCAGLOBAL_clientes

CREATE TABLE IF NOT EXISTS `OCAGLOBAL_clientes` (
  `id_cliente` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_cliente` varchar(255) NOT NULL,
  `codigo_cliente` varchar(100) DEFAULT NULL,
  `tipo_cliente` varchar(100) DEFAULT NULL,
  `nombre_contacto` varchar(255) DEFAULT NULL,
  `telefono_cliente` varchar(20) DEFAULT NULL,
  `correo_electronico` varchar(255) DEFAULT NULL,
  `direccion_cliente` text DEFAULT NULL,
  `ciudad_estado` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo (borrado lógico)',
  `id_usuario_creacion` int(11) DEFAULT NULL,
  `id_usuario_actualizacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `unique_nombre_cliente` (`nombre_cliente`),
  KEY `idx_codigo_cliente` (`codigo_cliente`),
  KEY `idx_activo` (`activo`),
  KEY `idx_fecha_creacion` (`fecha_creacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

-- Insertar datos de ejemplo (opcional)
INSERT INTO `OCAGLOBAL_clientes` 
(`nombre_cliente`, `codigo_cliente`, `tipo_cliente`, `nombre_contacto`, `telefono_cliente`, `correo_electronico`, `direccion_cliente`, `ciudad_estado`, `activo`) 
VALUES 
('Cliente Demo 1', 'CLI001', 'Empresa', 'Juan Pérez', '555-1234', 'juan@demo.com', 'Av. Principal 123', 'Ciudad de México, CDMX', 1),
('Cliente Demo 2', 'CLI002', 'Gobierno', 'María García', '555-5678', 'maria@demo.com', 'Calle Secundaria 456', 'Guadalajara, Jalisco', 1);
