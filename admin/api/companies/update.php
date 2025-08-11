<?php
/**
 * API para actualizar empresa existente
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Incluir configuración
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

try {
    // Crear instancia del controlador
    $companyController = new CompanyController();
    
    // Procesar actualización de empresa
    $companyController->update();
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>