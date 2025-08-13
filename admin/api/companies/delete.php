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
    // Verificar sesión
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticación
    $isAuthenticated = isset($_SESSION['user_id']) || 
                      isset($_SESSION['admin_logged_in']) || 
                      isset($_SESSION['admin_id']) || 
                      isset($_SESSION['logged_in']);
    
    if (!$isAuthenticated) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    
    $companyId = (int)($_POST['company_id'] ?? 0);
    
    if (!$companyId) {
        echo json_encode(['success' => false, 'error' => 'ID de empresa requerido']);
        exit;
    }
    
    $companyModel = new Company();
    $company = $companyModel->findById($companyId);
    
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Empresa no encontrada']);
        exit;
    }
    
    // Guardar nombre para el mensaje
    $companyName = $company['nombre'];
    
    // Eliminar logo si existe
    if ($company['logo_path']) {
        $logoPath = __DIR__ . '/../../..' . '/companies/data/' . $company['logo_path'];
        if (file_exists($logoPath)) {
            unlink($logoPath);
        }
    }
    
    // Eliminar empresa de la base de datos
    $result = $companyModel->delete($companyId);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "La empresa '$companyName' ha sido eliminada correctamente"
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la empresa']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
?>