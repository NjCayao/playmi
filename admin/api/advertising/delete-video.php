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
    
    $videoId = $_POST['video_id'] ?? null;
    if (!$videoId) {
        throw new Exception('ID de video requerido');
    }
    
    // Limpiar cualquier output anterior
    ob_clean();
    
    $controller = new AdvertisingController();
    
    // Llamar directamente al modelo para evitar problemas con el controlador
    require_once __DIR__ . '/../../models/Advertising.php';
    $advertisingModel = new Advertising();
    
    // Obtener info del video
    $video = $advertisingModel->getVideoById($videoId);
    
    if ($video) {
        // Eliminar de BD
        $deleted = $advertisingModel->deleteVideo($videoId);
        
        if ($deleted) {
            // Eliminar archivo físico
            $filePath = UPLOADS_PATH . $video['archivo_path'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Video eliminado correctamente'
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
            'error' => 'Video no encontrado'
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