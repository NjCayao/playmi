<?php
/**
 * passenger-portal/player/game-launcher.php
 * Lanzador de juegos HTML5 con sandbox seguro
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
                    'instructions' => $metadata['instructions'] ?? 'Usa las teclas de flecha para moverte y la barra espaciadora para disparar',
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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            position: fixed;
            background: #000;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Netflix Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: white;
            touch-action: none;
        }
        
        /* Contenedor principal */
        .game-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #000;
            display: flex;
            flex-direction: column;
        }
        
        /* Overlay de controles estilo Netflix */
        .controls-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100;
            background: linear-gradient(to bottom, 
                rgba(0,0,0,0.8) 0%, 
                rgba(0,0,0,0) 20%, 
                rgba(0,0,0,0) 80%, 
                rgba(0,0,0,0.8) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .controls-overlay.visible {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* Header minimalista */
        .game-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            padding: env(safe-area-inset-top, 15px) 15px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 101;
        }
        
        .game-title-section {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .back-button {
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
        }
        
        .game-title {
            font-size: 16px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 150px;
        }
        
        .header-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-button {
            width: 36px;
            height: 36px;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50%;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .action-button:active {
            transform: scale(0.9);
            background: rgba(255,255,255,0.2);
        }
        
        /* Contenedor del juego */
        .game-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
        }
        
        #gameFrame {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
        }
        
        /* Controles de juego - Bottom */
        .game-controls {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px;
            padding-bottom: env(safe-area-inset-bottom, 15px);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            z-index: 101;
        }
        
        .control-button {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 50%;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .control-button.primary {
            width: 80px;
            height: 80px;
            background: var(--company-primary, #e50914);
            border-color: var(--company-primary, #e50914);
            font-size: 24px;
        }
        
        .control-button:active {
            transform: scale(0.9);
            background: rgba(255,255,255,0.3);
        }
        
        .control-button.primary:active {
            background: var(--company-primary, #e50914);
            opacity: 0.8;
        }
        
        .control-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        
        /* Pantalla de carga estilo Netflix */
        .loading-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 200;
        }
        
        .loading-screen.hidden {
            display: none;
        }
        
        .loading-content {
            text-align: center;
        }
        
        .game-logo {
            width: 80px;
            height: 80px;
            background: var(--company-primary, #e50914);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        
        .loading-bar {
            width: 200px;
            height: 3px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
            margin: 20px auto;
        }
        
        .loading-progress {
            height: 100%;
            background: var(--company-primary, #e50914);
            border-radius: 3px;
            animation: loading 2s ease-in-out infinite;
        }
        
        @keyframes loading {
            0% { width: 0%; }
            50% { width: 80%; }
            100% { width: 100%; }
        }
        
        .loading-text {
            font-size: 14px;
            color: #999;
        }
        
        /* Error screen estilo Netflix */
        .error-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 200;
        }
        
        .error-screen.active {
            display: flex;
        }
        
        .error-icon {
            font-size: 60px;
            color: var(--company-primary, #e50914);
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 18px;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .error-submessage {
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
        }
        
        .error-button {
            padding: 12px 24px;
            background: var(--company-primary, #e50914);
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .error-button.secondary {
            background: transparent;
            border: 1px solid #666;
        }
        
        /* Modal de pausa */
        .pause-modal {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 150;
        }
        
        .pause-modal.active {
            display: flex;
        }
        
        .pause-content {
            text-align: center;
            padding: 30px;
        }
        
        .pause-icon {
            font-size: 60px;
            color: white;
            margin-bottom: 20px;
        }
        
        .pause-title {
            font-size: 24px;
            margin-bottom: 30px;
        }
        
        .pause-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 250px;
            margin: 0 auto;
        }
        
        .pause-button {
            padding: 15px 30px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .pause-button.primary {
            background: var(--company-primary, #e50914);
            border-color: var(--company-primary, #e50914);
        }
        
        .pause-button:active {
            transform: scale(0.95);
        }
        
        /* Indicador táctil */
        .touch-indicator {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 60px;
            color: white;
            opacity: 0;
            pointer-events: none;
            z-index: 110;
        }
        
        .touch-indicator.show {
            animation: touchFeedback 0.6s ease-out;
        }
        
        @keyframes touchFeedback {
            0% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(0.5);
            }
            50% {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1.2);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(1.5);
            }
        }
        
        /* Orientación - Mensaje mejorado */
        .orientation-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 300;
            padding: 20px;
        }
        
        .orientation-overlay.show {
            display: flex;
        }
        
        .rotate-device {
            width: 100px;
            height: 60px;
            border: 3px solid var(--company-primary, #e50914);
            border-radius: 10px;
            position: relative;
            animation: rotateDevice 2s ease-in-out infinite;
            margin-bottom: 30px;
        }
        
        .rotate-device::after {
            content: '';
            position: absolute;
            width: 10px;
            height: 10px;
            background: var(--company-primary, #e50914);
            border-radius: 50%;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        @keyframes rotateDevice {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(90deg); }
        }
        
        .orientation-text {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .orientation-subtext {
            font-size: 14px;
            color: #999;
            margin-bottom: 20px;
        }
        
        .continue-portrait-btn {
            padding: 10px 20px;
            background: transparent;
            border: 1px solid #666;
            border-radius: 4px;
            color: white;
            font-size: 14px;
            cursor: pointer;
        }
        
        /* Ajustes responsive */
        @media screen and (orientation: landscape) {
            .orientation-overlay {
                display: none !important;
            }
            
            .game-controls {
                flex-direction: row;
                padding: 10px 20px;
            }
            
            .control-button {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }
            
            .control-button.primary {
                width: 60px;
                height: 60px;
                font-size: 20px;
            }
        }
        
        @media screen and (min-height: 812px) {
            /* iPhone X y posteriores */
            .game-header {
                padding-top: max(env(safe-area-inset-top), 20px);
            }
            
            .game-controls {
                padding-bottom: max(env(safe-area-inset-bottom), 20px);
            }
        }
        
        /* Desactivar selección y zoom */
        * {
            -webkit-touch-callout: none;
            -webkit-text-size-adjust: 100%;
        }
        
        input, textarea, select {
            font-size: 16px !important;
        }
    </style>
    
    <!-- Font Awesome local -->
    <link rel="stylesheet" href="../assets/fonts/font-awesome/css/all.min.css">
</head>
<body>
    <div class="game-wrapper">
        <!-- Contenedor del juego -->
        <div class="game-container">
            <iframe 
                id="gameFrame"
                src=""
                sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-forms allow-orientation-lock"
                allow="autoplay; fullscreen; accelerometer; gyroscope"
                scrolling="no"
                frameborder="0">
            </iframe>
        </div>
        
        <!-- Overlay de controles -->
        <div class="controls-overlay" id="controlsOverlay">
            <!-- Header -->
            <div class="game-header">
                <div class="game-title-section">
                    <button class="back-button" onclick="exitGame()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <h1 class="game-title"><?php echo htmlspecialchars($gameData['title']); ?></h1>
                </div>
                
                <div class="header-actions">
                    <button class="action-button" onclick="toggleSound()" id="soundBtn">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <button class="action-button" onclick="toggleFullscreen()" id="fullscreenBtn">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            
            <!-- Controles del juego -->
            <div class="game-controls">
                <button class="control-button" onclick="gameAction('left')">
                    <i class="fas fa-chevron-left"></i>
                    <span class="control-label">Izq</span>
                </button>
                
                <button class="control-button primary" onclick="gameAction('action')">
                    <i class="fas fa-hand-pointer"></i>
                    <span class="control-label">Acción</span>
                </button>
                
                <button class="control-button" onclick="gameAction('right')">
                    <i class="fas fa-chevron-right"></i>
                    <span class="control-label">Der</span>
                </button>
                
                <button class="control-button" onclick="pauseGame()">
                    <i class="fas fa-pause"></i>
                    <span class="control-label">Pausa</span>
                </button>
            </div>
        </div>
        
        <!-- Indicador táctil -->
        <div class="touch-indicator" id="touchIndicator">
            <i class="fas fa-hand-pointer"></i>
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
        
        <!-- Modal de pausa -->
        <div class="pause-modal" id="pauseModal">
            <div class="pause-content">
                <div class="pause-icon">
                    <i class="fas fa-pause-circle"></i>
                </div>
                <h2 class="pause-title">Juego en pausa</h2>
                <div class="pause-actions">
                    <button class="pause-button primary" onclick="resumeGame()">
                        Continuar jugando
                    </button>
                    <button class="pause-button" onclick="restartGame()">
                        Reiniciar juego
                    </button>
                    <button class="pause-button" onclick="exitGame()">
                        Salir al menú
                    </button>
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
        const controlsOverlay = document.getElementById('controlsOverlay');
        const loadingScreen = document.getElementById('loadingScreen');
        const errorScreen = document.getElementById('errorScreen');
        const pauseModal = document.getElementById('pauseModal');
        const orientationOverlay = document.getElementById('orientationOverlay');
        const touchIndicator = document.getElementById('touchIndicator');
        
        let soundEnabled = true;
        let isFullscreen = false;
        let isPaused = false;
        let controlsTimer = null;
        let lastInteraction = Date.now();
        
        // Configuración
        const gameConfig = {
            id: <?php echo $gameData['id']; ?>,
            title: '<?php echo addslashes($gameData['title']); ?>',
            path: '<?php echo $gameUrl; ?>',
            companyId: <?php echo $companyConfig['company_id']; ?>
        };
        
        // Detectar dispositivo
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
        
        // Inicializar
        function init() {
            console.log('Iniciando juego:', gameConfig.path);
            
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
                    showControls();
                    
                    // Enviar configuración inicial
                    try {
                        gameFrame.contentWindow.postMessage({
                            type: 'init',
                            config: {
                                soundEnabled: soundEnabled,
                                isMobile: isMobile,
                                companyId: gameConfig.companyId
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
            // Auto-ocultar controles
            document.addEventListener('touchstart', showControls);
            document.addEventListener('touchmove', showControls);
            document.addEventListener('click', showControls);
            
            // Escuchar mensajes del juego
            window.addEventListener('message', handleGameMessage);
            
            // Detectar inactividad
            setInterval(checkInactivity, 1000);
        }
        
        // Mostrar controles
        function showControls() {
            lastInteraction = Date.now();
            controlsOverlay.classList.add('visible');
            
            clearTimeout(controlsTimer);
            controlsTimer = setTimeout(hideControls, 3000);
        }
        
        // Ocultar controles
        function hideControls() {
            if (!isPaused && Date.now() - lastInteraction > 2500) {
                controlsOverlay.classList.remove('visible');
            }
        }
        
        // Verificar inactividad
        function checkInactivity() {
            if (!isPaused && Date.now() - lastInteraction > 3000) {
                hideControls();
            }
        }
        
        // Acciones del juego
        function gameAction(action) {
            console.log('Acción:', action);
            
            // Mostrar indicador táctil
            showTouchFeedback();
            
            // Enviar comando al juego
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'control',
                    action: action
                }, '*');
            } catch (e) {}
            
            // Vibración táctil
            if ('vibrate' in navigator) {
                navigator.vibrate(10);
            }
        }
        
        // Mostrar feedback táctil
        function showTouchFeedback() {
            touchIndicator.classList.remove('show');
            void touchIndicator.offsetWidth; // Force reflow
            touchIndicator.classList.add('show');
        }
        
        // Pausar juego
        function pauseGame() {
            isPaused = true;
            pauseModal.classList.add('active');
            
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'pause'
                }, '*');
            } catch (e) {}
        }
        
        // Reanudar juego
        function resumeGame() {
            isPaused = false;
            pauseModal.classList.remove('active');
            showControls();
            
            try {
                gameFrame.contentWindow.postMessage({
                    type: 'resume'
                }, '*');
            } catch (e) {}
        }
        
        // Reiniciar juego
        function restartGame() {
            pauseModal.classList.remove('active');
            loadingScreen.classList.remove('hidden');
            
            gameFrame.src = '';
            setTimeout(() => {
                gameFrame.src = gameConfig.path;
            }, 100);
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
            
            switch(data.type) {
                case 'gameOver':
                    handleGameOver(data);
                    break;
                case 'score':
                    console.log('Puntuación:', data.score);
                    break;
                case 'requestPause':
                    pauseGame();
                    break;
            }
        }
        
        // Manejar fin del juego
        function handleGameOver(data) {
            if (confirm(`¡Juego terminado!\nPuntuación: ${data.score || 0}\n\n¿Jugar de nuevo?`)) {
                restartGame();
            } else {
                exitGame();
            }
        }
        
        // Prevenir gestos por defecto
        function preventDefaultGestures() {
            // Prevenir scroll
            document.addEventListener('touchmove', (e) => {
                e.preventDefault();
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
                pauseGame();
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