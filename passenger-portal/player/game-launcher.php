<?php
/**
 * passenger-portal/player/game-launcher.php
 * Lanzador de juegos HTML5 con sandbox seguro
 */

define('PORTAL_ACCESS', true);
require_once '../config/portal-config.php';

$gameId = $_GET['id'] ?? 0;
$companyConfig = getCompanyConfig();

// Simular datos del juego
$gameData = [
    'id' => $gameId,
    'title' => 'Juego de ejemplo',
    'category' => 'arcade',
    'description' => 'Un juego divertido para pasar el tiempo',
    'instructions' => 'Usa las teclas de flecha para moverte y la barra espaciadora para disparar',
    'game_path' => 'games/example-game/index.html',
    'controls' => ['keyboard', 'mouse', 'touch']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gameData['title']); ?> - PLAYMI Games</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0a0a0a;
            color: white;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }
        
        .game-launcher {
            width: 100vw;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header del juego */
        .game-header {
            background: rgba(20, 20, 20, 0.95);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--company-primary, #e50914);
            z-index: 100;
        }
        
        .game-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .game-title {
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .game-category {
            padding: 0.25rem 0.75rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            font-size: 0.875rem;
            color: #b3b3b3;
        }
        
        .game-controls {
            display: flex;
            gap: 1rem;
        }
        
        .control-button {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .control-button:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .exit-button {
            background: rgba(231, 9, 20, 0.8);
            border-color: #e50914;
        }
        
        .exit-button:hover {
            background: #e50914;
        }
        
        /* Contenedor del juego */
        .game-container {
            flex: 1;
            position: relative;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        #gameFrame {
            width: 100%;
            height: 100%;
            border: none;
            background: #000;
        }
        
        /* Pantalla de carga */
        .loading-screen {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #0a0a0a;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }
        
        .loading-screen.hidden {
            display: none;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 3px solid rgba(255,255,255,0.1);
            border-top-color: var(--company-primary, #e50914);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 2rem;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-text {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .loading-tip {
            color: #b3b3b3;
            font-size: 0.875rem;
        }
        
        /* Modal de instrucciones */
        .instructions-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }
        
        .instructions-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .instructions-content {
            background: #1a1a1a;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s;
        }
        
        .instructions-modal.active .instructions-content {
            transform: scale(1);
        }
        
        .instructions-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--company-primary, #e50914);
        }
        
        .instructions-text {
            line-height: 1.6;
            margin-bottom: 1.5rem;
            color: #e0e0e0;
        }
        
        .close-instructions {
            background: var(--company-primary, #e50914);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .close-instructions:hover {
            transform: scale(1.05);
        }
        
        /* Controles de juego móvil */
        .mobile-controls {
            display: none;
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            padding: 1rem;
            border-radius: 10px;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .game-header {
                padding: 0.75rem 1rem;
            }
            
            .game-title {
                font-size: 1rem;
            }
            
            .control-button {
                padding: 0.4rem 0.8rem;
                font-size: 0.875rem;
            }
            
            .mobile-controls {
                display: flex;
            }
        }
        
        /* Fullscreen styles */
        .game-launcher.fullscreen .game-header {
            position: absolute;
            top: -100px;
            transition: top 0.3s;
        }
        
        .game-launcher.fullscreen:hover .game-header {
            top: 0;
        }
        
        .game-launcher.fullscreen .game-container {
            height: 100vh;
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="game-launcher" id="gameLauncher">
        <!-- Header del juego -->
        <div class="game-header">
            <div class="game-info">
                <h1 class="game-title"><?php echo htmlspecialchars($gameData['title']); ?></h1>
                <span class="game-category"><?php echo htmlspecialchars($gameData['category']); ?></span>
            </div>
            
            <div class="game-controls">
                <button class="control-button" onclick="showInstructions()">
                    <i class="fas fa-info-circle"></i> Instrucciones
                </button>
                <button class="control-button" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i> Pantalla completa
                </button>
                <button class="control-button" onclick="toggleSound()" id="soundButton">
                    <i class="fas fa-volume-up"></i> Sonido
                </button>
                <button class="control-button exit-button" onclick="exitGame()">
                    <i class="fas fa-times"></i> Salir
                </button>
            </div>
        </div>
        
        <!-- Contenedor del juego -->
        <div class="game-container">
            <!-- Pantalla de carga -->
            <div class="loading-screen" id="loadingScreen">
                <div class="loading-spinner"></div>
                <h2 class="loading-text">Cargando juego...</h2>
                <p class="loading-tip">Prepárate para la diversión</p>
            </div>
            
            <!-- Iframe del juego con sandbox -->
            <iframe 
                id="gameFrame"
                src=""
                sandbox="allow-scripts allow-same-origin allow-pointer-lock allow-forms"
                allow="autoplay; fullscreen"
                loading="lazy">
            </iframe>
        </div>
        
        <!-- Controles móviles (opcional) -->
        <div class="mobile-controls" id="mobileControls">
            <button class="control-button" onclick="sendGameCommand('pause')">
                <i class="fas fa-pause"></i>
            </button>
            <button class="control-button" onclick="sendGameCommand('restart')">
                <i class="fas fa-redo"></i>
            </button>
        </div>
    </div>
    
    <!-- Modal de instrucciones -->
    <div class="instructions-modal" id="instructionsModal">
        <div class="instructions-content">
            <h2 class="instructions-title">Cómo jugar</h2>
            <p class="instructions-text">
                <?php echo nl2br(htmlspecialchars($gameData['instructions'])); ?>
            </p>
            <button class="close-instructions" onclick="closeInstructions()">
                ¡Entendido!
            </button>
        </div>
    </div>
    
    <script>
        // Variables globales
        let gameFrame = document.getElementById('gameFrame');
        let loadingScreen = document.getElementById('loadingScreen');
        let soundEnabled = true;
        let isFullscreen = false;
        
        // Configuración del juego
        const gameConfig = {
            id: <?php echo $gameId; ?>,
            title: '<?php echo addslashes($gameData['title']); ?>',
            path: '<?php echo CONTENT_URL . $gameData['game_path']; ?>',
            companyId: <?php echo $companyConfig['company_id']; ?>
        };
        
        // Inicializar juego
        function initGame() {
            // Establecer comunicación con el iframe
            window.addEventListener('message', handleGameMessage);
            
            // Cargar el juego
            gameFrame.src = gameConfig.path;
            
            // Ocultar pantalla de carga cuando el juego esté listo
            gameFrame.onload = function() {
                setTimeout(() => {
                    loadingScreen.classList.add('hidden');
                    
                    // Enviar configuración inicial al juego
                    gameFrame.contentWindow.postMessage({
                        type: 'init',
                        config: {
                            soundEnabled: soundEnabled,
                            companyId: gameConfig.companyId
                        }
                    }, '*');
                    
                    // Registrar inicio de juego
                    trackGameEvent('start');
                }, 1000);
            };
            
            // Manejar errores
            gameFrame.onerror = function() {
                alert('Error al cargar el juego. Por favor, intenta nuevamente.');
                exitGame();
            };
        }
        
        // Manejar mensajes del juego
        function handleGameMessage(event) {
            const data = event.data;
            
            switch(data.type) {
                case 'score':
                    // Guardar puntuación
                    saveScore(data.score);
                    break;
                case 'gameOver':
                    // Manejar fin del juego
                    handleGameOver(data);
                    break;
                case 'requestFullscreen':
                    toggleFullscreen();
                    break;
            }
        }
        
        // Mostrar instrucciones
        function showInstructions() {
            document.getElementById('instructionsModal').classList.add('active');
        }
        
        // Cerrar instrucciones
        function closeInstructions() {
            document.getElementById('instructionsModal').classList.remove('active');
        }
        
        // Toggle pantalla completa
        function toggleFullscreen() {
            const launcher = document.getElementById('gameLauncher');
            
            if (!document.fullscreenElement) {
                launcher.requestFullscreen().then(() => {
                    launcher.classList.add('fullscreen');
                    isFullscreen = true;
                }).catch(err => {
                    console.error('Error al entrar en pantalla completa:', err);
                });
            } else {
                document.exitFullscreen().then(() => {
                    launcher.classList.remove('fullscreen');
                    isFullscreen = false;
                });
            }
        }
        
        // Toggle sonido
        function toggleSound() {
            soundEnabled = !soundEnabled;
            const button = document.getElementById('soundButton');
            const icon = button.querySelector('i');
            
            if (soundEnabled) {
                icon.className = 'fas fa-volume-up';
            } else {
                icon.className = 'fas fa-volume-mute';
            }
            
            // Enviar comando al juego
            gameFrame.contentWindow.postMessage({
                type: 'sound',
                enabled: soundEnabled
            }, '*');
        }
        
        // Enviar comando al juego
        function sendGameCommand(command) {
            gameFrame.contentWindow.postMessage({
                type: 'command',
                command: command
            }, '*');
        }
        
        // Guardar puntuación
        async function saveScore(score) {
            try {
                await fetch('../api/track-usage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'game_score',
                        data: {
                            game_id: gameConfig.id,
                            score: score
                        },
                        company_id: gameConfig.companyId
                    })
                });
            } catch (error) {
                console.error('Error saving score:', error);
            }
        }
        
        // Manejar fin del juego
        function handleGameOver(data) {
            if (data.score) {
                saveScore(data.score);
            }
            
            // Mostrar opción de reiniciar
            if (confirm(`¡Juego terminado! Puntuación: ${data.score || 0}\n\n¿Quieres jugar de nuevo?`)) {
                gameFrame.src = gameFrame.src; // Recargar juego
            }
        }
        
        // Salir del juego
        function exitGame() {
            if (confirm('¿Estás seguro de que quieres salir del juego?')) {
                trackGameEvent('exit');
                window.location.href = '../games.php';
            }
        }
        
        // Tracking de eventos
        async function trackGameEvent(event) {
            try {
                await fetch('../api/track-usage.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'game_' + event,
                        data: {
                            game_id: gameConfig.id,
                            game_title: gameConfig.title
                        },
                        company_id: gameConfig.companyId
                    })
                });
            } catch (error) {
                console.error('Error tracking event:', error);
            }
        }
        
        // Detectar salida de página
        window.addEventListener('beforeunload', function(e) {
            if (!isFullscreen) {
                trackGameEvent('abandon');
            }
        });
        
        // Iniciar cuando todo esté listo
        document.addEventListener('DOMContentLoaded', initGame);
    </script>
</body>
</html>