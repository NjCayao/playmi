<?php
/**
 * API para subir video publicitario
 */

// Habilitar reporte de errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// NO establecer header JSON todavía para poder ver errores PHP
// header('Content-Type: application/json');

try {
    // Verificar que los archivos existen
    $configPath = __DIR__ . '/../../config/system.php';
    $controllerPath = __DIR__ . '/../../controllers/AdvertisingController.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("No se encuentra config/system.php en: " . $configPath);
    }
    
    if (!file_exists($controllerPath)) {
        throw new Exception("No se encuentra AdvertisingController.php en: " . $controllerPath);
    }
    
    require_once $configPath;
    require_once $controllerPath;
    
    // Ahora sí, establecer header JSON
    header('Content-Type: application/json');
    
    // El controlador maneja todo
    $controller = new AdvertisingController();
    $controller->uploadVideo();
    
} catch (Exception $e) {
    // Si hay error, asegurarse de enviar JSON
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>