<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../models/Company.php';

try {
    // Verificar sesión de admin
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'No autorizado']);
        exit;
    }
    
    $companyModel = new Company();
    
    // Obtener estadísticas básicas
    $stats = [
        'total_companies' => $companyModel->count(),
        'active_companies' => $companyModel->count("estado = 'activo'"),
        'suspended_companies' => $companyModel->count("estado = 'suspendido'"),
        'expired_companies' => $companyModel->count("estado = 'vencido'")
    ];
    
    // Calcular ingresos mensuales
    try {
        $sql = "SELECT SUM(costo_mensual) as total FROM companies WHERE estado = 'activo'";
        $stmt = $companyModel->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['monthly_revenue'] = (float)($result['total'] ?? 0);
    } catch (Exception $e) {
        $stats['monthly_revenue'] = 0;
    }
    
    // Empresas próximas a vencer
    try {
        $sql = "SELECT COUNT(*) as total FROM companies 
            WHERE estado = 'activo' 
            AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        $stmt = $companyModel->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['expiring_soon'] = (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        $stats['expiring_soon'] = 0;
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()]);
}
?>