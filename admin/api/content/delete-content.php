<?php
/**
 * MÓDULO 2.2.10: API para eliminar contenido y archivos asociados
 * Propósito: Eliminar completamente contenido incluyendo archivos físicos
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

try {
    // Validar ID
    $contentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (!$contentId) {
        throw new Exception('ID de contenido no válido');
    }

    // Obtener información del contenido
    $contentModel = new Content();
    $content = $contentModel->findById($contentId);
    
    if (!$content) {
        throw new Exception('Contenido no encontrado');
    }

    // Verificar permisos adicionales si es necesario
    // Por ejemplo, verificar que no esté en uso en algún paquete activo
    if (isContentInUse($contentId)) {
        throw new Exception('El contenido está siendo utilizado en paquetes activos y no puede ser eliminado');
    }

    // Lista de archivos a eliminar
    $filesToDelete = [];
    
    // Archivo principal
    if (!empty($content['archivo_path'])) {
        $filesToDelete[] = ROOT_PATH . 'content/' . $content['archivo_path'];
    }
    
    // Thumbnail
    if (!empty($content['thumbnail_path'])) {
        $filesToDelete[] = ROOT_PATH . 'content/' . $content['thumbnail_path'];
    }
    
    // Trailer (si existe)
    if (!empty($content['trailer_path'])) {
        $filesToDelete[] = ROOT_PATH . 'content/' . $content['trailer_path'];
    }
    
    // Archivos específicos según tipo
    switch ($content['tipo']) {
        case 'pelicula':
            // Eliminar versiones comprimidas si existen
            $movieId = pathinfo($content['archivo_path'], PATHINFO_FILENAME);
            $compressedPath = ROOT_PATH . 'content/movies/compressed/' . $movieId . '_compressed.mp4';
            if (file_exists($compressedPath)) {
                $filesToDelete[] = $compressedPath;
            }
            break;
            
        case 'juego':
            // Eliminar carpeta extraída
            $metadata = json_decode($content['metadata'] ?? '{}', true);
            if (!empty($metadata['extracted_path'])) {
                $extractedPath = ROOT_PATH . 'content/' . $metadata['extracted_path'];
                if (is_dir($extractedPath)) {
                    deleteDirectory($extractedPath);
                }
            }
            break;
    }

    // Registrar en log antes de eliminar
    $db = Database::getInstance()->getConnection();
    $logStmt = $db->prepare("
        INSERT INTO logs_sistema 
        (usuario_id, accion, tabla_afectada, registro_id, valores_anteriores, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $logStmt->execute([
        $_SESSION['admin_id'] ?? null,
        'delete_content',
        'contenido',
        $contentId,
        json_encode($content),
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    // Eliminar archivos físicos
    $deletedFiles = [];
    $failedFiles = [];
    
    foreach ($filesToDelete as $file) {
        if (file_exists($file)) {
            if (@unlink($file)) {
                $deletedFiles[] = $file;
            } else {
                $failedFiles[] = $file;
            }
        }
    }

    // Eliminar registro de base de datos
    $deleteResult = $contentModel->delete($contentId);
    
    if (!$deleteResult) {
        throw new Exception('Error al eliminar el registro de la base de datos');
    }

    // Limpiar referencias en otras tablas
    cleanupReferences($contentId);

    // Limpiar trabajos pendientes
    cleanupPendingJobs($contentId);

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Contenido eliminado exitosamente',
        'deleted_files' => count($deletedFiles),
        'failed_files' => count($failedFiles),
        'warnings' => $failedFiles ? 'Algunos archivos no pudieron ser eliminados' : null
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Verificar si el contenido está en uso
 */
function isContentInUse($contentId) {
    $db = Database::getInstance()->getConnection();
    
    // Verificar en paquetes generados
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM paquetes_contenido pc
        JOIN paquetes_generados pg ON pc.paquete_id = pg.id
        WHERE pc.contenido_id = ? 
        AND pg.estado IN ('listo', 'instalado')
    ");
    $stmt->execute([$contentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

/**
 * Limpiar referencias en otras tablas
 */
function cleanupReferences($contentId) {
    $db = Database::getInstance()->getConnection();
    
    // Eliminar de favoritos (si existe esa tabla)
    try {
        $stmt = $db->prepare("DELETE FROM favoritos WHERE contenido_id = ?");
        $stmt->execute([$contentId]);
    } catch (Exception $e) {
        // Ignorar si la tabla no existe
    }
    
    // Eliminar de historial de reproducción (si existe)
    try {
        $stmt = $db->prepare("DELETE FROM historial_reproduccion WHERE contenido_id = ?");
        $stmt->execute([$contentId]);
    } catch (Exception $e) {
        // Ignorar si la tabla no existe
    }
    
    // Eliminar de estadísticas (si existe)
    try {
        $stmt = $db->prepare("DELETE FROM estadisticas_contenido WHERE contenido_id = ?");
        $stmt->execute([$contentId]);
    } catch (Exception $e) {
        // Ignorar si la tabla no existe
    }
}

/**
 * Limpiar trabajos pendientes relacionados
 */
function cleanupPendingJobs($contentId) {
    $jobsPath = ROOT_PATH . 'jobs/';
    
    if (!is_dir($jobsPath)) {
        return;
    }
    
    // Buscar y eliminar archivos de trabajos relacionados
    $patterns = [
        'compress_' . $contentId . '.json',
        'optimize_audio_' . $contentId . '.json',
        'screenshot_game_' . $contentId . '.json',
        'thumbnail_' . $contentId . '.json'
    ];
    
    foreach ($patterns as $pattern) {
        $jobFile = $jobsPath . $pattern;
        if (file_exists($jobFile)) {
            @unlink($jobFile);
        }
    }
}

/**
 * Eliminar directorio recursivamente
 */
function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return true;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            @unlink($path);
        }
    }
    
    return @rmdir($dir);
}