<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../controllers/AdvertisingController.php';
    
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('No autorizado');
    }
    
    $bannerId = $_POST['banner_id'] ?? null;
    if (!$bannerId) {
        throw new Exception('ID de banner requerido');
    }
    
    $controller = new AdvertisingController();
    $controller->delete($bannerId, 'banner');
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>