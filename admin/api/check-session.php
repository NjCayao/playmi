<?php
/**
 * API para verificar estado de sesión
 * Usado por JavaScript para verificaciones periódicas
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Incluir configuración
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../controllers/AuthController.php';

try {
    // Crear instancia del controlador
    $authController = new AuthController();
    
    // Verificar autenticación
    $isAuthenticated = $authController->isAuthenticated();
    $user = $isAuthenticated ? $authController->getCurrentUser() : null;
    
    // Calcular tiempo restante de sesión
    $sessionTimeLeft = 0;
    if ($isAuthenticated && isset($_SESSION['last_activity'])) {
        $sessionTimeLeft = SESSION_TIMEOUT - (time() - $_SESSION['last_activity']);
        $sessionTimeLeft = max(0, $sessionTimeLeft);
    }
    
    // Respuesta JSON
    $response = [
        'success' => true,
        'authenticated' => $isAuthenticated,
        'session_time_left' => $sessionTimeLeft,
        'user' => $user ? [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'nombre_completo' => $user['nombre_completo']
        ] : null,
        'server_time' => time(),
        'formatted_time' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Error en la verificación
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor',
        'authenticated' => false
    ]);
}
?>