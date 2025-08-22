<?php
session_start();
require_once __DIR__ . '/../../config/system.php';

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['error' => 'No autorizado']));
}

$videoPath = $_POST['path'] ?? '';
$fullPath = CONTENT_PATH . $videoPath;
$outputPath = CONTENT_PATH . 'temp/' . uniqid() . '_converted.mp4';

if (!file_exists($fullPath)) {
    die(json_encode(['error' => 'Archivo no encontrado']));
}

// Crear directorio temporal si no existe
if (!is_dir(CONTENT_PATH . 'temp/')) {
    mkdir(CONTENT_PATH . 'temp/', 0755, true);
}

// Comando ffmpeg para convertir a H.264 compatible con navegadores
$command = sprintf(
    'ffmpeg -i %s -c:v libx264 -preset fast -crf 22 -c:a aac -movflags +faststart %s 2>&1',
    escapeshellarg($fullPath),
    escapeshellarg($outputPath)
);

exec($command, $output, $returnCode);

if ($returnCode === 0) {
    // Reemplazar el archivo original
    unlink($fullPath);
    rename($outputPath, $fullPath);
    
    echo json_encode([
        'success' => true,
        'message' => 'Video convertido exitosamente'
    ]);
} else {
    echo json_encode([
        'error' => 'Error al convertir video',
        'output' => implode("\n", $output)
    ]);
}
?>