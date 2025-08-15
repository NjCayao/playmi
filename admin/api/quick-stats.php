<?php
// admin/api/quick-stats.php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Stats básicas
    $stats = [
        'companies' => $db->query("SELECT COUNT(*) FROM empresas")->fetchColumn(),
        'packages' => $db->query("SELECT COUNT(*) FROM paquetes")->fetchColumn(),
        'content' => $db->query("SELECT COUNT(*) FROM contenido")->fetchColumn(),
        'buses' => $db->query("SELECT COUNT(*) FROM buses")->fetchColumn()
    ];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>