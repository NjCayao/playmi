<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../models/Advertising.php';
    
    // Verificar si la sesión ya está iniciada
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('No autorizado');
    }
    
    $companyId = $_GET['company_id'] ?? null;
    if (!$companyId) {
        throw new Exception('ID de empresa requerido');
    }
    
    $advertisingModel = new Advertising();
    
    // Obtener videos y banners de la empresa
    $videos = $advertisingModel->getVideos($companyId);
    $banners = $advertisingModel->getBanners($companyId);
    
    // Filtrar solo los activos
    $activeVideos = array_filter($videos, function($v) { return $v['activo'] == 1; });
    $activeBanners = array_filter($banners, function($b) { return $b['activo'] == 1; });
    
    echo json_encode([
        'success' => true,
        'videos' => array_values($activeVideos),
        'banners' => array_values($activeBanners)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>