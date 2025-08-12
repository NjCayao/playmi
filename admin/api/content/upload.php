<?php
// ============= api/content/upload.php =============
/**
 * API para subir contenido
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
require_once __DIR__ . '/../../controllers/ContentController.php';

try {
    // Crear instancia del controlador
    $contentController = new ContentController();
    
    // Procesar subida
    $contentController->upload();
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

// ============= api/content/delete.php =============
/**
 * API para eliminar contenido
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
require_once __DIR__ . '/../../controllers/ContentController.php';

try {
    // Crear instancia del controlador
    $contentController = new ContentController();
    
    // Obtener ID
    $contentId = (int)($_POST['content_id'] ?? 0);
    
    if (!$contentId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de contenido requerido'
        ]);
        exit;
    }
    
    // Procesar eliminación
    $contentController->delete($contentId);
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}

// ============= api/content/toggle-status.php =============
/**
 * API para cambiar estado de contenido
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
require_once __DIR__ . '/../../controllers/BaseController.php';
require_once __DIR__ . '/../../models/Content.php';

try {
    // Verificar autenticación
    $baseController = new BaseController();
    $baseController->requireAuth();
    
    // Obtener parámetros
    $contentId = (int)($_POST['content_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    
    if (!$contentId || !$newStatus) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Parámetros requeridos faltantes'
        ]);
        exit;
    }
    
    // Validar estado
    $validStatuses = ['activo', 'inactivo'];
    if (!in_array($newStatus, $validStatuses)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Estado no válido'
        ]);
        exit;
    }
    
    // Actualizar estado
    $contentModel = new Content();
    $result = $contentModel->update($contentId, ['estado' => $newStatus]);
    
    if ($result) {
        // Registrar actividad
        $baseController->logActivity('status_update', 'contenido', $contentId, null, ['new_status' => $newStatus]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar el estado'
        ]);
    }
    
} catch (Exception $e) {
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>