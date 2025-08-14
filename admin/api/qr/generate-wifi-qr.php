<?php
// admin/api/qr/generate-wifi-qr.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir configuración primero
require_once dirname(dirname(__DIR__)) . '/config/system.php';
require_once dirname(dirname(__DIR__)) . '/libs/phpqrcode/qrlib.php';

// Headers
header('Content-Type: image/png');
header('Cache-Control: no-cache');

// Verificar sesión
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    // Generar imagen de error
    $img = imagecreate(200, 200);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textcolor = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 50, 90, "No autorizado", $textcolor);
    imagepng($img);
    imagedestroy($img);
    exit;
}

// Obtener parámetros
$ssid = $_GET['ssid'] ?? 'PLAYMI-WIFI';
$password = $_GET['password'] ?? '';
$hidden = $_GET['hidden'] === 'true' ? 'true' : 'false';
$security = $_GET['security'] ?? 'WPA';
$correction = $_GET['correction'] ?? 'M';
$includeLogo = isset($_GET['include_logo']) && $_GET['include_logo'] === 'true';
$companyId = $_GET['company_id'] ?? null;

// Formato WiFi
$wifiString = "WIFI:T:{$security};S:{$ssid};P:{$password};H:{$hidden};;";

try {
    // Generar QR básico primero
    $tempFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    QRcode::png($wifiString, $tempFile, constant('QR_ECLEVEL_' . $correction), 10, 3);
    
    // Cargar la imagen QR
    $qr = imagecreatefrompng($tempFile);
    $qrWidth = imagesx($qr);
    $qrHeight = imagesy($qr);
    
    // Si incluye logo
    if ($includeLogo && $companyId) {
        require_once dirname(dirname(__DIR__)) . '/config/database.php';
        require_once dirname(dirname(__DIR__)) . '/models/Company.php';
        
        $db = Database::getInstance()->getConnection();
        $companyModel = new Company($db);
        $company = $companyModel->findById($companyId);
        
        if ($company && !empty($company['logo_path'])) {
            $logoPath = COMPANIES_PATH . '/' . $company['logo_path'];
            
            if (file_exists($logoPath)) {
                // Detectar tipo de imagen
                $imageInfo = getimagesize($logoPath);
                $logo = null;
                
                switch ($imageInfo['mime']) {
                    case 'image/jpeg':
                        $logo = imagecreatefromjpeg($logoPath);
                        break;
                    case 'image/png':
                        $logo = imagecreatefrompng($logoPath);
                        break;
                    case 'image/gif':
                        $logo = imagecreatefromgif($logoPath);
                        break;
                }
                
                if ($logo) {
                    $logoWidth = imagesx($logo);
                    $logoHeight = imagesy($logo);
                    
                    // Logo al 15% del QR
                    $logoQrWidth = $qrWidth * 0.15;
                    $logoQrHeight = $logoHeight * ($logoQrWidth / $logoWidth);
                    
                    $logoX = ($qrWidth - $logoQrWidth) / 2;
                    $logoY = ($qrHeight - $logoQrHeight) / 2;
                    
                    // Crear área blanca con borde
                    $white = imagecolorallocate($qr, 255, 255, 255);
                    $border = imagecolorallocate($qr, 230, 230, 230);
                    
                    // Borde
                    imagefilledrectangle(
                        $qr, 
                        $logoX - 8, 
                        $logoY - 8, 
                        $logoX + $logoQrWidth + 8, 
                        $logoY + $logoQrHeight + 8, 
                        $border
                    );
                    
                    // Fondo blanco
                    imagefilledrectangle(
                        $qr, 
                        $logoX - 5, 
                        $logoY - 5, 
                        $logoX + $logoQrWidth + 5, 
                        $logoY + $logoQrHeight + 5, 
                        $white
                    );
                    
                    // Insertar logo con mejor calidad
                    imagecopyresampled(
                        $qr, $logo,
                        $logoX, $logoY,
                        0, 0,
                        $logoQrWidth, $logoQrHeight,
                        $logoWidth, $logoHeight
                    );
                    
                    imagedestroy($logo);
                }
            }
        }
    }
    
    // Salida de imagen
    imagepng($qr);
    imagedestroy($qr);
    unlink($tempFile);
    
} catch (Exception $e) {
    // QR de error
    $img = imagecreate(200, 200);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textcolor = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 10, 90, "Error: " . $e->getMessage(), $textcolor);
    imagepng($img);
    imagedestroy($img);
}
?>