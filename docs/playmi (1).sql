-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-08-2025 a las 22:08:20
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

--
-- Volcado de datos para la tabla `companies`
--

INSERT INTO `companies` (`id`, `nombre`, `email_contacto`, `ruc`, `persona_contacto`, `telefono`, `logo_path`, `color_primario`, `color_secundario`, `nombre_servicio`, `tipo_paquete`, `total_buses`, `costo_mensual`, `fecha_inicio`, `fecha_vencimiento`, `estado`, `notas`, `created_at`, `updated_at`) VALUES
(1, 'Transportes San Martín', 'contacto@sanmartin.com', '20100123456', 'Carlos Rodríguez', '+51 987 654 321', NULL, '#000000', '#FFFFFF', NULL, 'premium', 25, 299.99, '2024-01-01', '2024-12-31', 'activo', NULL, '2025-08-11 08:31:10', '2025-08-12 19:36:47'),
(2, 'Empresa Turismo Norte', 'admin@turismonorte.com', '20100123457', 'María González', '+51 456 789 123', NULL, '#000000', '#FFFFFF', NULL, 'intermedio', 15, 199.99, '2024-02-15', '2024-11-15', 'activo', NULL, '2025-08-11 08:31:10', '2025-08-12 19:36:47'),
(3, 'Líneas Express', 'info@lineasexpress.com', '20100123458', 'Juan Pérez', '+51 123 456 789', NULL, '#000000', '#FFFFFF', NULL, 'basico', 8, 99.99, '2024-03-01', '2024-09-01', 'suspendido', NULL, '2025-08-11 08:31:10', '2025-08-12 19:36:47'),
(4, 'Buses del Centro', 'contacto@busescentro.com', '20100123459', 'Ana López', '+51 789 123 456', NULL, '#000000', '#FFFFFF', NULL, 'premium', 30, 349.99, '2023-12-01', '2024-08-15', 'vencido', NULL, '2025-08-11 08:31:10', '2025-08-12 19:36:47'),
(5, 'Transportes San Martín', 'contacto@sanmartin.com', '20100123460', 'Carlos Rodríguez', '+51 987 654 321', NULL, '#000000', '#FFFFFF', NULL, 'premium', 25, 299.99, '2024-01-01', '2024-12-31', 'activo', NULL, '2025-08-11 08:41:13', '2025-08-12 19:36:47'),
(6, 'Empresa Turismo Norte', 'admin@turismonorte.com', '20100123461', 'María González', '+51 456 789 123', NULL, '#000000', '#FFFFFF', NULL, 'intermedio', 15, 199.99, '2024-02-15', '2024-11-15', 'activo', NULL, '2025-08-11 08:41:13', '2025-08-12 19:36:47'),
(7, 'Líneas Express', 'info@lineasexpress.com', '20100123462', 'Juan Pérez', '+51 123 456 789', NULL, '#000000', '#FFFFFF', NULL, 'basico', 8, 99.99, '2024-03-01', '2024-09-01', 'suspendido', NULL, '2025-08-11 08:41:13', '2025-08-12 19:36:47');

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

--
-- Volcado de datos para la tabla `configuracion_sistema`
--

INSERT INTO `configuracion_sistema` (`id`, `clave_config`, `valor_config`, `tipo_config`, `descripcion`, `es_editable`, `categoria`, `updated_at`) VALUES
(1, 'nombre_sistema', 'PLAYMI Entertainment Admin', 'string', 'Nombre del sistema', 1, 'general', '2025-08-11 06:41:28'),
(2, 'version_sistema', '1.0.0', 'string', 'Versión del sistema', 1, 'general', '2025-08-11 06:41:28'),
(3, 'nombre_marca', 'PLAYMI', 'string', 'Nombre de la marca', 1, 'general', '2025-08-11 06:41:28'),
(4, 'tamanio_max_video', '5368709120', 'number', 'Tamaño máximo de video en bytes (5GB)', 1, 'uploads', '2025-08-12 16:24:05'),
(5, 'tamanio_max_audio', '524288000', 'number', 'Tamaño máximo de audio en bytes (500MB)', 1, 'uploads', '2025-08-12 16:24:05'),
(6, 'tamanio_max_imagen', '10485760', 'number', 'Tamaño máximo de imagen en bytes (10MB)', 1, 'uploads', '2025-08-12 16:24:05'),
(7, 'formatos_video_permitidos', '[\"mp4\",\"avi\",\"mkv\",\"mov\"]', 'json', 'Formatos de video permitidos', 1, 'uploads', '2025-08-11 06:41:28'),
(8, 'formatos_audio_permitidos', '[\"mp3\",\"wav\",\"flac\",\"m4a\"]', 'json', 'Formatos de audio permitidos', 1, 'uploads', '2025-08-11 06:41:28'),
(9, 'formatos_imagen_permitidos', '[\"jpg\",\"jpeg\",\"png\",\"gif\",\"webp\"]', 'json', 'Formatos de imagen permitidos', 1, 'uploads', '2025-08-11 06:41:28'),
(10, 'precio_paquete_basico', '100.00', 'number', 'Precio paquete básico', 1, 'precios', '2025-08-11 06:41:28'),
(11, 'precio_paquete_intermedio', '150.00', 'number', 'Precio paquete intermedio', 1, 'precios', '2025-08-11 06:41:28'),
(12, 'precio_paquete_premium', '200.00', 'number', 'Precio paquete premium', 1, 'precios', '2025-08-11 06:41:28'),
(13, 'dias_advertencia_vencimiento', '30', 'number', 'Días de advertencia antes de vencimiento', 1, 'notificaciones', '2025-08-11 06:41:28'),
(14, 'texto_footer_pi', 'Powered by PLAYMI Entertainment © 2025', 'string', 'Texto del footer en Pi', 1, 'branding', '2025-08-11 06:41:28'),
(15, 'nombre_wifi_por_defecto', 'PLAYMI-Entertainment', 'string', 'Nombre WiFi por defecto para Pi', 1, 'pi', '2025-08-11 06:41:28');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contenido`
--

INSERT INTO `contenido` (`id`, `titulo`, `descripcion`, `archivo_path`, `tamanio_archivo`, `duracion`, `tipo`, `categoria`, `genero`, `anio_lanzamiento`, `calificacion`, `thumbnail_path`, `trailer_path`, `estado`, `descargas_count`, `archivo_hash`, `created_at`, `updated_at`) VALUES
(1, 'Contenido de Ejemplo - Película', NULL, 'placeholder.mp4', NULL, NULL, 'pelicula', 'accion', NULL, NULL, NULL, NULL, NULL, 'inactivo', 0, NULL, '2025-08-11 06:41:28', '2025-08-11 06:41:28'),
(2, 'Contenido de Ejemplo - Música', NULL, 'placeholder.mp3', NULL, NULL, 'musica', 'pop', NULL, NULL, NULL, NULL, NULL, 'inactivo', 0, NULL, '2025-08-11 06:41:28', '2025-08-11 06:41:28'),
(3, 'Contenido de Ejemplo - Juego', 'Descripción actualizada', 'placeholder.html', NULL, NULL, 'juego', 'puzzle', NULL, NULL, NULL, NULL, NULL, 'inactivo', 0, NULL, '2025-08-11 06:41:28', '2025-08-12 16:00:27');

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

--
-- Volcado de datos para la tabla `logs_sistema`
--

INSERT INTO `logs_sistema` (`id`, `usuario_id`, `accion`, `tabla_afectada`, `registro_id`, `valores_anteriores`, `valores_nuevos`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login_success', 'usuarios', 1, NULL, '{\"username\":\"admin\",\"remember\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-11 07:31:23'),
(2, 1, 'login_success', 'usuarios', 1, NULL, '{\"username\":\"admin\",\"remember\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-12 01:25:26'),
(3, NULL, 'login_failed', 'usuarios', NULL, NULL, '{\"username\":\"admin\",\"ip\":\"::1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-12 15:58:27'),
(4, 1, 'login_success', 'usuarios', 1, NULL, '{\"username\":\"admin\",\"remember\":false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36', '2025-08-12 15:58:30');

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
  `notas` text DEFAULT NULL
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
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `email`, `nombre_completo`, `activo`, `ultimo_acceso`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@playmi.com', 'Administrador', 1, '2025-08-12 15:58:30', '2025-08-11 06:41:28', '2025-08-12 15:58:30');

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
-- Indices de la tabla `paquetes_generados`
--
ALTER TABLE `paquetes_generados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generado_por` (`generado_por`),
  ADD KEY `idx_empresa_estado` (`empresa_id`,`estado`);

--
-- Indices de la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_empresa_tipo` (`empresa_id`,`tipo_video`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `configuracion_sistema`
--
ALTER TABLE `configuracion_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `contenido`
--
ALTER TABLE `contenido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `logs_sistema`
--
ALTER TABLE `logs_sistema`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `paquetes_generados`
--
ALTER TABLE `paquetes_generados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
-- Filtros para la tabla `publicidad_empresa`
--
ALTER TABLE `publicidad_empresa`
  ADD CONSTRAINT `publicidad_empresa_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
