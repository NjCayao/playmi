<?php
/**
 * MÓDULO 2.2.12: API para regenerar thumbnails automáticamente
 * Propósito: Generar o regenerar miniaturas para cualquier tipo de contenido
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar límites
set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '512M');

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

    // Obtener tipo específico si se proporciona
    $specificType = isset($_POST['type']) ? $_POST['type'] : null;

    // Obtener información del contenido
    $contentModel = new Content();
    $content = $contentModel->findById($contentId);
    
    if (!$content) {
        throw new Exception('Contenido no encontrado');
    }

    // Determinar tipo de contenido
    $contentType = $specificType ?: $content['tipo'];
    
    // Verificar que el archivo principal existe
    $mainFilePath = ROOT_PATH . 'content/' . $content['archivo_path'];
    if (!file_exists($mainFilePath)) {
        throw new Exception('El archivo principal no existe');
    }

    // Eliminar thumbnail anterior si existe
    if (!empty($content['thumbnail_path'])) {
        $oldThumbnailPath = ROOT_PATH . 'content/' . $content['thumbnail_path'];
        if (file_exists($oldThumbnailPath)) {
            @unlink($oldThumbnailPath);
        }
    }

    // Generar nuevo thumbnail según tipo
    $result = null;
    switch ($contentType) {
        case 'pelicula':
        case 'movie':
            $result = generateVideoThumbnail($content, $mainFilePath);
            break;
            
        case 'musica':
        case 'music':
            $result = generateMusicThumbnail($content, $mainFilePath);
            break;
            
        case 'juego':
        case 'game':
            $result = generateGameThumbnail($content);
            break;
            
        default:
            throw new Exception('Tipo de contenido no soportado');
    }

    if (!$result || !$result['success']) {
        throw new Exception($result['error'] ?? 'Error generando thumbnail');
    }

    // Actualizar base de datos
    $updateResult = $contentModel->update($contentId, [
        'thumbnail_path' => $result['thumbnail_path']
    ]);
    
    if (!$updateResult) {
        // Eliminar thumbnail creado si falla la actualización
        if (isset($result['full_path']) && file_exists($result['full_path'])) {
            @unlink($result['full_path']);
        }
        throw new Exception('Error actualizando base de datos');
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Thumbnail generado exitosamente',
        'thumbnail_url' => SITE_URL . 'content/' . $result['thumbnail_path'],
        'details' => $result['details'] ?? null
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Generar thumbnail para videos
 */
function generateVideoThumbnail($content, $videoPath) {
    $ffmpegPath = 'ffmpeg'; // Ajustar según instalación
    
    // Configuración
    $timeOffset = $_POST['time_offset'] ?? 10; // Segundo del que extraer el frame
    $width = 640;
    $quality = 2; // Calidad JPG (1-31, menor es mejor)
    
    // Generar nombre y ruta
    $thumbnailName = uniqid('thumb_') . '_' . time() . '.jpg';
    $thumbnailDir = ROOT_PATH . 'content/thumbnails/';
    $thumbnailPath = $thumbnailDir . $thumbnailName;
    
    // Asegurar que el directorio existe
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    // Obtener duración del video para validar offset
    $duration = getVideoDuration($videoPath);
    if ($timeOffset > $duration) {
        $timeOffset = min(10, $duration / 2); // Usar mitad del video si es muy corto
    }
    
    // Comando FFmpeg
    $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) . " ";
    $cmd .= "-ss $timeOffset ";
    $cmd .= "-vframes 1 ";
    $cmd .= "-vf scale=$width:-1 ";
    $cmd .= "-q:v $quality ";
    $cmd .= escapeshellarg($thumbnailPath) . " 2>&1";
    
    $output = shell_exec($cmd);
    
    // Verificar si se creó
    if (!file_exists($thumbnailPath) || filesize($thumbnailPath) < 1000) {
        // Intentar con el primer frame
        $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) . " ";
        $cmd .= "-vframes 1 ";
        $cmd .= "-vf scale=$width:-1 ";
        $cmd .= "-q:v $quality ";
        $cmd .= escapeshellarg($thumbnailPath) . " 2>&1";
        
        shell_exec($cmd);
    }
    
    if (!file_exists($thumbnailPath)) {
        return [
            'success' => false,
            'error' => 'No se pudo generar el thumbnail del video'
        ];
    }
    
    // Optimizar imagen
    optimizeImage($thumbnailPath);
    
    return [
        'success' => true,
        'thumbnail_path' => 'thumbnails/' . $thumbnailName,
        'full_path' => $thumbnailPath,
        'details' => [
            'size' => filesize($thumbnailPath),
            'dimensions' => getimagesize($thumbnailPath),
            'time_offset' => $timeOffset
        ]
    ];
}

/**
 * Generar thumbnail para música
 */
function generateMusicThumbnail($content, $audioPath) {
    // Opciones:
    // 1. Extraer carátula embebida del archivo
    // 2. Generar visualización de forma de onda
    // 3. Usar imagen por defecto según género
    
    $thumbnailDir = ROOT_PATH . 'content/music/thumbnails/';
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    // Intentar extraer carátula embebida
    $ffmpegPath = 'ffmpeg';
    $thumbnailName = uniqid('cover_') . '_' . time() . '.jpg';
    $thumbnailPath = $thumbnailDir . $thumbnailName;
    
    // Comando para extraer carátula
    $cmd = "$ffmpegPath -i " . escapeshellarg($audioPath) . " ";
    $cmd .= "-an -vcodec copy ";
    $cmd .= escapeshellarg($thumbnailPath) . " 2>&1";
    
    shell_exec($cmd);
    
    // Si no se extrajo carátula, generar forma de onda
    if (!file_exists($thumbnailPath) || filesize($thumbnailPath) < 1000) {
        $waveformName = uniqid('wave_') . '_' . time() . '.png';
        $waveformPath = $thumbnailDir . $waveformName;
        
        // Generar forma de onda con FFmpeg
        $cmd = "$ffmpegPath -i " . escapeshellarg($audioPath) . " ";
        $cmd .= "-filter_complex \"[0:a]showwavespic=s=640x360:colors=#FF6B6B\" ";
        $cmd .= "-frames:v 1 ";
        $cmd .= escapeshellarg($waveformPath) . " 2>&1";
        
        shell_exec($cmd);
        
        if (file_exists($waveformPath)) {
            @unlink($thumbnailPath); // Eliminar intento fallido anterior
            $thumbnailPath = $waveformPath;
            $thumbnailName = $waveformName;
        } else {
            // Usar imagen por defecto según género
            $genre = $content['categoria'] ?? 'default';
            $defaultImage = ROOT_PATH . 'assets/images/music-genres/' . $genre . '.jpg';
            
            if (!file_exists($defaultImage)) {
                $defaultImage = ROOT_PATH . 'assets/images/music-placeholder.jpg';
            }
            
            if (file_exists($defaultImage)) {
                copy($defaultImage, $thumbnailPath);
            }
        }
    }
    
    if (!file_exists($thumbnailPath)) {
        return [
            'success' => false,
            'error' => 'No se pudo generar thumbnail para la música'
        ];
    }
    
    // Optimizar imagen
    optimizeImage($thumbnailPath);
    
    return [
        'success' => true,
        'thumbnail_path' => 'music/thumbnails/' . $thumbnailName,
        'full_path' => $thumbnailPath,
        'details' => [
            'type' => strpos($thumbnailName, 'wave_') !== false ? 'waveform' : 'cover',
            'size' => filesize($thumbnailPath)
        ]
    ];
}

/**
 * Generar thumbnail para juegos
 */
function generateGameThumbnail($content) {
    // Para juegos, necesitamos usar un navegador headless
    // o buscar screenshots en la carpeta del juego
    
    $metadata = json_decode($content['metadata'] ?? '{}', true);
    $gameDir = ROOT_PATH . 'content/' . ($metadata['extracted_path'] ?? '');
    $thumbnailDir = ROOT_PATH . 'content/games/thumbnails/';
    
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    // Buscar imagen existente en el juego
    $possibleScreenshots = [
        'screenshot.png', 'screenshot.jpg', 'preview.png', 'preview.jpg',
        'thumb.png', 'thumb.jpg', 'cover.png', 'cover.jpg'
    ];
    
    $foundScreenshot = null;
    foreach ($possibleScreenshots as $filename) {
        if (file_exists($gameDir . $filename)) {
            $foundScreenshot = $gameDir . $filename;
            break;
        }
    }
    
    if ($foundScreenshot) {
        // Usar screenshot encontrado
        $ext = pathinfo($foundScreenshot, PATHINFO_EXTENSION);
        $thumbnailName = uniqid('game_') . '_' . time() . '.' . $ext;
        $thumbnailPath = $thumbnailDir . $thumbnailName;
        
        // Copiar y optimizar
        copy($foundScreenshot, $thumbnailPath);
        optimizeImage($thumbnailPath, 640);
        
        return [
            'success' => true,
            'thumbnail_path' => 'games/thumbnails/' . $thumbnailName,
            'full_path' => $thumbnailPath,
            'details' => [
                'source' => 'game_files',
                'size' => filesize($thumbnailPath)
            ]
        ];
    }
    
    // Si no hay screenshot, programar generación con navegador headless
    $gameUrl = SITE_URL . 'content/' . $metadata['extracted_path'] . ($metadata['main_file'] ?? 'index.html');
    $thumbnailName = uniqid('game_') . '_' . time() . '.jpg';
    $thumbnailPath = $thumbnailDir . $thumbnailName;
    
    // En producción, esto usaría Puppeteer o similar
    // Por ahora, usar placeholder
    $placeholderPath = ROOT_PATH . 'assets/images/game-placeholder.jpg';
    if (file_exists($placeholderPath)) {
        copy($placeholderPath, $thumbnailPath);
        
        return [
            'success' => true,
            'thumbnail_path' => 'games/thumbnails/' . $thumbnailName,
            'full_path' => $thumbnailPath,
            'details' => [
                'source' => 'placeholder',
                'note' => 'Screenshot automático pendiente'
            ]
        ];
    }
    
    return [
        'success' => false,
        'error' => 'No se pudo generar thumbnail para el juego'
    ];
}

/**
 * Obtener duración de video
 */
function getVideoDuration($videoPath) {
    $ffprobePath = 'ffprobe';
    $cmd = "$ffprobePath -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
    $duration = shell_exec($cmd);
    return $duration ? floatval($duration) : 0;
}

/**
 * Optimizar imagen
 */
function optimizeImage($imagePath, $maxWidth = 640) {
    if (!extension_loaded('gd')) {
        return; // No se puede optimizar sin GD
    }
    
    $info = getimagesize($imagePath);
    if (!$info) {
        return;
    }
    
    list($width, $height) = $info;
    
    // Si ya es menor que el ancho máximo, no hacer nada
    if ($width <= $maxWidth) {
        return;
    }
    
    // Calcular nuevas dimensiones
    $ratio = $maxWidth / $width;
    $newWidth = $maxWidth;
    $newHeight = intval($height * $ratio);
    
    // Crear imagen según tipo
    switch ($info['mime']) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $source = imagecreatefrompng($imagePath);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($imagePath);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($imagePath);
            break;
        default:
            return;
    }
    
    // Crear nueva imagen
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preservar transparencia para PNG
    if ($info['mime'] == 'image/png') {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    
    // Redimensionar
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Guardar según tipo
    switch ($info['mime']) {
        case 'image/jpeg':
            imagejpeg($destination, $imagePath, 85);
            break;
        case 'image/png':
            imagepng($destination, $imagePath, 8);
            break;
        case 'image/gif':
            imagegif($destination, $imagePath);
            break;
        case 'image/webp':
            imagewebp($destination, $imagePath, 85);
            break;
    }
    
    // Liberar memoria
    imagedestroy($source);
    imagedestroy($destination);
}
?>