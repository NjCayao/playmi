<?php
/**
 * Modelo de Contenido PLAYMI
 * Maneja películas, música y juegos
 */

require_once 'BaseModel.php';

class Content extends BaseModel {
    protected $table = 'contenido';
    
    /**
     * Obtener contenido por tipo
     */
    public function getByType($type, $activeOnly = true) {
        try {
            $sql = "SELECT * FROM contenido WHERE tipo = ?";
            $params = [$type];
            
            if ($activeOnly) {
                $sql .= " AND estado = ?";
                $params[] = STATUS_ACTIVE;
            }
            
            $sql .= " ORDER BY titulo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getByType: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener películas
     */
    public function getMovies($activeOnly = true) {
        return $this->getByType(CONTENT_MOVIE, $activeOnly);
    }
    
    /**
     * Obtener música
     */
    public function getMusic($activeOnly = true) {
        return $this->getByType(CONTENT_MUSIC, $activeOnly);
    }
    
    /**
     * Obtener juegos
     */
    public function getGames($activeOnly = true) {
        return $this->getByType(CONTENT_GAME, $activeOnly);
    }
    
    /**
     * Obtener tamaño total de contenido
     */
    public function getTotalSize($type = null) {
        try {
            $sql = "SELECT SUM(tamanio_archivo) as total FROM contenido WHERE estado = ?";
            $params = [STATUS_ACTIVE];
            
            if ($type) {
                $sql .= " AND tipo = ?";
                $params[] = $type;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return intval($result['total'] ?? 0);
        } catch(Exception $e) {
            $this->logError("Error en getTotalSize: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Obtener estadísticas de contenido
     */
    public function getContentStats() {
        try {
            $sql = "SELECT 
                        tipo,
                        COUNT(*) as cantidad,
                        SUM(tamanio_archivo) as tamanio_total,
                        AVG(duracion) as duracion_promedio
                    FROM contenido 
                    WHERE estado = ? 
                    GROUP BY tipo";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([STATUS_ACTIVE]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getContentStats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar contenido con filtros
     */
    public function searchContent($filters = [], $page = 1, $limit = RECORDS_PER_PAGE) {
        try {
            $whereConditions = ["1=1"]; // Condición base
            $params = [];
            
            // Filtro por búsqueda
            if (!empty($filters['search'])) {
                $whereConditions[] = "(titulo LIKE ? OR descripcion LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Filtro por tipo
            if (!empty($filters['tipo'])) {
                $whereConditions[] = "tipo = ?";
                $params[] = $filters['tipo'];
            }
            
            // Filtro por estado
            if (!empty($filters['estado'])) {
                $whereConditions[] = "estado = ?";
                $params[] = $filters['estado'];
            }
            
            // Filtro por categoría
            if (!empty($filters['categoria'])) {
                $whereConditions[] = "categoria = ?";
                $params[] = $filters['categoria'];
            }
            
            // Filtro por género
            if (!empty($filters['genero'])) {
                $whereConditions[] = "genero = ?";
                $params[] = $filters['genero'];
            }
            
            // Filtro por año
            if (!empty($filters['anio_lanzamiento'])) {
                $whereConditions[] = "anio_lanzamiento = ?";
                $params[] = $filters['anio_lanzamiento'];
            }
            
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            
            // Contar total
            $countSql = "SELECT COUNT(*) as total FROM contenido $whereClause";
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener registros paginados
            $offset = ($page - 1) * $limit;
            $sql = "SELECT * FROM contenido 
                    $whereClause 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'data' => $content,
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalRecords / $limit)
            ];
            
        } catch(Exception $e) {
            $this->logError("Error en searchContent: " . $e->getMessage());
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
     * Obtener categorías únicas por tipo
     */
    public function getCategoriesByType($type) {
        try {
            $sql = "SELECT DISTINCT categoria FROM contenido WHERE tipo = ? AND categoria IS NOT NULL AND categoria != '' ORDER BY categoria";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$type]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(Exception $e) {
            $this->logError("Error en getCategoriesByType: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener géneros únicos por tipo
     */
    public function getGenresByType($type) {
        try {
            $sql = "SELECT DISTINCT genero FROM contenido WHERE tipo = ? AND genero IS NOT NULL AND genero != '' ORDER BY genero";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$type]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch(Exception $e) {
            $this->logError("Error en getGenresByType: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar contador de descargas
     */
    public function incrementDownloadCount($contentId) {
        try {
            $sql = "UPDATE contenido SET descargas_count = descargas_count + 1 WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$contentId]);
        } catch(Exception $e) {
            $this->logError("Error en incrementDownloadCount: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar integridad de archivo por hash
     */
    public function verifyFileIntegrity($contentId, $fileHash) {
        try {
            $content = $this->findById($contentId);
            if (!$content) {
                return ['error' => 'Contenido no encontrado'];
            }
            
            if ($content['archivo_hash'] === $fileHash) {
                return ['success' => true, 'message' => 'Archivo íntegro'];
            } else {
                return ['error' => 'Archivo corrupto o modificado'];
            }
        } catch(Exception $e) {
            $this->logError("Error en verifyFileIntegrity: " . $e->getMessage());
            return ['error' => 'Error al verificar integridad'];
        }
    }
}
?>