<?php
/**
 * passenger-portal/player/video-player.php
 * Reproductor de video con sistema de publicidad integrado
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';

$videoId = $_GET['id'] ?? 0;
$companyConfig = getCompanyConfig();

// Aquí obtendrías los datos del video de la BD
$videoData = [
    'id' => $videoId,
    'title' => 'Película de ejemplo',
    'file_path' => 'movies/example.mp4',
    'duration' => 7200 // 2 horas en segundos
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($videoData['title']); ?> - Player</title>
    
    <link rel="stylesheet" href="../assets/css/player.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            overflow: hidden;
        }
        
        .video-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #mainVideo, #adVideo {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        #adVideo {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
        }
        
        .controls-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);
            transition: opacity 0.3s;
        }
        
        .controls-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .video-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .play-pause-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 10px;
        }
        
        .progress-bar {
            flex: 1;
            height: 4px;
            background: rgba(255,255,255,0.3);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--company-primary, #e50914);
            border-radius: 2px;
            width: 0%;
        }
        
        .time-display {
            color: white;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }
        
        /* Overlay de publicidad */
        .ad-overlay {
            position: absolute;
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
        
        .ad-countdown {
            font-size: 48px;
            font-weight: bold;
            margin: 20px 0;
            color: var(--company-primary, #e50914);
        }
        
        .ad-message {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .skip-button {
            display: none;
            margin-top: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border: 1px solid white;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .skip-button:hover {
            background: rgba(255,255,255,0.3);
        }
        
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
            z-index: 30;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <!-- Botón volver -->
        <button class="back-button" onclick="window.history.back()">
            ← Volver
        </button>
        
        <!-- Video principal -->
        <video id="mainVideo" src="<?php echo CONTENT_URL . $videoData['file_path']; ?>"></video>
        
        <!-- Video de publicidad (oculto) -->
        <video id="adVideo"></video>
        
        <!-- Overlay de publicidad -->
        <div class="ad-overlay" id="adOverlay">
            <h2 class="ad-message">PUBLICIDAD</h2>
            <div class="ad-countdown" id="adCountdown">30</div>
            <p>La película continuará automáticamente</p>
            <button class="skip-button" id="skipButton" onclick="skipAd()">Saltar publicidad</button>
        </div>
        
        <!-- Controles -->
        <div class="controls-overlay" id="controlsOverlay">
            <div class="video-controls">
                <button class="play-pause-btn" id="playPauseBtn" onclick="togglePlayPause()">
                    <i class="fas fa-play"></i>
                </button>
                
                <div class="progress-bar" id="progressBar" onclick="seek(event)">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <span class="time-display">
                    <span id="currentTime">0:00</span> / <span id="duration">0:00</span>
                </span>
                
                <button class="fullscreen-btn" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- JavaScript del reproductor -->
    <script src="../assets/js/video-player.js"></script>
    <script>
        // Configuración del reproductor
        const playerConfig = {
            videoId: <?php echo $videoId; ?>,
            companyId: <?php echo $companyConfig['company_id']; ?>,
            adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>,
            adInterval: <?php echo AD_DELAY_MINUTES; ?> * 60 * 1000, // Convertir a milisegundos
            adDuration: <?php echo AD_DURATION_SECONDS; ?>,
            midrollEnabled: <?php echo AD_MID_MOVIE_ENABLED ? 'true' : 'false'; ?>
        };
        
        // Inicializar reproductor
        VideoPlayer.init(playerConfig);
    </script>
</body>
</html>