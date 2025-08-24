<?php
/**
 * passenger-portal/config/portal-config.php
 * Configuración del Portal de Pasajeros
 */

// Evitar acceso directo
if (!defined('PORTAL_ACCESS')) {
    define('PORTAL_ACCESS', true);
}

// Configuración de rutas
define('PORTAL_URL', '/playmi/passenger-portal/');
define('CONTENT_URL', '/playmi/content/');
define('PORTAL_PATH', dirname(__DIR__) . '/');
define('CONTENT_PATH', dirname(dirname(__DIR__)) . '/content/');

// Configuración de publicidad
define('AD_DELAY_MINUTES', 5);          // Mostrar publicidad a los 5 minutos
define('AD_DURATION_SECONDS', 30);       // Duración de publicidad
define('AD_SKIP_AFTER_SECONDS', 5);      // Permitir saltar después de 5 segundos (opcional)
define('AD_MID_MOVIE_ENABLED', true);    // Publicidad a mitad de película

// Configuración de contenido
define('THUMBNAIL_DEFAULT_MOVIE', PORTAL_URL . 'assets/images/default-movie.jpg');
define('THUMBNAIL_DEFAULT_MUSIC', PORTAL_URL . 'assets/images/default-music.jpg');
define('THUMBNAIL_DEFAULT_GAME', PORTAL_URL . 'assets/images/default-game.jpg');

// Configuración de streaming
define('VIDEO_CHUNK_SIZE', 1024 * 1024); // 1MB chunks para streaming
define('ENABLE_VIDEO_CACHE', true);
define('MAX_CONCURRENT_USERS', 50);

// Tema por defecto (Netflix Dark)
define('DEFAULT_THEME', [
    'primary_bg' => '#141414',
    'secondary_bg' => '#1a1a1a',
    'card_bg' => '#2a2a2a',
    'text_primary' => '#ffffff',
    'text_secondary' => '#b3b3b3',
    'accent' => '#e50914',
    'hover_bg' => '#333333'
]);

// Obtener configuración de empresa/paquete
function getCompanyConfig() {
    // Por ahora simulamos, luego se conectará con la BD
    return [
        'company_id' => $_SESSION['company_id'] ?? 1,
        'company_name' => $_SESSION['company_name'] ?? 'PLAYMI Entertainment',
        'primary_color' => $_SESSION['primary_color'] ?? '#e50914',
        'secondary_color' => $_SESSION['secondary_color'] ?? '#141414',
        'logo_url' => $_SESSION['logo_url'] ?? null,
        'welcome_message' => $_SESSION['welcome_message'] ?? 'Bienvenido a bordo',
        'package_type' => $_SESSION['package_type'] ?? 'premium',
        'ads_enabled' => $_SESSION['ads_enabled'] ?? true
    ];
}

// Inicializar sesión si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>