/**
 * passenger-portal/assets/js/games-manager.js
 * Gestión de juegos HTML5 y comunicación con iframes
 */

const GamesManager = {
    // Configuración
    config: {
        apiUrl: '/playmi/passenger-portal/api/',
        contentUrl: '/playmi/content/',
        maxHighScores: 10,
        autoSaveInterval: 30000 // 30 segundos
    },
    
    // Estado
    currentGame: null,
    gameFrame: null,
    gameScores: {},
    isPlaying: false,
    sessionStartTime: null,
    autoSaveTimer: null,
    
    // Inicializar gestor
    init(config) {
        Object.assign(this.config, config);
        
        // Cargar puntuaciones guardadas
        this.loadLocalScores();
        
        // Configurar eventos
        this.setupEventListeners();
        
        // Iniciar comunicación con juegos
        this.setupGameCommunication();
    },
    
    // Configurar eventos
    setupEventListeners() {
        // Escuchar mensajes de juegos en iframe
        window.addEventListener('message', (event) => {
            this.handleGameMessage(event);
        });
        
        // Detectar cuando se cierra/recarga la página
        window.addEventListener('beforeunload', () => {
            if (this.isPlaying) {
                this.endGameSession();
            }
        });
        
        // Visibilidad de página (pausar cuando no está visible)
        document.addEventListener('visibilitychange', () => {
            if (this.gameFrame && this.isPlaying) {
                if (document.hidden) {
                    this.pauseGame();
                } else {
                    this.resumeGame();
                }
            }
        });
    },
    
    // Configurar comunicación con juegos
    setupGameCommunication() {
        // Protocolo de comunicación estándar para juegos PLAYMI
        this.messageProtocol = {
            // Mensajes del manager al juego
            INIT: 'GAME_INIT',
            START: 'GAME_START',
            PAUSE: 'GAME_PAUSE',
            RESUME: 'GAME_RESUME',
            STOP: 'GAME_STOP',
            MUTE: 'GAME_MUTE',
            UNMUTE: 'GAME_UNMUTE',
            SAVE_STATE: 'GAME_SAVE_STATE',
            LOAD_STATE: 'GAME_LOAD_STATE',
            
            // Mensajes del juego al manager
            READY: 'GAME_READY',
            STARTED: 'GAME_STARTED',
            PAUSED: 'GAME_PAUSED',
            RESUMED: 'GAME_RESUMED',
            OVER: 'GAME_OVER',
            SCORE_UPDATE: 'GAME_SCORE_UPDATE',
            ACHIEVEMENT: 'GAME_ACHIEVEMENT',
            ERROR: 'GAME_ERROR',
            STATE_SAVED: 'GAME_STATE_SAVED'
        };
    },
    
    // Cargar juego
    loadGame(gameId, gameData, frameElement) {
        this.currentGame = {
            id: gameId,
            ...gameData
        };
        this.gameFrame = frameElement;
        this.sessionStartTime = Date.now();
        
        // Esperar a que el juego esté listo
        const readyTimeout = setTimeout(() => {
            console.warn('Game did not send READY signal');
            this.onGameReady();
        }, 5000);
        
        // Guardar timeout para limpiarlo si llega READY
        this.readyTimeout = readyTimeout;
        
        // Iniciar auto-guardado
        this.startAutoSave();
        
        // Registrar inicio de sesión
        this.trackGameEvent('session_start', {
            game_id: gameId,
            game_title: gameData.title
        });
    },
    
    // Manejar mensajes del juego
    handleGameMessage(event) {
        // Validar origen (seguridad)
        if (!this.isValidOrigin(event.origin)) {
            console.warn('Invalid message origin:', event.origin);
            return;
        }
        
        const { type, data } = event.data;
        
        switch (type) {
            case this.messageProtocol.READY:
                this.onGameReady();
                break;
                
            case this.messageProtocol.STARTED:
                this.onGameStarted(data);
                break;
                
            case this.messageProtocol.PAUSED:
                this.onGamePaused();
                break;
                
            case this.messageProtocol.RESUMED:
                this.onGameResumed();
                break;
                
            case this.messageProtocol.OVER:
                this.onGameOver(data);
                break;
                
            case this.messageProtocol.SCORE_UPDATE:
                this.onScoreUpdate(data);
                break;
                
            case this.messageProtocol.ACHIEVEMENT:
                this.onAchievement(data);
                break;
                
            case this.messageProtocol.ERROR:
                this.onGameError(data);
                break;
                
            case this.messageProtocol.STATE_SAVED:
                this.onStateSaved(data);
                break;
                
            default:
                // Manejar mensajes personalizados del juego
                this.handleCustomMessage(type, data);
        }
    },
    
    // Validar origen del mensaje
    isValidOrigin(origin) {
        // Permitir mismo origen y URLs de contenido configuradas
        const allowedOrigins = [
            window.location.origin,
            this.config.contentUrl.replace(/\/$/, '')
        ];
        
        return allowedOrigins.some(allowed => origin.startsWith(allowed));
    },
    
    // Eventos del juego
    onGameReady() {
        clearTimeout(this.readyTimeout);
        console.log('Game is ready');
        
        // Enviar configuración inicial
        this.sendToGame(this.messageProtocol.INIT, {
            player: this.getPlayerInfo(),
            settings: this.getGameSettings(),
            previousScore: this.getHighScore(this.currentGame.id)
        });
    },
    
    onGameStarted(data) {
        this.isPlaying = true;
        console.log('Game started:', data);
        
        this.trackGameEvent('game_start', {
            level: data.level || 1,
            difficulty: data.difficulty || 'normal'
        });
    },
    
    onGamePaused() {
        this.isPlaying = false;
        console.log('Game paused');
    },
    
    onGameResumed() {
        this.isPlaying = true;
        console.log('Game resumed');
    },
    
    onGameOver(data) {
        this.isPlaying = false;
        const sessionDuration = Date.now() - this.sessionStartTime;
        
        console.log('Game over:', data);
        
        // Guardar puntuación
        if (data.score !== undefined) {
            this.saveScore(this.currentGame.id, data.score, data);
        }
        
        // Mostrar resultados
        this.showGameResults(data);
        
        // Tracking
        this.trackGameEvent('game_over', {
            score: data.score,
            level: data.level,
            duration: sessionDuration,
            ...data
        });
    },
    
    onScoreUpdate(data) {
        console.log('Score update:', data);
        
        // Actualizar UI si es necesario
        if (window.updateGameScore) {
            window.updateGameScore(data.score);
        }
    },
    
    onAchievement(data) {
        console.log('Achievement unlocked:', data);
        
        // Mostrar notificación de logro
        this.showAchievement(data);
        
        // Guardar logro
        this.saveAchievement(this.currentGame.id, data);
        
        // Tracking
        this.trackGameEvent('achievement_unlocked', data);
    },
    
    onGameError(data) {
        console.error('Game error:', data);
        
        // Mostrar error al usuario
        this.showError(data.message || 'Error en el juego');
    },
    
    onStateSaved(data) {
        console.log('Game state saved:', data);
        
        // Guardar estado localmente también
        this.saveGameState(this.currentGame.id, data.state);
    },
    
    // Enviar mensaje al juego
    sendToGame(type, data = {}) {
        if (!this.gameFrame || !this.gameFrame.contentWindow) {
            console.warn('Game frame not available');
            return;
        }
        
        this.gameFrame.contentWindow.postMessage({
            type: type,
            data: data,
            timestamp: Date.now()
        }, '*');
    },
    
    // Comandos del juego
    startGame() {
        this.sendToGame(this.messageProtocol.START);
    },
    
    pauseGame() {
        this.sendToGame(this.messageProtocol.PAUSE);
        this.isPlaying = false;
    },
    
    resumeGame() {
        this.sendToGame(this.messageProtocol.RESUME);
        this.isPlaying = true;
    },
    
    stopGame() {
        this.sendToGame(this.messageProtocol.STOP);
        this.isPlaying = false;
        this.endGameSession();
    },
    
    muteGame() {
        this.sendToGame(this.messageProtocol.MUTE);
    },
    
    unmuteGame() {
        this.sendToGame(this.messageProtocol.UNMUTE);
    },
    
    // Gestión de puntuaciones
    saveScore(gameId, score, additionalData = {}) {
        // Obtener puntuaciones existentes
        const scores = this.gameScores[gameId] || [];
        
        // Agregar nueva puntuación
        scores.push({
            score: score,
            timestamp: Date.now(),
            duration: Date.now() - this.sessionStartTime,
            ...additionalData
        });
        
        // Ordenar por puntuación (mayor a menor)
        scores.sort((a, b) => b.score - a.score);
        
        // Mantener solo las mejores puntuaciones
        this.gameScores[gameId] = scores.slice(0, this.config.maxHighScores);
        
        // Guardar en localStorage
        this.saveLocalScores();
        
        // Enviar al servidor
        this.syncScoreToServer(gameId, score, additionalData);
    },
    
    getHighScore(gameId) {
        const scores = this.gameScores[gameId] || [];
        return scores.length > 0 ? scores[0].score : 0;
    },
    
    getTopScores(gameId, limit = 5) {
        const scores = this.gameScores[gameId] || [];
        return scores.slice(0, limit);
    },
    
    // Gestión de estado del juego
    saveGameState(gameId, state) {
        try {
            const key = `gameState_${gameId}`;
            localStorage.setItem(key, JSON.stringify({
                state: state,
                timestamp: Date.now()
            }));
        } catch (error) {
            console.error('Error saving game state:', error);
        }
    },
    
    loadGameState(gameId) {
        try {
            const key = `gameState_${gameId}`;
            const saved = localStorage.getItem(key);
            
            if (saved) {
                const data = JSON.parse(saved);
                
                // Verificar que no sea muy antiguo (24 horas)
                if (Date.now() - data.timestamp < 24 * 60 * 60 * 1000) {
                    return data.state;
                }
            }
        } catch (error) {
            console.error('Error loading game state:', error);
        }
        
        return null;
    },
    
    requestSaveState() {
        this.sendToGame(this.messageProtocol.SAVE_STATE);
    },
    
    requestLoadState() {
        const state = this.loadGameState(this.currentGame.id);
        if (state) {
            this.sendToGame(this.messageProtocol.LOAD_STATE, { state });
        }
    },
    
    // Auto-guardado
    startAutoSave() {
        this.stopAutoSave();
        
        this.autoSaveTimer = setInterval(() => {
            if (this.isPlaying) {
                this.requestSaveState();
            }
        }, this.config.autoSaveInterval);
    },
    
    stopAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
            this.autoSaveTimer = null;
        }
    },
    
    // Logros
    saveAchievement(gameId, achievement) {
        try {
            const key = `achievements_${gameId}`;
            const achievements = JSON.parse(localStorage.getItem(key) || '[]');
            
            // Verificar si ya existe
            if (!achievements.find(a => a.id === achievement.id)) {
                achievements.push({
                    ...achievement,
                    unlockedAt: Date.now()
                });
                
                localStorage.setItem(key, JSON.stringify(achievements));
            }
        } catch (error) {
            console.error('Error saving achievement:', error);
        }
    },
    
    getAchievements(gameId) {
        try {
            const key = `achievements_${gameId}`;
            return JSON.parse(localStorage.getItem(key) || '[]');
        } catch (error) {
            console.error('Error loading achievements:', error);
            return [];
        }
    },
    
    // UI y notificaciones
    showGameResults(data) {
        const isHighScore = data.score > this.getHighScore(this.currentGame.id);
        
        // Crear modal de resultados
        const modal = document.createElement('div');
        modal.className = 'game-results-modal';
        modal.innerHTML = `
            <div class="game-results-content">
                <h2>¡Juego Terminado!</h2>
                <div class="score-display">
                    <div class="score-label">Puntuación</div>
                    <div class="score-value">${data.score || 0}</div>
                    ${isHighScore ? '<div class="high-score-badge">¡NUEVO RÉCORD!</div>' : ''}
                </div>
                ${data.level ? `<p>Nivel alcanzado: ${data.level}</p>` : ''}
                ${data.time ? `<p>Tiempo: ${this.formatTime(data.time)}</p>` : ''}
                <div class="results-actions">
                    <button onclick="GamesManager.playAgain()" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Jugar de nuevo
                    </button>
                    <button onclick="GamesManager.exitGame()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Salir
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Animar entrada
        setTimeout(() => modal.classList.add('active'), 10);
    },
    
    showAchievement(achievement) {
        const notification = document.createElement('div');
        notification.className = 'achievement-notification';
        notification.innerHTML = `
            <div class="achievement-icon">
                <i class="fas fa-trophy"></i>
            </div>
            <div class="achievement-content">
                <h4>¡Logro Desbloqueado!</h4>
                <p>${achievement.name}</p>
                ${achievement.description ? `<small>${achievement.description}</small>` : ''}
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animar
        setTimeout(() => notification.classList.add('show'), 10);
        
        // Remover después de 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    },
    
    showError(message) {
        // Usar el sistema de notificaciones del portal si existe
        if (window.Portal && window.Portal.showError) {
            window.Portal.showError(message);
        } else {
            alert('Error: ' + message);
        }
    },
    
    // Acciones del usuario
    playAgain() {
        // Cerrar modal
        const modal = document.querySelector('.game-results-modal');
        if (modal) {
            modal.classList.remove('active');
            setTimeout(() => modal.remove(), 300);
        }
        
        // Reiniciar juego
        if (this.gameFrame) {
            this.gameFrame.src = this.gameFrame.src;
        }
    },
    
    exitGame() {
        this.endGameSession();
        window.location.href = '../games.php';
    },
    
    // Finalizar sesión
    endGameSession() {
        this.stopAutoSave();
        
        if (this.isPlaying) {
            this.stopGame();
        }
        
        const sessionDuration = Date.now() - this.sessionStartTime;
        
        // Tracking de fin de sesión
        this.trackGameEvent('session_end', {
            duration: sessionDuration,
            completed: false
        });
    },
    
    // Utilidades
    getPlayerInfo() {
        // Obtener información del jugador (si existe sistema de usuarios)
        return {
            id: 'guest',
            name: 'Jugador',
            preferences: this.getGameSettings()
        };
    },
    
    getGameSettings() {
        // Obtener configuraciones guardadas
        try {
            return JSON.parse(localStorage.getItem('gameSettings') || '{}');
        } catch (error) {
            return {
                soundEnabled: true,
                musicEnabled: true,
                difficulty: 'normal'
            };
        }
    },
    
    saveGameSettings(settings) {
        try {
            localStorage.setItem('gameSettings', JSON.stringify(settings));
        } catch (error) {
            console.error('Error saving game settings:', error);
        }
    },
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    },
    
    // Almacenamiento local
    loadLocalScores() {
        try {
            const saved = localStorage.getItem('gameScores');
            if (saved) {
                this.gameScores = JSON.parse(saved);
            }
        } catch (error) {
            console.error('Error loading scores:', error);
            this.gameScores = {};
        }
    },
    
    saveLocalScores() {
        try {
            localStorage.setItem('gameScores', JSON.stringify(this.gameScores));
        } catch (error) {
            console.error('Error saving scores:', error);
        }
    },
    
    // Sincronización con servidor
    async syncScoreToServer(gameId, score, additionalData) {
        try {
            await fetch(this.config.apiUrl + 'track-usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'game_score',
                    data: {
                        game_id: gameId,
                        score: score,
                        ...additionalData
                    },
                    company_id: this.config.companyId,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Error syncing score to server:', error);
        }
    },
    
    // Tracking de eventos
    async trackGameEvent(event, data = {}) {
        try {
            await fetch(this.config.apiUrl + 'track-usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: event,
                    data: {
                        ...data,
                        game_id: this.currentGame?.id,
                        game_title: this.currentGame?.title
                    },
                    company_id: this.config.companyId,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Error tracking game event:', error);
        }
    },
    
    // Mensajes personalizados
    handleCustomMessage(type, data) {
        // Manejar mensajes específicos del juego
        console.log('Custom game message:', type, data);
        
        // Disparar evento personalizado
        window.dispatchEvent(new CustomEvent('gameMessage', {
            detail: { type, data }
        }));
    }
};

// Estilos para notificaciones
const style = document.createElement('style');
style.textContent = `
    /* Modal de resultados */
    .game-results-modal {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        opacity: 0;
        transition: opacity 0.3s;
    }
    
    .game-results-modal.active {
        opacity: 1;
    }
    
    .game-results-content {
        background: var(--bg-card, #1a1a1a);
        padding: 3rem;
        border-radius: 12px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        transform: scale(0.9);
        transition: transform 0.3s;
    }
    
    .game-results-modal.active .game-results-content {
        transform: scale(1);
    }
    
    .score-display {
        margin: 2rem 0;
    }
    
    .score-label {
        font-size: 1.25rem;
        color: var(--text-secondary, #b3b3b3);
        margin-bottom: 0.5rem;
    }
    
    .score-value {
        font-size: 4rem;
        font-weight: 700;
        color: var(--company-primary, #e50914);
    }
    
    .high-score-badge {
        background: #f39c12;
        color: black;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        margin-top: 1rem;
        display: inline-block;
        animation: pulse 1s ease-in-out infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .results-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        margin-top: 2rem;
    }
    
    /* Notificación de logro */
    .achievement-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 1rem;
        max-width: 350px;
        transform: translateX(400px);
        transition: transform 0.3s ease;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        z-index: 10001;
    }
    
    .achievement-notification.show {
        transform: translateX(0);
    }
    
    .achievement-icon {
        font-size: 2rem;
        animation: bounce 1s ease-in-out;
    }
    
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .achievement-content h4 {
        margin: 0 0 0.25rem 0;
        font-size: 1rem;
    }
    
    .achievement-content p {
        margin: 0;
        font-weight: 600;
    }
    
    .achievement-content small {
        opacity: 0.9;
        font-size: 0.875rem;
    }
`;
document.head.appendChild(style);

// Exportar para uso global
window.GamesManager = GamesManager;