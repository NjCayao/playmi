<?php
/**
 * passenger-portal/api/get-advertising.php
 * API para obtener publicidad según empresa y paquete
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../admin/config/database.php';
require_once '../../admin/models/Advertising.php';

try {
    $companyId = (int)($_GET['company_id'] ?? 1);
    $adType = $_GET['type'] ?? 'all'; // 'inicio', 'mitad', 'all'
    
    // Crear instancia del modelo
    $advertisingModel = new Advertising();
    
    // Obtener publicidad activa
    $ads = [];
    
    if ($adType === 'all' || $adType === 'video') {
        // Obtener videos publicitarios
        $videos = $advertisingModel->getVideos($companyId);
        
        foreach ($videos as $video) {
            if ($video['activo']) {
                $ads[] = [
                    'id' => $video['id'],
                    'type' => 'video',
                    'position' => $video['tipo_video'], // 'inicio' o 'mitad'
                    'archivo_path' => $video['archivo_path'],
                    'duration' => $video['duracion'],
                    'order' => $video['orden_reproduccion']
                ];
            }
        }
    }
    
    if ($adType === 'all' || $adType === 'banner') {
        // Obtener banners
        $banners = $advertisingModel->getBanners($companyId);
        
        foreach ($banners as $banner) {
            if ($banner['activo']) {
                $ads[] = [
                    'id' => $banner['id'],
                    'type' => 'banner',
                    'position' => $banner['tipo_banner'], // 'header', 'footer', 'catalogo'
                    'imagen_path' => $banner['imagen_path'],
                    'width' => $banner['ancho'],
                    'height' => $banner['alto'],
                    'order' => $banner['orden_visualizacion']
                ];
            }
        }
    }
    
    // Ordenar por orden de reproducción/visualización
    usort($ads, function($a, $b) {
        return $a['order'] - $b['order'];
    });
    
    echo json_encode([
        'success' => true,
        'ads' => $ads,
        'company_id' => $companyId,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener publicidad',
        'message' => $e->getMessage()
    ]);
}
?>