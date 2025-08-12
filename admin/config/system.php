<?php
/**
 * Configuración General del Sistema PLAYMI
 * Define todas las constantes y configuraciones globales
 */

// Configuración de zona horaria
date_default_timezone_set('America/Lima');

// URLs y rutas principales
define('SITE_URL', 'http://localhost/playmi/'); // URL del sitio principal
define('BASE_URL', SITE_URL . 'admin/'); // URL del panel admin
define('ASSETS_URL', BASE_URL . 'assets/');
define('API_URL', BASE_URL . 'api/');

// Rutas de directorios
define('ROOT_PATH', dirname(dirname(__FILE__)) . '/');
define('ADMIN_PATH', ROOT_PATH);
define('UPLOADS_PATH', dirname(ROOT_PATH) . '/content/');
define('COMPANIES_PATH', dirname(ROOT_PATH) . '/companies/data/');
define('PACKAGES_PATH', dirname(ROOT_PATH) . '/packages/generated/');
define('LOGS_PATH', dirname(ROOT_PATH) . '/logs/');

// Configuración de sesiones
define('SESSION_TIMEOUT', 3600); // 1 hora
define('SESSION_NAME', 'PLAYMI_ADMIN_SESSION');

// Configuración de archivos
define('MAX_VIDEO_SIZE', 5 * 1024 * 1024 * 1024); // 5GB
define('MAX_AUDIO_SIZE', 500 * 1024 * 1024); // 500MB
define('MAX_IMAGE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB por defecto

// Extensiones permitidas
define('ALLOWED_VIDEO_EXT', ['mp4', 'avi', 'mkv', 'mov']);
define('ALLOWED_AUDIO_EXT', ['mp3', 'wav', 'flac', 'm4a']);
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Tipos MIME permitidos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/avi', 'video/mkv', 'video/mov']);
define('ALLOWED_AUDIO_TYPES', ['audio/mp3', 'audio/wav', 'audio/ogg', 'audio/m4a']);

// Configuración de logs
define('LOG_LEVEL', 'INFO');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de notificaciones
define('DAYS_BEFORE_EXPIRY_WARNING', 30); // Días antes del vencimiento para mostrar alerta

// Configuración de desarrollo/producción
define('DEVELOPMENT_MODE', true);

if (DEVELOPMENT_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Incluir archivo de constantes
require_once __DIR__ . '/constants.php';

// Alias para compatibilidad (si PAGINATION_LIMIT está definido en constants.php)
if (!defined('RECORDS_PER_PAGE') && defined('PAGINATION_LIMIT')) {
    define('RECORDS_PER_PAGE', PAGINATION_LIMIT);
}

// Inicializar sesión si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
    
    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header('Location: ' . BASE_URL . 'login.php?timeout=1');
            exit;
        }
    }
    
    $_SESSION['last_activity'] = time();
}

// Crear directorios necesarios si no existen
$required_dirs = [
    UPLOADS_PATH . 'movies/',
    UPLOADS_PATH . 'music/',
    UPLOADS_PATH . 'games/',
    UPLOADS_PATH . 'advertising/',
    UPLOADS_PATH . 'banners/',
    UPLOADS_PATH . 'thumbnails/',
    COMPANIES_PATH,
    PACKAGES_PATH,
    LOGS_PATH
];

foreach ($required_dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>