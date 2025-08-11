<?php
/**
 * API para cambio de contraseña
 * Permite a los usuarios cambiar su contraseña desde el navbar
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
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../controllers/AuthController.php';

try {
    // Crear instancia del controlador
    $authController = new AuthController();
    
    // Verificar autenticación
    $authController->requireAuth();
    
    // Procesar cambio de contraseña
    $authController->changePassword();
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>