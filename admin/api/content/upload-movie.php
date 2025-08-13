<?php

/**
 * MÓDULO 2.2.7: API para procesar subida de películas
 * Propósito: Manejar la subida, procesamiento y almacenamiento de películas
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar límites para archivos grandes
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
    // Validar archivo
    if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No se recibió el archivo o hubo un error en la subida');
    }

    $file = $_FILES['archivo'];

    // Validar tipo de archivo
    $allowedTypes = ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv'];
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Formatos válidos: ' . implode(', ', $allowedTypes));
    }

    // Validar tamaño
    if ($file['size'] > MAX_VIDEO_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido de ' . (MAX_VIDEO_SIZE / 1024 / 1024 / 1024) . 'GB');
    }

    // Validar datos del formulario
    $requiredFields = ['titulo'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo '$field' es requerido");
        }
    } 

    // Crear directorio si no existe
    $uploadPath = dirname(ROOT_PATH) . '/content/movies/originals/';
    $compressedPath = dirname(ROOT_PATH) . '/content/movies/compressed/';
    $thumbnailPath = dirname(ROOT_PATH) . '/content/thumbnails/';

    foreach ([$uploadPath, $compressedPath, $thumbnailPath] as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    // Generar nombre único para el archivo
    $movieId = uniqid('movie_');
    $originalFilename = $movieId . '.' . $extension;
    $originalFullPath = $uploadPath . $originalFilename;

    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $originalFullPath)) {
        throw new Exception('Error al guardar el archivo');
    }

    // Obtener información del video (duración, resolución)
    $videoInfo = getVideoInfo($originalFullPath);

    // Manejar thumbnail - primero intentar personalizado
    $thumbnailFilename = null;
    $thumbnailFullPath = null;

    // 1. Verificar si se subió thumbnail personalizado
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $thumbInfo = pathinfo($thumbFile['name']);
        $thumbExt = strtolower($thumbInfo['extension'] ?? '');

        if (in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnailFilename = $movieId . '_custom.' . $thumbExt;
            $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;

            if (move_uploaded_file($thumbFile['tmp_name'], $thumbnailFullPath)) {
                // Thumbnail personalizado subido exitosamente
            } else {
                $thumbnailFilename = null; // Falló la subida
            }
        }
    }
    // 2. Si no hay thumbnail personalizado, intentar generar con FFmpeg
    if (!$thumbnailFilename) {
        $thumbnailFilename = $movieId . '_thumb.jpg';
        $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;

        if (!generateVideoThumbnail($originalFullPath, $thumbnailFullPath, 10)) {
            // Si FFmpeg falla, no hay thumbnail
            $thumbnailFilename = null;
            $thumbnailFullPath = null;
        }
    }
    // Preparar datos para guardar en BD
    $contentModel = new Content();
    $contentData = [
        'titulo' => $_POST['titulo'],
        'descripcion' => $_POST['descripcion'] ?? null,
        'tipo' => 'pelicula',
        'categoria' => $_POST['categoria'] ?? null,
        'genero' => $_POST['genero'] ?? null,
        'director' => $_POST['director'] ?? null,
        'anio_lanzamiento' => $_POST['anio_lanzamiento'] ?? null,
        'calificacion' => $_POST['calificacion'] ?? null,
        'archivo_path' => 'movies/originals/' . $originalFilename,
        'tamanio_archivo' => $file['size'],
        'duracion' => $videoInfo['duration'] ?? null,
        'thumbnail_path' => 'thumbnails/' . $thumbnailFilename,
        'estado' => 'procesando',
        'archivo_hash' => hash_file('sha256', $originalFullPath)
    ];

    // Guardar en base de datos
    $contentId = $contentModel->create($contentData);

    if (!$contentId) {
        // Si falla, eliminar archivos
        @unlink($originalFullPath);
        @unlink($thumbnailFullPath);
        throw new Exception('Error al guardar en la base de datos');
    }

    // Encolar para compresión (proceso en segundo plano)
    queueVideoCompression($contentId, $originalFullPath, $compressedPath . $movieId . '_compressed.mp4');

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Película subida exitosamente',
        'content_id' => $contentId,
        'status' => 'procesando',
    ]);
} catch (Exception $e) {
    // Limpiar archivos si hay error
    if (isset($originalFullPath) && file_exists($originalFullPath)) {
        @unlink($originalFullPath);
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
 * Obtener información del video usando FFprobe
 */
function getVideoInfo($videoPath)
{
    try {
        // Cargar getID3
        if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
            require_once __DIR__ . '/../../vendor/autoload.php';
        } else {
            require_once __DIR__ . '/../../libraries/getid3/getid3.php';
        }

        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($videoPath);

        return [
            'duration' => isset($fileInfo['playtime_seconds']) ? intval($fileInfo['playtime_seconds']) : 0,
            'resolution' => isset($fileInfo['video']['resolution_x']) ?
                $fileInfo['video']['resolution_x'] . 'x' . $fileInfo['video']['resolution_y'] : null,
            'bitrate' => $fileInfo['bitrate'] ?? null,
            'filesize' => $fileInfo['filesize'] ?? null
        ];
    } catch (Exception $e) {
        return ['duration' => 0, 'resolution' => null];
    }
}

/**
 * Generar thumbnail del video
 */
function generateVideoThumbnail($videoPath, $outputPath, $timeInSeconds = 10)
{
    // Verificar si FFmpeg está disponible
    $ffmpegPath = 'ffmpeg'; // Ajustar según instalación

    // DEBUG
    echo "Generando thumbnail de: $videoPath\n";
    echo "Guardando en: $outputPath\n";


    // Comando para generar thumbnail
    $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) .
        " -ss $timeInSeconds -vframes 1 -vf scale=640:-1 " .
        escapeshellarg($outputPath) . " 2>&1";

    $output = shell_exec($cmd);

    // DEBUG
    echo "Comando ejecutado: $cmd\n";
    echo "Salida: $output\n";
    echo "¿Archivo creado?: " . (file_exists($outputPath) ? 'SÍ' : 'NO') . "\n";


    // Si falla, intentar con el primer frame
    if (!file_exists($outputPath)) {
        $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) .
            " -vframes 1 -vf scale=640:-1 " .
            escapeshellarg($outputPath) . " 2>&1";
        shell_exec($cmd);
    }

    return file_exists($outputPath);
}

/**
 * Encolar video para compresión
 */
function queueVideoCompression($contentId, $inputPath, $outputPath)
{
    // Aquí se implementaría un sistema de colas real
    // Por ahora, solo actualizamos el estado

    // En producción, esto enviaría a una cola de trabajos:
    // - Redis Queue
    // - Base de datos con tabla de trabajos
    // - Sistema de mensajería

    // Simulación: crear archivo de trabajo
    $jobFile = ROOT_PATH . 'jobs/compress_' . $contentId . '.json';
    $jobData = [
        'content_id' => $contentId,
        'input' => $inputPath,
        'output' => $outputPath,
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];

    if (!is_dir(ROOT_PATH . 'jobs/')) {
        mkdir(ROOT_PATH . 'jobs/', 0755, true);
    }

    file_put_contents($jobFile, json_encode($jobData));
}
