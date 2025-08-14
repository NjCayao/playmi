<?php
/**
 * MÓDULO 2.3.5: API para generar paquetes completos en background
 * Proceso pesado que genera paquetes personalizados para empresas
 * 
 * Proceso de generación:
 * 1. Validar datos de entrada
 * 2. Crear estructura de directorios
 * 3. Copiar contenido seleccionado
 * 4. Generar configuraciones
 * 5. Personalizar portal con branding
 * 6. Crear archivos de configuración JSON
 * 7. Comprimir todo en ZIP
 * 8. Mover a directorio final
 * 9. Actualizar base de datos
 * 10. Limpiar archivos temporales
 */

// Incluir configuración y controladores necesarios
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';
require_once __DIR__ . '/../../models/Package.php';
require_once __DIR__ . '/../../models/Company.php';
require_once __DIR__ . '/../../models/Content.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Aumentar límites para proceso pesado
set_time_limit(0); // Sin límite de tiempo
ini_set('memory_limit', '1024M'); // 1GB de memoria

// Función principal de generación
try {
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
    $packageModel = new Package();
    $companyModel = new Company();
    $contentModel = new Content();
    
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
        'generado_por' => $_SESSION['admin_id'],
        'estado' => 'generando',
        'notas' => $data['notas'] ?? null
    ];
    
    $packageResult = $packageModel->startGeneration(
        $data['empresa_id'], 
        $_SESSION['admin_id'], 
        $packageData
    );
    
    if (!$packageResult['success']) {
        throw new Exception('Error al iniciar generación del paquete');
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
        $tempPath . '/config',
        $tempPath . '/portal',
        $tempPath . '/portal/assets',
        $tempPath . '/portal/assets/css',
        $tempPath . '/portal/assets/js',
        $tempPath . '/portal/assets/images',
        $tempPath . '/install'
    ];
    
    foreach ($packageStructure as $dir) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception('Error creando estructura de directorios');
        }
    }
    
    // Copiar contenido seleccionado
    $totalSize = 0;
    $contentCount = 0;
    
    foreach ($selectedContent as $content) {
        $sourcePath = UPLOADS_PATH . $content['archivo_path'];
        
        if (!file_exists($sourcePath)) {
            continue; // Saltar si el archivo no existe
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
                
                if (!is_dir(dirname($thumbDest))) {
                    mkdir(dirname($thumbDest), 0755, true);
                }
                
                if (file_exists($thumbSource)) {
                    copy($thumbSource, $thumbDest);
                }
            }
        }
    }
    
    // Generar archivos de configuración
    generateConfigFiles($tempPath, $company, $data);
    
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
        'checksum' => null // Se calculará después de comprimir
    ];
    
    file_put_contents(
        $tempPath . '/package-info.json',
        json_encode($packageInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
    
    // Comprimir todo en un archivo ZIP
    $zipPath = PACKAGES_PATH . $company['id'] . '/';
    if (!is_dir($zipPath)) {
        mkdir($zipPath, 0755, true);
    }
    
    $zipFilename = 'package_' . $packageId . '_' . date('YmdHis') . '.zip';
    $zipFullPath = $zipPath . $zipFilename;
    
    $zip = new ZipArchive();
    if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new Exception('Error creando archivo ZIP');
    }
    
    // Función recursiva para agregar archivos al ZIP
    $addDirToZip = function($dir, $zipPath = '') use (&$addDirToZip, $zip, $tempPath) {
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
    if (isset($packageId)) {
        $packageModel->updateStatus($packageId, 'error', [
            'error_message' => $e->getMessage()
        ]);
    }
    
    // Limpiar archivos temporales si existen
    if (isset($tempPath) && is_dir($tempPath)) {
        deleteDirectory($tempPath);
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Funciones auxiliares
 */

// Registrar contenido del paquete
function registerPackageContent($packageId, $contentIds) {
    $db = Database::getInstance()->getConnection();
    
    $sql = "INSERT INTO paquetes_contenido (paquete_id, contenido_id) VALUES (?, ?)";
    $stmt = $db->prepare($sql);
    
    foreach ($contentIds as $contentId) {
        $stmt->execute([$packageId, $contentId]);
    }
}

// Generar archivos de configuración
function generateConfigFiles($basePath, $company, $data) {
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
    
    // Configuración del portal
    $portalConfig = [
        'company' => [
            'id' => $company['id'],
            'name' => $company['nombre'],
            'logo' => $company['logo_path'],
            'service_name' => $data['portal_name'] ?? $company['nombre_servicio'] ?? 'PLAYMI Entertainment'
        ],
        'branding' => [
            'primary_color' => $data['color_primario'] ?? $company['color_primario'] ?? '#2563eb',
            'secondary_color' => $data['color_secundario'] ?? $company['color_secundario'] ?? '#64748b',
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
    
    // Configuración de QR codes
    $qrConfig = [
        'wifi_qr' => generateWiFiQRString($wifiConfig),
        'portal_url' => 'http://192.168.1.1',
        'company_id' => $company['id']
    ];
    
    file_put_contents(
        $basePath . '/config/qr-config.json',
        json_encode($qrConfig, JSON_PRETTY_PRINT)
    );
}

// Copiar archivos del portal
function copyPortalFiles($portalPath, $company, $data) {
    // Aquí copiaríamos los archivos del portal web
    // Por ahora solo creamos un index.html básico
    
    $indexHtml = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($data['portal_name'] ?? 'PLAYMI Entertainment') . '</title>
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
    <h1>' . htmlspecialchars($data['portal_name'] ?? 'PLAYMI Entertainment') . '</h1>
    <p>' . htmlspecialchars($data['mensaje_bienvenida'] ?? 'Bienvenido a bordo!') . '</p>
    <p>Portal en construcción...</p>
</body>
</html>';
    
    file_put_contents($portalPath . '/index.html', $indexHtml);
    
    // Copiar logo de la empresa si existe
    if ($company['logo_path'] && file_exists(COMPANIES_PATH . $company['logo_path'])) {
        copy(
            COMPANIES_PATH . $company['logo_path'],
            $portalPath . '/assets/images/company-logo.png'
        );
    }
}

// Generar scripts de instalación
function generateInstallScripts($installPath, $data) {
    // Script principal de instalación
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

# Función para verificar permisos de root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        echo "Este script debe ejecutarse como root (sudo)"
        exit 1
    fi
}

# Función principal de instalación
main() {
    check_root
    
    echo "1. Actualizando sistema..."
    apt-get update
    
    echo "2. Instalando dependencias..."
    apt-get install -y apache2 php libapache2-mod-php hostapd dnsmasq
    
    echo "3. Configurando WiFi Access Point..."
    # Configuración de hostapd y dnsmasq
    
    echo "4. Copiando archivos del portal..."
    cp -r ../portal/* /var/www/html/
    
    echo "5. Configurando permisos..."
    chown -R www-data:www-data /var/www/html
    chmod -R 755 /var/www/html
    
    echo "6. Reiniciando servicios..."
    systemctl restart apache2
    systemctl restart hostapd
    systemctl restart dnsmasq
    
    echo "==================================="
    echo "Instalación completada!"
    echo "SSID: $WIFI_SSID"
    echo "==================================="
}

# Ejecutar instalación
main
';
    
    file_put_contents($installPath . '/install.sh', $installScript);
    chmod($installPath . '/install.sh', 0755);
    
    // Script de configuración de red
    $networkScript = '#!/bin/bash
# Network Configuration Script

# Configurar interfaz WiFi como Access Point
echo "Configurando red WiFi..."

# Contenido de configuración...
';
    
    file_put_contents($installPath . '/setup-network.sh', $networkScript);
    chmod($installPath . '/setup-network.sh', 0755);
}

// Generar string QR para WiFi
function generateWiFiQRString($wifiConfig) {
    $hidden = $wifiConfig['hidden'] ? 'true' : 'false';
    return "WIFI:T:WPA;S:{$wifiConfig['ssid']};P:{$wifiConfig['password']};H:{$hidden};;";
}

// Eliminar directorio recursivamente
function deleteDirectory($dir) {
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

/**
 * Generar QR Code WiFi para el paquete
 */
function generatePackageQR($packagePath, $wifiConfig) {
    require_once '../../libs/phpqrcode/qrlib.php';
    
    // Formato WiFi QR
    $wifiString = "WIFI:T:WPA;S:{$wifiConfig['ssid']};P:{$wifiConfig['password']};H:{$wifiConfig['hidden']};;";
    
    // Crear directorio si no existe
    $qrDir = $packagePath . '/config/qr/';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }
    
    // Generar múltiples formatos del QR
    $sizes = [
        'small' => ['size' => 5, 'file' => 'wifi-qr-small.png'],
        'medium' => ['size' => 10, 'file' => 'wifi-qr-medium.png'],
        'large' => ['size' => 15, 'file' => 'wifi-qr-large.png'],
        'print' => ['size' => 20, 'file' => 'wifi-qr-print.png'] // Para imprimir
    ];
    
    foreach ($sizes as $key => $config) {
        $qrPath = $qrDir . $config['file'];
        QRcode::png($wifiString, $qrPath, QR_ECLEVEL_L, $config['size'], 2);
    }
    
    return true;
}
?>