<?php
/**
 * Modelo de Paquetes PLAYMI
 * Maneja la generación y gestión de paquetes para Pi
 */

require_once 'BaseModel.php';

class Package extends BaseModel {
    protected $table = 'paquetes_generados';
    
    /**
     * Obtener paquetes por empresa
     */
    public function getByCompany($companyId, $limit = null) {
        try {
            $sql = "SELECT p.*, e.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN empresas e ON p.empresa_id = e.id 
                    WHERE p.empresa_id = ? 
                    ORDER BY p.fecha_generacion DESC";
            
            if ($limit) {
                $sql .= " LIMIT $limit";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getByCompany: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener paquetes recientes
     */
    public function getRecent($limit = 10) {
        try {
            $sql = "SELECT p.*, e.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN empresas e ON p.empresa_id = e.id 
                    ORDER BY p.fecha_generacion DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getRecent: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de paquetes
     */
    public function getPackageStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_paquetes,
                        SUM(tamaño_paquete) as tamaño_total,
                        AVG(tamaño_paquete) as tamaño_promedio,
                        COUNT(CASE WHEN estado = 'listo' THEN 1 END) as listos,
                        COUNT(CASE WHEN estado = 'instalado' THEN 1 END) as instalados,
                        COUNT(CASE WHEN estado = 'generando' THEN 1 END) as generando
                    FROM paquetes_generados";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getPackageStats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Iniciar generación de paquete
     */
    public function startGeneration($companyId, $generatedBy, $packageData = []) {
        try {
            $data = [
                'empresa_id' => $companyId,
                'nombre_paquete' => $packageData['nombre'] ?? "Paquete_" . date('Y-m-d_H-i-s'),
                'version_paquete' => $packageData['version'] ?? '1.0',
                'generado_por' => $generatedBy,
                'estado' => 'generando',
                'clave_instalacion' => $this->generateInstallationKey()
            ];
            
            // Obtener datos de la empresa para calcular fecha de vencimiento
            require_once 'Company.php';
            $companyModel = new Company();
            $company = $companyModel->findById($companyId);
            
            if ($company) {
                $data['fecha_vencimiento_licencia'] = $company['fecha_vencimiento'];
            }
            
            $packageId = $this->create($data);
            
            if ($packageId) {
                return [
                    'success' => true,
                    'package_id' => $packageId,
                    'installation_key' => $data['clave_instalacion']
                ];
            }
            
            return ['error' => 'Error al iniciar la generación del paquete'];
            
        } catch(Exception $e) {
            $this->logError("Error en startGeneration: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Actualizar estado del paquete
     */
    public function updateStatus($packageId, $newStatus, $additionalData = []) {
        try {
            $validStatuses = ['generando', 'listo', 'descargado', 'instalado', 'vencido'];
            if (!in_array($newStatus, $validStatuses)) {
                return ['error' => 'Estado no válido'];
            }
            
            $updateData = array_merge(['estado' => $newStatus], $additionalData);
            $result = $this->update($packageId, $updateData);
            
            if ($result) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            }
            
            return ['error' => 'Error al actualizar el estado'];
            
        } catch(Exception $e) {
            $this->logError("Error en updateStatus: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Marcar paquete como completado
     */
    public function markAsComplete($packageId, $packagePath, $packageSize, $contentCount) {
        try {
            $checksum = $this->generateChecksum($packagePath);
            
            $data = [
                'estado' => 'listo',
                'ruta_paquete' => $packagePath,
                'tamaño_paquete' => $packageSize,
                'cantidad_contenido' => $contentCount,
                'checksum' => $checksum
            ];
            
            $result = $this->update($packageId, $data);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Paquete completado exitosamente',
                    'checksum' => $checksum
                ];
            }
            
            return ['error' => 'Error al marcar paquete como completado'];
            
        } catch(Exception $e) {
            $this->logError("Error en markAsComplete: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Verificar integridad del paquete
     */
    public function verifyPackageIntegrity($packageId) {
        try {
            $package = $this->findById($packageId);
            if (!$package) {
                return ['error' => 'Paquete no encontrado'];
            }
            
            if (!file_exists($package['ruta_paquete'])) {
                return ['error' => 'Archivo de paquete no encontrado'];
            }
            
            $currentChecksum = $this->generateChecksum($package['ruta_paquete']);
            
            if ($currentChecksum === $package['checksum']) {
                return ['success' => true, 'message' => 'Paquete íntegro'];
            } else {
                return ['error' => 'Paquete corrupto o modificado'];
            }
            
        } catch(Exception $e) {
            $this->logError("Error en verifyPackageIntegrity: " . $e->getMessage());
            return ['error' => 'Error al verificar integridad'];
        }
    }
    
    /**
     * Generar clave de instalación única
     */
    private function generateInstallationKey() {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
    }
    
    /**
     * Generar checksum del archivo
     */
    private function generateChecksum($filePath) {
        if (file_exists($filePath)) {
            return hash_file('sha256', $filePath);
        }
        return null;
    }
    
    /**
     * Obtener paquetes vencidos
     */
    public function getExpiredPackages() {
        try {
            $sql = "SELECT p.*, e.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN empresas e ON p.empresa_id = e.id 
                    WHERE p.fecha_vencimiento_licencia < CURDATE() 
                    AND p.estado != 'vencido' 
                    ORDER BY p.fecha_vencimiento_licencia";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getExpiredPackages: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar paquetes vencidos
     */
    public function markExpiredPackages() {
        try {
            $sql = "UPDATE paquetes_generados 
                    SET estado = 'vencido' 
                    WHERE fecha_vencimiento_licencia < CURDATE() 
                    AND estado != 'vencido'";
            
            $stmt = $this->db->query($sql);
            return $stmt->rowCount();
        } catch(Exception $e) {
            $this->logError("Error en markExpiredPackages: " . $e->getMessage());
            return 0;
        }
    }
}
?>