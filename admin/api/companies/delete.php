<?php
/**
 * API para eliminar empresa
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Permitir GET y POST para flexibilidad
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
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
    
    // Obtener ID de la empresa
    $companyId = (int)($_GET['id'] ?? $_POST['company_id'] ?? 0);
    
    if (!$companyId) {
        // Si es una petición web (GET), redirigir con error
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $companyController->setMessage('ID de empresa requerido', MSG_ERROR);
            header('Location: ' . BASE_URL . 'views/companies/index.php');
            exit;
        }
        
        // Si es AJAX (POST), devolver JSON
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de empresa requerido'
        ]);
        exit;
    }
    
    // Procesar eliminación
    $companyController->destroy($companyId);
    
} catch (Exception $e) {
    // Si es una petición web (GET), redirigir con error
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $companyController->setMessage('Error al eliminar la empresa', MSG_ERROR);
        header('Location: ' . BASE_URL . 'views/companies/index.php');
        exit;
    }
    
    // Si es AJAX (POST), devolver JSON de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>