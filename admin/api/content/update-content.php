<?php

/**
 * API para actualizar contenido existente
 * Maneja la actualización de metadatos y archivos
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar límites
set_time_limit(300);
ini_set('memory_limit', '512M');

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    // Obtener contenido existente
    $contentModel = new Content();
    $existingContent = $contentModel->findById($contentId);

    if (!$existingContent) {
        throw new Exception('Contenido no encontrado');
    }

    // Validar datos básicos
    if (empty($_POST['titulo'])) {
        throw new Exception('El título es requerido');
    }

    // Preparar datos para actualizar
    $updateData = [
        'titulo' => $_POST['titulo'],
        'descripcion' => $_POST['descripcion'] ?? null,
        'categoria' => $_POST['categoria'] ?? null,
        'genero' => $_POST['genero'] ?? null,
        'anio_lanzamiento' => $_POST['anio_lanzamiento'] ?? null,
        'calificacion' => $_POST['calificacion'] ?? null,
        'estado' => $_POST['estado'] ?? $existingContent['estado']
    ];

    // Campos específicos según tipo
    switch ($existingContent['tipo']) {
        case 'pelicula':
            $updateData['director'] = $_POST['director'] ?? null;
            break;

        case 'musica':
            $updateData['artista'] = $_POST['artista'] ?? null;
            $updateData['album'] = $_POST['album'] ?? null;
            break;

        case 'juego':
            // Actualizar metadata para juegos
            $metadata = json_decode($existingContent['metadata'] ?? '{}', true);
            $metadata['instrucciones'] = $_POST['instrucciones'] ?? null;
            $metadata['controles'] = $_POST['controles'] ?? null;
            $updateData['metadata'] = json_encode($metadata);
            break;
    }

    // Si no se cambió el archivo, pero la duración es 0, recalcularla
    if ($existingContent['duracion'] == 0 && in_array($existingContent['tipo'], ['pelicula', 'musica'])) {
        // Limpiar la ruta si tiene path absoluto de Windows
        $archivo_path = $existingContent['archivo_path'];
        if (strpos($archivo_path, 'C:\\') !== false) {
            $archivo_path = 'movies/originals/' . basename($archivo_path);
        }

        $filePath = dirname(ROOT_PATH) . '/content/' . $archivo_path;

        if (file_exists($filePath)) {
            $updateData['duracion'] = getMediaDuration($filePath);
        }
    }

    // Manejar nuevo archivo principal si se subió
    if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['archivo'];

        // Validar tipo de archivo según el tipo de contenido
        $allowedTypes = getAllowedTypes($existingContent['tipo']);
        $fileInfo = pathinfo($file['name']);
        $extension = strtolower($fileInfo['extension'] ?? '');

        if (!in_array($extension, $allowedTypes)) {
            throw new Exception('Tipo de archivo no permitido');
        }

        // Validar tamaño
        $maxSize = getMaxSize($existingContent['tipo']);
        if ($file['size'] > $maxSize) {
            throw new Exception('El archivo excede el tamaño máximo permitido');
        }

        // Determinar ruta de subida
        $uploadPath = dirname(ROOT_PATH) . '/content/';
        switch ($existingContent['tipo']) {
            case 'pelicula':
                $uploadPath .= 'movies/originals/';
                break;
            case 'musica':
                $uploadPath .= 'music/audio/';
                break;
            case 'juego':
                $uploadPath .= 'games/source/';
                break;
        }

        // Normalizar separadores de ruta
        $uploadPath = str_replace('/', DIRECTORY_SEPARATOR, $uploadPath);

        // Generar nombre único
        $newFilename = uniqid($existingContent['tipo'] . '_') . '.' . $extension;
        $fullPath = $uploadPath . $newFilename;

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $fullPath)) {
            // Eliminar archivo anterior
            $oldFilePath = ROOT_PATH . 'content/' . $existingContent['archivo_path'];
            if (file_exists($oldFilePath)) {
                @unlink($oldFilePath);
            }

            // Actualizar datos
            $updateData['archivo_path'] = str_replace(ROOT_PATH . 'content/', '', $fullPath);
            $updateData['tamanio_archivo'] = $file['size'];
            $updateData['archivo_hash'] = hash_file('sha256', $fullPath);

            // Para videos/audio, actualizar duración
            if (in_array($existingContent['tipo'], ['pelicula', 'musica'])) {
                $updateData['duracion'] = getMediaDuration($fullPath);
            }

            // Para juegos, re-extraer y validar
            if ($existingContent['tipo'] === 'juego') {
                $extractResult = extractAndValidateGame($contentId, $fullPath);
                if (!$extractResult['success']) {
                    @unlink($fullPath);
                    throw new Exception($extractResult['error']);
                }

                // Actualizar metadata con nueva información
                $metadata = json_decode($updateData['metadata'] ?? '{}', true);
                $metadata['extracted_path'] = $extractResult['extracted_path'];
                $metadata['main_file'] = $extractResult['main_file'];
                $updateData['metadata'] = json_encode($metadata);
            }
        } else {
            throw new Exception('Error al guardar el nuevo archivo');
        }
    }

    // Manejar nuevo thumbnail si se subió
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $thumbInfo = pathinfo($thumbFile['name']);
        $thumbExt = strtolower($thumbInfo['extension'] ?? '');

        if (in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            // IMPORTANTE: Corregir la ruta
            $thumbnailPath = dirname(ROOT_PATH) . '/content/thumbnails/';

            // Crear directorio si no existe
            if (!is_dir($thumbnailPath)) {
                mkdir($thumbnailPath, 0755, true);
            }

            $thumbnailName = uniqid('thumb_') . '_' . time() . '.' . $thumbExt;
            $thumbnailFullPath = $thumbnailPath . $thumbnailName;

            if (move_uploaded_file($thumbFile['tmp_name'], $thumbnailFullPath)) {
                // Eliminar thumbnail anterior
                if (!empty($existingContent['thumbnail_path'])) {
                    $oldThumbPath = dirname(ROOT_PATH) . '/content/' . $existingContent['thumbnail_path'];
                    if (file_exists($oldThumbPath)) {
                        @unlink($oldThumbPath);
                    }
                }

                $updateData['thumbnail_path'] = 'thumbnails/' . $thumbnailName;
            }
        }
    }

    // Actualizar en base de datos
    $result = $contentModel->update($contentId, $updateData);

    if ($result) {
        // Registrar actividad
        logActivity('update', 'contenido', $contentId, $existingContent, $updateData);

        // Respuesta exitosa
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Contenido actualizado exitosamente',
            'content_id' => $contentId
        ]);
    } else {
        throw new Exception('Error al actualizar la base de datos');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Obtener tipos permitidos según tipo de contenido
 */
function getAllowedTypes($contentType)
{
    $types = [
        'pelicula' => ['mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv'],
        'musica' => ['mp3', 'wav', 'flac', 'm4a', 'ogg', 'mp4', 'avi'],
        'juego' => ['zip']
    ];

    return $types[$contentType] ?? [];
}

/**
 * Obtener tamaño máximo según tipo
 */
function getMaxSize($contentType)
{
    $sizes = [
        'pelicula' => MAX_VIDEO_SIZE,
        'musica' => MAX_AUDIO_SIZE,
        'juego' => MAX_GAME_SIZE
    ];

    return $sizes[$contentType] ?? MAX_UPLOAD_SIZE;
}

/**
 * Obtener duración de media
 */
function getMediaDuration($filePath)
{
    $ffprobePath = 'ffprobe';
    $cmd = "$ffprobePath -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($filePath);
    $duration = shell_exec($cmd);

    return $duration ? intval($duration) : 0;
}

/**
 * Extraer y validar juego actualizado
 */
function extractAndValidateGame($contentId, $zipPath)
{
    $extractBasePath = ROOT_PATH . 'content/games/extracted/';
    $gameId = 'game_' . $contentId . '_' . time();
    $extractPath = $extractBasePath . $gameId;

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== TRUE) {
        return ['success' => false, 'error' => 'No se pudo abrir el archivo ZIP'];
    }

    // Crear directorio
    if (!is_dir($extractPath)) {
        mkdir($extractPath, 0755, true);
    }

    // Extraer
    $zip->extractTo($extractPath);
    $zip->close();

    // Buscar index.html
    $mainFiles = ['index.html', 'index.htm', 'game.html'];
    $foundMainFile = null;

    foreach ($mainFiles as $mainFile) {
        if (file_exists($extractPath . '/' . $mainFile)) {
            $foundMainFile = $mainFile;
            break;
        }
    }

    if (!$foundMainFile) {
        deleteDirectory($extractPath);
        return ['success' => false, 'error' => 'No se encontró archivo HTML principal'];
    }

    return [
        'success' => true,
        'extracted_path' => 'games/extracted/' . $gameId . '/',
        'main_file' => $foundMainFile
    ];
}

/**
 * Registrar actividad
 */
function logActivity($action, $table, $recordId, $oldData, $newData)
{
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO logs_sistema 
            (usuario_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $action,
            $table,
            $recordId,
            json_encode($oldData),
            json_encode($newData),
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Log silencioso
    }
}

/**
 * Eliminar directorio recursivamente
 */
function deleteDirectory($dir)
{
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
