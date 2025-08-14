<?php
/**
 * MÓDULO 2.2.11: API para recomprimir videos existentes
 * Propósito: Procesar videos para optimizar tamaño y generar múltiples calidades
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar para procesos largos
set_time_limit(0);
ini_set('memory_limit', '2G');

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
    
    // Verificar que sea un video
    if ($content['tipo'] !== 'pelicula') {
        throw new Exception('El contenido no es un video');
    }
    
    // Verificar que el archivo existe
    $originalPath = ROOT_PATH . 'content/' . $content['archivo_path'];
    if (!file_exists($originalPath)) {
        throw new Exception('El archivo de video no existe');
    }

    // Actualizar estado a procesando
    $contentModel->update($contentId, ['estado' => 'procesando']);

    // Configuración de calidades
    $qualities = [
        'hd' => [
            'resolution' => '1280x720',
            'bitrate' => '2500k',
            'audio_bitrate' => '128k',
            'suffix' => '_720p'
        ],
        'fullhd' => [
            'resolution' => '1920x1080',
            'bitrate' => '4000k',
            'audio_bitrate' => '192k',
            'suffix' => '_1080p'
        ],
        'sd' => [
            'resolution' => '854x480',
            'bitrate' => '1000k',
            'audio_bitrate' => '96k',
            'suffix' => '_480p'
        ]
    ];

    // Obtener información del video original
    $videoInfo = getVideoInfo($originalPath);
    
    // Determinar qué calidades generar basándose en el video original
    $qualityQueue = determineQualities($videoInfo, $qualities);

    // Path base para archivos comprimidos
    $compressedPath = ROOT_PATH . 'content/movies/compressed/';
    $movieId = pathinfo($content['archivo_path'], PATHINFO_FILENAME);
    
    // Resultados del procesamiento
    $results = [
        'original_size' => filesize($originalPath),
        'processed_files' => [],
        'errors' => []
    ];

    // Procesar cada calidad
    foreach ($qualityQueue as $qualityKey => $settings) {
        $outputFile = $compressedPath . $movieId . $settings['suffix'] . '.mp4';
        
        try {
            $processResult = processVideoQuality(
                $originalPath, 
                $outputFile, 
                $settings,
                $contentId
            );
            
            if ($processResult['success']) {
                $results['processed_files'][] = [
                    'quality' => $qualityKey,
                    'file' => basename($outputFile),
                    'size' => filesize($outputFile),
                    'compression_ratio' => round((1 - filesize($outputFile) / $results['original_size']) * 100, 2)
                ];
            } else {
                $results['errors'][] = "Error procesando calidad $qualityKey: " . $processResult['error'];
            }
            
        } catch (Exception $e) {
            $results['errors'][] = "Error procesando calidad $qualityKey: " . $e->getMessage();
        }
    }

    // Actualizar base de datos con resultados
    $updateData = [
        'estado' => empty($results['errors']) ? 'activo' : 'activo',
        'metadata' => json_encode([
            'compression_results' => $results,
            'processed_at' => date('Y-m-d H:i:s'),
            'qualities_available' => array_column($results['processed_files'], 'quality')
        ])
    ];
    
    $contentModel->update($contentId, $updateData);

    // Notificar resultado (en producción usaría WebSocket o SSE)
    notifyProcessingComplete($contentId, $results);

    // Respuesta
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Video procesado exitosamente',
        'results' => $results
    ]);

} catch (Exception $e) {
    // Actualizar estado a error
    if (isset($contentModel) && isset($contentId)) {
        $contentModel->update($contentId, [
            'estado' => 'activo',
            'metadata' => json_encode(['processing_error' => $e->getMessage()])
        ]);
    }

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Obtener información del video
 */
function getVideoInfo($videoPath) {
    $ffprobePath = 'ffprobe'; // Ajustar según instalación
    
    // Obtener información en formato JSON
    $cmd = "$ffprobePath -v quiet -print_format json -show_format -show_streams " . escapeshellarg($videoPath);
    $output = shell_exec($cmd);
    
    if (!$output) {
        throw new Exception('No se pudo obtener información del video');
    }
    
    $info = json_decode($output, true);
    
    // Extraer información relevante
    $videoStream = null;
    foreach ($info['streams'] as $stream) {
        if ($stream['codec_type'] === 'video') {
            $videoStream = $stream;
            break;
        }
    }
    
    return [
        'duration' => $info['format']['duration'] ?? 0,
        'bitrate' => $info['format']['bit_rate'] ?? 0,
        'width' => $videoStream['width'] ?? 0,
        'height' => $videoStream['height'] ?? 0,
        'codec' => $videoStream['codec_name'] ?? '',
        'fps' => eval('return ' . $videoStream['r_frame_rate'] . ';') ?? 30
    ];
}

/**
 * Determinar qué calidades generar
 */
function determineQualities($videoInfo, $availableQualities) {
    $queue = [];
    $originalHeight = $videoInfo['height'];
    
    // Solo generar calidades menores o iguales a la original
    foreach ($availableQualities as $key => $quality) {
        list($width, $height) = explode('x', $quality['resolution']);
        
        if ($height <= $originalHeight) {
            $queue[$key] = $quality;
        }
    }
    
    // Si el video original es menor que SD, solo comprimir
    if (empty($queue)) {
        $queue['compressed'] = [
            'resolution' => $videoInfo['width'] . 'x' . $videoInfo['height'],
            'bitrate' => '1500k',
            'audio_bitrate' => '128k',
            'suffix' => '_compressed'
        ];
    }
    
    return $queue;
}

/**
 * Procesar video en una calidad específica
 */
function processVideoQuality($inputPath, $outputPath, $settings, $contentId) {
    $ffmpegPath = 'ffmpeg'; // Ajustar según instalación
    
    // Construir comando FFmpeg
    $cmd = "$ffmpegPath -i " . escapeshellarg($inputPath) . " ";
    $cmd .= "-vf scale=" . $settings['resolution'] . " ";
    $cmd .= "-c:v libx264 -preset medium -crf 23 ";
    $cmd .= "-b:v " . $settings['bitrate'] . " ";
    $cmd .= "-c:a aac -b:a " . $settings['audio_bitrate'] . " ";
    $cmd .= "-movflags +faststart "; // Optimización para streaming
    $cmd .= "-y " . escapeshellarg($outputPath) . " ";
    $cmd .= "2>&1"; // Capturar errores
    
    // Ejecutar conversión
    $startTime = microtime(true);
    $output = shell_exec($cmd);
    $processingTime = microtime(true) - $startTime;
    
    // Verificar si se creó el archivo
    if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
        return [
            'success' => false,
            'error' => 'El archivo de salida no se creó correctamente'
        ];
    }
    
    // Log de procesamiento
    logProcessing($contentId, $settings, $processingTime, filesize($outputPath));
    
    return [
        'success' => true,
        'processing_time' => $processingTime,
        'output_size' => filesize($outputPath)
    ];
}

/**
 * Registrar procesamiento en log
 */
function logProcessing($contentId, $settings, $time, $size) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $stmt = $db->prepare("
            INSERT INTO logs_procesamiento 
            (contenido_id, tipo_proceso, configuracion, tiempo_proceso, tamanio_resultado) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $contentId,
            'video_compression',
            json_encode($settings),
            $time,
            $size
        ]);
    } catch (Exception $e) {
        // Log silencioso, no interrumpir el proceso
    }
}

/**
 * Notificar que el procesamiento está completo
 */
function notifyProcessingComplete($contentId, $results) {
    // En producción, esto podría:
    // 1. Enviar notificación por WebSocket
    // 2. Actualizar un sistema de notificaciones
    // 3. Enviar email al administrador
    // 4. Actualizar un dashboard en tiempo real
    
    // Por ahora, solo guardamos un archivo de notificación
    $notificationFile = ROOT_PATH . 'notifications/video_processed_' . $contentId . '.json';
    $notificationData = [
        'content_id' => $contentId,
        'completed_at' => date('Y-m-d H:i:s'),
        'results' => $results
    ];
    
    if (!is_dir(ROOT_PATH . 'notifications/')) {
        mkdir(ROOT_PATH . 'notifications/', 0755, true);
    }
    
    file_put_contents($notificationFile, json_encode($notificationData));
}
?>