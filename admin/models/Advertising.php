<?php

/**
 * Modelo de Publicidad PLAYMI
 * Maneja videos publicitarios y banners
 */

require_once 'BaseModel.php';

class Advertising extends BaseModel
{
    // No se define $table porque manejamos múltiples tablas

    /**
     * Obtener videos publicitarios
     */
    public function getVideos($companyId = 0, $tipo = '')
    {
        try {
            $sql = "SELECT p.*, c.nombre as empresa_nombre 
                FROM publicidad_empresa p
                JOIN companies c ON p.empresa_id = c.id
                WHERE 1=1";

            $params = [];

            if ($companyId > 0) {
                $sql .= " AND p.empresa_id = ?";
                $params[] = $companyId;
            }

            if (!empty($tipo)) {
                $sql .= " AND p.tipo_video = ?";
                $params[] = $tipo;
            }

            $sql .= " ORDER BY p.empresa_id, p.orden_reproduccion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getVideos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener video por ID
     */
    public function getVideoById($id)
    {
        try {
            $sql = "SELECT * FROM publicidad_empresa WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getVideoById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear video publicitario
     */
    public function createVideo($data)
    {
        try {
            $sql = "INSERT INTO publicidad_empresa 
                (empresa_id, tipo_video, archivo_path, duracion, tamanio_archivo, activo, orden_reproduccion) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['empresa_id'],
                $data['tipo_video'],
                $data['archivo_path'],
                $data['duracion'],
                $data['tamanio_archivo'],
                $data['activo'] ?? 1,
                $data['orden_reproduccion'] ?? 1
            ]);

            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            $this->logError("Error en createVideo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado de video
     */
    public function updateVideoStatus($id, $status)
    {
        try {
            $sql = "UPDATE publicidad_empresa SET activo = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            $this->logError("Error en updateVideoStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar video
     */
    public function deleteVideo($id)
    {
        try {
            $sql = "DELETE FROM publicidad_empresa WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            $this->logError("Error en deleteVideo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener banners
     */
    public function getBanners($companyId = 0, $tipo = '')
    {
        try {
            $sql = "SELECT b.*, c.nombre as empresa_nombre 
                FROM banners_empresa b
                JOIN companies c ON b.empresa_id = c.id
                WHERE 1=1";

            $params = [];

            if ($companyId > 0) {
                $sql .= " AND b.empresa_id = ?";
                $params[] = $companyId;
            }

            if (!empty($tipo)) {
                $sql .= " AND b.tipo_banner = ?";
                $params[] = $tipo;
            }

            $sql .= " ORDER BY b.empresa_id, b.orden_visualizacion";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getBanners: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener banner por ID
     */
    public function getBannerById($id)
    {
        try {
            $sql = "SELECT * FROM banners_empresa WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $this->logError("Error en getBannerById: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear banner
     */
    public function createBanner($data)
    {
        try {
            $sql = "INSERT INTO banners_empresa 
                (empresa_id, tipo_banner, imagen_path, posicion, ancho, alto, tamanio_archivo, activo, orden_visualizacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['empresa_id'],
                $data['tipo_banner'],
                $data['imagen_path'],
                $data['posicion'] ?? null,
                $data['ancho'],
                $data['alto'],
                $data['tamanio_archivo'], 
                $data['activo'] ?? 1,
                $data['orden_visualizacion'] ?? 1
            ]);

            return $result ? $this->db->lastInsertId() : false;
        } catch (Exception $e) {
            $this->logError("Error en createBanner: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar estado de banner
     */
    public function updateBannerStatus($id, $status)
    {
        try {
            $sql = "UPDATE banners_empresa SET activo = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $id]);
        } catch (Exception $e) {
            $this->logError("Error en updateBannerStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar banner
     */
    public function deleteBanner($id)
    {
        try {
            $sql = "DELETE FROM banners_empresa WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            $this->logError("Error en deleteBanner: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Contar empresas con publicidad
     */
    public function countCompaniesWithAds()
    {
        try {
            $sql = "SELECT COUNT(DISTINCT empresa_id) as total 
                    FROM (
                        SELECT empresa_id FROM publicidad_empresa WHERE activo = 1
                        UNION
                        SELECT empresa_id FROM banners_empresa WHERE activo = 1
                    ) as empresas_con_ads";

            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (Exception $e) {
            $this->logError("Error en countCompaniesWithAds: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener publicidad activa por empresa
     */
    public function getActiveAdsByCompany($companyId)
    {
        try {
            $result = [
                'videos' => [],
                'banners' => []
            ];

            // Videos
            $sql = "SELECT * FROM publicidad_empresa 
                    WHERE empresa_id = ? AND activo = 1 
                    ORDER BY tipo_video, orden_reproduccion";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            $result['videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Banners
            $sql = "SELECT * FROM banners_empresa 
                    WHERE empresa_id = ? AND activo = 1 
                    ORDER BY tipo_banner, orden_visualizacion";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            $result['banners'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $result;
        } catch (Exception $e) {
            $this->logError("Error en getActiveAdsByCompany: " . $e->getMessage());
            return ['videos' => [], 'banners' => []];
        }
    }
}
