<?php
/**
 * Controlador de Publicidad PLAYMI
 * Maneja videos publicitarios y banners por empresa
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../models/Advertising.php';
require_once __DIR__ . '/../models/Company.php';

class AdvertisingController extends BaseController {
    private $advertisingModel;
    private $companyModel;
    
    public function __construct() {
        parent::__construct();
        $this->advertisingModel = new Advertising();
        $this->companyModel = new Company();
    }
    
    /**
     * Listar videos publicitarios
     */
    public function videos() {
        try {
            $this->requireAuth();
            
            // Obtener filtros
            $companyId = (int)$this->getParam('company_id', 0);
            $tipo = $this->getParam('tipo', '');
            
            // Obtener videos
            $videos = $this->advertisingModel->getVideos($companyId, $tipo);
            
            // Obtener empresas para filtro
            $companies = $this->companyModel->getActiveCompanies();
            
            // Estadísticas
            $stats = [
                'total_videos' => count($videos),
                'total_inicio' => count(array_filter($videos, fn($v) => $v['tipo_video'] === 'inicio')),
                'total_mitad' => count(array_filter($videos, fn($v) => $v['tipo_video'] === 'mitad')),
                'empresas_con_publicidad' => $this->advertisingModel->countCompaniesWithAds()
            ];
            
            return [
                'videos' => $videos,
                'companies' => $companies,
                'filters' => [
                    'company_id' => $companyId,
                    'tipo' => $tipo
                ],
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en advertising videos: " . $e->getMessage());
            return ['error' => true];
        }
    }
    
    /**
     * Subir video publicitario
     */
    public function uploadVideo() {
        try {
            $this->requireAuth();
            
            if (!$this->isPost()) {
                // Mostrar formulario
                return [
                    'companies' => $this->companyModel->getActiveCompanies(),
                    'tipos' => [
                        'inicio' => 'Video al inicio',
                        'mitad' => 'Video a la mitad'
                    ]
                ];
            }
            
            // Procesar subida
            $data = $this->sanitizeInput($_POST);
            
            // Validar datos
            $errors = $this->validateInput($data, [
                'empresa_id' => [
                    'required' => true,
                    'label' => 'Empresa',
                    'numeric' => true
                ],
                'tipo_video' => [
                    'required' => true,
                    'label' => 'Tipo de video'
                ]
            ]);
            
            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }
            
            // Validar archivo
            if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse(['error' => 'Video requerido'], 400);
            }
            
            // Subir video
            $uploadPath = UPLOADS_PATH . 'advertising/';
            $uploadResult = $this->handleFileUpload(
                $_FILES['video'],
                $uploadPath,
                ['mp4', 'avi', 'mov'],
                100 * 1024 * 1024 // 100MB máximo para publicidad
            );
            
            if (!$uploadResult['success']) {
                $this->jsonResponse(['error' => $uploadResult['error']], 400);
            }
            
            // Obtener duración del video
            $duration = $this->getVideoDuration($uploadResult['path']);
            
            // Validar duración (máximo 30 segundos)
            if ($duration > 30) {
                @unlink($uploadResult['path']);
                $this->jsonResponse(['error' => 'El video no debe superar los 30 segundos'], 400);
            }
            
            // Guardar en base de datos
            $adData = [
                'empresa_id' => $data['empresa_id'],
                'tipo_video' => $data['tipo_video'],
                'archivo_path' => 'advertising/' . $uploadResult['filename'],
                'duracion' => $duration,
                'tamaño_archivo' => $uploadResult['size'],
                'orden_reproduccion' => $data['orden_reproduccion'] ?? 1,
                'activo' => 1
            ];
            
            $adId = $this->advertisingModel->createVideo($adData);
            
            if ($adId) {
                $this->logActivity('upload_ad_video', 'publicidad_empresa', $adId, null, $adData);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Video publicitario subido exitosamente',
                    'ad_id' => $adId
                ]);
            } else {
                @unlink($uploadResult['path']);
                $this->jsonResponse(['error' => 'Error al guardar el video'], 500);
            }
            
        } catch (Exception $e) {
            $this->logError("Error en uploadVideo: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Gestionar banners
     */
    public function banners() {
        try {
            $this->requireAuth();
            
            // Obtener filtros
            $companyId = (int)$this->getParam('company_id', 0);
            $tipo = $this->getParam('tipo', '');
            
            // Obtener banners
            $banners = $this->advertisingModel->getBanners($companyId, $tipo);
            
            // Obtener empresas
            $companies = $this->companyModel->getActiveCompanies();
            
            return [
                'banners' => $banners,
                'companies' => $companies,
                'filters' => [
                    'company_id' => $companyId,
                    'tipo' => $tipo
                ],
                'banner_types' => [
                    'header' => 'Banner Superior',
                    'footer' => 'Banner Inferior',
                    'catalogo' => 'Banner en Catálogo'
                ]
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en advertising banners: " . $e->getMessage());
            return ['error' => true];
        }
    }
    
    /**
     * Subir banner
     */
    public function uploadBanner() {
        try {
            $this->requireAuth();
            
            if (!$this->isPost()) {
                return [
                    'companies' => $this->companyModel->getActiveCompanies(),
                    'tipos' => [
                        'header' => 'Banner Superior (1920x200)',
                        'footer' => 'Banner Inferior (1920x100)',
                        'catalogo' => 'Banner en Catálogo (300x250)'
                    ]
                ];
            }
            
            // Procesar subida
            $data = $this->sanitizeInput($_POST);
            
            // Validar datos
            $errors = $this->validateInput($data, [
                'empresa_id' => [
                    'required' => true,
                    'label' => 'Empresa',
                    'numeric' => true
                ],
                'tipo_banner' => [
                    'required' => true,
                    'label' => 'Tipo de banner'
                ]
            ]);
            
            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }
            
            // Validar archivo
            if (!isset($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
                $this->jsonResponse(['error' => 'Imagen requerida'], 400);
            }
            
            // Subir imagen
            $uploadPath = UPLOADS_PATH . 'banners/';
            $uploadResult = $this->handleFileUpload(
                $_FILES['banner'],
                $uploadPath,
                ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                5 * 1024 * 1024 // 5MB máximo
            );
            
            if (!$uploadResult['success']) {
                $this->jsonResponse(['error' => $uploadResult['error']], 400);
            }
            
            // Obtener dimensiones
            list($width, $height) = getimagesize($uploadResult['path']);
            
            // Validar dimensiones según tipo
            $validDimensions = $this->validateBannerDimensions($data['tipo_banner'], $width, $height);
            if (!$validDimensions['valid']) {
                @unlink($uploadResult['path']);
                $this->jsonResponse(['error' => $validDimensions['message']], 400);
            }
            
            // Guardar en base de datos
            $bannerData = [
                'empresa_id' => $data['empresa_id'],
                'tipo_banner' => $data['tipo_banner'],
                'imagen_path' => 'banners/' . $uploadResult['filename'],
                'ancho' => $width,
                'alto' => $height,
                'tamaño_archivo' => $uploadResult['size'],
                'posicion' => $data['posicion'] ?? null,
                'orden_visualizacion' => $data['orden_visualizacion'] ?? 1,
                'activo' => 1
            ];
            
            $bannerId = $this->advertisingModel->createBanner($bannerData);
            
            if ($bannerId) {
                $this->logActivity('upload_banner', 'banners_empresa', $bannerId, null, $bannerData);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Banner subido exitosamente',
                    'banner_id' => $bannerId
                ]);
            } else {
                @unlink($uploadResult['path']);
                $this->jsonResponse(['error' => 'Error al guardar el banner'], 500);
            }
            
        } catch (Exception $e) {
            $this->logError("Error en uploadBanner: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Eliminar publicidad
     */
    public function delete($id, $type = 'video') {
        try {
            $this->requireAuth();
            
            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }
            
            if ($type === 'video') {
                $ad = $this->advertisingModel->getVideoById($id);
                if (!$ad) {
                    $this->jsonResponse(['error' => 'Video no encontrado'], 404);
                }
                
                // Eliminar archivo
                $filePath = UPLOADS_PATH . $ad['archivo_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                
                // Eliminar registro
                $result = $this->advertisingModel->deleteVideo($id);
                
            } else {
                $banner = $this->advertisingModel->getBannerById($id);
                if (!$banner) {
                    $this->jsonResponse(['error' => 'Banner no encontrado'], 404);
                }
                
                // Eliminar archivo
                $filePath = UPLOADS_PATH . $banner['imagen_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                
                // Eliminar registro
                $result = $this->advertisingModel->deleteBanner($id);
            }
            
            if ($result) {
                $this->logActivity('delete_ad', $type === 'video' ? 'publicidad_empresa' : 'banners_empresa', $id);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => ucfirst($type) . ' eliminado exitosamente'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error al eliminar'], 500);
            }
            
        } catch (Exception $e) {
            $this->logError("Error en delete advertising: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleStatus($id, $type = 'video') {
        try {
            $this->requireAuth();
            
            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }
            
            $newStatus = $this->postParam('status') === 'true' ? 1 : 0;
            
            if ($type === 'video') {
                $result = $this->advertisingModel->updateVideoStatus($id, $newStatus);
            } else {
                $result = $this->advertisingModel->updateBannerStatus($id, $newStatus);
            }
            
            if ($result) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Estado actualizado'
                ]);
            } else {
                $this->jsonResponse(['error' => 'Error al actualizar estado'], 500);
            }
            
        } catch (Exception $e) {
            $this->logError("Error en toggleStatus advertising: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Obtener duración de video
     */
    private function getVideoDuration($filePath) {
        // Implementar con ffprobe
        // Por ahora retornar duración fija
        return 15; // 15 segundos
    }
    
    /**
     * Validar dimensiones de banner
     */
    private function validateBannerDimensions($type, $width, $height) {
        $dimensions = [
            'header' => ['width' => 1920, 'height' => 200],
            'footer' => ['width' => 1920, 'height' => 100],
            'catalogo' => ['width' => 300, 'height' => 250]
        ];
        
        if (!isset($dimensions[$type])) {
            return ['valid' => false, 'message' => 'Tipo de banner no válido'];
        }
        
        $expected = $dimensions[$type];
        
        // Permitir 10% de variación
        $widthMin = $expected['width'] * 0.9;
        $widthMax = $expected['width'] * 1.1;
        $heightMin = $expected['height'] * 0.9;
        $heightMax = $expected['height'] * 1.1;
        
        if ($width < $widthMin || $width > $widthMax || $height < $heightMin || $height > $heightMax) {
            return [
                'valid' => false,
                'message' => "Las dimensiones deben ser aproximadamente {$expected['width']}x{$expected['height']}px"
            ];
        }
        
        return ['valid' => true];
    }
}
?>