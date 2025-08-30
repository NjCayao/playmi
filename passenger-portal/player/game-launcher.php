<?php

/**
 * passenger-portal/player/game-launcher.php
 * Lanzador de juegos HTML5 con controles tipo gamepad para móvil
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';
require_once '../../admin/config/database.php';

$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$companyConfig = getCompanyConfig();

// Obtener datos REALES del juego de la BD
$gameData = null;
$error = false;
$errorMessage = '';

try {
    $db = Database::getInstance()->getConnection();

    // Obtener información del juego
    $sql = "SELECT id, titulo, descripcion, archivo_path, categoria, metadata 
            FROM contenido 
            WHERE id = ? AND tipo = 'juego' AND estado = 'activo'
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$gameId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($game) {
        // Extraer el nombre del archivo sin la extensión .zip
        $gameFileName = basename($game['archivo_path'], '.zip');

        // Verificar que el juego extraído existe
        $extractedDir = dirname(dirname(__DIR__)) . '/content/games/extracted/' . $gameFileName;

        if (!is_dir($extractedDir)) {
            error_log("Error: El juego no está extraído en: " . $extractedDir);
            $error = true;
            $errorMessage = "El juego no está disponible. Por favor, contacte al administrador.";
        } else {
            // Buscar el archivo HTML principal
            $htmlFile = null;
            $possibleFiles = ['index.html', 'game.html', 'main.html', 'index.htm'];

            foreach ($possibleFiles as $file) {
                if (file_exists($extractedDir . '/' . $file)) {
                    $htmlFile = $file;
                    break;
                }
            }

            if (!$htmlFile) {
                // Si no encuentra archivos HTML estándar, buscar cualquier archivo .html
                $files = glob($extractedDir . '/*.html');
                if (!empty($files)) {
                    $htmlFile = basename($files[0]);
                } else {
                    error_log("Error: No se encontró archivo HTML en: " . $extractedDir);
                    $error = true;
                    $errorMessage = "El juego no tiene un archivo principal válido.";
                }
            }

            if (!$error) {
                $metadata = json_decode($game['metadata'], true) ?? [];
                $extractedPath = 'games/extracted/' . $gameFileName . '/' . $htmlFile;

                $gameData = [
                    'id' => $game['id'],
                    'title' => $game['titulo'],
                    'category' => $game['categoria'] ?? 'arcade',
                    'description' => $game['descripcion'] ?? 'Un juego divertido para pasar el tiempo',
                    'instructions' => $metadata['instructions'] ?? 'Usa las teclas de flecha para moverte y los botones para las acciones',
                    'game_path' => $extractedPath,
                    'controls' => $metadata['controls'] ?? ['keyboard', 'mouse', 'touch']
                ];

                // Debug
                error_log("Game ZIP path: " . $game['archivo_path']);
                error_log("Game extracted path: " . $extractedPath);
                error_log("Full game URL: " . CONTENT_URL . $extractedPath);
            }
        }
    } else {
        $error = true;
        $errorMessage = "Juego no encontrado";
    }
} catch (Exception $e) {
    $error = true;
    $errorMessage = "Error al cargar el juego: " . $e->getMessage();
    error_log("Error en game-launcher.php: " . $e->getMessage());
}

// Si hay error, redirigir
if ($error) {
    header('Location: ../games.php?error=' . urlencode($errorMessage));
    exit;
}

// Construir la URL completa del juego
$gameUrl = CONTENT_URL . $gameData['game_path'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title><?php echo htmlspecialchars($gameData['title']); ?> - PLAYMI Games</title>
    <!-- Font Awesome local -->
    <link rel="stylesheet" href="../assets/fonts/font-awesome/css/all.min.css">
    <link rel="stylesheet" href="css/game-launcher.css">
</head>

<body>
    <div class="game-wrapper">
        <!-- Contenedor del juego -->
        <div class="game-container">
            <iframe
                id="gameFrame"
                src=""
                allow="autoplay; fullscreen; accelerometer; gyroscope"
                scrolling="no"
                frameborder="0">
            </iframe>
        </div>

        <!-- Header con controles del sistema -->
        <div class="game-header" id="gameHeader">
            <div class="game-title-section">
                <button class="back-button" onclick="exitGame()">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <h1 class="game-title"><?php echo htmlspecialchars($gameData['title']); ?></h1>
            </div>

            <div class="header-actions">
                <button class="action-button" onclick="togglePause()" id="pauseBtn">
                    <i class="fas fa-pause"></i>
                </button>
                <button class="action-button" onclick="toggleSound()" id="soundBtn">
                    <i class="fas fa-volume-up"></i>
                </button>
                <button class="action-button" onclick="openSettings()" id="settingsBtn">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="action-button" onclick="toggleFullscreen()" id="fullscreenBtn">
                    <i class="fas fa-expand"></i>
                </button>
            </div>
        </div>

        <!-- Controles táctiles para móvil -->
        <div class="touch-controls" id="touchControls">
            <!-- D-Pad (lado izquierdo) -->
            <div class="dpad-container">
                <div class="dpad">
                    <button class="dpad-btn dpad-up" data-key="ArrowUp">
                        <i class="fas fa-caret-up"></i>
                    </button>
                    <button class="dpad-btn dpad-right" data-key="ArrowRight">
                        <i class="fas fa-caret-right"></i>
                    </button>
                    <button class="dpad-btn dpad-down" data-key="ArrowDown">
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <button class="dpad-btn dpad-left" data-key="ArrowLeft">
                        <i class="fas fa-caret-left"></i>
                    </button>
                    <div class="dpad-center"></div>
                </div>
            </div>

            <!-- Botones de acción (lado derecho) -->
            <div class="action-buttons-container">
                <div class="action-buttons">
                    <button class="action-btn btn-triangle" data-key="v">
                        <span>△</span>
                    </button>
                    <button class="action-btn btn-circle" data-key="x">
                        <span>○</span>
                    </button>
                    <button class="action-btn btn-x" data-key="z">
                        <span>✕</span>
                    </button>
                    <button class="action-btn btn-square" data-key="c">
                        <span>□</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Pantalla de carga -->
        <div class="loading-screen" id="loadingScreen">
            <div class="loading-content">
                <div class="game-logo">
                    <i class="fas fa-gamepad"></i>
                </div>
                <div class="loading-bar">
                    <div class="loading-progress"></div>
                </div>
                <p class="loading-text">Cargando juego...</p>
            </div>
        </div>

        <!-- Pantalla de error -->
        <div class="error-screen" id="errorScreen">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 class="error-message">No se pudo cargar el juego</h2>
            <p class="error-submessage">Verifica tu conexión e intenta nuevamente</p>
            <div class="error-actions">
                <button class="error-button" onclick="retryGame()">Reintentar</button>
                <button class="error-button secondary" onclick="exitGame()">Salir</button>
            </div>
        </div>

        <!-- Modal de configuración -->
        <div class="settings-modal" id="settingsModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Configuración</h2>
                    <button class="close-modal" onclick="closeSettings()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="settings-option">
                        <button class="settings-button" onclick="restartGame()">
                            <i class="fas fa-redo"></i>
                            <span>Reiniciar juego</span>
                        </button>
                    </div>
                    <div class="settings-option">
                        <button class="settings-button" onclick="goToGameMenu()">
                            <i class="fas fa-gamepad"></i>
                            <span>Menú de juegos</span>
                        </button>
                    </div>
                    <div class="settings-option">
                        <label class="settings-label">
                            <span>Volumen</span>
                            <input type="range" id="volumeSlider" min="0" max="100" value="100" onchange="changeVolume(this.value)">
                            <span id="volumeValue">100%</span>
                        </label>
                    </div>
                    <div class="settings-option">
                        <button class="settings-button danger" onclick="exitGame()">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Salir del juego</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overlay de orientación -->
        <div class="orientation-overlay" id="orientationOverlay">
            <div class="rotate-device"></div>
            <p class="orientation-text">Gira tu dispositivo</p>
            <p class="orientation-subtext">Este juego funciona mejor en horizontal</p>
            <button class="continue-portrait-btn" onclick="continueInPortrait()">
                Continuar así
            </button>
        </div>
    </div>

    <script>
        // Variables globales
        const gameFrame = document.getElementById('gameFrame');
        const gameHeader = document.getElementById('gameHeader');
        const touchControls = document.getElementById('touchControls');
        const loadingScreen = document.getElementById('loadingScreen');
        const errorScreen = document.getElementById('errorScreen');
        const settingsModal = document.getElementById('settingsModal');
        const orientationOverlay = document.getElementById('orientationOverlay');

        let soundEnabled = true;
        let isFullscreen = false;
        let isPaused = false;
        let headerTimer = null;
        let lastInteraction = Date.now();
        let currentVolume = 100;
        let activeKeys = new Set();

        // Configuración
        const gameConfig = {
            id: <?php echo $gameData['id']; ?>,
            title: '<?php echo addslashes($gameData['title']); ?>',
            path: '<?php echo $gameUrl; ?>',
            companyId: <?php echo $companyConfig['company_id']; ?>
        };

        // Detectar dispositivo
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent) || 
                        ('ontouchstart' in window) || 
                        (window.innerWidth < 768);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

        // Inicializar
        function init() {
            console.log('Iniciando juego:', gameConfig.path);
            console.log('Es dispositivo móvil:', isMobile);

            // Agregar clase al body según dispositivo
            document.body.classList.add(isMobile ? 'is-mobile' : 'is-desktop');

            // Verificar orientación
            checkOrientation();
            window.addEventListener('orientationchange', checkOrientation);
            window.addEventListener('resize', checkOrientation);

            // Configurar controles
            setupControls();

            // Cargar juego
            loadGame();

            // Prevenir scroll y zoom
            preventDefaultGestures();

            // Auto fullscreen en móvil
            if (isMobile) {
                document.addEventListener('touchstart', tryFullscreen, { once: true });
            }
        }

        // Cargar juego
        function loadGame() {
            gameFrame.onload = () => {
                console.log('Juego cargado');
                setTimeout(() => {
                    loadingScreen.classList.add('hidden');
                    showHeader();

                    // Enviar configuración inicial
                    try {
                        gameFrame.contentWindow.postMessage({
                            type: 'init',
                            config: {
                                soundEnabled: soundEnabled,
                                isMobile: isMobile,
                                companyId: gameConfig.companyId,
                                volume: currentVolume
                            }
                        }, '*');
                    } catch (e) {
                        console.warn('No se pudo enviar mensaje al juego:', e);
                    }
                }, 1500);
            };

            gameFrame.onerror = () => {
                console.error('Error al cargar el juego');
                showError();
            };

            // Cargar el juego
            gameFrame.src = gameConfig.path;
        }

        // Verificar orientación
        function checkOrientation() {
            const isPortrait = window.innerHeight > window.innerWidth;

            if (isMobile && isPortrait && !isFullscreen) {
                orientationOverlay.classList.add('show');
            } else {
                orientationOverlay.classList.remove('show');
            }
        }

        // Continuar en portrait
        function continueInPortrait() {
            orientationOverlay.classList.remove('show');
        }

        // Configurar controles
        function setupControls() {
            if (isMobile) {
                // Configurar controles táctiles
                setupTouchControls();
            } else {
                // Configurar controles de teclado
                setupKeyboardControls();
            }

            // Auto-ocultar header
            document.addEventListener('touchstart', showHeader);
            document.addEventListener('mousemove', showHeader);
            document.addEventListener('click', showHeader);

            // Detectar inactividad
            setInterval(checkInactivity, 1000);

            // Escuchar mensajes del juego
            window.addEventListener('message', handleGameMessage);
        }

        // Configurar controles táctiles
        function setupTouchControls() {
            // D-Pad
            const dpadButtons = document.querySelectorAll('.dpad-btn');
            dpadButtons.forEach(btn => {
                btn.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyDown(key);
                    btn.classList.add('active');
                    vibrate(10);
                });

                btn.addEventListener('touchend', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyUp(key);
                    btn.classList.remove('active');
                });

                btn.addEventListener('touchcancel', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyUp(key);
                    btn.classList.remove('active');
                });
            });

            // Botones de acción
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(btn => {
                btn.addEventListener('touchstart', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyDown(key);
                    btn.classList.add('active');
                    vibrate(10);
                });

                btn.addEventListener('touchend', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyUp(key);
                    btn.classList.remove('active');
                });

                btn.addEventListener('touchcancel', (e) => {
                    e.preventDefault();
                    const key = btn.dataset.key;
                    sendKeyUp(key);
                    btn.classList.remove('active');
                });
            });
        }

        // Configurar controles de teclado
        function setupKeyboardControls() {
            document.addEventListener('keydown', (e) => {
                if (!activeKeys.has(e.key)) {
                    activeKeys.add(e.key);
                    sendKeyDown(e.key);
                }
            });

            document.addEventListener('keyup', (e) => {
                activeKeys.delete(e.key);
                sendKeyUp(e.key);
            });
        }

        // Enviar tecla presionada
        function sendKeyDown(key) {
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'keydown',
                    key: key
                }, '*');
            } catch (e) {
                console.error('Error enviando keydown:', e);
            }
        }

        // Enviar tecla liberada
        function sendKeyUp(key) {
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'keyup',
                    key: key
                }, '*');
            } catch (e) {
                console.error('Error enviando keyup:', e);
            }
        }

        // Vibración táctil
        function vibrate(duration) {
            if ('vibrate' in navigator) {
                navigator.vibrate(duration);
            }
        }

        // Mostrar header
        function showHeader() {
            lastInteraction = Date.now();
            gameHeader.classList.add('visible');

            clearTimeout(headerTimer);
            headerTimer = setTimeout(hideHeader, 3000);
        }

        // Ocultar header
        function hideHeader() {
            if (!isPaused && Date.now() - lastInteraction > 2500) {
                gameHeader.classList.remove('visible');
            }
        }

        // Verificar inactividad
        function checkInactivity() {
            if (!isPaused && Date.now() - lastInteraction > 3000) {
                hideHeader();
            }
        }

        // Toggle pausa
        function togglePause() {
            isPaused = !isPaused;
            const icon = document.querySelector('#pauseBtn i');
            
            if (isPaused) {
                icon.className = 'fas fa-play';
                sendPause();
            } else {
                icon.className = 'fas fa-pause';
                sendResume();
            }
            
            showHeader();
        }

        // Enviar pausa
        function sendPause() {
            try {
                gameFrame.contentWindow.postMessage({ type: 'pause' }, '*');
            } catch (e) {}
        }

        // Enviar reanudar
        function sendResume() {
            try {
                gameFrame.contentWindow.postMessage({ type: 'resume' }, '*');
            } catch (e) {}
        }

        // Reiniciar juego
        function restartGame() {
            closeSettings();
            loadingScreen.classList.remove('hidden');
            
            gameFrame.src = '';
            setTimeout(() => {
                gameFrame.src = gameConfig.path;
            }, 100);
        }

        // Ir al menú de juegos
        function goToGameMenu() {
            if (confirm('¿Salir al menú de juegos?')) {
                window.location.href = '../games.php';
            }
        }

        // Toggle sonido
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const icon = document.querySelector('#soundBtn i');
            icon.className = soundEnabled ? 'fas fa-volume-up' : 'fas fa-volume-mute';

            try {
                gameFrame.contentWindow.postMessage({
                    type: 'sound',
                    enabled: soundEnabled
                }, '*');
            } catch (e) {}
        }

        // Cambiar volumen
        function changeVolume(value) {
            currentVolume = value;
            document.getElementById('volumeValue').textContent = value + '%';
            
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'volume',
                    value: value / 100
                }, '*');
            } catch (e) {}
        }

        // Abrir configuración
        function openSettings() {
            settingsModal.classList.add('active');
            if (!isPaused) togglePause();
        }

        // Cerrar configuración
        function closeSettings() {
            settingsModal.classList.remove('active');
        }

        // Toggle fullscreen
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                tryFullscreen();
            } else {
                exitFullscreen();
            }
        }

        // Intentar fullscreen
        function tryFullscreen() {
            const wrapper = document.querySelector('.game-wrapper');
            const promise = wrapper.requestFullscreen ||
                            wrapper.webkitRequestFullscreen ||
                            wrapper.mozRequestFullScreen ||
                            wrapper.msRequestFullscreen;

            if (promise) {
                promise.call(wrapper).then(() => {
                    isFullscreen = true;
                    document.querySelector('#fullscreenBtn i').className = 'fas fa-compress';
                    checkOrientation();
                }).catch(err => {
                    console.log('No se pudo activar pantalla completa');
                });
            }
        }

        // Salir de fullscreen
        function exitFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }

            isFullscreen = false;
            document.querySelector('#fullscreenBtn i').className = 'fas fa-expand';
        }

        // Salir del juego
        function exitGame() {
            if (confirm('¿Salir del juego?')) {
                if (document.fullscreenElement) {
                    exitFullscreen();
                }
                window.location.href = '../games.php';
            }
        }

        // Mostrar error
        function showError() {
            loadingScreen.classList.add('hidden');
            errorScreen.classList.add('active');
        }

        // Reintentar
        function retryGame() {
            errorScreen.classList.remove('active');
            loadingScreen.classList.remove('hidden');
            loadGame();
        }

        // Manejar mensajes del juego
        function handleGameMessage(event) {
            const data = event.data;

            switch (data.type) {
                case 'gameOver':
                    handleGameOver(data);
                    break;
                case 'score':
                    console.log('Puntuación:', data.score);
                    break;
                case 'requestPause':
                    if (!isPaused) togglePause();
                    break;
            }
        }

        // Manejar fin del juego
        function handleGameOver(data) {
            if (confirm(`¡Juego terminado!\nPuntuación: ${data.score || 0}\n\n¿Jugar de nuevo?`)) {
                restartGame();
            }
        }

        // Prevenir gestos por defecto
        function preventDefaultGestures() {
            // Prevenir scroll
            document.addEventListener('touchmove', (e) => {
                if (!e.target.closest('.modal-content')) {
                    e.preventDefault();
                }
            }, { passive: false });

            // Prevenir zoom con doble tap
            let lastTouchEnd = 0;
            document.addEventListener('touchend', (e) => {
                const now = Date.now();
                if (now - lastTouchEnd <= 300) {
                    e.preventDefault();
                }
                lastTouchEnd = now;
            }, false);

            // Prevenir menú contextual
            document.addEventListener('contextmenu', (e) => {
                e.preventDefault();
            });
        }

        // Detectar cambios de fullscreen
        document.addEventListener('fullscreenchange', () => {
            isFullscreen = !!document.fullscreenElement;
            checkOrientation();
        });

        // Manejar visibilidad
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !isPaused) {
                togglePause();
            }
        });

        // Iniciar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    </script>
</body>

</html>