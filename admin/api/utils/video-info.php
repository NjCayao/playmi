<?php
header('Content-Type: application/json');

$videoPath = $_GET['path'] ?? '';
$fullPath = $_SERVER['DOCUMENT_ROOT'] . '/playmi/content/' . $videoPath;

if (!file_exists($fullPath)) {
    die(json_encode(['error' => 'Archivo no encontrado: ' . $fullPath]));
}

// Obtener información básica del archivo
$fileInfo = [
    'size' => filesize($fullPath),
    'size_mb' => round(filesize($fullPath) / 1024 / 1024, 2),
    'mime_type' => mime_content_type($fullPath)
];

// Intentar obtener información con getID3 si está disponible
if (file_exists(__DIR__ . '/../../libs/getid3/getid3.php')) {
    require_once __DIR__ . '/../../libs/getid3/getid3.php';
    $getID3 = new getID3;
    $info = $getID3->analyze($fullPath);
    
    $fileInfo['video'] = [
        'codec' => $info['video']['dataformat'] ?? 'unknown',
        'width' => $info['video']['resolution_x'] ?? 0,
        'height' => $info['video']['resolution_y'] ?? 0,
        'duration' => $info['playtime_seconds'] ?? 0,
        'bitrate' => $info['bitrate'] ?? 0
    ];
}

echo json_encode($fileInfo);
?>