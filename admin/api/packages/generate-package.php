<?php
// admin/api/packages/generate-package.php

// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json');

// Log para debug
$debugLog = __DIR__ . '/package_generation.log';
function logDebug($message)
{
    //     global $debugLog;
    //     file_put_contents($debugLog, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// ob_start();
// logDebug("=== INICIO GENERACIÓN PAQUETE ===");

try {
    // Incluir configuración y controladores necesarios
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../controllers/PackageController.php';
    require_once __DIR__ . '/../../models/Package.php';
    require_once __DIR__ . '/../../models/Company.php';
    require_once __DIR__ . '/../../models/Content.php';

    // Verificar autenticación
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('No autorizado');
    }

    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Aumentar límites para proceso pesado
    set_time_limit(0);
    ini_set('memory_limit', '1024M');

    // Obtener y validar datos de entrada
    $data = $_POST;

    // Validaciones básicas
    if (empty($data['empresa_id'])) {
        throw new Exception('Empresa requerida');
    }

    if (empty($data['nombre_paquete'])) {
        throw new Exception('Nombre del paquete requerido');
    }

    if (empty($data['content_ids']) || !is_array($data['content_ids'])) {
        throw new Exception('Debe seleccionar al menos un contenido');
    }

    if (empty($data['wifi_ssid']) || empty($data['wifi_password'])) {
        throw new Exception('Configuración WiFi requerida');
    }

    if (strlen($data['wifi_password']) < 8) {
        throw new Exception('La contraseña WiFi debe tener al menos 8 caracteres');
    }


    // Inicializar modelos
    $db = Database::getInstance()->getConnection();
    $packageModel = new Package($db);
    $companyModel = new Company($db);
    $contentModel = new Content($db);

    // Verificar que la empresa existe
    $company = $companyModel->findById($data['empresa_id']);
    if (!$company) {
        throw new Exception('Empresa no encontrada');
    }

    // Verificar que el contenido existe
    $selectedContent = [];
    foreach ($data['content_ids'] as $contentId) {
        $content = $contentModel->findById($contentId);
        if ($content && $content['estado'] == 'activo') {
            $selectedContent[] = $content;
        }
    }

    if (empty($selectedContent)) {
        throw new Exception('No se encontró contenido válido');
    }

    // Crear registro inicial del paquete
    $packageData = [
        'empresa_id' => $data['empresa_id'],
        'nombre_paquete' => $data['nombre_paquete'],
        'version_paquete' => $data['version_paquete'] ?? '1.0',
        'generado_por' => $_SESSION['admin_id'] ?? 1,
        'estado' => 'generando',
        'notas' => $data['notas'] ?? null
    ];

    $packageResult = $packageModel->startGeneration(
        $data['empresa_id'],
        $_SESSION['admin_id'] ?? 1,
        $packageData
    );

    if (!$packageResult['success']) {
        throw new Exception('Error al iniciar generación del paquete: ' . ($packageResult['error'] ?? 'Error desconocido'));
    }

    $packageId = $packageResult['package_id'];
    $installationKey = $packageResult['installation_key'];

    // Registrar contenido del paquete
    registerPackageContent($packageId, $data['content_ids']);

    // Crear estructura de directorios temporales
    $tempPath = sys_get_temp_dir() . '/playmi_package_' . $packageId . '_' . time();
    $packageStructure = [
        $tempPath,
        $tempPath . '/content',
        $tempPath . '/content/movies',
        $tempPath . '/content/music',
        $tempPath . '/content/games',
        $tempPath . '/content/movies/thumbnails',
        $tempPath . '/content/music/thumbnails',
        $tempPath . '/content/games/thumbnails',
        $tempPath . '/config',
        $tempPath . '/config/qr',
        $tempPath . '/portal',
        $tempPath . '/portal/assets',
        $tempPath . '/portal/assets/css',
        $tempPath . '/portal/assets/js',
        $tempPath . '/portal/assets/images',
        $tempPath . '/install',
        $tempPath . '/print'
    ];

    foreach ($packageStructure as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception('Error creando estructura de directorios: ' . $dir);
            }
        }
    }

    // Copiar contenido seleccionado
    $totalSize = 0;
    $contentCount = 0;

    foreach ($selectedContent as $content) {
        $sourcePath = UPLOADS_PATH . $content['archivo_path'];

        if (!file_exists($sourcePath)) {
            continue;
        }

        // Determinar destino según tipo
        $destFolder = '';
        switch ($content['tipo']) {
            case 'pelicula':
                $destFolder = '/content/movies/';
                break;
            case 'musica':
                $destFolder = '/content/music/';
                break;
            case 'juego':
                $destFolder = '/content/games/';
                break;
        }

        $destPath = $tempPath . $destFolder . basename($content['archivo_path']);

        // Copiar archivo
        if (copy($sourcePath, $destPath)) {
            $totalSize += filesize($sourcePath);
            $contentCount++;

            // Copiar thumbnail si existe
            if ($content['thumbnail_path']) {
                $thumbSource = UPLOADS_PATH . $content['thumbnail_path'];
                $thumbDest = $tempPath . $destFolder . 'thumbnails/' . basename($content['thumbnail_path']);

                if (file_exists($thumbSource)) {
                    copy($thumbSource, $thumbDest);
                }
            }
        }
    }


    // Copiar publicidad seleccionada
    if (
        !empty($data['video_inicio_id']) || !empty($data['video_mitad_id']) ||
        !empty($data['banner_header_id']) || !empty($data['banner_footer_id']) ||
        !empty($data['banner_catalogo_id'])
    ) {

        // Crear directorios de publicidad
        $adsPath = $tempPath . '/advertising';
        mkdir($adsPath, 0755, true);
        mkdir($adsPath . '/videos', 0755, true);
        mkdir($adsPath . '/banners', 0755, true);

        // Copiar videos
        if (!empty($data['video_inicio_id'])) {
            copyAdvertisingFile('video', $data['video_inicio_id'], $adsPath . '/videos/inicio.mp4');
        }
        if (!empty($data['video_mitad_id'])) {
            copyAdvertisingFile('video', $data['video_mitad_id'], $adsPath . '/videos/mitad.mp4');
        }

        // Copiar banners
        if (!empty($data['banner_header_id'])) {
            copyAdvertisingFile('banner', $data['banner_header_id'], $adsPath . '/banners/header.jpg');
        }
        if (!empty($data['banner_footer_id'])) {
            copyAdvertisingFile('banner', $data['banner_footer_id'], $adsPath . '/banners/footer.jpg');
        }
        if (!empty($data['banner_catalogo_id'])) {
            copyAdvertisingFile('banner', $data['banner_catalogo_id'], $adsPath . '/banners/catalogo.jpg');
        }

        // Generar configuración de publicidad
        generateAdvertisingConfig($tempPath, $data);
    }

    // Función para copiar archivo de publicidad
    function copyAdvertisingFile($type, $id, $destination)
    {
        require_once __DIR__ . '/../../models/Advertising.php';
        $advertisingModel = new Advertising();

        if ($type === 'video') {
            $ad = $advertisingModel->getVideoById($id);
            $sourcePath = UPLOADS_PATH . $ad['archivo_path'];
        } else {
            $ad = $advertisingModel->getBannerById($id);
            $sourcePath = UPLOADS_PATH . $ad['imagen_path'];
        }

        if (file_exists($sourcePath)) {
            copy($sourcePath, $destination);
        }
    }

    // Función para generar configuración de publicidad
    function generateAdvertisingConfig($basePath, $data)
    {
        $config = [
            'videos' => [
                'inicio' => [
                    'enabled' => !empty($data['video_inicio_id']),
                    'file' => 'advertising/videos/inicio.mp4',
                    'trigger_time' => 300, // 5 minutos
                    'skippable' => isset($data['video_skip_allowed']) && $data['video_skip_allowed'],
                    'skip_after' => 5,
                    'mutable' => isset($data['video_mute_allowed']) && $data['video_mute_allowed']
                ],
                'mitad' => [
                    'enabled' => !empty($data['video_mitad_id']),
                    'file' => 'advertising/videos/mitad.mp4',
                    'trigger_type' => 'midroll',
                    'min_content_duration' => 1800, // 30 minutos
                    'skippable' => isset($data['video_skip_allowed']) && $data['video_skip_allowed'],
                    'skip_after' => 5,
                    'mutable' => isset($data['video_mute_allowed']) && $data['video_mute_allowed']
                ]
            ],
            'banners' => [
                'header' => [
                    'enabled' => !empty($data['banner_header_id']),
                    'file' => 'advertising/banners/header.jpg',
                    'position' => 'top',
                    'clickable' => true
                ],
                'footer' => [
                    'enabled' => !empty($data['banner_footer_id']),
                    'file' => 'advertising/banners/footer.jpg',
                    'position' => 'bottom',
                    'clickable' => true
                ],
                'catalogo' => [
                    'enabled' => !empty($data['banner_catalogo_id']),
                    'file' => 'advertising/banners/catalogo.jpg',
                    'frequency' => $data['banner_catalogo_frequency'] ?? 3,
                    'clickable' => true
                ]
            ],
            'tracking' => [
                'impressions' => isset($data['track_impressions']) && $data['track_impressions'],
                'clicks' => isset($data['track_clicks']) && $data['track_clicks'],
                'completion' => isset($data['track_completion']) && $data['track_completion']
            ]
        ];

        file_put_contents(
            $basePath . '/config/advertising.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }


    // Generar archivos de configuración
    generateConfigFiles($tempPath, $company, $data, $packageId);

    // Copiar portal web personalizado
    copyPortalFiles($tempPath . '/portal', $company, $data);

    // Generar scripts de instalación
    generateInstallScripts($tempPath . '/install', $data);

    // Crear archivo package-info.json
    $packageInfo = [
        'package_id' => $packageId,
        'package_name' => $data['nombre_paquete'],
        'version' => $data['version_paquete'] ?? '1.0',
        'generated_at' => date('Y-m-d H:i:s'),
        'company' => [
            'id' => $company['id'],
            'name' => $company['nombre'],
            'license_expiry' => $company['fecha_vencimiento']
        ],
        'content' => [
            'total_items' => $contentCount,
            'total_size' => $totalSize,
            'movies' => count(array_filter($selectedContent, fn($c) => $c['tipo'] == 'pelicula')),
            'music' => count(array_filter($selectedContent, fn($c) => $c['tipo'] == 'musica')),
            'games' => count(array_filter($selectedContent, fn($c) => $c['tipo'] == 'juego'))
        ],
        'installation_key' => $installationKey,
        'checksum' => null
    ];

    file_put_contents(
        $tempPath . '/package-info.json',
        json_encode($packageInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    // Comprimir todo en un archivo ZIP
    $zipPath = PACKAGES_PATH . $company['id'] . '/';
    if (!is_dir($zipPath)) {
        if (!mkdir($zipPath, 0755, true)) {
            throw new Exception('Error creando directorio para ZIP: ' . $zipPath);
        }
    }

    $zipFilename = 'package_' . $packageId . '_' . date('YmdHis') . '.zip';
    $zipFullPath = $zipPath . $zipFilename;


    $zip = new ZipArchive();
    if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Error creando archivo ZIP');
    }

    // Función recursiva para agregar archivos al ZIP
    $addDirToZip = function ($dir, $zipPath = '') use (&$addDirToZip, $zip, $tempPath) {
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;

            $fullPath = $dir . '/' . $file;
            $relativePath = str_replace($tempPath . '/', '', $fullPath);

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($relativePath);
                $addDirToZip($fullPath, $relativePath);
            } else {
                $zip->addFile($fullPath, $relativePath);
            }
        }
    };

    $addDirToZip($tempPath);
    $zip->close();


    // Calcular checksum del archivo ZIP
    $checksum = hash_file('sha256', $zipFullPath);

    // Actualizar información del paquete en BD
    $updateResult = $packageModel->markAsComplete(
        $packageId,
        $zipFullPath,
        filesize($zipFullPath),
        $contentCount
    );

    if (!$updateResult['success']) {
        throw new Exception('Error actualizando información del paquete');
    }

    // Limpiar archivos temporales
    deleteDirectory($tempPath);

    // Limpiar buffer y enviar respuesta
    ob_clean();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Paquete generado exitosamente',
        'package_id' => $packageId,
        'download_url' => API_URL . 'packages/download-package.php?id=' . $packageId,
        'installation_key' => $installationKey,
        'checksum' => $checksum,
        'size' => filesize($zipFullPath),
        'content_count' => $contentCount
    ]);
} catch (Exception $e) {

    // En caso de error, marcar paquete como fallido si existe
    if (isset($packageId) && isset($packageModel)) {
        $packageModel->updateStatus($packageId, 'error', [
            'error_message' => $e->getMessage()
        ]);
    }

    // Limpiar archivos temporales si existen
    if (isset($tempPath) && is_dir($tempPath)) {
        deleteDirectory($tempPath);
    }

    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Funciones auxiliares
 */

// Registrar contenido del paquete
function registerPackageContent($packageId, $contentIds)
{
    $db = Database::getInstance()->getConnection();

    $sql = "INSERT INTO paquetes_contenido (paquete_id, contenido_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);

    foreach ($contentIds as $contentId) {
        $stmt->execute([$packageId, $contentId]);
    }
}

// Generar archivos de configuración
function generateConfigFiles($basePath, $company, $data, $packageId)
{
    // Configuración WiFi
    $wifiConfig = [
        'ssid' => $data['wifi_ssid'],
        'password' => $data['wifi_password'],
        'security' => 'WPA2',
        'hidden' => isset($data['wifi_hidden']) && $data['wifi_hidden'] ? true : false,
        'channel' => $data['wifi_channel'] ?? 'auto',
        'max_connections' => (int)($data['max_connections'] ?? 50)
    ];

    file_put_contents(
        $basePath . '/config/wifi-config.json',
        json_encode($wifiConfig, JSON_PRETTY_PRINT)
    );

    // Generar QR sin logo (simplificado)
    generatePackageQR($basePath, $wifiConfig, $packageId, $data['empresa_id']);

    // Configuración del portal
    $portalConfig = [
        'company' => [
            'id' => $company['id'],
            'name' => $company['nombre'],
            'logo' => $company['logo_path'],
            'service_name' => $data['portal_name'] ?? 'PLAYMI Entertainment'
        ],
        'branding' => [
            'primary_color' => $data['color_primario'] ?? '#2563eb',
            'secondary_color' => $data['color_secundario'] ?? '#64748b',
            'use_company_logo' => isset($data['usar_logo_empresa']) && $data['usar_logo_empresa'] ? true : false,
            'welcome_message' => $data['mensaje_bienvenida'] ?? 'Bienvenido a bordo!'
        ],
        'features' => [
            'movies_enabled' => !isset($data['enable_movies']) || $data['enable_movies'],
            'music_enabled' => !isset($data['enable_music']) || $data['enable_music'],
            'games_enabled' => !isset($data['enable_games']) || $data['enable_games'],
            'analytics_enabled' => isset($data['analytics_enabled']) && $data['analytics_enabled']
        ],
        'sync' => [
            'auto_sync' => isset($data['auto_sync']) && $data['auto_sync'],
            'sync_interval' => (int)($data['sync_interval'] ?? 3600)
        ],
        'footer_text' => $data['portal_footer'] ?? '© 2025 PLAYMI Entertainment. Todos los derechos reservados.'
    ];

    file_put_contents(
        $basePath . '/config/portal-config.json',
        json_encode($portalConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );

    // Configuración DNS
    $dnsConfig = "# PLAYMI DNS Configuration
interface=wlan0
dhcp-range=192.168.4.2,192.168.4.100,255.255.255.0,24h
address=/playmi.pe/192.168.4.1
address=/#/192.168.4.1
no-resolv
server=8.8.8.8
server=8.8.4.4";

    file_put_contents(
        $basePath . '/config/dnsmasq-playmi.conf',
        $dnsConfig
    );

    // Generar instrucciones imprimibles
    generatePrintableInstructions($basePath, $wifiConfig);
}

// Generar instrucciones imprimibles
function generatePrintableInstructions($basePath, $wifiConfig)
{
    $instructionsHtml = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Instrucciones PLAYMI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        .instruction-card {
            border: 3px solid #2563eb;
            border-radius: 15px;
            padding: 30px;
            max-width: 400px;
            margin: 0 auto;
            text-align: center;
        }
        h1 {
            color: #2563eb;
            margin: 0 0 20px 0;
            font-size: 28px;
        }
        .step {
            margin: 20px 0;
            text-align: left;
        }
        .step-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            background: #2563eb;
            color: white;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
        .wifi-info {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: monospace;
        }
        .url-box {
            background: #2563eb;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            letter-spacing: 2px;
        }
        .qr-placeholder {
            width: 200px;
            height: 200px;
            border: 2px dashed #ccc;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div class="instruction-card">
        <h1>WIFI GRATIS + PELÍCULAS</h1>
        
        <div class="qr-placeholder">
            <img src="../config/qr/wifi-qr-large.png" style="width: 200px; height: 200px;">
        </div>
        
        <div class="step">
            <span class="step-number">1</span>
            <strong>Escanea el código QR</strong>
            <div class="wifi-info">
                WiFi: ' . htmlspecialchars($wifiConfig['ssid']) . '
            </div>
        </div>
        
        <div class="step">
            <span class="step-number">2</span>
            <strong>Abre tu navegador y busca:</strong>
            <div class="url-box">
                playmi.pe
            </div>
        </div>
        
        <div class="step">
            <span class="step-number">3</span>
            <strong>¡Disfruta películas, música y juegos GRATIS!</strong>
        </div>
    </div>
</body>
</html>';

    file_put_contents(
        $basePath . '/print/instrucciones.html',
        $instructionsHtml
    );
}

// Copiar archivos del portal
function copyPortalFiles($portalPath, $company, $data)
{
    $indexHtml = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($data['portal_name'] ?? 'PLAYMI.PE') . '</title>
    <style>
        body {
            background: #1a1a1a;
            color: white;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        h1 { color: ' . ($data['color_primario'] ?? '#2563eb') . '; }
        p { color: ' . ($data['color_secundario'] ?? '#64748b') . '; }
    </style>
</head>
<body>
    <h1>' . htmlspecialchars($data['portal_name'] ?? 'PLAYMI') . '</h1>
    <h2>Entretenimiento que viaja contigo</h2>
    <p>' . htmlspecialchars($data['mensaje_bienvenida'] ?? 'Bienvenido a bordo!') . '</p>
    <p>Portal en construcción...</p>
</body>
</html>';

    file_put_contents($portalPath . '/index.html', $indexHtml);

    // Copiar logo si existe
    if (!empty($company['logo_path'])) {
        $logoSource = COMPANIES_PATH . $company['logo_path'];
        if (file_exists($logoSource)) {
            copy($logoSource, $portalPath . '/assets/images/company-logo.png');
        }
    }
}

// Generar scripts de instalación
function generateInstallScripts($installPath, $data)
{
    $installScript = '#!/bin/bash
# PLAYMI Installation Script
# Generated: ' . date('Y-m-d H:i:s') . '

echo "==================================="
echo "PLAYMI Entertainment System"
echo "Installation Script v1.0"
echo "==================================="

# Variables
WIFI_SSID="' . escapeshellarg($data['wifi_ssid']) . '"
WIFI_PASSWORD="' . escapeshellarg($data['wifi_password']) . '"

# Función principal
main() {
    echo "1. Actualizando sistema..."
    apt-get update
    
    echo "2. Instalando dependencias..."
    apt-get install -y apache2 php libapache2-mod-php hostapd dnsmasq
    
    echo "3. Configurando WiFi..."
    # Configuración aquí
    
    echo "4. Copiando archivos..."
    cp -r ../portal/* /var/www/html/
    cp -r ../content /var/www/html/
    
    echo "5. Configurando permisos..."
    chown -R www-data:www-data /var/www/html
    chmod -R 755 /var/www/html
    
    echo "Instalación completada!"
}

# Ejecutar
if [[ $EUID -ne 0 ]]; then
   echo "Este script debe ejecutarse como root (sudo)"
   exit 1
fi

main
';

    file_put_contents($installPath . '/install.sh', $installScript);
    chmod($installPath . '/install.sh', 0755);
}

// Generar QR para el paquete
function generatePackageQR($packagePath, $wifiConfig, $packageId, $companyId)
{

    // Crear directorio para QR
    $qrDir = $packagePath . '/config/qr/';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }

    // Generar QR estilizado usando el mismo endpoint que la vista previa
    $ssid = urlencode($wifiConfig['ssid']);
    $password = urlencode($wifiConfig['password']);
    $hidden = isset($wifiConfig['hidden']) && $wifiConfig['hidden'] ? 'true' : 'false';

    // URL del generador de QR estilizado
    $qrUrl = "http://localhost/playmi/admin/api/qr/generate-wifi-qr.php?" .
        "ssid={$ssid}&password={$password}&hidden={$hidden}&company_id={$companyId}";

    // Iniciar sesión para la autenticación
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "Cookie: PHPSESSID=" . session_id() . "\r\n"
        ]
    ]);

    // Descargar el QR estilizado
    $qrImageData = @file_get_contents($qrUrl, false, $context);

    if ($qrImageData === false) {

        // Fallback: usar phpqrcode básico
        require_once dirname(__DIR__) . '/../libs/phpqrcode/qrlib.php';
        $wifiString = "WIFI:T:WPA;S:{$wifiConfig['ssid']};P:{$wifiConfig['password']};H:{$hidden};;";

        $sizes = [
            'small' => ['size' => 5, 'file' => 'wifi-qr-small.png'],
            'medium' => ['size' => 10, 'file' => 'wifi-qr-medium.png'],
            'large' => ['size' => 15, 'file' => 'wifi-qr-large.png'],
            'print' => ['size' => 20, 'file' => 'wifi-qr-print.png']
        ];

        foreach ($sizes as $key => $config) {
            $qrPath = $qrDir . $config['file'];
            QRcode::png($wifiString, $qrPath, QR_ECLEVEL_H, $config['size'], 2);
        }
    } else {

        // Guardar el QR estilizado en diferentes tamaños
        file_put_contents($qrDir . 'wifi-qr-large.png', $qrImageData);
        file_put_contents($qrDir . 'wifi-qr-print.png', $qrImageData);

        // Crear versiones más pequeñas
        $image = imagecreatefromstring($qrImageData);
        if ($image !== false) {
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Versión mediana
            $mediumWidth = 300;
            $mediumHeight = ($originalHeight / $originalWidth) * $mediumWidth;
            $mediumImage = imagecreatetruecolor($mediumWidth, $mediumHeight);
            imagecopyresampled(
                $mediumImage,
                $image,
                0,
                0,
                0,
                0,
                $mediumWidth,
                $mediumHeight,
                $originalWidth,
                $originalHeight
            );
            imagepng($mediumImage, $qrDir . 'wifi-qr-medium.png');
            imagedestroy($mediumImage);

            // Versión pequeña
            $smallWidth = 150;
            $smallHeight = ($originalHeight / $originalWidth) * $smallWidth;
            $smallImage = imagecreatetruecolor($smallWidth, $smallHeight);
            imagecopyresampled(
                $smallImage,
                $image,
                0,
                0,
                0,
                0,
                $smallWidth,
                $smallHeight,
                $originalWidth,
                $originalHeight
            );
            imagepng($smallImage, $qrDir . 'wifi-qr-small.png');
            imagedestroy($smallImage);

            imagedestroy($image);
        }
    }

    // Guardar también una copia en el directorio de la empresa para el sistema QR
    $companyQrDir = dirname(dirname(dirname(__DIR__))) . '/companies/' . $companyId . '/qr-codes/';
    if (!is_dir($companyQrDir)) {
        mkdir($companyQrDir, 0755, true);
    }

    // Copiar el QR grande para el sistema QR
    $qrSystemPath = $companyQrDir . 'package_' . $packageId . '_qr.png';
    $copyResult = copy($qrDir . 'wifi-qr-large.png', $qrSystemPath);

    // Registrar en BD sin incluir el modelo (evitar conflicto de clases)
    try {
        $db = Database::getInstance()->getConnection();

        $qrData = [
            'empresa_id' => $companyId,
            'numero_bus' => 'PKG-' . $packageId,
            'wifi_ssid' => $wifiConfig['ssid'],
            'wifi_password' => $wifiConfig['password'],
            'portal_url' => 'http://playmi.pe',
            'archivo_path' => 'companies/' . $companyId . '/qr-codes/package_' . $packageId . '_qr.png',
            'tamano_qr' => 300,
            'nivel_correccion' => 'H',
            'estado' => 'activo'
        ];

        $sql = "INSERT INTO qr_codes (
                    empresa_id, numero_bus, wifi_ssid, wifi_password, 
                    portal_url, archivo_path, tamano_qr, nivel_correccion, 
                    estado, created_at
                ) VALUES (
                    :empresa_id, :numero_bus, :wifi_ssid, :wifi_password,
                    :portal_url, :archivo_path, :tamano_qr, :nivel_correccion,
                    :estado, NOW()
                )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':empresa_id' => $qrData['empresa_id'],
            ':numero_bus' => $qrData['numero_bus'],
            ':wifi_ssid' => $qrData['wifi_ssid'],
            ':wifi_password' => $qrData['wifi_password'],
            ':portal_url' => $qrData['portal_url'],
            ':archivo_path' => $qrData['archivo_path'],
            ':tamano_qr' => $qrData['tamano_qr'],
            ':nivel_correccion' => $qrData['nivel_correccion'],
            ':estado' => $qrData['estado']
        ]);
    } catch (Exception $e) {
    }
}

// Eliminar directorio recursivamente
function deleteDirectory($dir)
{
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}
