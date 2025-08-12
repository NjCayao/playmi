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
    $uploadPath = ROOT_PATH . 'content/movies/originals/';
    $compressedPath = ROOT_PATH . 'content/movies/compressed/';
    $thumbnailPath = ROOT_PATH . 'content/thumbnails/';
    
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

    // Generar thumbnail automático
    $thumbnailFilename = $movieId . '_thumb.jpg';
    $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;
    generateVideoThumbnail($originalFullPath, $thumbnailFullPath, 10); // Frame en segundo 10

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

    // Manejar thumbnail personalizado si se subió
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $thumbInfo = pathinfo($thumbFile['name']);
        $thumbExt = strtolower($thumbInfo['extension'] ?? '');
        
        if (in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $customThumbName = $movieId . '_custom.' . $thumbExt;
            $customThumbPath = $thumbnailPath . $customThumbName;
            
            if (move_uploaded_file($thumbFile['tmp_name'], $customThumbPath)) {
                // Reemplazar con el thumbnail personalizado
                @unlink($thumbnailFullPath);
                $contentData['thumbnail_path'] = 'thumbnails/' . $customThumbName;
            }
        }
    }

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
        'status' => 'procesando'
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
function getVideoInfo($videoPath) {
    // Verificar si FFprobe está disponible
    $ffprobePath = 'ffprobe'; // Ajustar según instalación
    
    // Comando para obtener duración
    $durationCmd = "$ffprobePath -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
    $duration = shell_exec($durationCmd);
    
    // Comando para obtener resolución
    $resolutionCmd = "$ffprobePath -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 " . escapeshellarg($videoPath);
    $resolution = shell_exec($resolutionCmd);
    
    return [
        'duration' => $duration ? intval($duration) : null,
        'resolution' => trim($resolution) ?: null
    ];
}

/**
 * Generar thumbnail del video
 */
function generateVideoThumbnail($videoPath, $outputPath, $timeInSeconds = 10) {
    // Verificar si FFmpeg está disponible
    $ffmpegPath = 'ffmpeg'; // Ajustar según instalación
    
    // Comando para generar thumbnail
    $cmd = "$ffmpegPath -i " . escapeshellarg($videoPath) . 
           " -ss $timeInSeconds -vframes 1 -vf scale=640:-1 " . 
           escapeshellarg($outputPath) . " 2>&1";
    
    $output = shell_exec($cmd);
    
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
function queueVideoCompression($contentId, $inputPath, $outputPath) {
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
?>