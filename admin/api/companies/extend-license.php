<?php
/**
 * API para extender licencia de empresa
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
    
    // Verificar parámetros
    $companyId = (int)($_POST['company_id'] ?? 0);
    $months = (int)($_POST['months'] ?? 0);
    
    if (!$companyId || !$months) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetros requeridos faltantes']);
        exit;
    }
    
    // Extender licencia
    $companyController->extendLicense($companyId);
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>