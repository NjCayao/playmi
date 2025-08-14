<?php
/**
 * API para actualizar estado de contenido
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../models/Content.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Validar parámetros
    $contentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $newStatus = $_POST['status'] ?? '';
    
    if (!$contentId || !$newStatus) {
        throw new Exception('Parámetros inválidos');
    }
    
    // Validar estado
    $validStatuses = ['activo', 'inactivo', 'procesando'];
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Estado no válido');
    }
    
    // Actualizar
    $contentModel = new Content();
    $result = $contentModel->update($contentId, ['estado' => $newStatus]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar estado');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>