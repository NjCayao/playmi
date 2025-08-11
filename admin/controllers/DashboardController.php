<?php
/**
 * Controlador de Dashboard PLAYMI
 * Maneja el dashboard principal con estadísticas y resúmenes
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Content.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Notification.php';

class DashboardController extends BaseController {
    private $userModel;
    private $companyModel;
    private $contentModel;
    private $packageModel;
    private $notificationModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->companyModel = new Company();
        $this->contentModel = new Content();
        $this->packageModel = new Package();
        $this->notificationModel = new Notification();
    }
    
    /**
     * Mostrar dashboard principal
     */
    public function index() {
        try {
            $this->requireAuth();
            
            // Obtener estadísticas principales
            $stats = $this->getMainStats();
            
            // Obtener actividad reciente
            $recentActivity = $this->getRecentActivity();
            
            // Obtener alertas del sistema
            $systemAlerts = $this->getSystemAlerts();
            
            // Obtener datos para gráficos
            $chartData = $this->getChartData();
            
            // Si es petición AJAX, devolver solo datos
            if ($this->isAjax()) {
                $this->jsonResponse([
                    'stats' => $stats,
                    'recent_activity' => $recentActivity,
                    'system_alerts' => $systemAlerts,
                    'chart_data' => $chartData
                ]);
            }
            
            // Cargar vista del dashboard
            $data = [
                'stats' => $stats,
                'recent_activity' => $recentActivity,
                'system_alerts' => $systemAlerts,
                'chart_data' => $chartData,
                'current_user' => $this->getCurrentUser()
            ];
            
            return $data; // La vista usará estos datos
            
        } catch (Exception $e) {
            $this->logError("Error en dashboard index: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Error cargando dashboard'], 500);
            } else {
                $this->setMessage('Error cargando el dashboard', MSG_ERROR);
                return ['error' => true];
            }
        }
    }
    
    /**
     * Obtener estadísticas principales
     */
    public function getMainStats() {
        try {
            $stats = [
                'companies' => [
                    'total' => $this->companyModel->count(),
                    'active' => $this->companyModel->count("estado = 'activo'"),
                    'expiring_soon' => count($this->companyModel->getExpiringCompanies()),
                    'suspended' => $this->companyModel->count("estado = 'suspendido'")
                ],
                'content' => [
                    'total' => $this->contentModel->count(),
                    'movies' => $this->contentModel->count("tipo = 'pelicula' AND estado = 'activo'"),
                    'music' => $this->contentModel->count("tipo = 'musica' AND estado = 'activo'"),
                    'games' => $this->contentModel->count("tipo = 'juego' AND estado = 'activo'"),
                    'total_size' => $this->contentModel->getTotalSize()
                ],
                'packages' => [
                    'total' => $this->packageModel->count(),
                    'ready' => $this->packageModel->count("estado = 'listo'"),
                    'generating' => $this->packageModel->count("estado = 'generando'"),
                    'installed' => $this->packageModel->count("estado = 'instalado'")
                ],
                'revenue' => [
                    'monthly_total' => $this->companyModel->getTotalRevenue(),
                    'annual_projected' => $this->companyModel->getTotalRevenue() * 12
                ]
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo estadísticas principales: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener actividad reciente del sistema
     */
    public function getRecentActivity($limit = 15) {
        try {
            $sql = "
                SELECT 
                    l.accion,
                    l.tabla_afectada,
                    l.registro_id,
                    l.created_at,
                    u.username,
                    u.nombre_completo,
                    CASE 
                        WHEN l.tabla_afectada = 'empresas' THEN (SELECT nombre FROM empresas WHERE id = l.registro_id)
                        WHEN l.tabla_afectada = 'contenido' THEN (SELECT titulo FROM contenido WHERE id = l.registro_id)
                        ELSE NULL
                    END as record_name
                FROM logs_sistema l
                LEFT JOIN usuarios u ON l.usuario_id = u.id
                WHERE l.accion NOT IN ('login_success', 'logout', 'notification_queued')
                ORDER BY l.created_at DESC
                LIMIT ?
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear actividades para mostrar
            $formattedActivities = [];
            foreach ($activities as $activity) {
                $formattedActivities[] = [
                    'action' => $this->formatActivityAction($activity['accion']),
                    'table' => $activity['tabla_afectada'],
                    'record_name' => $activity['record_name'],
                    'user' => $activity['nombre_completo'] ?? $activity['username'] ?? 'Sistema',
                    'timestamp' => $activity['created_at'],
                    'time_ago' => $this->timeAgo($activity['created_at']),
                    'icon' => $this->getActivityIcon($activity['accion']),
                    'color' => $this->getActivityColor($activity['accion'])
                ];
            }
            
            return $formattedActivities;
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo actividad reciente: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener alertas del sistema
     */
    public function getSystemAlerts() {
        try {
            $alerts = [];
            
            // Verificar empresas próximas a vencer
            $expiringCompanies = $this->companyModel->getExpiringCompanies();
            if (!empty($expiringCompanies)) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title' => 'Licencias próximas a vencer',
                    'message' => count($expiringCompanies) . ' empresas tienen licencias que vencen pronto',
                    'action_url' => 'views/companies/index.php?filter=expiring',
                    'count' => count($expiringCompanies)
                ];
            }
            
            // Verificar espacio en disco
            $totalSpace = disk_total_space(ROOT_PATH);
            $freeSpace = disk_free_space(ROOT_PATH);
            
            if ($totalSpace && $freeSpace) {
                $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;
                
                if ($usedPercentage > 90) {
                    $alerts[] = [
                        'type' => 'danger',
                        'icon' => 'fas fa-hdd',
                        'title' => 'Poco espacio en disco',
                        'message' => 'Espacio usado: ' . number_format($usedPercentage, 1) . '% (' . $this->formatFileSize($freeSpace) . ' disponibles)',
                        'action_url' => 'views/content/index.php',
                        'count' => null
                    ];
                } elseif ($usedPercentage > 80) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => 'fas fa-hdd',
                        'title' => 'Espacio en disco limitado',
                        'message' => 'Espacio usado: ' . number_format($usedPercentage, 1) . '% (' . $this->formatFileSize($freeSpace) . ' disponibles)',
                        'action_url' => 'views/content/index.php',
                        'count' => null
                    ];
                }
            }
            
            // Verificar paquetes en generación
            $generatingPackages = $this->packageModel->count("estado = 'generando'");
            if ($generatingPackages > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-cogs',
                    'title' => 'Paquetes en generación',
                    'message' => $generatingPackages . ' paquetes se están generando actualmente',
                    'action_url' => 'views/packages/index.php',
                    'count' => $generatingPackages
                ];
            }
            
            // Verificar contenido sin procesar
            $processingContent = $this->contentModel->count("estado = 'procesando'");
            if ($processingContent > 0) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => 'fas fa-clock',
                    'title' => 'Contenido en procesamiento',
                    'message' => $processingContent . ' elementos de contenido están siendo procesados',
                    'action_url' => 'views/content/index.php?filter=processing',
                    'count' => $processingContent
                ];
            }
            
            return $alerts;
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo alertas del sistema: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener datos para gráficos
     */
    public function getChartData() {
        try {
            $chartData = [
                'companies_by_package' => $this->companyModel->getPackageStats(),
                'content_by_type' => $this->contentModel->getContentStats(),
                'revenue_trend' => $this->getRevenueTrend(),
                'activity_trend' => $this->getActivityTrend()
            ];
            
            return $chartData;
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo datos de gráficos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * API para actualizar estadísticas en tiempo real
     */
    public function getStatsAPI() {
        try {
            $this->requireAuth();
            
            if (!$this->isAjax()) {
                $this->jsonResponse(['error' => 'Solo peticiones AJAX'], 400);
            }
            
            $stats = $this->getMainStats();
            $this->jsonResponse(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            $this->logError("Error en getStatsAPI: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error obteniendo estadísticas'], 500);
        }
    }
    
    /**
     * Obtener tendencia de ingresos (últimos 12 meses)
     */
    private function getRevenueTrend() {
        try {
            $sql = "
                SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    SUM(costo_mensual) as revenue
                FROM empresas 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                AND estado = 'activo'
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month
            ";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo tendencia de ingresos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener tendencia de actividad (últimos 30 días)
     */
    private function getActivityTrend() {
        try {
            $sql = "
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as activity_count
                FROM logs_sistema 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logError("Error obteniendo tendencia de actividad: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Formatear acción de actividad para mostrar
     */
    private function formatActivityAction($action) {
        $actions = [
            'create' => 'Creó',
            'update' => 'Actualizó',
            'delete' => 'Eliminó',
            'login_success' => 'Inició sesión',
            'logout' => 'Cerró sesión',
            'password_changed' => 'Cambió contraseña',
            'upload' => 'Subió archivo',
            'generate_package' => 'Generó paquete'
        ];
        
        return $actions[$action] ?? ucfirst($action);
    }
    
    /**
     * Obtener icono para actividad
     */
    private function getActivityIcon($action) {
        $icons = [
            'create' => 'fas fa-plus-circle',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'upload' => 'fas fa-upload',
            'generate_package' => 'fas fa-box'
        ];
        
        return $icons[$action] ?? 'fas fa-circle';
    }
    
    /**
     * Obtener color para actividad
     */
    private function getActivityColor($action) {
        $colors = [
            'create' => 'success',
            'update' => 'warning',
            'delete' => 'danger',
            'upload' => 'info',
            'generate_package' => 'primary'
        ];
        
        return $colors[$action] ?? 'secondary';
    }
    
    /**
     * Calcular tiempo transcurrido
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'hace menos de 1 minuto';
        if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
        if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
        if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
        if ($time < 31104000) return 'hace ' . floor($time/2592000) . ' meses';
        
        return 'hace más de 1 año';
    }
}
?>