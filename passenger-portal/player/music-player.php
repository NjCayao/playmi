<?php
/**
 * passenger-portal/player/music-player.php
 * Reproductor moderno de música/video musical estilo Spotify/YouTube Music
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';
require_once '../../admin/config/database.php';

$musicId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$playlistId = $_GET['playlist'] ?? null;
$companyConfig = getCompanyConfig();

// Obtener datos reales de la BD
$musicData = null;
$playlist = [];
$error = false;

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener información de la música/video
    $sql = "SELECT id, titulo, descripcion, archivo_path, duracion, thumbnail_path, metadata 
            FROM contenido 
            WHERE id = ? AND tipo = 'musica' AND estado = 'activo'
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$musicId]);
    $music = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($music) {
        $metadata = json_decode($music['metadata'], true) ?? [];
        $musicData = [
            'id' => $music['id'],
            'title' => $music['titulo'],
            'artist' => $metadata['artist'] ?? 'Artista desconocido',
            'album' => $metadata['album'] ?? 'Álbum desconocido',
            'file_path' => $music['archivo_path'],
            'duration' => $music['duracion'] ?? 0,
            'cover_path' => $music['thumbnail_path'] ?? 'thumbnails/default-album.jpg',
            'is_video' => str_ends_with($music['archivo_path'], '.mp4') || str_ends_with($music['archivo_path'], '.webm')
        ];
        
        // Obtener playlist relacionada
        $sql = "SELECT id, titulo, duracion, thumbnail_path, metadata, archivo_path 
                FROM contenido 
                WHERE tipo = 'musica' AND estado = 'activo' AND id != ?
                ORDER BY RAND()
                LIMIT 20";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$musicId]);
        $playlistData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Agregar la canción actual al inicio del playlist
        array_unshift($playlist, [
            'id' => $musicData['id'],
            'title' => $musicData['title'],
            'artist' => $musicData['artist'],
            'duration' => $musicData['duration'],
            'cover' => $musicData['cover_path'],
            'file_path' => $musicData['file_path'],
            'is_video' => $musicData['is_video']
        ]);
        
        foreach ($playlistData as $item) {
            $itemMeta = json_decode($item['metadata'], true) ?? [];
            $playlist[] = [
                'id' => $item['id'],
                'title' => $item['titulo'],
                'artist' => $itemMeta['artist'] ?? 'Artista desconocido',
                'duration' => $item['duracion'] ?? 0,
                'cover' => $item['thumbnail_path'] ?? 'thumbnails/default-album.jpg',
                'file_path' => $item['archivo_path'],
                'is_video' => str_ends_with($item['archivo_path'], '.mp4') || str_ends_with($item['archivo_path'], '.webm')
            ];
        }
        
    } else {
        $error = true;
        $errorMessage = "Contenido no encontrado";
    }
    
} catch (Exception $e) {
    $error = true;
    $errorMessage = "Error al cargar el contenido: " . $e->getMessage();
    error_log("Error en music-player.php: " . $e->getMessage());
}

// Si hay error, redirigir
if ($error) {
    header('Location: ../music.php?error=' . urlencode($errorMessage));
    exit;
}

// Construir la URL completa
$mediaUrl = CONTENT_URL . $musicData['file_path'];
$coverUrl = CONTENT_URL . $musicData['cover_path'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($musicData['title']); ?> - PLAYMI Music</title>
    
    <link rel="stylesheet" href="../assets/css/player.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            overflow: hidden;
            height: 100vh;
        }
        
        /* Contenedor principal */
        .music-player-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            height: 100vh;
            position: relative;
        }
        
        /* Panel principal */
        .player-main {
            display: flex;
            flex-direction: column;
            background: #0a0a0a;
            position: relative;
            overflow: hidden;
        }
        
        /* Área de video/visualización */
        .media-container {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(ellipse at center, #1a1a2e 0%, #0a0a0a 100%);
        }
        
        /* Video musical */
        #musicVideo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }
        
        #musicVideo.active {
            display: block;
        }
        
        /* Visualizador de audio */
        .audio-visualizer {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        
        .audio-visualizer.hidden {
            display: none;
        }
        
        #visualizer {
            width: 100%;
            height: 100%;
            opacity: 0.6;
        }
        
        /* Artwork central */
        .artwork-container {
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }
        
        .artwork-container.with-video {
            position: absolute;
            bottom: 20px;
            left: 20px;
            width: 80px;
            height: 80px;
        }
        
        .album-artwork {
            width: 300px;
            height: 300px;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            object-fit: cover;
            transition: all 0.3s ease;
        }
        
        .artwork-container.with-video .album-artwork {
            width: 80px;
            height: 80px;
            border-radius: 4px;
        }
        
        /* Información de la canción (overlay) */
        .song-info-overlay {
            position: absolute;
            bottom: 100px;
            left: 0;
            right: 0;
            text-align: center;
            padding: 0 20px;
            transition: all 0.3s ease;
        }
        
        .song-info-overlay.with-video {
            bottom: 20px;
            left: 120px;
            right: auto;
            text-align: left;
        }
        
        .current-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        }
        
        .artwork-container.with-video ~ .song-info-overlay .current-title {
            font-size: 1.25rem;
        }
        
        .current-artist {
            font-size: 1.25rem;
            color: #b3b3b3;
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        }
        
        .artwork-container.with-video ~ .song-info-overlay .current-artist {
            font-size: 1rem;
        }
        
        /* Controles del reproductor */
        .player-controls-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.7) 100%);
            padding: 20px;
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }
        
        .player-controls-bar.hidden {
            transform: translateY(100%);
            opacity: 0;
        }
        
        /* Barra de progreso mejorada */
        .progress-container {
            margin-bottom: 15px;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
            overflow: visible;
            transition: height 0.2s;
        }
        
        .progress-bar:hover {
            height: 6px;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--company-primary, #1db954);
            border-radius: 2px;
            width: 0%;
            position: relative;
        }
        
        .progress-handle {
            position: absolute;
            right: -8px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .progress-bar:hover .progress-handle {
            opacity: 1;
        }
        
        .time-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #b3b3b3;
            margin-top: 5px;
        }
        
        /* Controles principales */
        .control-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: #b3b3b3;
            cursor: pointer;
            transition: all 0.2s;
            padding: 8px;
            border-radius: 50%;
            position: relative;
        }
        
        .control-btn:hover {
            color: white;
            transform: scale(1.1);
        }
        
        .control-btn.active {
            color: var(--company-primary, #1db954);
        }
        
        /* Botón play/pause principal */
        .play-pause-btn {
            width: 56px;
            height: 56px;
            background: white;
            color: black;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .play-pause-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }
        
        .play-pause-btn i {
            margin-left: 2px;
        }
        
        /* Botones skip estilo Netflix */
        .skip-10 {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            position: relative;
        }
        
        .skip-10:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.4);
        }
        
        .skip-10 span {
            position: absolute;
            bottom: -2px;
            font-size: 9px;
            font-weight: 600;
        }
        
        /* Controles adicionales mejorados */
        .additional-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .left-controls, .right-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        /* Botón de playlist mejorado */
        .playlist-toggle {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .playlist-toggle:hover {
            background: rgba(255,255,255,0.2);
        }
        
        /* Botón cerrar playlist */
        .playlist-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 1;
        }
        
        .playlist-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        /* Botón fullscreen mejorado */
        .fullscreen-button {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            opacity: 0;
            pointer-events: none;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .fullscreen-button.visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        .fullscreen-button:hover {
            background: rgba(0,0,0,0.9);
            transform: scale(1.1);
        }
        
        /* Overlay para cerrar playlist en móvil */
        .playlist-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 49;
        }
        
        .playlist-overlay.active {
            display: block;
        }
        
        /* Control de volumen moderno */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .volume-slider {
            width: 100px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .volume-fill {
            height: 100%;
            background: white;
            width: 100%;
            transition: width 0.1s;
        }
        
        /* Panel de playlist */
        .playlist-panel {
            background: #121212;
            border-left: 1px solid rgba(255,255,255,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .playlist-header {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.3);
        }
        
        .playlist-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .playlist-info {
            color: #b3b3b3;
            font-size: 0.875rem;
        }
        
        .playlist-items {
            flex: 1;
            overflow-y: auto;
            padding: 10px 0;
        }
        
        .playlist-item {
            padding: 8px 20px;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }
        
        .playlist-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .playlist-item.active {
            background: rgba(29, 185, 84, 0.2);
        }
        
        .playlist-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--company-primary, #1db954);
        }
        
        .playlist-item-cover {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .playlist-item-info {
            flex: 1;
            overflow: hidden;
        }
        
        .playlist-item-title {
            font-weight: 500;
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .playlist-item.active .playlist-item-title {
            color: var(--company-primary, #1db954);
        }
        
        .playlist-item-artist {
            font-size: 0.75rem;
            color: #b3b3b3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .playlist-item-duration {
            color: #b3b3b3;
            font-size: 0.75rem;
        }
        
        /* Botón volver */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            opacity: 0;
            pointer-events: none;
        }
        
        .back-button.visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        .back-button:hover {
            background: rgba(0,0,0,0.9);
            transform: translateX(-5px);
        }
        
        /* Toggle para video/visualizador */
        .media-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            z-index: 10;
            font-size: 0.875rem;
            transition: all 0.2s;
            opacity: 0;
            pointer-events: none;
        }
        
        .media-toggle.visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        .media-toggle:hover {
            background: rgba(0,0,0,0.9);
        }
        
        /* Overlay de publicidad */
        .ad-overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.95);
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            color: white;
            z-index: 100;
            display: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.9);
        }
        
        .ad-overlay.active {
            display: block;
        }
        
        /* Loading spinner */
        .loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--company-primary, #1db954);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }
        
        .loading-spinner.active {
            display: block;
        }
        
        @keyframes spin {
            to { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        /* Responsive mejorado */
        @media (max-width: 1024px) {
            .music-player-container {
                grid-template-columns: 1fr;
            }
            
            .playlist-panel {
                position: fixed;
                right: -350px;
                top: 0;
                bottom: 0;
                width: 350px;
                transition: right 0.3s ease;
                z-index: 50;
                box-shadow: -5px 0 20px rgba(0,0,0,0.5);
            }
            
            .playlist-panel.active {
                right: 0;
            }
            
            .playlist-toggle {
                display: flex !important;
            }
        }
        
        @media (max-width: 768px) {
            .album-artwork {
                width: 200px;
                height: 200px;
            }
            
            .current-title {
                font-size: 1.5rem;
            }
            
            .current-artist {
                font-size: 1rem;
            }
            
            .control-buttons {
                gap: 10px;
            }
            
            .control-btn {
                padding: 6px;
            }
            
            .skip-10 {
                width: 36px;
                height: 36px;
            }
            
            .skip-10 i {
                font-size: 12px;
            }
            
            .skip-10 span {
                font-size: 8px;
            }
            
            .play-pause-btn {
                width: 48px;
                height: 48px;
                font-size: 18px;
            }
            
            .volume-control {
                display: none;
            }
            
            .playlist-toggle {
                display: flex !important;
                padding: 8px 12px;
                font-size: 0.75rem;
            }
            
            .fullscreen-button {
                top: 10px;
                right: 10px;
                width: 40px;
                height: 40px;
            }
            
            .back-button {
                top: 10px;
                left: 10px;
                padding: 8px 16px;
                font-size: 0.8125rem;
            }
            
            .media-toggle {
                top: 10px;
                right: 60px;
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            .player-controls-bar {
                padding: 15px;
            }
            
            .playlist-panel {
                width: 100%;
                right: -100%;
            }
            
            /* Para landscape en móvil */
            @media (orientation: landscape) {
                .media-container {
                    height: 100vh;
                }
                
                .album-artwork {
                    width: 150px;
                    height: 150px;
                }
                
                .current-title {
                    font-size: 1.25rem;
                }
                
                .player-controls-bar {
                    padding: 10px 15px;
                }
                
                .control-buttons {
                    margin: 10px 0;
                }
            }
        }
        
        @media (max-width: 480px) {
            .control-buttons {
                gap: 8px;
            }
            
            .control-btn:not(.play-pause-btn):not(.skip-10) {
                font-size: 16px;
            }
        }
        
        /* Scrollbar personalizado */
        .playlist-items::-webkit-scrollbar {
            width: 8px;
        }
        
        .playlist-items::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .playlist-items::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
        }
        
        .playlist-items::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* Mostrar playlist en móvil */
        .playlist-toggle {
            display: none;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../assets/fonts/font-awesome/css/all.min.css">
</head>
<body>
    <div class="music-player-container">
        <!-- Panel principal del reproductor -->
        <div class="player-main">
            <!-- Botón volver -->
            <button class="back-button" id="backButton" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
            
            <!-- Toggle para video/visualizador (solo si es video) -->
            <?php if ($musicData['is_video']): ?>
            <button class="media-toggle" id="mediaToggle" onclick="toggleMediaView()">
                <i class="fas fa-music"></i> Visualizador
            </button>
            <?php endif; ?>
            
            <!-- Botón fullscreen más visible -->
            <button class="fullscreen-button" id="fullscreenButton" onclick="toggleFullscreen()">
                <i class="fas fa-expand" id="fullscreenIcon"></i>
            </button>
            
            <!-- Área de visualización -->
            <div class="media-container">
                <!-- Loading spinner -->
                <div class="loading-spinner active" id="loadingSpinner"></div>
                
                <!-- Video musical (si aplica) -->
                <video id="musicVideo" class="<?php echo $musicData['is_video'] ? 'active' : ''; ?>" 
                       preload="metadata"
                       style="display: <?php echo $musicData['is_video'] ? 'block' : 'none'; ?>">
                    <source src="<?php echo $mediaUrl; ?>" type="video/mp4">
                </video>
                
                <!-- Visualizador de audio -->
                <div class="audio-visualizer <?php echo $musicData['is_video'] ? 'hidden' : ''; ?>" id="audioVisualizer">
                    <canvas id="visualizer"></canvas>
                </div>
                
                <!-- Artwork (se muestra cuando no hay video o está en modo audio) -->
                <div class="artwork-container <?php echo $musicData['is_video'] ? 'with-video' : ''; ?>" id="artworkContainer">
                    <img src="<?php echo $coverUrl; ?>" 
                         alt="Album artwork" 
                         class="album-artwork" 
                         id="albumArt">
                </div>
                
                <!-- Información de la canción -->
                <div class="song-info-overlay <?php echo $musicData['is_video'] ? 'with-video' : ''; ?>" id="songInfo">
                    <h1 class="current-title" id="currentTitle"><?php echo htmlspecialchars($musicData['title']); ?></h1>
                    <p class="current-artist" id="currentArtist"><?php echo htmlspecialchars($musicData['artist']); ?></p>
                </div>
            </div>
            
            <!-- Controles del reproductor -->
            <div class="player-controls-bar" id="playerControls">
                <div class="progress-container">
                    <div class="progress-bar" id="progressBar" onclick="seek(event)">
                        <div class="progress-fill" id="progressFill">
                            <div class="progress-handle"></div>
                        </div>
                    </div>
                    <div class="time-info">
                        <span id="currentTime">0:00</span>
                        <span id="duration">0:00</span>
                    </div>
                </div>
                
                <div class="control-buttons">
                    <button class="control-btn" onclick="toggleShuffle()" id="shuffleBtn" title="Aleatorio">
                        <i class="fas fa-random" style="font-size: 18px"></i>
                    </button>
                    
                    <button class="control-btn" onclick="previousTrack()" title="Anterior">
                        <i class="fas fa-step-backward" style="font-size: 20px"></i>
                    </button>
                    
                    <button class="control-btn skip-10" onclick="skipBackward()" title="Retroceder 10s">
                        <i class="fas fa-undo"></i>
                        <span>10</span>
                    </button>
                    
                    <button class="control-btn play-pause-btn" onclick="togglePlayPause()" id="playPauseBtn">
                        <i class="fas fa-play"></i>
                    </button>
                    
                    <button class="control-btn skip-10" onclick="skipForward()" title="Adelantar 10s">
                        <i class="fas fa-redo"></i>
                        <span>10</span>
                    </button>
                    
                    <button class="control-btn" onclick="nextTrack()" title="Siguiente">
                        <i class="fas fa-step-forward" style="font-size: 20px"></i>
                    </button>
                    
                    <button class="control-btn" onclick="toggleRepeat()" id="repeatBtn" title="Repetir">
                        <i class="fas fa-redo" style="font-size: 18px"></i>
                    </button>
                </div>
                
                <div class="additional-controls">
                    <div class="left-controls">
                        <div class="volume-control">
                            <button class="control-btn" onclick="toggleMute()">
                                <i class="fas fa-volume-up" id="volumeIcon"></i>
                            </button>
                            <div class="volume-slider" id="volumeSlider" onclick="setVolume(event)">
                                <div class="volume-fill" id="volumeFill"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="right-controls">
                        <button class="control-btn playlist-toggle" onclick="togglePlaylist()" title="Lista de reproducción">
                            <i class="fas fa-list"></i>
                            <span class="playlist-text">Playlist</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de playlist -->
        <div class="playlist-panel" id="playlistPanel">
            <button class="playlist-close" onclick="togglePlaylist()">
                <i class="fas fa-times"></i>
            </button>
            <div class="playlist-header">
                <h3>Lista de reproducción</h3>
                <p class="playlist-info"><?php echo count($playlist); ?> canciones</p>
            </div>
            <div class="playlist-items" id="playlistItems">
                <?php foreach ($playlist as $index => $song): ?>
                <div class="playlist-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                     data-id="<?php echo $song['id']; ?>"
                     data-index="<?php echo $index; ?>"
                     onclick="playTrack(<?php echo $index; ?>)">
                    <img src="<?php echo CONTENT_URL . $song['cover']; ?>" alt="" class="playlist-item-cover">
                    <div class="playlist-item-info">
                        <div class="playlist-item-title">
                            <?php echo htmlspecialchars($song['title']); ?>
                            <?php if ($song['is_video']): ?>
                                <i class="fas fa-video" style="font-size: 0.75rem; margin-left: 0.5rem; color: #b3b3b3;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="playlist-item-artist"><?php echo htmlspecialchars($song['artist']); ?></div>
                    </div>
                    <div class="playlist-item-duration">
                        <?php echo floor($song['duration'] / 60) . ':' . str_pad($song['duration'] % 60, 2, '0', STR_PAD_LEFT); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Overlay para cerrar playlist en móvil -->
    <div class="playlist-overlay" id="playlistOverlay" onclick="togglePlaylist()"></div>
    
    <!-- Audio element (siempre presente, incluso para videos) -->
    <audio id="audioPlayer" preload="metadata">
        <source src="<?php echo $mediaUrl; ?>" type="<?php echo $musicData['is_video'] ? 'video/mp4' : 'audio/mpeg'; ?>">
    </audio>
    
    <!-- Overlay de publicidad -->
    <div class="ad-overlay" id="adOverlay">
        <h2>PUBLICIDAD</h2>
        <div class="ad-countdown" id="adCountdown" style="font-size: 48px; color: var(--company-primary); margin: 20px 0;">30</div>
        <p>La música continuará automáticamente</p>
        <button class="skip-button" id="skipButton" onclick="skipAd()" style="display: none; margin-top: 20px;">
            Saltar publicidad
        </button>
    </div>
    
    <!-- JavaScript -->
    <script src="../assets/js/music-player.js"></script>
    <script>
        // Configuración
        const playerConfig = {
            musicId: <?php echo $musicData['id']; ?>,
            companyId: <?php echo $companyConfig['company_id']; ?>,
            adsEnabled: false, // Sin publicidad en música
            playlist: <?php echo json_encode($playlist); ?>,
            isVideo: <?php echo $musicData['is_video'] ? 'true' : 'false'; ?>
        };
        
        // Variables globales
        let currentTrackIndex = 0;
        let isVideo = playerConfig.isVideo;
        let showingVideo = isVideo;
        let mediaElement = null;
        let controlsTimer = null;
        
        // Elementos del DOM
        const audioPlayer = document.getElementById('audioPlayer');
        let videoPlayer = document.getElementById('musicVideo');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const playerControls = document.getElementById('playerControls');
        const backButton = document.getElementById('backButton');
        const mediaToggle = document.getElementById('mediaToggle');
        
        // Debug
        console.log('Initial setup:', {
            isVideo: isVideo,
            playlist: playerConfig.playlist,
            videoElement: videoPlayer
        });
        
        // Configurar elemento de media inicial
        setupMediaElement();
        
        function setupMediaElement() {
            if (isVideo && videoPlayer) {
                console.log('Setting up video element');
                mediaElement = videoPlayer;
                videoPlayer.style.display = 'block';
                videoPlayer.classList.add('active');
                document.getElementById('audioVisualizer').classList.add('hidden');
                
                // Configurar eventos del video
                videoPlayer.addEventListener('loadstart', function() {
                    loadingSpinner.classList.add('active');
                });
                
                videoPlayer.addEventListener('canplay', function() {
                    loadingSpinner.classList.remove('active');
                });
                
                videoPlayer.addEventListener('ended', function() {
                    nextTrack();
                });
                
                videoPlayer.addEventListener('error', function(e) {
                    console.error('Video error:', e);
                    console.error('Video src:', videoPlayer.src);
                    loadingSpinner.classList.remove('active');
                    alert('Error al cargar el video. Verificando la ruta...');
                });
                
            } else {
                console.log('Setting up audio element');
                mediaElement = audioPlayer;
                if (videoPlayer) {
                    videoPlayer.style.display = 'none';
                    videoPlayer.classList.remove('active');
                }
                document.getElementById('audioVisualizer').classList.remove('hidden');
            }
            
            // Eventos de audio
            audioPlayer.addEventListener('loadstart', function() {
                if (!isVideo) loadingSpinner.classList.add('active');
            });
            
            audioPlayer.addEventListener('canplay', function() {
                if (!isVideo) loadingSpinner.classList.remove('active');
            });
            
            audioPlayer.addEventListener('ended', function() {
                if (!isVideo) nextTrack();
            });
        }
        
        // Reproducir pista específica
        function playTrack(index) {
            console.log('Playing track:', index);
            currentTrackIndex = index;
            const track = playerConfig.playlist[index];
            
            if (!track) {
                console.error('Track not found:', index);
                return;
            }
            
            console.log('Track info:', track);
            
            // Actualizar UI
            document.getElementById('currentTitle').textContent = track.title;
            document.getElementById('currentArtist').textContent = track.artist;
            document.getElementById('albumArt').src = '/playmi/content/' + track.cover;
            
            // Actualizar playlist activa
            document.querySelectorAll('.playlist-item').forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });
            
            // Actualizar la duración en la playlist
            const activeItem = document.querySelector('.playlist-item.active');
            if (activeItem && track.duration) {
                const durationEl = activeItem.querySelector('.playlist-item-duration');
                if (durationEl) {
                    const minutes = Math.floor(track.duration / 60);
                    const seconds = track.duration % 60;
                    durationEl.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
                }
            }
            
            // Determinar si es video
            isVideo = track.is_video;
            const mediaUrl = '/playmi/content/' + track.file_path;
            
            console.log('Media URL:', mediaUrl);
            console.log('Is video:', isVideo);
            
            // Pausar cualquier reproducción actual
            if (audioPlayer && !audioPlayer.paused) audioPlayer.pause();
            if (videoPlayer && !videoPlayer.paused) videoPlayer.pause();
            
            // Configurar media apropiada
            if (isVideo) {
                // Es video
                console.log('Configuring video playback');
                
                // Asegurarse de que el elemento de video existe
                if (!videoPlayer) {
                    console.log('Video element not found, creating...');
                    location.reload(); // Recargar si no hay elemento de video
                    return;
                }
                
                // Actualizar src del video
                videoPlayer.src = mediaUrl;
                videoPlayer.style.display = 'block';
                videoPlayer.classList.add('active');
                
                // Ocultar visualizador y mostrar video
                document.getElementById('audioVisualizer').classList.add('hidden');
                document.getElementById('artworkContainer').classList.add('with-video');
                document.getElementById('songInfo').classList.add('with-video');
                
                // Mostrar toggle si existe
                // if (mediaToggle) {
                //     mediaToggle.style.display = 'block';
                //     mediaToggle.classList.add('visible');
                //     mediaToggle.innerHTML = '<i class="fas fa-music"></i> Mostrar visualizador';
                // }
                
                mediaElement = videoPlayer;
                showingVideo = true;
                
                // Actualizar MusicPlayer para usar video
                MusicPlayer.switchMedia(true);
                MusicPlayer.currentTrackIndex = currentTrackIndex;
                
                // Cargar y reproducir
                videoPlayer.load();
                const playPromise = videoPlayer.play();
                
                if (playPromise !== undefined) {
                    playPromise.catch(e => {
                        console.error('Error playing video:', e);
                    });
                }
                
            } else {
                // Es audio MP3
                console.log('Configuring audio playback');
                audioPlayer.src = mediaUrl;
                
                if (videoPlayer) {
                    videoPlayer.pause();
                    videoPlayer.style.display = 'none';
                    videoPlayer.classList.remove('active');
                }
                
                // Mostrar visualizador y ocultar video
                document.getElementById('audioVisualizer').classList.remove('hidden');
                document.getElementById('artworkContainer').classList.remove('with-video');
                document.getElementById('songInfo').classList.remove('with-video');
                
                // Ocultar toggle
                if (mediaToggle) {
                    mediaToggle.style.display = 'none';
                    mediaToggle.classList.remove('visible');
                }
                
                mediaElement = audioPlayer;
                showingVideo = false;
                
                // Actualizar MusicPlayer para usar audio
                MusicPlayer.switchMedia(false);
                MusicPlayer.currentTrackIndex = currentTrackIndex;
                
                // Reproducir
                audioPlayer.play().catch(e => {
                    console.error('Error playing audio:', e);
                });
            }
            
            updatePlayPauseButton(true);
        }
        
        // Control de visibilidad de controles mejorado
        function showControls() {
            playerControls.classList.remove('hidden');
            backButton.classList.add('visible');
            if (mediaToggle && isVideo) mediaToggle.classList.add('visible');
            if (fullscreenButton) fullscreenButton.classList.add('visible');
            
            clearTimeout(controlsTimer);
            
            // Solo ocultar si está reproduciendo
            if (mediaElement && !mediaElement.paused) {
                controlsTimer = setTimeout(hideControls, 3000);
            }
        }
        
        function hideControls() {
            if (mediaElement && !mediaElement.paused && !isPlaylistOpen()) {
                playerControls.classList.add('hidden');
                backButton.classList.remove('visible');
                if (mediaToggle) mediaToggle.classList.remove('visible');
                if (fullscreenButton) fullscreenButton.classList.remove('visible');
            }
        }
        
        function isPlaylistOpen() {
            const playlist = document.getElementById('playlistPanel');
            return playlist && playlist.classList.contains('active');
        }
        
        // Eventos para mostrar/ocultar controles
        const playerMain = document.querySelector('.player-main');
        const fullscreenButton = document.getElementById('fullscreenButton');
        
        // Mouse events
        playerMain.addEventListener('mousemove', showControls);
        playerMain.addEventListener('mouseenter', showControls);
        playerMain.addEventListener('mouseleave', () => {
            clearTimeout(controlsTimer);
            controlsTimer = setTimeout(hideControls, 1000);
        });
        
        // Touch events para móvil
        let touchTimer;
        playerMain.addEventListener('touchstart', (e) => {
            // No mostrar controles si está tocando un botón
            if (e.target.closest('button') || e.target.closest('.player-controls-bar')) {
                return;
            }
            showControls();
            clearTimeout(touchTimer);
            touchTimer = setTimeout(hideControls, 3000);
        });
        
        // Click para play/pause solo en el área del video/visualizador
        playerMain.addEventListener('click', function(e) {
            // Solo toggle play/pause si hace click en el área principal, no en controles
            if (e.target === playerMain || e.target.closest('.media-container')) {
                if (!e.target.closest('button') && !e.target.closest('.player-controls-bar')) {
                    togglePlayPause();
                    showControls(); // Mostrar controles al hacer play/pause
                }
            }
        });
        
        // Pausar cuando se pausa el video
        if (mediaElement) {
            mediaElement.addEventListener('pause', showControls);
            mediaElement.addEventListener('play', () => {
                clearTimeout(controlsTimer);
                controlsTimer = setTimeout(hideControls, 3000);
            });
        }
        
        // Toggle entre video y visualizador
        function toggleMediaView() {
            if (!isVideo) return;
            
            showingVideo = !showingVideo;
            const videoEl = document.getElementById('musicVideo');
            const visualizer = document.getElementById('audioVisualizer');
            const artwork = document.getElementById('artworkContainer');
            const songInfo = document.getElementById('songInfo');
            const toggleBtn = document.getElementById('mediaToggle');
            
            if (showingVideo) {
                videoEl.classList.add('active');
                visualizer.classList.add('hidden');
                artwork.classList.add('with-video');
                songInfo.classList.add('with-video');
                toggleBtn.innerHTML = '<i class="fas fa-music"></i> Mostrar visualizador';
                mediaElement = videoPlayer;
                audioPlayer.pause();
            } else {
                videoEl.classList.remove('active');
                visualizer.classList.remove('hidden');
                artwork.classList.remove('with-video');
                songInfo.classList.remove('with-video');
                toggleBtn.innerHTML = '<i class="fas fa-video"></i> Mostrar video';
                mediaElement = audioPlayer;
                videoPlayer.pause();
                // Sincronizar tiempo
                audioPlayer.currentTime = videoPlayer.currentTime;
            }
            
            // Continuar reproducción si estaba activa
            if (!videoPlayer.paused || !audioPlayer.paused) {
                mediaElement.play();
            }
        }
        
        // Controles de reproducción
        function togglePlayPause() {
            if (mediaElement.paused) {
                mediaElement.play();
                updatePlayPauseButton(true);
            } else {
                mediaElement.pause();
                updatePlayPauseButton(false);
            }
        }
        
        function updatePlayPauseButton(playing) {
            const icon = document.getElementById('playPauseBtn').querySelector('i');
            icon.className = playing ? 'fas fa-pause' : 'fas fa-play';
        }
        
        function previousTrack() {
            if (currentTrackIndex > 0) {
                playTrack(currentTrackIndex - 1);
            } else {
                playTrack(playerConfig.playlist.length - 1);
            }
        }
        
        function nextTrack() {
            if (currentTrackIndex < playerConfig.playlist.length - 1) {
                playTrack(currentTrackIndex + 1);
            } else {
                playTrack(0);
            }
        }
        
        // Toggle playlist mejorado
        function togglePlaylist() {
            const panel = document.getElementById('playlistPanel');
            const overlay = document.getElementById('playlistOverlay');
            const isActive = panel.classList.contains('active');
            
            if (isActive) {
                // Cerrar
                panel.classList.remove('active');
                if (overlay) overlay.classList.remove('active');
                // Reanudar auto-hide de controles
                if (mediaElement && !mediaElement.paused) {
                    controlsTimer = setTimeout(hideControls, 3000);
                }
            } else {
                // Abrir
                panel.classList.add('active');
                if (overlay && window.innerWidth <= 1024) {
                    overlay.classList.add('active');
                }
                // Mantener controles visibles mientras playlist está abierta
                showControls();
                clearTimeout(controlsTimer);
            }
        }
        
        // Cerrar playlist con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && isPlaylistOpen()) {
                togglePlaylist();
            }
        });
        
        // Funciones de control adicionales
        function skipBackward() {
            mediaElement.currentTime = Math.max(0, mediaElement.currentTime - 10);
        }
        
        function skipForward() {
            mediaElement.currentTime = Math.min(mediaElement.duration, mediaElement.currentTime + 10);
        }
        
        function toggleFullscreen() {
            const container = document.querySelector('.player-main');
            
            if (!document.fullscreenElement) {
                container.requestFullscreen().catch(err => {
                    console.error('Error al entrar en pantalla completa:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }
        
        // Funciones stub para compatibilidad
        function toggleShuffle() {
            if (window.MusicPlayer) MusicPlayer.toggleShuffle();
        }
        
        function toggleRepeat() {
            if (window.MusicPlayer) MusicPlayer.toggleRepeat();
        }
        
        function toggleMute() {
            if (window.MusicPlayer) MusicPlayer.toggleMute();
        }
        
        function setVolume(event) {
            if (window.MusicPlayer) MusicPlayer.setVolume(event);
        }
        
        function seek(event) {
            if (window.MusicPlayer) MusicPlayer.seek(event);
        }
        
        // Actualizar ícono de fullscreen
        document.addEventListener('fullscreenchange', function() {
            const icon = document.getElementById('fullscreenIcon');
            if (icon) {
                if (document.fullscreenElement) {
                    icon.className = 'fas fa-compress';
                } else {
                    icon.className = 'fas fa-expand';
                }
            }
        });
        
        // Inicializar MusicPlayer
        MusicPlayer.init(playerConfig);
        
        // Hacer que playTrack sea global para MusicPlayer
        window.playTrack = playTrack;
        
        // Atajos de teclado ya están manejados en MusicPlayer.js
        
        // Mostrar controles inicialmente
        showControls();
    </script>
</body>
</html>