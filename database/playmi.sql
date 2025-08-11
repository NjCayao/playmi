-- EJECUTAR ESTE ARCHIVO PRIMERO EN PHPMYADMIN
CREATE DATABASE IF NOT EXISTS playmi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE playmi;

-- Tabla de usuarios administradores
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    nombre_completo VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de empresas clientes
CREATE TABLE empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email_contacto VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    persona_contacto VARCHAR(100),
    logo_path VARCHAR(255),
    color_primario VARCHAR(7) DEFAULT '#000000',
    color_secundario VARCHAR(7) DEFAULT '#FFFFFF',
    nombre_servicio VARCHAR(100),
    tipo_paquete ENUM('basico', 'intermedio', 'premium') DEFAULT 'basico',
    fecha_inicio DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('activo', 'suspendido', 'vencido') DEFAULT 'activo',
    total_buses INT DEFAULT 0,
    costo_mensual DECIMAL(10,2) DEFAULT 0.00,
    notas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_vencimiento (fecha_vencimiento)
);

-- Tabla de contenido multimedia global
CREATE TABLE contenido (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT,
    archivo_path VARCHAR(500) NOT NULL,
    tamaño_archivo BIGINT,
    duracion INT, -- en segundos
    tipo ENUM('pelicula', 'musica', 'juego') NOT NULL,
    categoria VARCHAR(50),
    genero VARCHAR(50),
    año_lanzamiento YEAR,
    calificacion VARCHAR(10),
    thumbnail_path VARCHAR(500),
    trailer_path VARCHAR(500),
    estado ENUM('activo', 'inactivo', 'procesando') DEFAULT 'procesando',
    descargas_count INT DEFAULT 0,
    archivo_hash VARCHAR(64), -- Para verificar integridad
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_estado (estado),
    INDEX idx_categoria (categoria)
);

-- Tabla de publicidad por empresa
CREATE TABLE publicidad_empresa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    tipo_video ENUM('inicio', 'mitad') NOT NULL,
    archivo_path VARCHAR(500) NOT NULL,
    duracion INT NOT NULL, -- en segundos
    tamaño_archivo BIGINT,
    activo BOOLEAN DEFAULT TRUE,
    orden_reproduccion INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_empresa_tipo (empresa_id, tipo_video)
);

-- Tabla de banners por empresa
CREATE TABLE banners_empresa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    tipo_banner ENUM('header', 'footer', 'catalogo') NOT NULL,
    imagen_path VARCHAR(500) NOT NULL,
    posicion VARCHAR(50),
    ancho INT,
    alto INT,
    tamaño_archivo BIGINT,
    activo BOOLEAN DEFAULT TRUE,
    orden_visualizacion INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_empresa_tipo (empresa_id, tipo_banner)
);

-- Tabla de paquetes generados
CREATE TABLE paquetes_generados (
    id INT PRIMARY KEY AUTO_INCREMENT,
    empresa_id INT NOT NULL,
    nombre_paquete VARCHAR(100) NOT NULL,
    version_paquete VARCHAR(20) DEFAULT '1.0',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tamaño_paquete BIGINT,
    cantidad_contenido INT,
    fecha_vencimiento_licencia DATE,
    ruta_paquete VARCHAR(500),
    ruta_descarga VARCHAR(500),
    estado ENUM('generando', 'listo', 'descargado', 'instalado', 'vencido') DEFAULT 'generando',
    generado_por INT,
    checksum VARCHAR(64),
    clave_instalacion VARCHAR(32),
    notas TEXT,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (generado_por) REFERENCES usuarios(id),
    INDEX idx_empresa_estado (empresa_id, estado)
);

-- Tabla de configuración del sistema
CREATE TABLE configuracion_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    clave_config VARCHAR(100) UNIQUE NOT NULL,
    valor_config TEXT,
    tipo_config ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    descripcion TEXT,
    es_editable BOOLEAN DEFAULT TRUE,
    categoria VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabla de logs del sistema
CREATE TABLE logs_sistema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    tabla_afectada VARCHAR(50),
    registro_id INT,
    valores_anteriores JSON,
    valores_nuevos JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario_accion (usuario_id, accion),
    INDEX idx_created_at (created_at)
);