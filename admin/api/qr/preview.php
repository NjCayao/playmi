<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Content-Type: image/png');
    $img = imagecreate(400, 400);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textcolor = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 100, 180, "No autorizado", $textcolor);
    imagepng($img);
    imagedestroy($img);
    exit;
}

$qrId = $_GET['id'] ?? null;
if (!$qrId || !is_numeric($qrId)) {
    header('Content-Type: image/png');
    $img = imagecreate(400, 400);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $textcolor = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 100, 180, "ID inválido", $textcolor);
    imagepng($img);
    imagedestroy($img);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/QRCode.php';
    
    $db = Database::getInstance()->getConnection();
    $qrModel = new QRCode($db);
    $qr = $qrModel->findById($qrId);
    
    if (!$qr) {
        throw new Exception('QR no encontrado en BD');
    }
    
    // Construir la ruta correcta (confirmada por el test)
    $root_path = dirname(dirname(dirname(__DIR__))); // C:\xampp\htdocs\playmi
    $filePath = $root_path . '/' . $qr['archivo_path'];
    
    // Normalizar la ruta para Windows
    $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    
    if (!file_exists($filePath)) {
        throw new Exception('Archivo no existe: ' . $filePath);
    }
    
    // Obtener información de la imagen
    $imageInfo = getimagesize($filePath);
    if ($imageInfo === false) {
        throw new Exception('No es una imagen válida');
    }
    
    // Enviar headers correctos
    header('Content-Type: ' . $imageInfo['mime']);
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Limpiar cualquier salida previa
    ob_clean();
    flush();
    
    // Enviar el archivo
    readfile($filePath);
    exit;
    
} catch (Exception $e) {
    // Mostrar imagen de error
    header('Content-Type: image/png');
    $img = imagecreate(400, 400);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $red = imagecolorallocate($img, 255, 0, 0);
    
    imagerectangle($img, 0, 0, 399, 399, $red);
    imagestring($img, 5, 50, 180, "Error: " . $e->getMessage(), $red);
    
    imagepng($img);
    imagedestroy($img);
}
?>