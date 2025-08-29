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
                LIMIT 100";

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
    <link rel="stylesheet" href="css/music-player.css">

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

                <!-- Botón central de play/pause -->
                <div class="center-play-button paused" id="centerPlayButton">
                    <button class="center-play-btn" onclick="togglePlayPause()">
                        <i class="fas fa-play" id="centerPlayIcon"></i>
                    </button>
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
                <input type="text"
                    id="playlistSearch"
                    placeholder="Buscar en playlist..."
                    class="playlist-search"
                    onkeyup="searchPlaylist()">
            </div>
            <div class="playlist-filters">
                <button class="filter-chip active" onclick="filterPlaylist('all')">Todos</button>
                <button class="filter-chip" onclick="filterPlaylist('video')">Videos</button>
                <button class="filter-chip" onclick="filterPlaylist('audio')">Audio</button>
            </div>
            <div class="playlist-items" id="playlistItems">
                <?php foreach ($playlist as $index => $song): ?>
                    <div class="playlist-item <?php echo $index === 0 ? 'active' : ''; ?>"
                        data-id="<?php echo $song['id']; ?>"
                        data-index="<?php echo $index; ?>"
                        data-type="<?php echo $song['is_video'] ? 'video' : 'audio'; ?>"
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
            <div class="playlist-pagination">
                <button onclick="changePage(-1)" id="prevPageBtn">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span id="pageInfo">1 / 1</span>
                <button onclick="changePage(1)" id="nextPageBtn">
                    <i class="fas fa-chevron-right"></i>
                </button>
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
        let currentFilter = 'all';

        // Paginación
        const ITEMS_PER_PAGE = 20;
        let currentPage = 1;
        let filteredItems = [];

        // Elementos del DOM
        const audioPlayer = document.getElementById('audioPlayer');
        let videoPlayer = document.getElementById('musicVideo');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const playerControls = document.getElementById('playerControls');
        const backButton = document.getElementById('backButton');
        const mediaToggle = document.getElementById('mediaToggle');

        // Configurar elemento de media inicial
        setupMediaElement();

        function setupMediaElement() {
            if (isVideo && videoPlayer) {
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
                    loadingSpinner.classList.remove('active');
                });

            } else {
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
            currentTrackIndex = index;
            const track = playerConfig.playlist[index];

            if (!track) {
                console.error('Track not found:', index);
                return;
            }

            // Actualizar UI
            document.getElementById('currentTitle').textContent = track.title;
            document.getElementById('currentArtist').textContent = track.artist;
            document.getElementById('albumArt').src = '/playmi/content/' + track.cover;

            // Actualizar playlist activa
            document.querySelectorAll('.playlist-item').forEach((item, i) => {
                item.classList.toggle('active', i === index);
            });

            // Determinar si es video
            isVideo = track.is_video;
            const mediaUrl = '/playmi/content/' + track.file_path;

            // Pausar cualquier reproducción actual
            if (audioPlayer && !audioPlayer.paused) audioPlayer.pause();
            if (videoPlayer && !videoPlayer.paused) videoPlayer.pause();

            // Configurar media apropiada
            if (isVideo) {
                // Es video
                if (!videoPlayer) {
                    location.reload();
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

                mediaElement = videoPlayer;
                showingVideo = true;

                // Actualizar MusicPlayer para usar video
                MusicPlayer.switchMedia(true);
                MusicPlayer.currentTrackIndex = currentTrackIndex;

                // Cargar y reproducir
                videoPlayer.load();
                videoPlayer.play().catch(e => {
                    console.error('Error playing video:', e);
                });

            } else {
                // Es audio MP3
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

            // Scroll a la canción actual si está fuera de vista
            scrollToCurrentTrack();
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
            if (e.target.closest('button') || e.target.closest('.player-controls-bar')) {
                return;
            }
            showControls();
            clearTimeout(touchTimer);
            touchTimer = setTimeout(hideControls, 3000);
        });

        // Click para play/pause solo en el área del video/visualizador
        playerMain.addEventListener('click', function(e) {
            if (e.target === playerMain || e.target.closest('.media-container')) {
                if (!e.target.closest('button') && !e.target.closest('.player-controls-bar')) {
                    togglePlayPause();
                    showControls();
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
            // Actualizar botón de la barra de controles
            const icon = document.getElementById('playPauseBtn').querySelector('i');
            icon.className = playing ? 'fas fa-pause' : 'fas fa-play';

            // Actualizar botón central
            const centerButton = document.getElementById('centerPlayButton');
            const centerIcon = document.getElementById('centerPlayIcon');

            if (centerButton && centerIcon) {
                if (playing) {
                    centerButton.classList.add('playing');
                    centerButton.classList.remove('paused');
                    centerIcon.className = 'fas fa-pause';
                } else {
                    centerButton.classList.remove('playing');
                    centerButton.classList.add('paused');
                    centerIcon.className = 'fas fa-play';
                }
            }
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
                // Scroll a canción actual
                setTimeout(scrollToCurrentTrack, 100);
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

        // Paginación de playlist
        function paginatePlaylist() {
            const items = document.querySelectorAll('.playlist-item');
            const start = (currentPage - 1) * ITEMS_PER_PAGE;
            const end = start + ITEMS_PER_PAGE;

            // Array para items visibles según filtros
            let visibleItems = [];

            items.forEach((item) => {
                const title = item.querySelector('.playlist-item-title').textContent.toLowerCase();
                const artist = item.querySelector('.playlist-item-artist').textContent.toLowerCase();
                const type = item.dataset.type;

                // Verificar si cumple con el filtro de tipo
                const matchesType = currentFilter === 'all' || type === currentFilter;

                // Verificar si cumple con la búsqueda
                const matchesSearch = searchQuery === '' ||
                    title.includes(searchQuery) ||
                    artist.includes(searchQuery);

                if (matchesType && matchesSearch) {
                    visibleItems.push(item);
                }

                // Ocultar todos inicialmente
                item.style.display = 'none';
            });

            // Mostrar solo los de la página actual
            for (let i = start; i < end && i < visibleItems.length; i++) {
                visibleItems[i].style.display = 'flex';
            }

            updatePaginationControls(visibleItems.length);
        }

        function updatePaginationControls(totalVisible) {
            const totalPages = Math.ceil(totalVisible / ITEMS_PER_PAGE);
            document.getElementById('pageInfo').textContent = `${currentPage} / ${totalPages}`;
            document.getElementById('prevPageBtn').disabled = currentPage === 1;
            document.getElementById('nextPageBtn').disabled = currentPage === totalPages || totalPages === 0;
        }

        function changePage(direction) {
            currentPage += direction;
            paginatePlaylist();
        }

        // Variable para almacenar los items filtrados
        let searchQuery = '';

        // Buscar en playlist
        function searchPlaylist() {
            searchQuery = document.getElementById('playlistSearch').value.toLowerCase();
            currentPage = 1; // Reset a primera página
            paginatePlaylist();
        }

        // Filtrar playlist
        function filterPlaylist(type) {
            currentFilter = type;
            currentPage = 1;

            // Actualizar botones
            document.querySelectorAll('.filter-chip').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            paginatePlaylist();
        }

        // Scroll a canción actual
        function scrollToCurrentTrack() {
            const activeItem = document.querySelector('.playlist-item.active');
            if (activeItem && activeItem.style.display !== 'none') {
                activeItem.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        // Inicializar MusicPlayer
        MusicPlayer.init(playerConfig);

        // Hacer que playTrack sea global para MusicPlayer
        window.playTrack = playTrack;

        // Mostrar controles inicialmente
        showControls();

        // Inicializar paginación
        paginatePlaylist();
    </script>
</body>

</html>