<?php
/**
 * API para verificar si un RUC ya existe
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/BaseController.php';
require_once __DIR__ . '/../../models/Company.php';

try {
    $baseController = new BaseController();
    $baseController->requireAuth();
    
    $ruc = trim($_POST['ruc'] ?? '');
    
    if (empty($ruc)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    $companyModel = new Company();
    $existingCompany = $companyModel->rucExistsActive($ruc);
    
    if ($existingCompany) {
        echo json_encode([
            'exists' => true,
            'company' => [
                'id' => $existingCompany['id'],
                'nombre' => $existingCompany['nombre'],
                'estado' => $existingCompany['estado']
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor']);
}
?>