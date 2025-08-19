<?php
/**
 * API para subir banner publicitario
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../controllers/AdvertisingController.php';
    
    $controller = new AdvertisingController();
    $controller->uploadBanner();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>