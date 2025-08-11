<?php

/**
 * Modelo de Empresas PLAYMI
 * Maneja toda la lógica relacionada con las empresas clientes
 */

require_once 'BaseModel.php';

class Company extends BaseModel
{
    protected $table = 'empresas';

    /**
     * Obtener empresas activas
     */
    public function getActiveCompanies()
    {
        try {
            $sql = "SELECT * FROM empresas WHERE estado = ? ORDER BY nombre";
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
                    FROM empresas 
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
     * Obtener total de ingresos mensuales
     */
    public function getTotalRevenue()
    {
        try {
            $sql = "SELECT SUM(costo_mensual) as total FROM empresas WHERE estado = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([STATUS_ACTIVE]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return floatval($result['total'] ?? 0);
        } catch (Exception $e) {
            $this->logError("Error en getTotalRevenue: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas por tipo de paquete
     */
    public function getPackageStats()
    {
        try {
            $sql = "SELECT 
                        tipo_paquete,
                        COUNT(*) as cantidad,
                        SUM(costo_mensual) as ingresos
                    FROM empresas 
                    WHERE estado = ? 
                    GROUP BY tipo_paquete";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([STATUS_ACTIVE]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getPackageStats: " . $e->getMessage());
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
                $whereConditions[] = "(nombre LIKE ? OR email_contacto LIKE ? OR persona_contacto LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
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
            $countSql = "SELECT COUNT(*) as total FROM empresas $whereClause";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener registros paginados
            $offset = ($page - 1) * $limit;
            $sql = "SELECT *, DATEDIFF(fecha_vencimiento, CURDATE()) as dias_restantes 
                    FROM empresas 
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
            $sql = "SELECT id FROM empresas WHERE email_contacto = ?";
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
            $sql = "SELECT id FROM empresas WHERE nombre = ?";
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
            $company = $this->findById($companyId);
            if (!$company) {
                return ['error' => 'Empresa no encontrada'];
            }

            // Calcular nueva fecha de vencimiento
            $currentExpiry = $company['fecha_vencimiento'];
            $newExpiry = date('Y-m-d', strtotime($currentExpiry . " +$months months"));

            $result = $this->update($companyId, [
                'fecha_vencimiento' => $newExpiry,
                'estado' => STATUS_ACTIVE
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => "Licencia extendida hasta $newExpiry",
                    'new_expiry' => $newExpiry
                ];
            }

            return ['error' => 'Error al extender la licencia'];
        } catch (Exception $e) {
            $this->logError("Error en extendLicense: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
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
}
