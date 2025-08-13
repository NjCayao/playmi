<?php

/**
 * Modelo de Empresas PLAYMI
 * Maneja toda la lógica relacionada con las empresas clientes
 */

require_once 'BaseModel.php';

class Company extends BaseModel
{
    protected $table = 'companies'; // Usar tabla companies

    /**
     * Obtener empresas activas
     */
    public function getActiveCompanies()
    {
        try {
            $sql = "SELECT * FROM companies WHERE estado = ? ORDER BY nombre";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([STATUS_ACTIVE]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getActiveCompanies: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener empresas próximas a vencer
     */
    public function getExpiringCompanies($days = WARNING_DAYS_BEFORE_EXPIRY)
    {
        try {
            $sql = "SELECT *, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes 
                    FROM companies 
                    WHERE fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY) 
                    AND estado = ? 
                    ORDER BY fecha_vencimiento ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days, STATUS_ACTIVE]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getExpiringCompanies: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar empresa por nombre (para validar duplicados)
     */
    public function findByName($nombre, $excludeId = null)
    {
        try {
            $sql = "SELECT * FROM companies WHERE nombre = :nombre";
            $params = [':nombre' => $nombre];

            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = (int)$excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding company by name: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar empresa por email (para validar duplicados)
     */
    public function findByEmail($email, $excludeId = null)
    {
        try {
            $sql = "SELECT * FROM companies WHERE email_contacto = :email";
            $params = [':email' => $email];

            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = (int)$excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding company by email: " . $e->getMessage());
            return false;
        }
    }


    /**
     * Buscar empresa por RUC (para validar duplicados)
     */
    public function findByRuc($ruc, $excludeId = null)
    {
        try {
            $sql = "SELECT * FROM companies WHERE ruc = :ruc";
            $params = [':ruc' => $ruc];

            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = (int)$excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding company by RUC: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el RUC ya existe
     */
    public function rucExists($ruc, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM companies WHERE ruc = ?";
            $params = [$ruc];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            $this->logError("Error en rucExists: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Obtener ingresos totales mensuales
     */
    public function getTotalRevenue()
    {
        try {
            $sql = "SELECT SUM(costo_mensual) as total 
                FROM companies 
                WHERE estado = 'activo'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error getting total revenue: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas de empresas por estado
     */
    public function getStatusStats()
    {
        try {
            $sql = "SELECT 
                    estado,
                    COUNT(*) as total,
                    SUM(costo_mensual) as revenue
                FROM companies 
                GROUP BY estado";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stats = [
                'activo' => ['total' => 0, 'revenue' => 0],
                'suspendido' => ['total' => 0, 'revenue' => 0],
                'vencido' => ['total' => 0, 'revenue' => 0]
            ];

            foreach ($results as $result) {
                $stats[$result['estado']] = [
                    'total' => (int)$result['total'],
                    'revenue' => (float)$result['revenue']
                ];
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Error getting status stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar empresas con filtros
     */
    public function searchCompanies($filters = [], $page = 1, $limit = RECORDS_PER_PAGE)
    {
        try {
            $whereConditions = [];
            $params = [];

            // Filtro por nombre
            if (!empty($filters['search'])) {
                $whereConditions[] = "(nombre LIKE ? OR email_contacto LIKE ? OR persona_contacto LIKE ? OR ruc LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            // Filtro por estado
            if (!empty($filters['estado'])) {
                $whereConditions[] = "estado = ?";
                $params[] = $filters['estado'];
            }

            // Filtro por tipo de paquete
            if (!empty($filters['tipo_paquete'])) {
                $whereConditions[] = "tipo_paquete = ?";
                $params[] = $filters['tipo_paquete'];
            }

            // Filtro por próximas a vencer
            if (!empty($filters['proximas_vencer'])) {
                $whereConditions[] = "fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $params[] = WARNING_DAYS_BEFORE_EXPIRY;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Contar total de registros
            $countSql = "SELECT COUNT(*) as total FROM companies $whereClause";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener registros paginados
            $offset = ($page - 1) * $limit;
            $sql = "SELECT *, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes 
                    FROM companies 
                    $whereClause 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $companies,
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalRecords / $limit)
            ];
        } catch (Exception $e) {
            $this->logError("Error en searchCompanies: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'page' => 1,
                'limit' => $limit,
                'pages' => 0
            ];
        }
    }

    /**
     * Verificar si el email ya existe
     */
    public function emailExists($email, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM companies WHERE email_contacto = ?";
            $params = [$email];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            $this->logError("Error en emailExists: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Verificar si el nombre ya existe
     */
    public function nameExists($name, $excludeId = null)
    {
        try {
            $sql = "SELECT id FROM companies WHERE nombre = ?";
            $params = [$name];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            $this->logError("Error en nameExists: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Actualizar estado de empresa
     */
    public function updateStatus($companyId, $newStatus)
    {
        try {
            $validStatuses = [STATUS_ACTIVE, STATUS_SUSPENDED, STATUS_EXPIRED];
            if (!in_array($newStatus, $validStatuses)) {
                return ['error' => 'Estado no válido'];
            }

            $result = $this->update($companyId, ['estado' => $newStatus]);

            if ($result) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            }

            return ['error' => 'Error al actualizar el estado'];
        } catch (Exception $e) {
            $this->logError("Error en updateStatus: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }

    /**
     * Extender licencia de empresa
     */
    public function extendLicense($companyId, $months)
    {
        try {
            // Obtener fecha actual de vencimiento
            $company = $this->findById($companyId);
            if (!$company) {
                return false;
            }

            $currentExpiry = new DateTime($company['fecha_vencimiento']);
            $today = new DateTime();

            // Si ya está vencida, extender desde hoy, sino desde la fecha actual
            $baseDate = $currentExpiry > $today ? $currentExpiry : $today;
            $baseDate->add(new DateInterval('P' . $months . 'M'));

            $updateData = [
                'fecha_vencimiento' => $baseDate->format('Y-m-d'),
                'estado' => 'activo', // Reactivar si estaba vencida
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->update($companyId, $updateData);
        } catch (Exception $e) {
            error_log("Error extending license: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Buscar empresas con filtros Y PAGINACIÓN
     */
    public function findWithFilters($filters = [], $limit = null, $offset = null)
    {
        try {
            $sql = "SELECT c.*, 
                DATEDIFF(c.fecha_vencimiento, CURDATE()) as dias_restantes
                FROM companies c WHERE 1=1";
            $params = [];

            // Filtros (igual que antes)
            if (!empty($filters['search'])) {
                $sql .= " AND (c.nombre LIKE :search OR c.email_contacto LIKE :search OR c.ruc LIKE :search2)";
                $params[':search'] = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
            }

            if (!empty($filters['estado'])) {
                $sql .= " AND c.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }

            if (!empty($filters['tipo_paquete'])) {
                $sql .= " AND c.tipo_paquete = :tipo_paquete";
                $params[':tipo_paquete'] = $filters['tipo_paquete'];
            }

            if (!empty($filters['proximas_vencer'])) {
                $sql .= " AND c.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                $sql .= " AND c.fecha_vencimiento >= CURDATE()";
            }

            // Ordenamiento (más nuevos primero)
            $sql .= " ORDER BY c.id DESC";

            // PAGINACIÓN
            if ($limit !== null) {
                $sql .= " LIMIT :limit";
                $params[':limit'] = (int)$limit;

                if ($offset !== null) {
                    $sql .= " OFFSET :offset";
                    $params[':offset'] = (int)$offset;
                }
            }

            $stmt = $this->db->prepare($sql);

            // Bind parámetros
            foreach ($params as $key => $value) {
                if ($key === ':limit' || $key === ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }

            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding companies with filters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de paquetes
     */
    public function getPackageStats()
    {
        try {
            $sql = "SELECT 
                tipo_paquete,
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos
            FROM companies 
            GROUP BY tipo_paquete";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear para gráficos
            $stats = [
                'labels' => [],
                'totals' => [],
                'activos' => []
            ];

            $packageNames = [
                'basico' => 'Básico',
                'intermedio' => 'Intermedio',
                'premium' => 'Premium'
            ];

            foreach ($results as $result) {
                $stats['labels'][] = $packageNames[$result['tipo_paquete']] ?? $result['tipo_paquete'];
                $stats['totals'][] = (int)$result['total'];
                $stats['activos'][] = (int)$result['activos'];
            }

            return $stats;
        } catch (Exception $e) {
            $this->logError("Error en getPackageStats: " . $e->getMessage());
            return [
                'labels' => ['Básico', 'Intermedio', 'Premium'],
                'totals' => [0, 0, 0],
                'activos' => [0, 0, 0]
            ];
        }
    }

    /**
     * Obtener crecimiento mensual
     */
    public function getMonthlyGrowth($months = 6)
    {
        try {
            $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as mes,
                COUNT(*) as nuevas_empresas
            FROM companies 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :months MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY mes ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':months', $months, PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear para gráficos
            $stats = [
                'labels' => [],
                'data' => []
            ];

            $monthNames = [
                '01' => 'Ene',
                '02' => 'Feb',
                '03' => 'Mar',
                '04' => 'Abr',
                '05' => 'May',
                '06' => 'Jun',
                '07' => 'Jul',
                '08' => 'Ago',
                '09' => 'Sep',
                '10' => 'Oct',
                '11' => 'Nov',
                '12' => 'Dic'
            ];

            foreach ($results as $result) {
                $parts = explode('-', $result['mes']);
                $monthNum = $parts[1];
                $year = $parts[0];

                $stats['labels'][] = $monthNames[$monthNum] . ' ' . $year;
                $stats['data'][] = (int)$result['nuevas_empresas'];
            }

            return $stats;
        } catch (Exception $e) {
            $this->logError("Error en getMonthlyGrowth: " . $e->getMessage());
            return [
                'labels' => [],
                'data' => []
            ];
        }
    }

    /**
     * Obtener ingresos por tipo de paquete
     */
    public function getRevenueByPackage()
    {
        try {
            $sql = "SELECT 
                tipo_paquete,
                COUNT(*) as cantidad,
                SUM(costo_mensual) as ingresos_mensuales
            FROM companies 
            WHERE estado = 'activo'
            GROUP BY tipo_paquete";

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Formatear para gráficos
            $stats = [
                'labels' => [],
                'data' => []
            ];

            $packageNames = [
                'basico' => 'Básico',
                'intermedio' => 'Intermedio',
                'premium' => 'Premium'
            ];

            foreach ($results as $result) {
                $stats['labels'][] = $packageNames[$result['tipo_paquete']] ?? $result['tipo_paquete'];
                $stats['data'][] = (float)$result['ingresos_mensuales'];
            }

            return $stats;
        } catch (Exception $e) {
            $this->logError("Error en getRevenueByPackage: " . $e->getMessage());
            return [
                'labels' => [],
                'data' => []
            ];
        }
    }

    /**
     * Obtener estadísticas generales para dashboard
     */
    public function getDashboardStats()
    {
        try {
            $stats = [];

            // Total de empresas
            $stats['total_companies'] = $this->count();

            // Empresas activas
            $stats['active_companies'] = $this->count("estado = 'activo'");

            // Empresas suspendidas
            $stats['suspended_companies'] = $this->count("estado = 'suspendido'");

            // Empresas vencidas
            $stats['expired_companies'] = $this->count("estado = 'vencido'");

            // Ingresos mensuales totales
            $sql = "SELECT SUM(costo_mensual) as total FROM companies WHERE estado = 'activo'";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['monthly_revenue'] = (float)($result['total'] ?? 0);

            // Empresas próximas a vencer (30 días)
            $sql = "SELECT COUNT(*) as total FROM companies 
                WHERE estado = 'activo' 
                AND fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['expiring_soon'] = (int)($result['total'] ?? 0);

            // Total de buses
            $sql = "SELECT SUM(total_buses) as total FROM companies WHERE estado = 'activo'";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_buses'] = (int)($result['total'] ?? 0);

            return $stats;
        } catch (Exception $e) {
            $this->logError("Error en getDashboardStats: " . $e->getMessage());
            return [
                'total_companies' => 0,
                'active_companies' => 0,
                'suspended_companies' => 0,
                'expired_companies' => 0,
                'monthly_revenue' => 0,
                'expiring_soon' => 0,
                'total_buses' => 0
            ];
        }
    }


    /**
     * Contar empresas con filtros
     */
    public function countWithFilters($filters = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM companies c WHERE 1=1";
            $params = [];

            // Filtro por búsqueda
            if (!empty($filters['search'])) {
                $sql .= " AND (c.nombre LIKE :search OR c.email_contacto LIKE :search2 OR c.ruc LIKE :search3)";
                $params[':search'] = '%' . $filters['search'] . '%';
                $params[':search2'] = '%' . $filters['search'] . '%';
                $params[':search3'] = '%' . $filters['search'] . '%';
            }

            // Filtro por estado
            if (!empty($filters['estado'])) {
                $sql .= " AND c.estado = :estado";
                $params[':estado'] = $filters['estado'];
            }

            // Filtro por tipo de paquete
            if (!empty($filters['tipo_paquete'])) {
                $sql .= " AND c.tipo_paquete = :tipo_paquete";
                $params[':tipo_paquete'] = $filters['tipo_paquete'];
            }

            // Filtro por próximas a vencer
            if (!empty($filters['proximas_vencer'])) {
                $sql .= " AND c.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
                $sql .= " AND c.fecha_vencimiento >= CURDATE()";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting companies with filters: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar total de empresas
     */
    public function count($condition = '')
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM companies";
            if (!empty($condition)) {
                $sql .= " WHERE " . $condition;
            }

            error_log("🔍 DEBUG Count SQL: " . $sql);

            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $count = (int)($result['total'] ?? 0);
            error_log("🔍 DEBUG Count result: " . $count . " (condition: '$condition')");

            return $count;
        } catch (PDOException $e) {
            error_log("Error counting companies: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Contar empresas por estado
     */
    public function countByStatus($status)
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM companies WHERE estado = :estado";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':estado', $status);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Error counting companies by status: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener empresas por estado
     */
    public function getByStatus($status)
    {
        try {
            $sql = "SELECT * FROM companies WHERE estado = :status ORDER BY nombre ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting companies by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si el RUC ya existe en empresas activas
     */
    public function rucExistsActive($ruc, $excludeId = null)
    {
        try {
            $sql = "SELECT id, nombre, estado FROM companies WHERE ruc = ? AND estado = 'activo'";
            $params = [$ruc];

            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result !== false ? $result : null;
        } catch (Exception $e) {
            $this->logError("Error en rucExistsActive: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validar RUC peruano
     */
    public function validateRuc($ruc)
    {
        // RUC debe tener 11 dígitos
        if (!preg_match('/^[0-9]{11}$/', $ruc)) {
            return false;
        }

        // Los dos primeros dígitos deben ser 10, 15, 17 o 20
        $tipoEmpresa = substr($ruc, 0, 2);
        if (!in_array($tipoEmpresa, ['10', '15', '17', '20'])) {
            return false;
        }

        // Para validación básica, esto es suficiente
        return true;

        /* 
        COMENTADO: Validación estricta del dígito verificador para futuras implementaciones
        
        $suma = 0;
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

        for ($i = 0; $i < 10; $i++) {
            $suma += $ruc[$i] * $pesos[$i];
        }

        $resto = 11 - ($suma % 11);
        $digitoVerificador = $resto == 10 ? 0 : ($resto == 11 ? 1 : $resto);

        return $digitoVerificador == $ruc[10];
        */
    }
}
