<?php

/**
 * MÓDULO 2.2.9: API para procesar subida de juegos HTML5
 * Propósito: Manejar la subida y validación de juegos en formato ZIP
 */

require_once '../../config/system.php';
require_once '../../config/database.php';
require_once '../../controllers/ContentController.php';
require_once '../../models/Content.php';

// Configurar límites
set_time_limit(0);
ini_set('memory_limit', '1G');

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

    // Validar tipo de archivo - solo ZIP para juegos
    $allowedTypes = ['zip'];
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');

    if (!in_array($extension, $allowedTypes)) {
        throw new Exception('Solo se permiten archivos ZIP para juegos');
    }

    // Validar tamaño
    if ($file['size'] > MAX_GAME_SIZE) {
        throw new Exception('El archivo excede el tamaño máximo permitido de ' . (MAX_GAME_SIZE / 1024 / 1024 / 1024) . 'GB');
    }

    // Validar datos del formulario
    $requiredFields = ['titulo'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo '$field' es requerido");
        }
    }

    // Crear directorios    
    $sourcePath = dirname(ROOT_PATH) . '/content/games/source/';
    $extractedPath = dirname(ROOT_PATH) . '/content/games/extracted/';
    $thumbnailPath = dirname(ROOT_PATH) . '/content/games/thumbnails/';

    foreach ([$sourcePath, $extractedPath, $thumbnailPath] as $path) {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    // Generar ID único para el juego
    $gameId = uniqid('game_');
    $zipFilename = $gameId . '.zip';
    $zipFullPath = $sourcePath . $zipFilename;

    // Mover archivo ZIP
    if (!move_uploaded_file($file['tmp_name'], $zipFullPath)) {
        throw new Exception('Error al guardar el archivo ZIP');
    }

    // Validar y extraer ZIP
    $validationResult = validateAndExtractGame($zipFullPath, $extractedPath . $gameId);

    if (!$validationResult['success']) {
        // Eliminar archivo si la validación falla
        @unlink($zipFullPath);
        throw new Exception($validationResult['error']);
    }

    // Buscar o generar thumbnail
    $thumbnailFilename = null;

    // 1. Buscar imagen en el juego extraído
    $gameScreenshots = findGameScreenshots($extractedPath . $gameId);
    if (!empty($gameScreenshots)) {
        $screenshotPath = $gameScreenshots[0];
        $ext = pathinfo($screenshotPath, PATHINFO_EXTENSION);
        $thumbnailFilename = $gameId . '_thumb.' . $ext;
        copy($screenshotPath, $thumbnailPath . $thumbnailFilename);
    }

    // 2. Si se subió una imagen personalizada
    if (!$thumbnailFilename && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $thumbFile = $_FILES['thumbnail'];
        $thumbInfo = pathinfo($thumbFile['name']);
        $thumbExt = strtolower($thumbInfo['extension'] ?? '');

        if (in_array($thumbExt, ['jpg', 'jpeg', 'png', 'webp'])) {
            $thumbnailFilename = $gameId . '_custom.' . $thumbExt;
            $thumbnailFullPath = $thumbnailPath . $thumbnailFilename;
            move_uploaded_file($thumbFile['tmp_name'], $thumbnailFullPath);
        }
    }

    // Preparar datos para BD
    $contentModel = new Content();
    $contentData = [
        'titulo' => $_POST['titulo'],
        'descripcion' => $_POST['descripcion'] ?? null,
        'tipo' => 'juego',
        'categoria' => $_POST['categoria'] ?? null,
        'archivo_path' => 'games/source/' . $zipFilename,
        'tamanio_archivo' => $file['size'],
        'thumbnail_path' => $thumbnailFilename ? 'games/thumbnails/' . $thumbnailFilename : null,
        'estado' => 'activo',
        'archivo_hash' => hash_file('sha256', $zipFullPath),
        'metadata' => json_encode([
            'extracted_path' => 'games/extracted/' . $gameId . '/',
            'main_file' => $validationResult['main_file'],
            'game_files' => $validationResult['files'],
            'instrucciones' => $_POST['instrucciones'] ?? null,
            'controles' => $_POST['controles'] ?? null,
            'validated_at' => date('Y-m-d H:i:s')
        ])
    ];

    // Guardar en base de datos
    $contentId = $contentModel->create($contentData);

    if (!$contentId) {
        // Limpiar archivos si falla
        @unlink($zipFullPath);
        deleteDirectory($extractedPath . $gameId);
        if ($thumbnailFilename) {
            @unlink($thumbnailPath . $thumbnailFilename);
        }
        throw new Exception('Error al guardar en la base de datos');
    }

    // Programar generación de screenshot si no hay thumbnail
    if (!$thumbnailFilename) {
        queueGameScreenshot($contentId, $gameId);
    }

    // Respuesta exitosa
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Juego subido exitosamente',
        'content_id' => $contentId,
        'game_url' => SITE_URL . 'content/games/extracted/' . $gameId . '/' . $validationResult['main_file']
    ]);
} catch (Exception $e) {
    // Limpiar archivos si hay error
    if (isset($zipFullPath) && file_exists($zipFullPath)) {
        @unlink($zipFullPath);
    }
    if (isset($gameId) && is_dir($extractedPath . $gameId)) {
        deleteDirectory($extractedPath . $gameId);
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
 * Validar y extraer juego ZIP
 */
function validateAndExtractGame($zipPath, $extractPath)
{
    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== TRUE) {
        return ['success' => false, 'error' => 'No se pudo abrir el archivo ZIP'];
    }

    // Crear directorio de extracción
    if (!is_dir($extractPath)) {
        mkdir($extractPath, 0755, true);
    }

    // Extraer ZIP
    $zip->extractTo($extractPath);
    $zip->close();

    // Buscar archivo principal (index.html)
    $mainFiles = ['index.html', 'index.htm', 'game.html', 'main.html'];
    $foundMainFile = null;

    foreach ($mainFiles as $mainFile) {
        if (file_exists($extractPath . '/' . $mainFile)) {
            $foundMainFile = $mainFile;
            break;
        }

        // Buscar en subdirectorios (máximo 1 nivel)
        $dirs = glob($extractPath . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            if (file_exists($dir . '/' . $mainFile)) {
                $foundMainFile = basename($dir) . '/' . $mainFile;
                break 2;
            }
        }
    }

    if (!$foundMainFile) {
        deleteDirectory($extractPath);
        return ['success' => false, 'error' => 'No se encontró archivo HTML principal (index.html)'];
    }

    // Validar que tenga archivos JavaScript
    $jsFiles = glob($extractPath . '/*.js') ?: [];
    if (empty($jsFiles)) {
        // Buscar en subdirectorios
        $jsFiles = glob($extractPath . '/*/*.js') ?: [];
    }

    if (empty($jsFiles)) {
        deleteDirectory($extractPath);
        return ['success' => false, 'error' => 'El juego debe contener al menos un archivo JavaScript'];
    }

    // Listar todos los archivos del juego
    $gameFiles = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $relativePath = str_replace($extractPath . '/', '', $file->getPathname());
            $gameFiles[] = $relativePath;
        }
    }

    return [
        'success' => true,
        'main_file' => $foundMainFile,
        'files' => $gameFiles,
        'js_count' => count($jsFiles)
    ];
}

/**
 * Buscar screenshots en el juego
 */
function findGameScreenshots($gamePath)
{
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $possibleNames = ['screenshot', 'preview', 'thumb', 'cover', 'splash', 'banner'];
    $foundImages = [];

    // Buscar imágenes con nombres específicos
    foreach ($possibleNames as $name) {
        foreach ($imageExtensions as $ext) {
            $imagePath = $gamePath . '/' . $name . '.' . $ext;
            if (file_exists($imagePath)) {
                $foundImages[] = $imagePath;
            }
        }
    }

    // Si no encuentra imágenes específicas, buscar cualquier imagen
    if (empty($foundImages)) {
        foreach ($imageExtensions as $ext) {
            $images = glob($gamePath . '/*.' . $ext);
            if ($images) {
                $foundImages = array_merge($foundImages, $images);
            }
        }
    }

    return $foundImages;
}

/**
 * Programar generación de screenshot
 */
function queueGameScreenshot($contentId, $gameId)
{
    $jobsPath = dirname(ROOT_PATH) . '/jobs/';  // <-- CAMBIAR
    if (!is_dir($jobsPath)) {
        mkdir($jobsPath, 0755, true);
    }
    
    $jobFile = $jobsPath . 'screenshot_game_' . $contentId . '.json';  // <-- CAMBIAR
    $jobData = [
        'content_id' => $contentId,
        'game_id' => $gameId,
        'game_url' => SITE_URL . 'content/games/extracted/' . $gameId . '/index.html',
        'created_at' => date('Y-m-d H:i:s'),
        'status' => 'pending'
    ];

    file_put_contents($jobFile, json_encode($jobData));
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
