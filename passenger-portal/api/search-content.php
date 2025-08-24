<?php
/**
 * passenger-portal/api/search-content.php
 * API para búsqueda de contenido
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Incluir configuración y modelos necesarios
require_once '../../admin/config/database.php';

try {
    // Obtener parámetros de búsqueda
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? $_GET['type'] : 'all';
    $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 50) : 20;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    // Validar query mínima
    if (strlen($query) < 2) {
        throw new Exception('La búsqueda debe tener al menos 2 caracteres');
    }
    
    // Conectar a la base de datos
    $db = Database::getInstance()->getConnection();
    
    // Preparar término de búsqueda para LIKE
    $searchTerm = '%' . $query . '%';
    
    // Resultados
    $results = [
        'movies' => [],
        'music' => [],
        'games' => []
    ];
    
    // Búsqueda en películas
    if ($type === 'all' || $type === 'movies') {
        $sql = "SELECT 
                    id, 
                    titulo, 
                    descripcion,
                    archivo_path,
                    thumbnail_path,
                    duracion,
                    anio_lanzamiento,
                    calificacion,
                    categoria,
                    genero,
                    descargas_count as views
                FROM contenido 
                WHERE tipo = 'pelicula' 
                    AND estado = 'activo'
                    AND (
                        titulo LIKE :search 
                        OR descripcion LIKE :search 
                        OR categoria LIKE :search 
                        OR genero LIKE :search
                        OR anio_lanzamiento LIKE :search
                    )
                ORDER BY 
                    CASE 
                        WHEN titulo LIKE :exact THEN 1
                        WHEN titulo LIKE :start THEN 2
                        ELSE 3
                    END,
                    descargas_count DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':exact', $query, PDO::PARAM_STR);
        $stmt->bindValue(':start', $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results['movies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Búsqueda en música
    if ($type === 'all' || $type === 'music') {
        $sql = "SELECT 
                    id, 
                    titulo, 
                    descripcion,
                    archivo_path,
                    thumbnail_path,
                    duracion,
                    categoria,
                    genero,
                    anio_lanzamiento,
                    descargas_count as plays
                FROM contenido 
                WHERE tipo = 'musica' 
                    AND estado = 'activo'
                    AND (
                        titulo LIKE :search 
                        OR descripcion LIKE :search 
                        OR categoria LIKE :search 
                        OR genero LIKE :search
                    )
                ORDER BY 
                    CASE 
                        WHEN titulo LIKE :exact THEN 1
                        WHEN titulo LIKE :start THEN 2
                        ELSE 3
                    END,
                    descargas_count DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':exact', $query, PDO::PARAM_STR);
        $stmt->bindValue(':start', $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results['music'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Búsqueda en juegos
    if ($type === 'all' || $type === 'games') {
        $sql = "SELECT 
                    id, 
                    titulo, 
                    descripcion,
                    archivo_path,
                    thumbnail_path,
                    categoria,
                    genero,
                    calificacion,
                    descargas_count as plays
                FROM contenido 
                WHERE tipo = 'juego' 
                    AND estado = 'activo'
                    AND (
                        titulo LIKE :search 
                        OR descripcion LIKE :search 
                        OR categoria LIKE :search 
                        OR genero LIKE :search
                    )
                ORDER BY 
                    CASE 
                        WHEN titulo LIKE :exact THEN 1
                        WHEN titulo LIKE :start THEN 2
                        ELSE 3
                    END,
                    descargas_count DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':search', $searchTerm, PDO::PARAM_STR);
        $stmt->bindValue(':exact', $query, PDO::PARAM_STR);
        $stmt->bindValue(':start', $query . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results['games'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Procesar resultados para agregar información adicional
    foreach (['movies', 'music', 'games'] as $contentType) {
        foreach ($results[$contentType] as &$item) {
            // Formatear duración
            if (isset($item['duracion']) && $item['duracion']) {
                $hours = floor($item['duracion'] / 3600);
                $minutes = floor(($item['duracion'] % 3600) / 60);
                $item['duracion_formato'] = $hours > 0 
                    ? "{$hours}h {$minutes}min" 
                    : "{$minutes} min";
            }
            
            // Agregar score de relevancia (para ordenamiento en frontend si es necesario)
            $item['relevance_score'] = calculateRelevanceScore($item, $query);
        }
    }
    
    // Contar totales
    $totalResults = count($results['movies']) + count($results['music']) + count($results['games']);
    
    // Registrar búsqueda para estadísticas
    logSearch($db, $query, $totalResults);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total' => $totalResults,
        'results' => $results,
        'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => $totalResults >= $limit
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Error en la búsqueda',
        'message' => $e->getMessage()
    ]);
}

/**
 * Calcular score de relevancia
 */
function calculateRelevanceScore($item, $query) {
    $score = 0;
    $queryLower = strtolower($query);
    
    // Coincidencia exacta en título: 100 puntos
    if (strtolower($item['titulo']) === $queryLower) {
        $score += 100;
    }
    // Título comienza con query: 50 puntos
    elseif (stripos($item['titulo'], $query) === 0) {
        $score += 50;
    }
    // Query en título: 25 puntos
    elseif (stripos($item['titulo'], $query) !== false) {
        $score += 25;
    }
    
    // Query en descripción: 10 puntos
    if (isset($item['descripcion']) && stripos($item['descripcion'], $query) !== false) {
        $score += 10;
    }
    
    // Query en categoría/género: 15 puntos
    if (isset($item['categoria']) && stripos($item['categoria'], $query) !== false) {
        $score += 15;
    }
    if (isset($item['genero']) && stripos($item['genero'], $query) !== false) {
        $score += 15;
    }
    
    // Bonus por popularidad (vistas/descargas)
    if (isset($item['views']) && $item['views'] > 0) {
        $score += min(10, $item['views'] / 100);
    }
    if (isset($item['plays']) && $item['plays'] > 0) {
        $score += min(10, $item['plays'] / 100);
    }
    
    return $score;
}

/**
 * Registrar búsqueda para estadísticas
 */
function logSearch($db, $query, $resultsCount) {
    try {
        // Registrar en logs del sistema
        $sql = "INSERT INTO logs_sistema (accion, tabla_afectada, valores_nuevos, ip_address, user_agent, created_at) 
                VALUES ('search', 'contenido', :valores, :ip, :agent, NOW())";
        
        $valores = json_encode([
            'query' => $query,
            'results_count' => $resultsCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':valores' => $valores,
            ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        // También registrar en portal_usage_logs si existe company_id
        if (isset($_GET['company_id'])) {
            $sql = "INSERT INTO portal_usage_logs (company_id, action, data, ip_address, user_agent, created_at)
                    VALUES (:company_id, 'search_query', :data, :ip, :agent, NOW())";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':company_id' => (int)$_GET['company_id'],
                ':data' => $valores,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        }
        
    } catch (Exception $e) {
        // No fallar la búsqueda por error en logs
        error_log("Error logging search: " . $e->getMessage());
    }
}
?>