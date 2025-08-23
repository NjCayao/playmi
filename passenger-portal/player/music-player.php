<?php
/**
 * passenger-portal/player/music-player.php
 * Reproductor de música con visualizador y publicidad
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';

$musicId = $_GET['id'] ?? 0;
$playlistId = $_GET['playlist'] ?? null;
$companyConfig = getCompanyConfig();

// Simular datos de música
$musicData = [
    'id' => $musicId,
    'title' => 'Canción de ejemplo',
    'artist' => 'Artista Demo',
    'album' => 'Álbum de viaje',
    'file_path' => 'music/example.mp3',
    'duration' => 240,
    'cover_path' => 'thumbnails/album-cover.jpg'
];

// Simular playlist
$playlist = [
    ['id' => 1, 'title' => 'Canción 1', 'artist' => 'Artista 1', 'duration' => 180],
    ['id' => 2, 'title' => 'Canción 2', 'artist' => 'Artista 2', 'duration' => 210],
    ['id' => 3, 'title' => 'Canción 3', 'artist' => 'Artista 3', 'duration' => 195]
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($musicData['title']); ?> - Music Player</title>
    
    <link rel="stylesheet" href="../assets/css/player.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #0a0a0a;
            color: white;
            font-family: Arial, sans-serif;
            overflow-x: hidden;
        }
        
        .music-player-container {
            display: flex;
            height: 100vh;
        }
        
        /* Panel principal */
        .player-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: radial-gradient(circle at center, #1a1a2a 0%, #0a0a0a 100%);
        }
        
        /* Visualizador */
        .visualizer-container {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 0;
            opacity: 0.5;
        }
        
        #visualizer {
            width: 100%;
            height: 100%;
        }
        
        /* Contenido del reproductor */
        .player-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .album-artwork {
            width: 300px;
            height: 300px;
            margin: 0 auto 2rem;
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.8);
            animation: rotate 20s linear infinite paused;
        }
        
        .album-artwork.playing {
            animation-play-state: running;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .track-info {
            margin-bottom: 2rem;
        }
        
        .track-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .track-artist {
            font-size: 1.25rem;
            color: #b3b3b3;
        }
        
        /* Controles */
        .player-controls {
            background: rgba(0,0,0,0.8);
            padding: 2rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }
        
        .progress-container {
            margin-bottom: 2rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,0.2);
            border-radius: 3px;
            cursor: pointer;
            position: relative;
            margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--company-primary, #1db954);
            border-radius: 3px;
            width: 0%;
            position: relative;
        }
        
        .progress-handle {
            position: absolute;
            right: -6px;
            top: -3px;
            width: 12px;
            height: 12px;
            background: white;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .progress-container:hover .progress-handle {
            opacity: 1;
        }
        
        .time-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #b3b3b3;
        }
        
        .control-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .control-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            padding: 0.5rem;
        }
        
        .control-btn:hover {
            color: var(--company-primary, #1db954);
            transform: scale(1.1);
        }
        
        .play-pause-btn {
            width: 64px;
            height: 64px;
            background: white;
            color: black;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .play-pause-btn:hover {
            transform: scale(1.1);
        }
        
        /* Volumen */
        .volume-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .volume-slider {
            width: 100px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        
        .volume-fill {
            height: 100%;
            background: white;
            border-radius: 2px;
            width: 70%;
        }
        
        /* Playlist */
        .playlist-panel {
            width: 350px;
            background: #121212;
            overflow-y: auto;
            padding: 2rem 0;
        }
        
        .playlist-header {
            padding: 0 2rem 1rem;
            border-bottom: 1px solid #282828;
        }
        
        .playlist-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .playlist-items {
            padding: 1rem 0;
        }
        
        .playlist-item {
            padding: 0.75rem 2rem;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .playlist-item:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .playlist-item.active {
            background: rgba(var(--company-primary-rgb, 29, 185, 84), 0.2);
            color: var(--company-primary, #1db954);
        }
        
        .playlist-item-info {
            overflow: hidden;
            flex: 1;
        }
        
        .playlist-item-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .playlist-item-artist {
            font-size: 0.875rem;
            color: #b3b3b3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .playlist-item-duration {
            color: #b3b3b3;
            font-size: 0.875rem;
        }
        
        /* Botón volver */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.5);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            z-index: 10;
            transition: background 0.2s;
        }
        
        .back-button:hover {
            background: rgba(0,0,0,0.8);
        }
        
        /* Overlay de publicidad */
        .ad-overlay {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.95);
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            color: white;
            z-index: 20;
            display: none;
        }
        
        .ad-overlay.active {
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .music-player-container {
                flex-direction: column;
            }
            
            .playlist-panel {
                width: 100%;
                height: 40%;
                order: 2;
            }
            
            .player-main {
                order: 1;
                padding: 1rem;
            }
            
            .album-artwork {
                width: 200px;
                height: 200px;
            }
            
            .track-title {
                font-size: 1.5rem;
            }
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <button class="back-button" onclick="window.history.back()">
        ← Volver
    </button>
    
    <div class="music-player-container">
        <!-- Panel principal del reproductor -->
        <div class="player-main">
            <!-- Visualizador de audio -->
            <div class="visualizer-container">
                <canvas id="visualizer"></canvas>
            </div>
            
            <!-- Contenido del reproductor -->
            <div class="player-content">
                <img src="<?php echo CONTENT_URL . $musicData['cover_path']; ?>" 
                     alt="Album artwork" 
                     class="album-artwork" 
                     id="albumArt">
                
                <div class="track-info">
                    <h1 class="track-title" id="trackTitle"><?php echo htmlspecialchars($musicData['title']); ?></h1>
                    <p class="track-artist" id="trackArtist"><?php echo htmlspecialchars($musicData['artist']); ?></p>
                </div>
                
                <div class="player-controls">
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
                        <button class="control-btn" onclick="toggleShuffle()" id="shuffleBtn">
                            <i class="fas fa-random" style="font-size: 20px"></i>
                        </button>
                        
                        <button class="control-btn" onclick="previousTrack()">
                            <i class="fas fa-backward" style="font-size: 24px"></i>
                        </button>
                        
                        <button class="control-btn play-pause-btn" onclick="togglePlayPause()" id="playPauseBtn">
                            <i class="fas fa-play"></i>
                        </button>
                        
                        <button class="control-btn" onclick="nextTrack()">
                            <i class="fas fa-forward" style="font-size: 24px"></i>
                        </button>
                        
                        <button class="control-btn" onclick="toggleRepeat()" id="repeatBtn">
                            <i class="fas fa-redo" style="font-size: 20px"></i>
                        </button>
                    </div>
                    
                    <div class="volume-container">
                        <i class="fas fa-volume-up"></i>
                        <div class="volume-slider" id="volumeSlider" onclick="setVolume(event)">
                            <div class="volume-fill" id="volumeFill"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Panel de playlist -->
        <div class="playlist-panel">
            <div class="playlist-header">
                <h3>Lista de reproducción</h3>
            </div>
            <div class="playlist-items" id="playlistItems">
                <?php foreach ($playlist as $index => $song): ?>
                <div class="playlist-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                     onclick="playTrack(<?php echo $index; ?>)"
                     data-id="<?php echo $song['id']; ?>">
                    <div class="playlist-item-info">
                        <div class="playlist-item-title"><?php echo htmlspecialchars($song['title']); ?></div>
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
    
    <!-- Audio element -->
    <audio id="audioPlayer" src="<?php echo CONTENT_URL . $musicData['file_path']; ?>"></audio>
    
    <!-- Overlay de publicidad -->
    <div class="ad-overlay" id="adOverlay">
        <h2>PUBLICIDAD</h2>
        <div class="ad-countdown" id="adCountdown" style="font-size: 48px; color: var(--company-primary); margin: 20px 0;">30</div>
        <p>La música continuará automáticamente</p>
        <button class="skip-button" id="skipButton" onclick="skipAd()" style="display: none; margin-top: 20px;">
            Saltar publicidad
        </button>
    </div>
    
    <!-- JavaScript del reproductor -->
    <script src="../assets/js/music-player.js"></script>
    <script>
        // Configuración
        const playerConfig = {
            musicId: <?php echo $musicId; ?>,
            companyId: <?php echo $companyConfig['company_id']; ?>,
            adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>,
            adInterval: <?php echo AD_DELAY_MINUTES; ?> * 60 * 1000,
            playlist: <?php echo json_encode($playlist); ?>
        };
        
        // Inicializar reproductor
        MusicPlayer.init(playerConfig);
    </script>
</body>
</html>