<?php

/**
 * MÓDULO 2.2.8: API para procesar subida de música
 * Propósito: Manejar la subida de archivos de audio y extracción de metadatos ID3
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar límites
set_time_limit(0);
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
    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo o hubo un error en la subida');
    }

    $file = $_FILES['archivo'];

    // Validar tipo de archivo
    $allowedAudioTypes = ['mp3', 'm4a', 'wav', 'flac', 'ogg'];
    $allowedVideoTypes = ['mp4', 'avi', 'mkv']; // Para videos musicales
    $allowedTypes = array_merge($allowedAudioTypes, $allowedVideoTypes);

    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Formatos válidos: ' . implode(', ', $allowedTypes));
    }

    // Determinar si es audio o video musical
    $isVideo = in_array($extension, $allowedVideoTypes);

    // Validar tamaño
    if ($file['size'] > MAX_AUDIO_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido de ' . (MAX_AUDIO_SIZE / 1024 / 1024 / 1024) . 'GB');
    }

    // Validar datos del formulario
    $requiredFields = ['titulo'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo '$field' es requerido");
        }
    }

    // Crear directorios
    $audioPath = dirname(ROOT_PATH) . '/content/music/audio/';
    $videoPath = dirname(ROOT_PATH) . '/content/music/videos/';
    $thumbnailPath = dirname(ROOT_PATH) . '/content/music/thumbnails/';

    foreach ([$audioPath, $videoPath, $thumbnailPath] as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    // Generar nombre único
    $musicId = uniqid('music_');
    $filename = $musicId . '.' . $extension;

    // Determinar ruta según tipo
    $uploadPath = $isVideo ? $videoPath : $audioPath;
    $fullPath = $uploadPath . $filename;

    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Función que usa getID3 (línea ~195)
    function getAudioDuration($filePath)
    {
        try {
            if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
                require_once __DIR__ . '/../../vendor/autoload.php';
            }

            $getID3 = new getID3;
            $fileInfo = $getID3->analyze($filePath);

            return isset($fileInfo['playtime_seconds']) ? intval($fileInfo['playtime_seconds']) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    // Obtener duración 
    $duration = getAudioDuration($fullPath);

    // Extraer metadatos ID3 (si es audio)
    $metadata = [];
    if (!$isVideo) {
        $metadata = extractID3Tags($fullPath);
    }

    // Manejar carátula/thumbnail
    $thumbnailFilename = null;

    // 1. Intentar extraer del archivo (ID3)
    if (!$isVideo && !empty($metadata['picture'])) {
        $thumbnailFilename = $musicId . '_cover.jpg';
        $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;
        file_put_contents($thumbnailFullPath, $metadata['picture']);
    }

    // 2. Si se subió una imagen personalizada
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $thumbInfo = pathinfo($thumbFile['name']);
        $thumbExt = strtolower($thumbInfo['extension'] ?? '');

        if (in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnailFilename = $musicId . '_custom.' . $thumbExt;
            $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;

            if (move_uploaded_file($thumbFile['tmp_name'], $thumbnailFullPath)) {
                // Eliminar carátula anterior si existe
                if (isset($metadata['picture'])) {
                    @unlink($thumbnailPath . $musicId . '_cover.jpg');
                }
            }
        }
    }

    // 3. Si es video, generar thumbnail del video
    if ($isVideo && !$thumbnailFilename) {
        $thumbnailFilename = $musicId . '_thumb.jpg';
        $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;
        generateVideoThumbnail($fullPath, $thumbnailFullPath);
    }

    // Preparar datos para BD
    $contentModel = new Content();
    $contentData = [
        'titulo' => $_POST['titulo'],
        'descripcion' => $_POST['descripcion'] ?? null,
        'tipo' => 'musica',
        'categoria' => $_POST['categoria'] ?? $metadata['genre'] ?? null,
        'genero' => $_POST['genero'] ?? null,
        'artista' => $_POST['artista'] ?? $metadata['artist'] ?? null,
        'album' => $_POST['album'] ?? $metadata['album'] ?? null,
        'anio_lanzamiento' => $_POST['anio_lanzamiento'] ?? $metadata['year'] ?? null,
        'archivo_path' => ($isVideo ? 'music/videos/' : 'music/audio/') . $filename,
        'tamanio_archivo' => $file['size'],
        'duracion' => $duration,
        'thumbnail_path' => $thumbnailFilename ? 'music/thumbnails/' . $thumbnailFilename : null,
        'estado' => 'activo',
        'archivo_hash' => hash_file('sha256', $fullPath)
    ];

    // Guardar en base de datos
    $contentId = $contentModel->create($contentData);

    if (!$contentId) {
        // Limpiar archivos si falla
        @unlink($fullPath);
        if ($thumbnailFilename) {
            @unlink($thumbnailPath . $thumbnailFilename);
        }
        throw new Exception('Error al guardar en la base de datos');
    }

    // Si es audio de alta calidad, podríamos optimizarlo
    if (!$isVideo && in_array($extension, ['wav', 'flac'])) {
        queueAudioOptimization($contentId, $fullPath);
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Música subida exitosamente',
        'content_id' => $contentId,
        'metadata' => [
            'artist' => $contentData['artista'],
            'album' => $contentData['album'],
            'duration' => gmdate("i:s", $duration)
        ]
    ]);
} catch (Exception $e) {
    // Limpiar archivos si hay error
    if (isset($fullPath) && file_exists($fullPath)) {
        @unlink($fullPath);
    }
    if (isset($thumbnailFullPath) && file_exists($thumbnailFullPath)) {
        @unlink($thumbnailFullPath);
    }

    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Extraer tags ID3 del archivo de audio
 */
function extractID3Tags($filePath)
{
    $metadata = [];

    // Usando getID3 (si está instalado)
    if (class_exists('getID3')) {
        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($filePath);

        if (isset($fileInfo['tags'])) {
            $tags = $fileInfo['tags'];

            // Preferir ID3v2 sobre ID3v1
            $source = isset($tags['id3v2']) ? $tags['id3v2'] : (isset($tags['id3v1']) ? $tags['id3v1'] : []);

            $metadata['title'] = $source['title'][0] ?? null;
            $metadata['artist'] = $source['artist'][0] ?? null;
            $metadata['album'] = $source['album'][0] ?? null;
            $metadata['year'] = $source['year'][0] ?? null;
            $metadata['genre'] = $source['genre'][0] ?? null;

            // Extraer imagen de portada
            if (isset($fileInfo['comments']['picture'][0])) {
                $metadata['picture'] = $fileInfo['comments']['picture'][0]['data'];
            }
        }

        // Duración
        if (isset($fileInfo['playtime_seconds'])) {
            $metadata['duration'] = intval($fileInfo['playtime_seconds']);
        }
    } else {
        // Fallback: usar herramientas del sistema
        $ffprobePath = 'ffprobe';

        // Obtener metadatos básicos con ffprobe
        $cmd = "$ffprobePath -v quiet -print_format json -show_format " . escapeshellarg($filePath);
        $output = shell_exec($cmd);

        if ($output) {
            $data = json_decode($output, true);
            if (isset($data['format']['tags'])) {
                $tags = $data['format']['tags'];
                $metadata['title'] = $tags['title'] ?? null;
                $metadata['artist'] = $tags['artist'] ?? null;
                $metadata['album'] = $tags['album'] ?? null;
                $metadata['date'] = $tags['date'] ?? null;
                $metadata['genre'] = $tags['genre'] ?? null;
            }
        }
    }

    return $metadata;
}


/**
 * Generar thumbnail de video musical
 */
function generateVideoThumbnail($videoPath, $outputPath, $timeInSeconds = 30)
{
    $ffmpegPath = 'ffmpeg';

    $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) .
        " -ss $timeInSeconds -vframes 1 -vf scale=640:-1 " .
        escapeshellarg($outputPath) . " 2>&1";

    shell_exec($cmd);

    return file_exists($outputPath);
}

/**
 * Encolar optimización de audio
 */
function queueAudioOptimization($contentId, $inputPath)
{
    // En producción, esto enviaría a una cola para convertir WAV/FLAC a MP3 de alta calidad
    $jobFile = ROOT_PATH . 'jobs/optimize_audio_' . $contentId . '.json';
    $jobData = [
        'content_id' => $contentId,
        'input' => $inputPath,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];

    if (!is_dir(ROOT_PATH . 'jobs/')) {
        mkdir(ROOT_PATH . 'jobs/', 0755, true);
    }

    file_put_contents($jobFile, json_encode($jobData));
}
