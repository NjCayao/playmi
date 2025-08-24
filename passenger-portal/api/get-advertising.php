<?php
/**
 * passenger-portal/api/get-advertising.php
 * API para obtener videos publicitarios de la empresa
 */

header('Content-Type: application/json');

// Verificar acceso
if (!defined('PORTAL_ACCESS')) {
    define('PORTAL_ACCESS', true);
}

require_once '../config/portal-config.php';
require_once '../../admin/config/database.php';

// Obtener parámetros
$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;

if ($company_id == 0) {
    echo json_encode([
        'success' => false,
        'error' => 'ID de empresa no válido'
    ]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener videos publicitarios activos de la empresa
    $sql = "SELECT 
                id,
                tipo_video,
                archivo_path,
                duracion,
                orden_reproduccion
            FROM publicidad_empresa 
            WHERE empresa_id = ? 
                AND activo = 1 
            ORDER BY tipo_video, orden_reproduccion";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$company_id]);
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar que los archivos existan
    $validAds = [];
    foreach ($ads as $ad) {
        $filePath = UPLOADS_PATH . $ad['archivo_path'];
        if (file_exists($filePath)) {
            $validAds[] = $ad;
        } else {
            error_log("Archivo publicitario no encontrado: " . $filePath);
        }
    }
    
    echo json_encode([
        'success' => true,
        'ads' => $validAds,
        'count' => count($validAds)
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-advertising.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener publicidad',
        'message' => $e->getMessage()
    ]);
}
?>