-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 23-08-2025 a las 09:01:37
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `playmi`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `banners_empresa`
--

CREATE TABLE `banners_empresa` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_banner` enum('header','footer','catalogo') NOT NULL,
  `imagen_path` varchar(500) NOT NULL,
  `posicion` varchar(50) DEFAULT NULL,
  `ancho` int(11) DEFAULT NULL,
  `alto` int(11) DEFAULT NULL,
  `tamanio_archivo` bigint(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden_visualizacion` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `companies`
--

CREATE TABLE `companies` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email_contacto` varchar(100) NOT NULL,
  `ruc` varchar(11) DEFAULT NULL,
  `persona_contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `logo_path` varchar(255) DEFAULT NULL,
  `color_primario` varchar(7) DEFAULT '#000000',
  `color_secundario` varchar(7) DEFAULT '#FFFFFF',
  `nombre_servicio` varchar(100) DEFAULT NULL,
  `tipo_paquete` enum('basico','intermedio','premium') NOT NULL DEFAULT 'basico',
  `total_buses` int(11) DEFAULT 0,
  `costo_mensual` decimal(10,2) DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `estado` enum('activo','suspendido','vencido') DEFAULT 'activo',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla principal de empresas clientes del sistema PLAYMI';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sistema`
--

CREATE TABLE `configuracion_sistema` (
  `id` int(11) NOT NULL,
  `clave_config` varchar(100) NOT NULL,
  `valor_config` text DEFAULT NULL,
  `tipo_config` enum('string','number','boolean','json') DEFAULT 'string',
  `descripcion` text DEFAULT NULL,
  `es_editable` tinyint(1) DEFAULT 1,
  `categoria` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenido`
--

CREATE TABLE `contenido` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo_path` varchar(500) NOT NULL,
  `tamanio_archivo` bigint(20) DEFAULT NULL,
  `duracion` int(11) DEFAULT NULL,
  `tipo` enum('pelicula','musica','juego') NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `genero` varchar(50) DEFAULT NULL,
  `anio_lanzamiento` year(4) DEFAULT NULL,
  `calificacion` varchar(10) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `trailer_path` varchar(500) DEFAULT NULL,
  `estado` enum('activo','inactivo','procesando') DEFAULT 'procesando',
  `descargas_count` int(11) DEFAULT 0,
  `archivo_hash` varchar(64) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_sistema`
--

CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `tabla_afectada` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `valores_anteriores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_anteriores`)),
  `valores_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`valores_nuevos`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes_contenido`
--

CREATE TABLE `paquetes_contenido` (
  `id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `contenido_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes_generados`
--

CREATE TABLE `paquetes_generados` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `nombre_paquete` varchar(100) NOT NULL,
  `version_paquete` varchar(20) DEFAULT '1.0',
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `tamanio_paquete` bigint(20) DEFAULT NULL,
  `cantidad_contenido` int(11) DEFAULT NULL,
  `fecha_vencimiento_licencia` date DEFAULT NULL,
  `ruta_paquete` varchar(500) DEFAULT NULL,
  `ruta_descarga` varchar(500) DEFAULT NULL,
  `estado` enum('generando','listo','descargado','instalado','vencido') DEFAULT 'generando',
  `generado_por` int(11) DEFAULT NULL,
  `checksum` varchar(64) DEFAULT NULL,
  `clave_instalacion` varchar(32) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `advertising_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`advertising_config`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquetes_progreso`
--

CREATE TABLE `paquetes_progreso` (
  `id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `progreso` int(11) DEFAULT 0,
  `mensaje` varchar(255) DEFAULT NULL,
  `archivos_procesados` int(11) DEFAULT 0,
  `total_archivos` int(11) DEFAULT 0,
  `tamanio_procesado` bigint(20) DEFAULT 0,
  `tamanio_total` bigint(20) DEFAULT 0,
  `paso_actual` int(11) DEFAULT 1,
  `total_pasos` int(11) DEFAULT 7,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_publicidad_banners`
--

CREATE TABLE `paquete_publicidad_banners` (
  `id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `banner_id` int(11) NOT NULL,
  `tipo_ubicacion` enum('header','footer','catalogo') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paquete_publicidad_videos`
--

CREATE TABLE `paquete_publicidad_videos` (
  `id` int(11) NOT NULL,
  `paquete_id` int(11) NOT NULL,
  `publicidad_id` int(11) NOT NULL,
  `tipo_reproduccion` enum('inicio','mitad') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `portal_usage_logs`
--

CREATE TABLE `portal_usage_logs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicidad_empresa`
--

CREATE TABLE `publicidad_empresa` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `tipo_video` enum('inicio','mitad') NOT NULL,
  `archivo_path` varchar(500) NOT NULL,
  `duracion` int(11) NOT NULL,
  `tamanio_archivo` bigint(20) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden_reproduccion` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `qr_codes`
--

CREATE TABLE `qr_codes` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `numero_bus` varchar(50) NOT NULL,
  `wifi_ssid` varchar(32) NOT NULL,
  `wifi_password` varchar(63) NOT NULL,
  `portal_url` varchar(255) DEFAULT NULL,
  `archivo_path` varchar(500) NOT NULL,
  `tamano_qr` int(11) DEFAULT 300,
  `nivel_correccion` char(1) DEFAULT 'M',
  `package_id` int(11) DEFAULT NULL,
  `estado` enum('activo','inactivo') DEFAULT 'activo',
  `descargas_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `qr_scans`
--

CREATE TABLE `qr_scans` (
  `id` int(11) NOT NULL,
  `qr_id` int(11) NOT NULL,
  `scan_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `device_info` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nombre_completo` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `banners_empresa`
--
ALTER TABLE `banners_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa_tipo` (`empresa_id`,`tipo_banner`);

--
-- Indices de la tabla `companies`
--
ALTER TABLE `companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ruc` (`ruc`),
  ADD KEY `idx_nombre` (`nombre`),
  ADD KEY `idx_email` (`email_contacto`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_fecha_vencimiento` (`fecha_vencimiento`),
  ADD KEY `idx_tipo_paquete` (`tipo_paquete`),
  ADD KEY `idx_ruc` (`ruc`);

--
-- Indices de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave_config` (`clave_config`);

--
-- Indices de la tabla `contenido`
--
ALTER TABLE `contenido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Indices de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_accion` (`usuario_id`,`accion`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indices de la tabla `paquetes_contenido`
--
ALTER TABLE `paquetes_contenido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paquete_id` (`paquete_id`),
  ADD KEY `contenido_id` (`contenido_id`);

--
-- Indices de la tabla `paquetes_generados`
--
ALTER TABLE `paquetes_generados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generado_por` (`generado_por`),
  ADD KEY `idx_empresa_estado` (`empresa_id`,`estado`);

--
-- Indices de la tabla `paquetes_progreso`
--
ALTER TABLE `paquetes_progreso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_paquete` (`paquete_id`);

--
-- Indices de la tabla `paquete_publicidad_banners`
--
ALTER TABLE `paquete_publicidad_banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paquete_id` (`paquete_id`),
  ADD KEY `banner_id` (`banner_id`);

--
-- Indices de la tabla `paquete_publicidad_videos`
--
ALTER TABLE `paquete_publicidad_videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paquete_id` (`paquete_id`),
  ADD KEY `publicidad_id` (`publicidad_id`);

--
-- Indices de la tabla `portal_usage_logs`
--
ALTER TABLE `portal_usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_action` (`company_id`,`action`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_portal_logs_date` (`created_at`),
  ADD KEY `idx_portal_logs_company` (`company_id`);

--
-- Indices de la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa_tipo` (`empresa_id`,`tipo_video`);

--
-- Indices de la tabla `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_unique_bus` (`empresa_id`,`numero_bus`),
  ADD KEY `idx_empresa_bus` (`empresa_id`,`numero_bus`),
  ADD KEY `idx_estado` (`estado`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_package_id` (`package_id`);

--
-- Indices de la tabla `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_qr_date` (`qr_id`,`scan_date`),
  ADD KEY `idx_scan_date` (`scan_date`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `banners_empresa`
--
ALTER TABLE `banners_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `companies`
--
ALTER TABLE `companies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenido`
--
ALTER TABLE `contenido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquetes_contenido`
--
ALTER TABLE `paquetes_contenido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquetes_generados`
--
ALTER TABLE `paquetes_generados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquetes_progreso`
--
ALTER TABLE `paquetes_progreso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquete_publicidad_banners`
--
ALTER TABLE `paquete_publicidad_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `paquete_publicidad_videos`
--
ALTER TABLE `paquete_publicidad_videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `portal_usage_logs`
--
ALTER TABLE `portal_usage_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `qr_codes`
--
ALTER TABLE `qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `qr_scans`
--
ALTER TABLE `qr_scans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `banners_empresa`
--
ALTER TABLE `banners_empresa`
  ADD CONSTRAINT `banners_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `logs_sistema_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `paquetes_generados`
--
ALTER TABLE `paquetes_generados`
  ADD CONSTRAINT `paquetes_generados_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paquetes_generados_ibfk_2` FOREIGN KEY (`generado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `paquetes_progreso`
--
ALTER TABLE `paquetes_progreso`
  ADD CONSTRAINT `paquetes_progreso_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes_generados` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_publicidad_banners`
--
ALTER TABLE `paquete_publicidad_banners`
  ADD CONSTRAINT `paquete_publicidad_banners_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes_generados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paquete_publicidad_banners_ibfk_2` FOREIGN KEY (`banner_id`) REFERENCES `banners_empresa` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paquete_publicidad_videos`
--
ALTER TABLE `paquete_publicidad_videos`
  ADD CONSTRAINT `paquete_publicidad_videos_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes_generados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paquete_publicidad_videos_ibfk_2` FOREIGN KEY (`publicidad_id`) REFERENCES `publicidad_empresa` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `portal_usage_logs`
--
ALTER TABLE `portal_usage_logs`
  ADD CONSTRAINT `fk_portal_logs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  ADD CONSTRAINT `publicidad_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `qr_codes`
--
ALTER TABLE `qr_codes`
  ADD CONSTRAINT `fk_qr_package` FOREIGN KEY (`package_id`) REFERENCES `paquetes_generados` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `qr_codes_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `qr_scans`
--
ALTER TABLE `qr_scans`
  ADD CONSTRAINT `qr_scans_ibfk_1` FOREIGN KEY (`qr_id`) REFERENCES `qr_codes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
