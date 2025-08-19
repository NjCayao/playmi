<?php
/**
 * API para obtener publicidad de una empresa
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../controllers/BaseController.php';
    require_once __DIR__ . '/../../models/Advertising.php';
    
    // Verificar autenticación
    session_start();
    $baseController = new BaseController();
    if (!$baseController->isAuthenticated()) {
        throw new Exception('No autorizado');
    }
    
    // Validar empresa
    $companyId = $_GET['company_id'] ?? null;
    if (!$companyId) {
        throw new Exception('ID de empresa requerido');
    }
    
    // Obtener publicidad activa
    $advertisingModel = new Advertising();
    $ads = $advertisingModel->getActiveAdsByCompany($companyId);
    
    echo json_encode([
        'success' => true,
        'videos' => $ads['videos'],
        'banners' => $ads['banners']
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>