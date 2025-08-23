<?php
/**
 * passenger-portal/api/get-content.php
 * API para obtener contenido dinámico del portal
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Incluir configuración y modelos necesarios
require_once '../../admin/config/database.php';
require_once '../../admin/config/system.php';
require_once '../../admin/models/Content.php';

try {
    // Obtener parámetros
    $type = $_GET['type'] ?? 'all';
    $limit = min((int)($_GET['limit'] ?? 20), 50); // Máximo 50
    $offset = (int)($_GET['offset'] ?? 0);
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? '';
    $featured = isset($_GET['featured']);
    
    // Mapear tipos del frontend a tipos de BD
    $typeMap = [
        'movies' => 'pelicula',
        'music' => 'musica',
        'games' => 'juego'
    ];
    
    $dbType = $typeMap[$type] ?? null;
    
    // Crear instancia del modelo
    $contentModel = new Content();
    
    // Construir query
    $conditions = ["estado = 'activo'"];
    $params = [];
    
    if ($dbType) {
        $conditions[] = "tipo = ?";
        $params[] = $dbType;
    }
    
    if (!empty($search)) {
        $conditions[] = "(titulo LIKE ? OR descripcion LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    if (!empty($category)) {
        $conditions[] = "categoria = ?";
        $params[] = $category;
    }
    
    $whereClause = implode(' AND ', $conditions);
    
    // Obtener contenido
    $db = Database::getInstance()->getConnection();
    
    // Query para obtener contenido
    $sql = "SELECT 
            id,
            titulo,
            descripcion,
            tipo,
            categoria,
            genero,
            duracion,
            anio_lanzamiento,
            calificacion,
            thumbnail_path,
            archivo_path,
            tamanio_archivo,
            descargas_count as views
        FROM contenido 
        WHERE $whereClause
        ORDER BY " . ($featured ? "descargas_count DESC, " : "") . "created_at DESC
        LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar metadata adicional según tipo
    foreach ($content as &$item) {
        // Formatear duración
        if ($item['duracion']) {
            $item['duracion_formato'] = formatDuration($item['duracion']);
        }
        
        // Agregar campos específicos por tipo
        switch ($item['tipo']) {
            case 'pelicula':
                $item['metadata'] = [
                    'year' => $item['anio_lanzamiento'],
                    'rating' => $item['calificacion'],
                    'genre' => $item['genero'],
                    'duration' => $item['duracion_formato'] ?? 'N/A'
                ];
                break;
                
            case 'musica':
                // Aquí podrías agregar artista, álbum, etc. si los tuvieras en la BD
                $item['metadata'] = [
                    'genre' => $item['genero'],
                    'duration' => $item['duracion_formato'] ?? 'N/A',
                    'artist' => 'Artista' // Placeholder
                ];
                break;
                
            case 'juego':
                $item['metadata'] = [
                    'category' => $item['categoria'],
                    'rating' => $item['calificacion']
                ];
                break;
        }
    }
    
    // Contar total para paginación
    $countSql = "SELECT COUNT(*) as total FROM contenido WHERE $whereClause";
    array_pop($params); // Quitar limit
    array_pop($params); // Quitar offset
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $content,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
    
} catch (Exception $e) {
    // Error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener contenido',
        'message' => $e->getMessage()
    ]);
}

// Función helper para formatear duración
function formatDuration($seconds) {
    if ($seconds < 60) {
        return $seconds . 's';
    } elseif ($seconds < 3600) {
        return floor($seconds / 60) . ' min';
    } else {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'min' : '');
    }
}
?>