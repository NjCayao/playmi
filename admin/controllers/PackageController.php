<?php
/**
 * Controlador de Paquetes
 * Maneja todas las operaciones relacionadas con paquetes de empresas
 * 
 * Módulos que gestiona:
 * - 2.3.1: Lista principal de paquetes
 * - 2.3.2: Generación de paquetes
 * - 2.3.3: Personalización de paquetes
 * - 2.3.4: Historial de paquetes
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Package.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Content.php';

class PackageController extends BaseController {
    private $packageModel;
    private $companyModel;
    private $contentModel;
    
    public function __construct() {
        parent::__construct();
        $this->packageModel = new Package();
        $this->companyModel = new Company();
        $this->contentModel = new Content();
    }
    
    /**
     * MÓDULO 2.3.1: Lista principal de paquetes
     * Obtiene todos los paquetes con filtros y estadísticas
     */
    public function index() {
        try {
            // $this->requireAuth(); // Temporalmente deshabilitado para pruebas
            
            // Obtener filtros
            $filters = [
                'company_id' => $_GET['company_id'] ?? null,
                'estado' => $_GET['estado'] ?? null,
                'page' => $_GET['page'] ?? 1
            ];
            
            // Obtener paquetes con filtros y paginación
            $page = max(1, (int)$filters['page']);
            $perPage = PAGINATION_LIMIT ?? 25;
            $offset = ($page - 1) * $perPage;
            
            // Construir consulta SQL con filtros
            $whereConditions = [];
            $params = [];
            
            if ($filters['company_id']) {
                $whereConditions[] = "p.empresa_id = ?";
                $params[] = $filters['company_id'];
            }
            
            if ($filters['estado']) {
                $whereConditions[] = "p.estado = ?";
                $params[] = $filters['estado'];
            }
            
            $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Obtener total para paginación
            $countSql = "SELECT COUNT(*) as total FROM paquetes_generados p" . $whereClause;
            $stmt = $this->packageModel->getDb()->prepare($countSql);
            $stmt->execute($params);
            $totalPackages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener paquetes
            $sql = "SELECT p.*, c.nombre as empresa_nombre, u.nombre as generado_por_nombre
                    FROM paquetes_generados p
                    LEFT JOIN companies c ON p.empresa_id = c.id
                    LEFT JOIN usuarios u ON p.generado_por = u.id" . 
                    $whereClause . " ORDER BY p.fecha_generacion DESC LIMIT ? OFFSET ?";
            
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->packageModel->getDb()->prepare($sql);
            $stmt->execute($params);
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener empresas para el filtro
            $companies = $this->companyModel->getActiveCompanies();
            
            // Obtener estadísticas
            $stats = $this->getPackageStats();
            
            // Configurar paginación
            $totalPages = ceil($totalPackages / $perPage);
            $pagination = [
                'total' => $totalPackages,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'previous_page' => max(1, $page - 1),
                'next_page' => min($totalPages, $page + 1)
            ];
            
            return [
                'packages' => $packages,
                'companies' => $companies,
                'filters' => $filters,
                'stats' => $stats,
                'pagination' => $pagination
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en PackageController::index - " . $e->getMessage());
            return [
                'packages' => [],
                'companies' => [],
                'filters' => [],
                'stats' => [],
                'pagination' => []
            ];
        }
    }
    
    /**
     * MÓDULO 2.3.2: Formulario para generar paquetes
     * Prepara datos para el wizard de generación
     */
    public function generate() {
        try {
            $this->requireAuth();
            
            // Obtener empresas activas
            $companies = $this->companyModel->getActiveCompanies();
            
            // Obtener contenido disponible por tipo
            $content = [
                'movies' => $this->contentModel->getMovies(true),
                'music' => $this->contentModel->getMusic(true),
                'games' => $this->contentModel->getGames(true)
            ];
            
            return [
                'companies' => $companies,
                'content' => $content
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en PackageController::generate - " . $e->getMessage());
            return [
                'companies' => [],
                'content' => []
            ];
        }
    }
    
    /**
     * MÓDULO 2.3.3: Personalizar contenido por empresa
     * Obtiene datos para personalización del portal
     */
    public function customize($companyId = null) {
        try {
            $this->requireAuth();
            
            // Obtener ID de GET si no se pasó como parámetro
            if (!$companyId) {
                $companyId = $_GET['company_id'] ?? null;
            }
            
            // Validar empresa
            if (!$companyId) {
                throw new Exception("ID de empresa requerido");
            }
            
            // Obtener datos de la empresa
            $company = $this->companyModel->findById($companyId);
            if (!$company) {
                throw new Exception("Empresa no encontrada");
            }
            
            // Obtener contenido disponible
            $content = [
                'movies' => $this->contentModel->getMovies(true),
                'music' => $this->contentModel->getMusic(true),
                'games' => $this->contentModel->getGames(true)
            ];
            
            // Obtener personalización actual (si existe)
            $customization = $this->getCompanyCustomization($companyId);
            
            return [
                'company' => $company,
                'content' => $content,
                'customization' => $customization
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en PackageController::customize - " . $e->getMessage());
            return [
                'company' => [],
                'content' => [],
                'customization' => []
            ];
        }
    }
    
    /**
     * MÓDULO 2.3.4: Historial de paquetes
     * Obtiene historial completo con timeline
     */
    public function history() {
        try {
            $this->requireAuth();
            
            // Obtener filtros
            $filters = [
                'company_id' => $_GET['company_id'] ?? null,
                'daterange' => $_GET['daterange'] ?? null,
                'version' => $_GET['version'] ?? null
            ];
            
            // Procesar rango de fechas si existe
            if ($filters['daterange']) {
                $dates = explode(' - ', $filters['daterange']);
                if (count($dates) == 2) {
                    $filters['start_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $dates[0])));
                    $filters['end_date'] = date('Y-m-d', strtotime(str_replace('/', '-', $dates[1])));
                }
            }
            
            // Construir consulta con filtros
            $whereConditions = [];
            $params = [];
            
            if ($filters['company_id']) {
                $whereConditions[] = "p.empresa_id = ?";
                $params[] = $filters['company_id'];
            }
            
            if (!empty($filters['start_date'])) {
                $whereConditions[] = "p.fecha_generacion >= ?";
                $params[] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $whereConditions[] = "p.fecha_generacion <= ?";
                $params[] = $filters['end_date'] . ' 23:59:59';
            }
            
            if ($filters['version']) {
                $whereConditions[] = "p.version_paquete = ?";
                $params[] = $filters['version'];
            }
            
            $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Obtener historial de paquetes
            $sql = "SELECT p.*, c.nombre as empresa_nombre, u.nombre as generado_por_nombre
                    FROM paquetes_generados p
                    LEFT JOIN companies c ON p.empresa_id = c.id
                    LEFT JOIN usuarios u ON p.generado_por = u.id" . 
                    $whereClause . " ORDER BY p.fecha_generacion DESC";
            
            $stmt = $this->packageModel->getDb()->prepare($sql);
            $stmt->execute($params);
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener empresas para filtro
            $companies = $this->companyModel->findAll();
            
            // Obtener estadísticas del historial
            $stats = $this->getHistoryStats($filters);
            
            // Agregar datos de actividad para el gráfico
            $stats['activity_data'] = $this->getActivityChartData();
            
            return [
                'packages' => $packages,
                'companies' => $companies,
                'filters' => $filters,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logError("Error en PackageController::history - " . $e->getMessage());
            return [
                'packages' => [],
                'companies' => [],
                'filters' => [],
                'stats' => []
            ];
        }
    }
    
    /**
     * Genera un nuevo paquete (llamado vía AJAX)
     */
    public function generatePackage() {
        try {
            // Este método será llamado desde la API
            // Los datos vienen del formulario POST
            $data = $_POST;
            
            // Validar datos requeridos
            $this->validatePackageData($data);
            
            // Crear registro del paquete
            $packageId = $this->packageModel->create($data);
            
            // Iniciar proceso de generación en background
            $this->startPackageGeneration($packageId, $data);
            
            return [
                'success' => true,
                'package_id' => $packageId,
                'message' => 'Paquete en proceso de generación'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Descarga un paquete
     */
    public function download($packageId) {
        try {
            // Obtener información del paquete
            $package = $this->packageModel->findById($packageId);
            
            if (!$package) {
                throw new Exception("Paquete no encontrado");
            }
            
            if ($package['estado'] !== 'listo') {
                throw new Exception("El paquete no está listo para descarga");
            }
            
            // Actualizar contador de descargas
            $this->packageModel->updateDownloadCount($packageId);
            
            // Devolver información para descarga
            return [
                'success' => true,
                'file_path' => $package['ruta_paquete'],
                'file_name' => $package['nombre_paquete'] . '.zip'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Actualiza el estado de un paquete
     */
    public function updateStatus($packageId, $newStatus) {
        try {
            // Validar estado
            $validStates = ['generando', 'listo', 'descargado', 'instalado', 'activo', 'error'];
            if (!in_array($newStatus, $validStates)) {
                throw new Exception("Estado inválido");
            }
            
            // Actualizar estado
            $this->packageModel->updateStatus($packageId, $newStatus);
            
            // Registrar en log
            $this->packageModel->createLog($packageId, [
                'action' => 'status_update',
                'description' => "Estado actualizado a: $newStatus"
            ]);
            
            return [
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene logs de un paquete
     */
    public function getLogs($packageId) {
        try {
            $logs = $this->packageModel->getLogs($packageId);
            
            // Formatear logs para el timeline
            $formattedLogs = [];
            foreach ($logs as $log) {
                $formattedLogs[] = [
                    'date' => date('d/m/Y', strtotime($log['fecha_log'])),
                    'time' => date('H:i:s', strtotime($log['fecha_log'])),
                    'datetime' => date('d/m/Y H:i:s', strtotime($log['fecha_log'])),
                    'action' => $log['accion'],
                    'description' => $log['descripcion'],
                    'icon' => $this->getLogIcon($log['tipo']),
                    'color' => $this->getLogColor($log['tipo'])
                ];
            }
            
            return [
                'success' => true,
                'logs' => $formattedLogs
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener la conexión a la base de datos del modelo
     * (Para compatibilidad con BaseModel)
     */
    protected function getDb() {
        return $this->db;
    }
    
    /**
     * Métodos privados auxiliares
     */
    
    private function getPackageStats() {
        try {
            $db = $this->packageModel->getDb();
            
            // Total de paquetes
            $stmt = $db->query("SELECT COUNT(*) as total FROM paquetes_generados");
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Por estado
            $stmt = $db->query("SELECT estado, COUNT(*) as count FROM paquetes_generados GROUP BY estado");
            $estados = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            return [
                'total' => $total ?? 0,
                'ready' => $estados['listo'] ?? 0,
                'generating' => $estados['generando'] ?? 0,
                'installed' => $estados['instalado'] ?? 0,
                'downloaded' => $estados['descargado'] ?? 0,
                'expired' => $estados['vencido'] ?? 0
            ];
        } catch (Exception $e) {
            $this->logError("Error en getPackageStats: " . $e->getMessage());
            return [
                'total' => 0,
                'ready' => 0,
                'generating' => 0,
                'installed' => 0,
                'downloaded' => 0,
                'expired' => 0
            ];
        }
    }
    
    private function getHistoryStats($filters = []) {
        try {
            $db = $this->packageModel->getDb();
            
            // Total histórico
            $stmt = $db->query("SELECT COUNT(*) as total FROM paquetes_generados");
            $totalPackages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Empresas con paquetes
            $stmt = $db->query("SELECT COUNT(DISTINCT empresa_id) as total FROM paquetes_generados");
            $companiesWithPackages = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Tamaño promedio
            $stmt = $db->query("SELECT AVG(tamanio_paquete) as avg_size FROM paquetes_generados WHERE tamanio_paquete > 0");
            $avgSize = $stmt->fetch(PDO::FETCH_ASSOC)['avg_size'];
            
            // Paquetes este mes
            $stmt = $db->query("SELECT COUNT(*) as total FROM paquetes_generados WHERE MONTH(fecha_generacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_generacion) = YEAR(CURRENT_DATE())");
            $thisMonth = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'total_packages' => $totalPackages,
                'companies_with_packages' => $companiesWithPackages,
                'avg_package_size' => round($avgSize / 1024 / 1024, 2), // En MB
                'packages_this_month' => $thisMonth
            ];
        } catch (Exception $e) {
            $this->logError("Error en getHistoryStats: " . $e->getMessage());
            return [
                'total_packages' => 0,
                'companies_with_packages' => 0,
                'avg_package_size' => 0,
                'packages_this_month' => 0
            ];
        }
    }
    
    private function getCompanyCustomization($companyId) {
        try {
            // Por ahora retornar valores por defecto
            // TODO: Implementar tabla de personalizaciones
            return [
                'service_name' => null,
                'primary_color' => null,
                'secondary_color' => null,
                'welcome_message' => null,
                'use_company_logo' => true,
                'dark_mode' => true,
                'featured_content' => [],
                'category_order' => []
            ];
        } catch (Exception $e) {
            $this->logError("Error en getCompanyCustomization: " . $e->getMessage());
            return [];
        }
    }
    
    private function validatePackageData($data) {
        $required = ['empresa_id', 'nombre_paquete', 'wifi_ssid', 'wifi_password'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Campo requerido: $field");
            }
        }
        
        if (strlen($data['wifi_password']) < 8) {
            throw new Exception("La contraseña WiFi debe tener al menos 8 caracteres");
        }
        
        if (empty($data['content_ids']) || !is_array($data['content_ids'])) {
            throw new Exception("Debe seleccionar al menos un elemento de contenido");
        }
    }
    
    private function startPackageGeneration($packageId, $data) {
        // En producción, esto iniciaría un job en background
        // Por ahora, simularemos que se inicia el proceso
        $this->packageModel->createLog($packageId, [
            'action' => 'generation_start',
            'description' => 'Iniciando generación del paquete'
        ]);
    }
    
    private function getActivityChartData() {
        try {
            $db = $this->packageModel->getDb();
            $months = [];
            $data = [];
            
            // Obtener datos de los últimos 6 meses
            for ($i = 5; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $monthName = date('F', strtotime("-$i months"));
                $months[] = $monthName;
                
                // Obtener cantidad de paquetes generados en ese mes
                $sql = "SELECT COUNT(*) as count FROM paquetes_generados WHERE DATE_FORMAT(fecha_generacion, '%Y-%m') = ?";
                $stmt = $db->prepare($sql);
                $stmt->execute([$date]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $data[] = $result['count'];
            }
            
            return [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'Paquetes Generados',
                        'data' => $data,
                        'borderColor' => '#2563eb',
                        'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                        'tension' => 0.4
                    ]
                ]
            ];
        } catch (Exception $e) {
            $this->logError("Error en getActivityChartData: " . $e->getMessage());
            return [
                'labels' => [],
                'datasets' => []
            ];
        }
    }
    
    private function getLogIcon($type) {
        $icons = [
            'info' => 'info-circle',
            'success' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'times-circle',
            'action' => 'cog'
        ];
        
        return $icons[$type] ?? 'circle';
    }
    
    private function getLogColor($type) {
        $colors = [
            'info' => 'blue',
            'success' => 'green',
            'warning' => 'yellow',
            'error' => 'red',
            'action' => 'gray'
        ];
        
        return $colors[$type] ?? 'gray';
    }
}
?>