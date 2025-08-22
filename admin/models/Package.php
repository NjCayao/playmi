<?php

/**
 * Modelo de Paquetes PLAYMI
 * Maneja la generación y gestión de paquetes para Pi
 */

require_once 'BaseModel.php';

class Package extends BaseModel
{
    protected $table = 'paquetes_generados';

    /**
     * Obtener conexión a la base de datos
     * (Método público para que el controlador pueda acceder)
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Obtener paquetes por empresa
     */
    public function getByCompany($companyId, $limit = null)
    {
        try {
            $sql = "SELECT p.*, c.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN companies c ON p.empresa_id = c.id 
                    WHERE p.empresa_id = ? 
                    ORDER BY p.fecha_generacion DESC";

            if ($limit) {
                $sql .= " LIMIT $limit";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getByCompany: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener paquetes recientes
     */
    public function getRecent($limit = 10)
    {
        try {
            $sql = "SELECT p.*, c.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN companies c ON p.empresa_id = c.id 
                    ORDER BY p.fecha_generacion DESC 
                    LIMIT ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getRecent: " . $e->getMessage());
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
                        COUNT(*) as total_paquetes,
                        SUM(tamanio_paquete) as tamanio_total,
                        AVG(tamanio_paquete) as tamanio_promedio,
                        COUNT(CASE WHEN estado = 'listo' THEN 1 END) as listos,
                        COUNT(CASE WHEN estado = 'instalado' THEN 1 END) as instalados,
                        COUNT(CASE WHEN estado = 'generando' THEN 1 END) as generando
                    FROM paquetes_generados";

            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getPackageStats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener detalles completos del paquete incluyendo publicidad
     */
    public function getPackageDetails($packageId)
    {
        try {
            // Obtener información básica del paquete
            $package = $this->findById($packageId);
            if (!$package) {
                return null;
            }

            // Obtener contenido del paquete
            $sql = "SELECT c.* FROM contenido_multimedia c
                JOIN paquetes_contenido pc ON c.id = pc.contenido_id
                WHERE pc.paquete_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $package['contenido'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener videos publicitarios del paquete
            $sql = "SELECT pv.*, pe.tipo_video, pe.archivo_path, pe.duracion 
                FROM paquete_publicidad_videos pv
                JOIN publicidad_empresa pe ON pv.publicidad_id = pe.id
                WHERE pv.paquete_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $package['videos_publicidad'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener banners del paquete
            $sql = "SELECT pb.*, be.tipo_banner, be.imagen_path, be.ancho, be.alto 
                FROM paquete_publicidad_banners pb
                JOIN banners_empresa be ON pb.banner_id = be.id
                WHERE pb.paquete_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $package['banners_publicidad'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $package;
        } catch (Exception $e) {
            $this->logError("Error en getPackageDetails: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener publicidad asociada al paquete
     */
    public function getPackageAdvertising($packageId)
    {
        try {
            $advertising = [
                'videos' => [],
                'banners' => []
            ];

            // Obtener videos
            $sql = "SELECT pv.tipo_reproduccion, pe.* 
                FROM paquete_publicidad_videos pv
                JOIN publicidad_empresa pe ON pv.publicidad_id = pe.id
                WHERE pv.paquete_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $advertising['videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener banners
            $sql = "SELECT pb.tipo_ubicacion, be.* 
                FROM paquete_publicidad_banners pb
                JOIN banners_empresa be ON pb.banner_id = be.id
                WHERE pb.paquete_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $advertising['banners'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener configuración
            $sql = "SELECT advertising_config FROM company_packages WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['advertising_config']) {
                $advertising['config'] = json_decode($result['advertising_config'], true);
            }

            return $advertising;
        } catch (Exception $e) {
            $this->logError("Error en getPackageAdvertising: " . $e->getMessage());
            return ['videos' => [], 'banners' => []];
        }
    }

    /**
     * Iniciar generación de paquete
     */
    public function startGeneration($empresaId, $userId, $packageData)
    {
        try {
            // Generar clave de instalación única
            $installationKey = $this->generateInstallationKey();

            // Preparar datos para insertar
            $sql = "INSERT INTO paquetes_generados (
                    empresa_id,
                    nombre_paquete,
                    version_paquete,
                    generado_por,
                    estado,
                    notas,
                    clave_instalacion,
                    fecha_generacion,
                    fecha_vencimiento_licencia
                ) VALUES (
                    :empresa_id,
                    :nombre_paquete,
                    :version_paquete,
                    :generado_por,
                    :estado,
                    :notas,
                    :clave_instalacion,
                    NOW(),
                    (SELECT fecha_vencimiento FROM companies WHERE id = :empresa_id_2)
                )";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':empresa_id' => $empresaId,
                ':nombre_paquete' => $packageData['nombre_paquete'],
                ':version_paquete' => $packageData['version_paquete'] ?? '1.0',
                ':generado_por' => $userId,
                ':estado' => 'generando',
                ':notas' => $packageData['notas'] ?? null,
                ':clave_instalacion' => $installationKey,
                ':empresa_id_2' => $empresaId
            ]);

            if ($result) {
                return [
                    'success' => true,
                    'package_id' => $this->db->lastInsertId(),
                    'installation_key' => $installationKey
                ];
            }

            return ['success' => false, 'error' => 'Error al crear registro del paquete'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualizar estado del paquete
     */
    public function updateStatus($packageId, $status, $additionalData = [])
    {
        try {
            $sql = "UPDATE paquetes_generados SET estado = :estado WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':estado' => $status,
                ':id' => $packageId
            ]);

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Marcar paquete como completado
     */
    public function markAsComplete($packageId, $filePath, $fileSize, $contentCount)
    {
        try {
            $checksum = hash_file('sha256', $filePath);

            $sql = "UPDATE paquetes_generados SET 
                estado = 'listo',
                ruta_paquete = :ruta_paquete,
                tamanio_paquete = :tamanio_paquete,
                cantidad_contenido = :cantidad_contenido,
                checksum = :checksum
                WHERE id = :id";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':ruta_paquete' => $filePath,
                ':tamanio_paquete' => $fileSize,
                ':cantidad_contenido' => $contentCount,
                ':checksum' => $checksum,
                ':id' => $packageId
            ]);

            return ['success' => $result];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Actualizar contador de descargas
     */
    public function updateDownloadCount($packageId)
    {
        try {
            $sql = "UPDATE paquetes_generados 
                    SET descargas_count = descargas_count + 1,
                        fecha_ultima_descarga = NOW() 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$packageId]);
        } catch (Exception $e) {
            $this->logError("Error en updateDownloadCount: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener logs de un paquete
     */
    public function getLogs($packageId)
    {
        try {
            $sql = "SELECT * FROM logs_sistema 
                WHERE tabla_afectada = 'paquetes_generados' 
                AND registro_id = ? 
                ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$packageId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getLogs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crear log para un paquete
     */
    public function createLog($packageId, $logData)
    {
        try {
            $sql = "INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, valores_nuevos, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $_SESSION['admin_id'] ?? null,
                $logData['action'] ?? 'package_action',
                'paquetes_generados',
                $packageId,
                json_encode(['descripcion' => $logData['description'] ?? '', 'tipo' => $logData['type'] ?? 'info']),
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            $this->logError("Error en createLog: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar integridad del paquete
     */
    public function verifyPackageIntegrity($packageId)
    {
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
        } catch (Exception $e) {
            $this->logError("Error en verifyPackageIntegrity: " . $e->getMessage());
            return ['error' => 'Error al verificar integridad'];
        }
    }

    /**
     * Generar clave de instalación única
     */
    private function generateInstallationKey()
    {
        return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 16));
    }

    /**
     * Generar checksum del archivo
     */
    private function generateChecksum($filePath)
    {
        if (file_exists($filePath)) {
            return hash_file('sha256', $filePath);
        }
        return null;
    }

    /**
     * Obtener paquetes vencidos
     */
    public function getExpiredPackages()
    {
        try {
            $sql = "SELECT p.*, c.nombre as empresa_nombre 
                    FROM paquetes_generados p 
                    JOIN companies c ON p.empresa_id = c.id 
                    WHERE p.fecha_vencimiento_licencia < CURDATE() 
                    AND p.estado != 'vencido' 
                    ORDER BY p.fecha_vencimiento_licencia";

            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getExpiredPackages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar paquetes vencidos
     */
    public function markExpiredPackages()
    {
        try {
            $sql = "UPDATE paquetes_generados 
                    SET estado = 'vencido' 
                    WHERE fecha_vencimiento_licencia < CURDATE() 
                    AND estado != 'vencido'";

            $stmt = $this->db->query($sql);
            return $stmt->rowCount();
        } catch (Exception $e) {
            $this->logError("Error en markExpiredPackages: " . $e->getMessage());
            return 0;
        }
    }
}
