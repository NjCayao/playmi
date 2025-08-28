<?php
/**
 * passenger-portal/player/video-player.php
 * Reproductor de video con sistema de publicidad integrado
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';
require_once '../../admin/config/database.php';

$videoId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$companyConfig = getCompanyConfig();

// Obtener datos reales del video de la BD
$videoData = null;
$error = false;

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener información de la película
    $sql = "SELECT id, titulo, descripcion, archivo_path, duracion, thumbnail_path 
            FROM contenido 
            WHERE id = ? AND tipo = 'pelicula' AND estado = 'activo'
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$videoId]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($video) {
        $videoData = [
            'id' => $video['id'],
            'title' => $video['titulo'],
            'description' => $video['descripcion'],
            'file_path' => $video['archivo_path'],
            'duration' => $video['duracion'] ?? 0,
            'thumbnail' => $video['thumbnail_path']
        ];
    } else {
        $error = true;
        $errorMessage = "Video no encontrado";
    }
    
} catch (Exception $e) {
    $error = true;
    $errorMessage = "Error al cargar el video: " . $e->getMessage();
    error_log("Error en video-player.php: " . $e->getMessage());
}

// Si hay error, redirigir
if ($error) {
    header('Location: ../movies.php?error=' . urlencode($errorMessage));
    exit;
}

// Construir la URL completa del video
$videoUrl = CONTENT_URL . $videoData['file_path'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($videoData['title']); ?> - PLAYMI Player</title>    
    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="css/video-player.css">
</head>
<body>
    <div class="video-container">
        <!-- Botón volver (oculto por defecto) -->
        <button class="back-button" id="backButton" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
        
        <!-- Título del video -->
        <h1 class="video-title"><?php echo htmlspecialchars($videoData['title']); ?></h1>
        
        <!-- Loading spinner -->
        <div class="loading-spinner active" id="loadingSpinner"></div>
        
        <!-- Error container -->
        <div class="error-container" id="errorContainer">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="error-message" id="errorMessage">
                Error al cargar el video
            </div>
            <button class="retry-button" onclick="retryVideo()">
                Reintentar
            </button>
        </div>
        
        <!-- Video principal -->
        <video id="mainVideo" 
               preload="metadata"
               poster="<?php echo $videoData['thumbnail'] ? CONTENT_URL . $videoData['thumbnail'] : ''; ?>">
            <source src="<?php echo $videoUrl; ?>" type="video/mp4">
            Tu navegador no soporta la reproducción de video HTML5.
        </video>
        
        <!-- Botón central de play/pause -->
        <div class="center-play-container" id="centerPlayContainer">
            <button class="center-play-btn" id="centerPlayBtn" onclick="togglePlayPause()">
                <i class="fas fa-play"></i>
            </button>
        </div>
        
        <!-- Video de publicidad (oculto) -->
        <video id="adVideo" preload="none"></video>
        
        <!-- Overlay de publicidad -->
        <div class="ad-overlay" id="adOverlay">
            <h2 class="ad-message">PUBLICIDAD</h2>
            <div class="ad-countdown" id="adCountdown">30</div>
            <p style="font-size: 18px; color: #ccc;">La película continuará automáticamente</p>
            <button class="skip-button" id="skipButton" onclick="skipAd()">
                Saltar publicidad →
            </button>
        </div>
        
        <!-- Controles -->
        <div class="controls-overlay" id="controlsOverlay">
            <div class="progress-bar" id="progressBar" onclick="seek(event)">
                <div class="progress-buffered" id="progressBuffered"></div>
                <div class="progress-fill" id="progressFill">
                    <div class="progress-handle"></div>
                </div>
            </div>
            
            <div class="video-controls">
                <button class="control-btn play-pause-btn" id="playPauseBtn" onclick="togglePlayPause()">
                    <i class="fas fa-play"></i>
                </button>
                
                <button class="control-btn skip-10" onclick="VideoPlayer.skip(-10)" title="Retroceder 10 segundos">
                    <i class="fas fa-undo"></i>
                    <span>10</span>
                </button>
                
                <button class="control-btn skip-10" onclick="VideoPlayer.skip(10)" title="Adelantar 10 segundos">
                    <i class="fas fa-redo"></i>
                    <span>10</span>
                </button>
                
                <span class="time-display">
                    <span id="currentTime">0:00</span> / <span id="duration">0:00</span>
                </span>
                
                <div class="volume-control">
                    <button class="control-btn" onclick="toggleMute()">
                        <i class="fas fa-volume-up" id="volumeIcon"></i>
                    </button>
                    <div class="volume-slider" onclick="setVolume(event)">
                        <div class="volume-fill" id="volumeFill"></div>
                    </div>
                </div>
                
                <button class="control-btn" onclick="toggleFullscreen()">
                    <i class="fas fa-expand" id="fullscreenIcon"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../assets/fonts/font-awesome/css/all.min.css">
    
    <!-- JavaScript del reproductor -->
    <script src="../assets/js/video-player.js"></script>
    <script>
        // Configuración del reproductor
        const playerConfig = {
            videoId: <?php echo $videoData['id']; ?>,
            videoTitle: '<?php echo addslashes($videoData['title']); ?>',
            videoUrl: '<?php echo $videoUrl; ?>',
            companyId: <?php echo $companyConfig['company_id']; ?>,
            adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>,
            adInterval: <?php echo AD_DELAY_MINUTES; ?> * 60 * 1000,
            adDuration: <?php echo AD_DURATION_SECONDS; ?>,
            midrollEnabled: <?php echo AD_MID_MOVIE_ENABLED ? 'true' : 'false'; ?>
        };
        
        // Clase para manejar orientación
        class OrientationManager {
            constructor() {
                this.isPortrait = window.innerHeight > window.innerWidth;
                this.isMobile = this.checkMobile();
                this.userDismissed = false;
                this.rotateOverlay = null;
                
                this.init();
            }
            
            checkMobile() {
                return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) 
                    || (window.matchMedia && window.matchMedia("(max-width: 768px)").matches);
            }
            
            init() {
                if (!this.isMobile) return;
                
                // Escuchar cambios de orientación
                window.addEventListener('orientationchange', () => this.handleOrientationChange());
                window.addEventListener('resize', () => this.handleOrientationChange());
                
                // Verificar orientación inicial
                this.checkOrientation();
                
                // Intentar entrar en fullscreen al reproducir
                const video = document.getElementById('mainVideo');
                video.addEventListener('play', () => {
                    this.tryEnterFullscreen();
                }, { once: true });
            }
            
            checkOrientation() {
                this.isPortrait = window.innerHeight > window.innerWidth;
                
                if (this.isMobile && this.isPortrait && !this.userDismissed) {
                    this.showRotateOverlay();
                } else {
                    this.hideRotateOverlay();
                }
            }
            
            handleOrientationChange() {
                // Pequeño delay para que el navegador actualice las dimensiones
                setTimeout(() => {
                    this.checkOrientation();
                }, 100);
            }
            
            showRotateOverlay() {
                if (this.rotateOverlay) return;
                
                this.rotateOverlay = document.createElement('div');
                this.rotateOverlay.className = 'rotate-overlay';
                this.rotateOverlay.innerHTML = `
                    <div class="rotate-content">
                        <div class="rotate-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="white">
                                <path d="M7.34 6.41L0.86 12.9l6.49 6.48 6.49-6.48-6.5-6.49zM3.69 12.9l3.66-3.66L11 12.9l-3.66 3.66-3.65-3.66zm15.67-6.26C17.61 4.88 15.3 4 13 4V.76L8.76 5 13 9.24V6c1.79 0 3.58.68 4.95 2.05 2.73 2.73 2.73 7.17 0 9.9C16.58 19.32 14.79 20 13 20c-.97 0-1.94-.21-2.84-.61l-1.49 1.49C10.02 21.62 11.51 22 13 22c2.3 0 4.61-.88 6.36-2.64 3.52-3.51 3.52-9.21 0-12.72z"/>
                            </svg>
                        </div>
                        <h2>Gira tu dispositivo</h2>
                        <p>Para una mejor experiencia de visualización</p>
                        <div class="rotate-actions">
                            <button class="rotate-continue" onclick="orientationManager.continueInPortrait()">
                                Continuar en vertical
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(this.rotateOverlay);
                
                // Pausar el video mientras se muestra el overlay
                const video = document.getElementById('mainVideo');
                if (!video.paused) {
                    this.wasPlaying = true;
                    video.pause();
                }
            }
            
            hideRotateOverlay() {
                if (!this.rotateOverlay) return;
                
                this.rotateOverlay.remove();
                this.rotateOverlay = null;
                
                // Reanudar video si estaba reproduciéndose
                if (this.wasPlaying) {
                    const video = document.getElementById('mainVideo');
                    video.play();
                    this.wasPlaying = false;
                }
                
                // Si ahora está en landscape, intentar fullscreen
                if (!this.isPortrait) {
                    this.tryEnterFullscreen();
                }
            }
            
            continueInPortrait() {
                this.userDismissed = true;
                this.hideRotateOverlay();
            }
            
            async tryEnterFullscreen() {
                if (!this.isMobile || this.isPortrait) return;
                
                const container = document.querySelector('.video-container');
                
                try {
                    if (container.requestFullscreen) {
                        await container.requestFullscreen();
                    } else if (container.webkitRequestFullscreen) {
                        await container.webkitRequestFullscreen();
                    } else if (container.mozRequestFullScreen) {
                        await container.mozRequestFullScreen();
                    } else if (container.msRequestFullscreen) {
                        await container.msRequestFullscreen();
                    }
                    
                    // Intentar bloquear orientación después de fullscreen
                    if ('orientation' in screen && 'lock' in screen.orientation) {
                        await screen.orientation.lock('landscape').catch(() => {});
                    }
                } catch (error) {
                    console.log('Fullscreen no disponible:', error);
                }
            }
        }
        
        // Inicializar el manager de orientación cuando el DOM esté listo
        let orientationManager;
        
        document.addEventListener('DOMContentLoaded', function() {
            orientationManager = new OrientationManager();
            
            // Hacer disponible globalmente para el botón
            window.orientationManager = orientationManager;
        });
        
        // Depuración
        console.log('Video URL:', playerConfig.videoUrl);
        console.log('Video Config:', playerConfig);
        
        // Manejar errores de carga del video
        const mainVideo = document.getElementById('mainVideo');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const errorContainer = document.getElementById('errorContainer');
        
        mainVideo.addEventListener('loadstart', function() {
            loadingSpinner.classList.add('active');
            errorContainer.classList.remove('active');
        });
        
        mainVideo.addEventListener('canplay', function() {
            loadingSpinner.classList.remove('active');
            console.log('Video listo para reproducir');
        });
        
        mainVideo.addEventListener('error', function(e) {
            loadingSpinner.classList.remove('active');
            errorContainer.classList.add('active');
            
            const error = e.target.error;
            let errorMsg = 'Error al cargar el video';
            
            if (error) {
                switch(error.code) {
                    case error.MEDIA_ERR_ABORTED:
                        errorMsg = 'La carga del video fue cancelada';
                        break;
                    case error.MEDIA_ERR_NETWORK:
                        errorMsg = 'Error de red al cargar el video';
                        break;
                    case error.MEDIA_ERR_DECODE:
                        errorMsg = 'Error al decodificar el video';
                        break;
                    case error.MEDIA_ERR_SRC_NOT_SUPPORTED:
                        errorMsg = 'Formato de video no soportado o archivo no encontrado';
                        break;
                }
            }
            
            document.getElementById('errorMessage').textContent = errorMsg;
            console.error('Error al cargar el video:', errorMsg, error);
        });
        
        // Función para reintentar
        function retryVideo() {
            errorContainer.classList.remove('active');
            loadingSpinner.classList.add('active');
            mainVideo.load();
        }
        
        // Funciones adicionales para controles
        function toggleMute() {
            const video = document.getElementById('mainVideo');
            const icon = document.getElementById('volumeIcon');
            
            video.muted = !video.muted;
            
            if (video.muted) {
                icon.className = 'fas fa-volume-mute';
                document.getElementById('volumeFill').style.width = '0%';
            } else {
                icon.className = 'fas fa-volume-up';
                document.getElementById('volumeFill').style.width = (video.volume * 100) + '%';
            }
        }
        
        function setVolume(event) {
            const video = document.getElementById('mainVideo');
            const volumeSlider = event.currentTarget;
            const rect = volumeSlider.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            
            video.volume = percent;
            video.muted = false;
            
            document.getElementById('volumeFill').style.width = (percent * 100) + '%';
            
            const icon = document.getElementById('volumeIcon');
            if (percent === 0) {
                icon.className = 'fas fa-volume-mute';
            } else if (percent < 0.5) {
                icon.className = 'fas fa-volume-down';
            } else {
                icon.className = 'fas fa-volume-up';
            }
        }
        
        // Mejorar la experiencia de fullscreen
        document.addEventListener('fullscreenchange', function() {
            const icon = document.getElementById('fullscreenIcon');
            const isFullscreen = document.fullscreenElement !== null;
            
            if (icon) {
                icon.className = isFullscreen ? 'fas fa-compress' : 'fas fa-expand';
            }
            
            // Si salimos de fullscreen en móvil, verificar orientación
            if (!isFullscreen && orientationManager) {
                orientationManager.checkOrientation();
            }
        });
        
        // Manejar el botón de fullscreen mejorado
        window.toggleFullscreen = function() {
            const container = document.querySelector('.video-container');
            
            if (!document.fullscreenElement) {
                // Entrar en fullscreen
                container.requestFullscreen().then(() => {
                    // En móvil, intentar forzar landscape
                    if (orientationManager && orientationManager.isMobile) {
                        if ('orientation' in screen && 'lock' in screen.orientation) {
                            screen.orientation.lock('landscape').catch(() => {});
                        }
                    }
                }).catch(err => {
                    console.error('Error al entrar en pantalla completa:', err);
                });
            } else {
                // Salir de fullscreen
                document.exitFullscreen().then(() => {
                    // Desbloquear orientación
                    if ('orientation' in screen && 'unlock' in screen.orientation) {
                        screen.orientation.unlock();
                    }
                });
            }
        };
        
        // Manejar visibilidad de controles y botón volver
        let controlsTimer;
        const controlsOverlay = document.getElementById('controlsOverlay');
        const backButton = document.getElementById('backButton');
        const videoContainer = document.querySelector('.video-container');
        
        function showControls() {
            controlsOverlay.classList.remove('hidden');
            backButton.classList.add('visible');
            clearTimeout(controlsTimer);
            
            // Ocultar después de 3 segundos si está reproduciendo
            if (!mainVideo.paused && !VideoPlayer.isAdPlaying) {
                controlsTimer = setTimeout(() => {
                    hideControls();
                }, 3000);
            }
        }
        
        function hideControls() {
            if (!mainVideo.paused && !VideoPlayer.isAdPlaying) {
                controlsOverlay.classList.add('hidden');
                backButton.classList.remove('visible');
            }
        }
        
        // Eventos para mostrar/ocultar controles
        videoContainer.addEventListener('mousemove', showControls);
        videoContainer.addEventListener('mouseenter', showControls);
        videoContainer.addEventListener('mouseleave', () => {
            clearTimeout(controlsTimer);
            controlsTimer = setTimeout(hideControls, 1000);
        });
        
        // En dispositivos táctiles
        videoContainer.addEventListener('touchstart', showControls);
        
        // Mostrar controles cuando el video está pausado
        mainVideo.addEventListener('pause', showControls);
        mainVideo.addEventListener('play', () => {
            clearTimeout(controlsTimer);
            controlsTimer = setTimeout(hideControls, 3000);
        });
        
        // Controlar botón central de play/pause
        const centerPlayContainer = document.getElementById('centerPlayContainer');
        const centerPlayBtn = document.getElementById('centerPlayBtn');
        
        // Actualizar botón central cuando cambia el estado
        mainVideo.addEventListener('play', () => {
            centerPlayContainer.classList.add('playing');
            centerPlayBtn.querySelector('i').className = 'fas fa-pause';
        });
        
        mainVideo.addEventListener('pause', () => {
            centerPlayContainer.classList.remove('playing');
            centerPlayBtn.querySelector('i').className = 'fas fa-play';
        });
        
        // Mostrar botón central al pausar
        mainVideo.addEventListener('pause', () => {
            centerPlayContainer.classList.add('visible');
        });
        
        // Ocultar botón central al reproducir (después de un delay)
        mainVideo.addEventListener('play', () => {
            setTimeout(() => {
                if (!mainVideo.paused) {
                    centerPlayContainer.classList.remove('visible');
                }
            }, 800);
        });
        
        // Click en el video para play/pause
        mainVideo.addEventListener('click', (e) => {
            e.preventDefault();
            togglePlayPause();
        });
        
        // Mostrar botón central con los controles
        function showControlsWithCenter() {
            showControls();
            if (mainVideo.paused) {
                centerPlayContainer.classList.add('visible');
            }
        }
        
        // Modificar eventos para incluir el botón central
        videoContainer.addEventListener('mousemove', showControlsWithCenter);
        videoContainer.addEventListener('mouseenter', showControlsWithCenter);
        videoContainer.addEventListener('touchstart', showControlsWithCenter);
        
        // Inicializar reproductor
        VideoPlayer.init(playerConfig);
        
        // Marcar cuando el video ha sido iniciado por primera vez
        mainVideo.addEventListener('play', function() {
            document.querySelector('.video-container').classList.add('video-started');
        }, { once: true });
        
        // Mostrar botón central al inicio
        centerPlayContainer.classList.add('visible');
        
        // Registrar inicio de reproducción
        fetch('../api/track-usage.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'video_start',
                data: {
                    video_id: playerConfig.videoId,
                    video_title: playerConfig.videoTitle
                },
                company_id: playerConfig.companyId
            })
        });
    </script>
</body>
</html>