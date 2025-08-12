<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../models/Company.php';

try {
    // Verificar sesión de admin
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    
    $companyId = (int)($_POST['company_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    
    if (!$companyId || !$newStatus) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    
    $validStates = ['activo', 'suspendido', 'vencido'];
    if (!in_array($newStatus, $validStates)) {
        echo json_encode(['success' => false, 'error' => 'Estado inválido']);
        exit;
    }
    
    $companyModel = new Company();
    $company = $companyModel->findById($companyId);
    
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Empresa no encontrada']);
        exit;
    }
    
    $result = $companyModel->update($companyId, [
        'estado' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
?>