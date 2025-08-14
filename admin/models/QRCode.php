
<?php
/**
 * Modelo de Códigos QR PLAYMI
 * Maneja la generación y gestión de QR para buses
 */
require_once 'BaseModel.php';
class QRCode extends BaseModel {
protected $table = 'qr_codes';
/**
 * Obtener QR codes con filtros
 */
public function getWithFilters($filters = [], $limit = null, $offset = null) {
    try {
        $sql = "SELECT q.*, c.nombre as empresa_nombre,
                (SELECT COUNT(*) FROM qr_scans WHERE qr_id = q.id) as total_escaneos
                FROM qr_codes q
                LEFT JOIN companies c ON q.empresa_id = c.id
                WHERE 1=1";
        $params = [];
        
        // Aplicar filtros
        if (!empty($filters['company_id'])) {
            $sql .= " AND q.empresa_id = :company_id";
            $params[':company_id'] = $filters['company_id'];
        }
        
        if (!empty($filters['bus_number'])) {
            $sql .= " AND q.numero_bus LIKE :bus_number";
            $params[':bus_number'] = '%' . $filters['bus_number'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND q.estado = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Ordenar por fecha de creación descendente
        $sql .= " ORDER BY q.created_at DESC";
        
        // Aplicar límite y offset
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params[':limit'] = (int)$limit;
            
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
                $params[':offset'] = (int)$offset;
            }
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind de parámetros
        foreach ($params as $key => $value) {
            if ($key === ':limit' || $key === ':offset') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $this->logError("Error en getWithFilters: " . $e->getMessage());
        return [];
    }
}

/**
 * Contar QR codes con filtros
 */
public function countWithFilters($filters = []) {
    try {
        $sql = "SELECT COUNT(*) as total FROM qr_codes q WHERE 1=1";
        $params = [];
        
        if (!empty($filters['company_id'])) {
            $sql .= " AND q.empresa_id = :company_id";
            $params[':company_id'] = $filters['company_id'];
        }
        
        if (!empty($filters['bus_number'])) {
            $sql .= " AND q.numero_bus LIKE :bus_number";
            $params[':bus_number'] = '%' . $filters['bus_number'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND q.estado = :status";
            $params[':status'] = $filters['status'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total'] ?? 0);
        
    } catch (Exception $e) {
        $this->logError("Error en countWithFilters: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtener estadísticas generales
 */
public function getStats() {
    try {
        $stats = [];
        
        // Total de QR codes
        $sql = "SELECT COUNT(*) as total FROM qr_codes";
        $stmt = $this->db->query($sql);
        $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // QR activos
        $sql = "SELECT COUNT(*) as active FROM qr_codes WHERE estado = 'activo'";
        $stmt = $this->db->query($sql);
        $stats['active'] = $stmt->fetch(PDO::FETCH_ASSOC)['active'];
        
        // QR inactivos
        $sql = "SELECT COUNT(*) as inactive FROM qr_codes WHERE estado = 'inactivo'";
        $stmt = $this->db->query($sql);
        $stats['inactive'] = $stmt->fetch(PDO::FETCH_ASSOC)['inactive'];
        
        return $stats;
        
    } catch (Exception $e) {
        $this->logError("Error en getStats: " . $e->getMessage());
        return [
            'total' => 0,
            'active' => 0,
            'inactive' => 0
        ];
    }
}

/**
 * Contar empresas activas con QR
 */
public function countActiveCompanies() {
    try {
        $sql = "SELECT COUNT(DISTINCT empresa_id) as total 
                FROM qr_codes 
                WHERE estado = 'activo'";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        $this->logError("Error en countActiveCompanies: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtener escaneos de hoy
 */
public function getTodayScans() {
    try {
        $sql = "SELECT COUNT(*) as total 
                FROM qr_scans 
                WHERE DATE(scan_date) = CURDATE()";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        // La tabla qr_scans puede no existir aún
        return 0;
    }
}

/**
 * Obtener escaneos de la semana
 */
public function getWeekScans() {
    try {
        $sql = "SELECT COUNT(*) as total 
                FROM qr_scans 
                WHERE scan_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Obtener QR más escaneados
 */
public function getMostScanned($limit = 5) {
    try {
        $sql = "SELECT q.*, c.nombre as empresa_nombre,
                COUNT(s.id) as scan_count
                FROM qr_codes q
                LEFT JOIN companies c ON q.empresa_id = c.id
                LEFT JOIN qr_scans s ON q.id = s.qr_id
                GROUP BY q.id
                ORDER BY scan_count DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Obtener último número de bus usado por empresa
 */
public function getLastBusNumber($empresaId) {
    try {
        $sql = "SELECT numero_bus 
                FROM qr_codes 
                WHERE empresa_id = :empresa_id 
                AND numero_bus REGEXP '^BUS-[0-9]+$'
                ORDER BY CAST(SUBSTRING(numero_bus, 5) AS UNSIGNED) DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':empresa_id' => $empresaId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && preg_match('/BUS-(\d+)/', $result['numero_bus'], $matches)) {
            return (int)$matches[1];
        }
        
        return 0;
        
    } catch (Exception $e) {
        $this->logError("Error en getLastBusNumber: " . $e->getMessage());
        return 0;
    }
}

/**
 * Actualizar estado de QR
 */
public function updateStatus($qrId, $newStatus) {
    try {
        $validStatuses = ['activo', 'inactivo'];
        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }
        
        return $this->update($qrId, ['estado' => $newStatus]);
        
    } catch (Exception $e) {
        $this->logError("Error en updateStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Incrementar contador de descargas
 */
public function incrementDownloadCount($qrId) {
    try {
        $sql = "UPDATE qr_codes 
                SET descargas_count = descargas_count + 1 
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $qrId]);
    } catch (Exception $e) {
        $this->logError("Error en incrementDownloadCount: " . $e->getMessage());
        return false;
    }
}

/**
 * Registrar escaneo de QR
 */
public function registerScan($qrId, $deviceInfo = []) {
    try {
        $sql = "INSERT INTO qr_scans (qr_id, scan_date, device_info, ip_address) 
                VALUES (:qr_id, NOW(), :device_info, :ip_address)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':qr_id' => $qrId,
            ':device_info' => json_encode($deviceInfo),
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        
    } catch (Exception $e) {
        // La tabla qr_scans puede no existir aún
        return false;
    }
}

/**
 * Obtener QR por empresa
 */
public function getByCompany($empresaId, $activeOnly = true) {
    try {
        $sql = "SELECT * FROM qr_codes WHERE empresa_id = :empresa_id";
        $params = [':empresa_id' => $empresaId];
        
        if ($activeOnly) {
            $sql .= " AND estado = 'activo'";
        }
        
        $sql .= " ORDER BY numero_bus";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $this->logError("Error en getByCompany: " . $e->getMessage());
        return [];
    }
}

/**
 * Verificar si existe QR para un bus
 */
public function existsForBus($empresaId, $numeroBus, $excludeId = null) {
    try {
        $sql = "SELECT id FROM qr_codes 
                WHERE empresa_id = :empresa_id 
                AND numero_bus = :numero_bus";
        $params = [
            ':empresa_id' => $empresaId,
            ':numero_bus' => $numeroBus
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
        
    } catch (Exception $e) {
        $this->logError("Error en existsForBus: " . $e->getMessage());
        return true; // Por seguridad, asumir que existe
    }
}

/**
 * Obtener historial de escaneos de un QR
 */
public function getScanHistory($qrId, $limit = 100) {
    try {
        $sql = "SELECT * FROM qr_scans 
                WHERE qr_id = :qr_id 
                ORDER BY scan_date DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':qr_id', $qrId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        return [];
    }
}
}
?>