<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$qrId = $_GET['id'] ?? null;
if (!$qrId || !is_numeric($qrId)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../models/QRCode.php';
    
    $db = Database::getInstance()->getConnection();
    $qrModel = new QRCode($db);
    $qr = $qrModel->findById($qrId);
    
    if (!$qr) {
        throw new Exception('QR no encontrado');
    }
    
    // Construir la ruta correcta (igual que en preview)
    $root_path = dirname(dirname(dirname(__DIR__))); // C:\xampp\htdocs\playmi
    $filePath = $root_path . '/' . $qr['archivo_path'];
    
    // Normalizar la ruta para Windows
    $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
    
    if (!file_exists($filePath)) {
        throw new Exception('Archivo no encontrado');
    }
    
    // Obtener información de la empresa para el nombre del archivo
    require_once __DIR__ . '/../../models/Company.php';
    $companyModel = new Company($db);
    $company = $companyModel->findById($qr['empresa_id']);
    
    // Preparar nombre de descarga
    $companyName = $company ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $company['nombre']) : 'Empresa';
    $busNumber = str_replace('PKG-', 'Paquete_', $qr['numero_bus']);
    $filename = 'QR_' . $companyName . '_' . $busNumber . '_' . date('Ymd') . '.png';
    
    // Limpiar cualquier salida previa
    ob_clean();
    
    // Configurar headers para descarga
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Para evitar problemas con la descarga
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Enviar archivo
    $handle = fopen($filePath, 'rb');
    if ($handle !== false) {
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        fclose($handle);
    } else {
        readfile($filePath);
    }
    
    // Incrementar contador de descargas
    try {
        $qrModel->incrementDownloadCount($qrId);
    } catch (Exception $e) {
        // No hacer nada si falla el contador
    }
    
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>