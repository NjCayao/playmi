<?php
/**
 * API para estadísticas del dashboard
 * Endpoint para actualizaciones en tiempo real
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Incluir configuración
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../controllers/DashboardController.php';

try {
    // Crear instancia del controlador
    $dashboardController = new DashboardController();
    
    // Verificar autenticación
    $dashboardController->requireAuth();
    
    // Obtener estadísticas actualizadas
    $stats = $dashboardController->getMainStats();
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => time(),
        'formatted_time' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    // Error en la obtención de estadísticas
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error obteniendo estadísticas'
    ]);
}
?>