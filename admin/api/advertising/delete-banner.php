<?php
// IMPORTANTE: No enviar nada antes de los headers
ob_start();

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../config/system.php';
    require_once __DIR__ . '/../../controllers/AdvertisingController.php';
    
    session_start();
    
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        throw new Exception('No autorizado');
    }
    
    $bannerId = $_POST['banner_id'] ?? null;
    if (!$bannerId) {
        throw new Exception('ID de banner requerido');
    }
    
    // Limpiar cualquier output anterior
    ob_clean();
    
    // Llamar directamente al modelo
    require_once __DIR__ . '/../../models/Advertising.php';
    $advertisingModel = new Advertising();
    
    // Obtener info del banner
    $banner = $advertisingModel->getBannerById($bannerId);
    
    if ($banner) {
        // Eliminar de BD
        $deleted = $advertisingModel->deleteBanner($bannerId);
        
        if ($deleted) {
            // Eliminar archivo físico
            $filePath = UPLOADS_PATH . $banner['imagen_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Banner eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Error al eliminar de la base de datos'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Banner no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Asegurarse de que no haya más output
exit();
?>