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
    // ✅ CORRECCIÓN: Verificar sesión antes de iniciarla
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // ✅ CORRECCIÓN: Verificar autenticación con múltiples campos posibles
    $isAuthenticated = isset($_SESSION['user_id']) || 
                      isset($_SESSION['admin_logged_in']) || 
                      isset($_SESSION['admin_id']) || 
                      isset($_SESSION['logged_in']);
    
    if (!$isAuthenticated) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    
    $companyId = (int)($_POST['company_id'] ?? 0);
    $months = (int)($_POST['months'] ?? 0);
    
    if (!$companyId || !$months) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
        exit;
    }
    
    $companyModel = new Company();
    $company = $companyModel->findById($companyId);
    
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Empresa no encontrada']);
        exit;
    }
    
    // Calcular nueva fecha de vencimiento
    $currentExpiry = new DateTime($company['fecha_vencimiento']);
    $today = new DateTime();
    
    // Si ya está vencida, extender desde hoy, sino desde fecha actual
    $baseDate = $currentExpiry > $today ? $currentExpiry : $today;
    $baseDate->add(new DateInterval('P' . $months . 'M'));
    
    $result = $companyModel->update($companyId, [
        'fecha_vencimiento' => $baseDate->format('Y-m-d'),
        'estado' => 'activo', // Reactivar
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => "Licencia extendida por $months meses hasta " . $baseDate->format('d/m/Y')
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al extender la licencia']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
?>