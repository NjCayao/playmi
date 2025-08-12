<?php
/**
 * Constantes del Sistema PLAYMI
 * Valores que no cambian durante la ejecución
 */

// Información del sistema
define('SYSTEM_NAME', 'PLAYMI Entertainment Admin');
define('SYSTEM_VERSION', '1.0.0');
define('BRAND_NAME', 'PLAYMI');
define('COMPANY_NAME', 'PLAYMI Entertainment');
define('COPYRIGHT_YEAR', date('Y'));

// Precios por defecto (se pueden sobrescribir desde base de datos)
define('DEFAULT_BASIC_PRICE', 100.00);
define('DEFAULT_INTERMEDIATE_PRICE', 150.00);
define('DEFAULT_PREMIUM_PRICE', 200.00);

// Configuración de licencias
define('DEFAULT_LICENSE_MONTHS', 12);
define('WARNING_DAYS_BEFORE_EXPIRY', 30);

// Estados del sistema
define('STATUS_ACTIVE', 'activo');
define('STATUS_SUSPENDED', 'suspendido');
define('STATUS_EXPIRED', 'vencido');
define('STATUS_INACTIVE', 'inactivo');

// Tipos de contenido
define('CONTENT_MOVIE', 'pelicula');
define('CONTENT_MUSIC', 'musica');
define('CONTENT_GAME', 'juego');

// Tipos de paquete
define('PACKAGE_BASIC', 'basico');
define('PACKAGE_INTERMEDIATE', 'intermedio');
define('PACKAGE_PREMIUM', 'premium');

// Tipos de publicidad
define('AD_TYPE_PREROLL', 'inicio');
define('AD_TYPE_MIDROLL', 'mitad');

// Configuración para Pi
define('PI_FOOTER_TEXT', 'Powered by PLAYMI Entertainment © ' . COPYRIGHT_YEAR);
define('PI_DEFAULT_WIFI_NAME', 'PLAYMI-Entertainment');

// Mensajes del sistema
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// Configuración de paginación
define('PAGINATION_LIMIT', 25);
define('PAGINATION_MAX_LINKS', 10);

// Formatos de fecha
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

?>