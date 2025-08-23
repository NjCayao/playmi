<?php
/**
 * passenger-portal/api/company-branding.php
 * API para obtener personalización de la empresa
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../../admin/config/database.php';
require_once '../../admin/models/Company.php';

try {
    // Por ahora usar company_id de sesión o parámetro
    session_start();
    $companyId = $_GET['company_id'] ?? $_SESSION['company_id'] ?? 1;
    
    // Obtener datos de la empresa
    $companyModel = new Company();
    $company = $companyModel->findById($companyId);
    
    if (!$company) {
        throw new Exception('Empresa no encontrada');
    }
    
    // Preparar respuesta con branding
    $branding = [
        'company_id' => $company['id'],
        'company_name' => $company['nombre'],
        'service_name' => $company['nombre_servicio'] ?? $company['nombre'] . ' Entertainment',
        'primary_color' => $company['color_primario'] ?? '#e50914',
        'secondary_color' => $company['color_secundario'] ?? '#141414',
        'logo_url' => $company['logo_path'] ? '/playmi/companies/data/' . $company['logo_path'] : null,
        'welcome_message' => 'Bienvenido a ' . $company['nombre'],
        'package_type' => $company['tipo_paquete'],
        'theme' => 'dark', // Por defecto tema oscuro
        'features' => [
            'ads_enabled' => in_array($company['tipo_paquete'], ['basico', 'intermedio']),
            'hd_enabled' => in_array($company['tipo_paquete'], ['intermedio', 'premium']),
            '4k_enabled' => $company['tipo_paquete'] === 'premium',
            'games_enabled' => true,
            'music_enabled' => true,
            'download_enabled' => $company['tipo_paquete'] === 'premium'
        ]
    ];
    
    // Obtener banners activos de la empresa
    $db = Database::getInstance()->getConnection();
    
    // Obtener banners activos organizados por tipo
    $sql = "SELECT * FROM banners_empresa 
            WHERE empresa_id = ? AND activo = 1 
            ORDER BY tipo_banner, orden_visualizacion";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($banners) {
        // Organizar banners por tipo para facilitar su uso
        $branding['banners'] = [
            'header' => [],
            'footer' => [],
            'catalogo' => []
        ];
        
        foreach ($banners as $banner) {
            $bannerData = [
                'id' => $banner['id'],
                'image_url' => '/playmi/content/banners/' . $banner['imagen_path'],
                'position' => $banner['posicion'],
                'width' => $banner['ancho'],
                'height' => $banner['alto'],
                'order' => $banner['orden_visualizacion']
            ];
            
            // Agregar al tipo correspondiente
            $branding['banners'][$banner['tipo_banner']][] = $bannerData;
        }
        
        // También incluir lista plana para compatibilidad
        $branding['banners_list'] = array_map(function($banner) {
            return [
                'id' => $banner['id'],
                'type' => $banner['tipo_banner'],
                'image_url' => '/playmi/content/banners/' . $banner['imagen_path'],
                'position' => $banner['posicion'],
                'width' => $banner['ancho'],
                'height' => $banner['alto'],
                'order' => $banner['orden_visualizacion']
            ];
        }, $banners);
    } else {
        // Si no hay banners, estructura vacía
        $branding['banners'] = [
            'header' => [],
            'footer' => [],
            'catalogo' => []
        ];
        $branding['banners_list'] = [];
    }
    
    // Obtener información de videos publicitarios también
    $sql = "SELECT COUNT(*) as total_videos,
            SUM(CASE WHEN tipo_video = 'inicio' THEN 1 ELSE 0 END) as videos_inicio,
            SUM(CASE WHEN tipo_video = 'mitad' THEN 1 ELSE 0 END) as videos_mitad
            FROM publicidad_empresa 
            WHERE empresa_id = ? AND activo = 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyId]);
    $videoStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Agregar estadísticas de publicidad
    $branding['advertising_stats'] = [
        'has_banners' => !empty($banners),
        'total_banners' => count($banners),
        'has_video_ads' => $videoStats['total_videos'] > 0,
        'video_ads_start' => (int)$videoStats['videos_inicio'],
        'video_ads_middle' => (int)$videoStats['videos_mitad']
    ];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'branding' => $branding,
        'cached_until' => date('Y-m-d H:i:s', strtotime('+24 hours'))
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener branding',
        'message' => $e->getMessage()
    ]);
}
?>