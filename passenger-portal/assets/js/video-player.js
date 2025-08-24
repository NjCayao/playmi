/**
 * passenger-portal/assets/js/video-player.js
 * Control del reproductor de video con sistema de publicidad
 */

const VideoPlayer = {
    config: null,
    mainVideo: null,
    adVideo: null,
    isPlaying: false,
    isAdPlaying: false,
    adTimer: null,
    adCountdownTimer: null,
    lastAdTime: 0,
    currentAdData: null,
    hideControlsTimer: null,
    
    // Inicializar reproductor
    init(config) {
        this.config = config;
        this.mainVideo = document.getElementById('mainVideo');
        this.adVideo = document.getElementById('adVideo');
        
        // Configurar eventos
        this.setupVideoEvents();
        this.setupControlEvents();
        
        // Cargar datos de publicidad
        this.loadAdvertisingData();
        
        // Iniciar timer de publicidad
        if (this.config.adsEnabled) {
            this.startAdTimer();
        }
        
        // Auto-play
        this.play();
    },
    
    // Configurar eventos del video
    setupVideoEvents() {
        // Video principal
        this.mainVideo.addEventListener('loadedmetadata', () => {
            this.updateDuration();
            
            // Configurar publicidad a mitad de película si está habilitado
            if (this.config.midrollEnabled) {
                this.setupMidrollAd();
            }
        });
        
        this.mainVideo.addEventListener('timeupdate', () => {
            this.updateProgress();
        });
        
        this.mainVideo.addEventListener('ended', () => {
            this.onVideoEnded();
        });
        
        // Video de publicidad
        this.adVideo.addEventListener('ended', () => {
            this.onAdEnded();
        });
    },
    
    // Configurar eventos de controles
    setupControlEvents() {
        const container = document.querySelector('.video-container');
        
        // Mostrar/ocultar controles
        container.addEventListener('mousemove', () => {
            this.showControls();
        });
        
        container.addEventListener('click', (e) => {
            if (e.target === this.mainVideo || e.target === container) {
                this.togglePlayPause();
            }
        });
        
        // Teclas de atajo
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    this.togglePlayPause();
                    break;
                case 'f':
                    this.toggleFullscreen();
                    break;
                case 'ArrowLeft':
                    this.skip(-10);
                    break;
                case 'ArrowRight':
                    this.skip(10);
                    break;
            }
        });
    },
    
    // Reproducir/Pausar
    play() {
        if (this.isAdPlaying) return;
        
        this.mainVideo.play();
        this.isPlaying = true;
        this.updatePlayPauseButton();
    },
    
    pause() {
        if (this.isAdPlaying) return;
        
        this.mainVideo.pause();
        this.isPlaying = false;
        this.updatePlayPauseButton();
    },
    
    togglePlayPause() {
        if (this.isAdPlaying) return;
        
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    },
    
    // Actualizar botón play/pause
    updatePlayPauseButton() {
        const btn = document.getElementById('playPauseBtn');
        const icon = btn.querySelector('i');
        
        if (this.isPlaying) {
            icon.className = 'fas fa-pause';
        } else {
            icon.className = 'fas fa-play';
        }
    },
    
    // Actualizar progreso
    updateProgress() {
        const progress = (this.mainVideo.currentTime / this.mainVideo.duration) * 100;
        document.getElementById('progressFill').style.width = progress + '%';
        document.getElementById('currentTime').textContent = this.formatTime(this.mainVideo.currentTime);
    },
    
    // Actualizar duración
    updateDuration() {
        document.getElementById('duration').textContent = this.formatTime(this.mainVideo.duration);
    },
    
    // Formatear tiempo
    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);
        
        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    },
    
    // Buscar en el video
    seek(event) {
        if (this.isAdPlaying) return;
        
        const progressBar = document.getElementById('progressBar');
        const clickX = event.offsetX;
        const width = progressBar.offsetWidth;
        const percentage = clickX / width;
        
        this.mainVideo.currentTime = percentage * this.mainVideo.duration;
    },
    
    // Saltar segundos
    skip(seconds) {
        if (this.isAdPlaying) return;
        
        this.mainVideo.currentTime = Math.max(0, Math.min(
            this.mainVideo.currentTime + seconds,
            this.mainVideo.duration
        ));
    },
    
    // Sistema de publicidad
    startAdTimer() {
        this.adTimer = setInterval(() => {
            this.playAd();
        }, this.config.adInterval);
    },
    
    async loadAdvertisingData() {
        try {
            const response = await fetch(`../api/get-advertising.php?company_id=${this.config.companyId}`);
            const data = await response.json();
            
            if (data.success && data.ads.length > 0) {
                this.currentAdData = data.ads[0]; // Por ahora tomamos el primer ad
            }
        } catch (error) {
            console.error('Error loading advertising:', error);
        }
    },
    
    playAd() {
        if (!this.currentAdData || this.isAdPlaying) return;
        
        // Pausar video principal
        this.pause();
        this.isAdPlaying = true;
        
        // Mostrar overlay
        const adOverlay = document.getElementById('adOverlay');
        adOverlay.classList.add('active');
        
        // Configurar video de publicidad
        this.adVideo.src = '/playmi/content/' + this.currentAdData.archivo_path;
        this.adVideo.style.display = 'block';
        this.adVideo.play();
        
        // Iniciar countdown
        let countdown = this.config.adDuration;
        const countdownElement = document.getElementById('adCountdown');
        
        this.adCountdownTimer = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            // Mostrar botón de saltar después de 5 segundos
            if (countdown <= this.config.adDuration - 5) {
                document.getElementById('skipButton').style.display = 'block';
            }
            
            if (countdown <= 0) {
                this.onAdEnded();
            }
        }, 1000);
        
        // Registrar reproducción de publicidad
        this.trackAd('play');
    },
    
    skipAd() {
        this.trackAd('skip');
        this.onAdEnded();
    },
    
    onAdEnded() {
        // Limpiar timers
        clearInterval(this.adCountdownTimer);
        
        // Ocultar publicidad
        this.adVideo.pause();
        this.adVideo.style.display = 'none';
        document.getElementById('adOverlay').classList.remove('active');
        document.getElementById('skipButton').style.display = 'none';
        
        // Reanudar video principal
        this.isAdPlaying = false;
        this.play();
        
        // Registrar tiempo del último ad
        this.lastAdTime = Date.now();
    },
    
    // Configurar publicidad a mitad de película
    setupMidrollAd() {
        const midPoint = this.mainVideo.duration / 2;
        let midrollPlayed = false;
        
        this.mainVideo.addEventListener('timeupdate', () => {
            if (!midrollPlayed && this.mainVideo.currentTime >= midPoint) {
                midrollPlayed = true;
                this.playAd();
            }
        });
    },
    
    // Mostrar/ocultar controles
    showControls() {
        const controls = document.getElementById('controlsOverlay');
        const backButton = document.getElementById('backButton');
        
        controls.classList.remove('hidden');
        if (backButton) backButton.classList.add('visible');
        
        // Ocultar después de 3 segundos de inactividad
        clearTimeout(this.hideControlsTimer);
        this.hideControlsTimer = setTimeout(() => {
            if (this.isPlaying && !this.isAdPlaying) {
                controls.classList.add('hidden');
                if (backButton) backButton.classList.remove('visible');
            }
        }, 3000);
    },
    
    // Pantalla completa
    toggleFullscreen() {
        const container = document.querySelector('.video-container');
        
        if (!document.fullscreenElement) {
            container.requestFullscreen().catch(err => {
                console.error('Error al entrar en pantalla completa:', err);
            });
        } else {
            document.exitFullscreen();
        }
    },
    
    // Video terminado
    onVideoEnded() {
        this.isPlaying = false;
        this.updatePlayPauseButton();
        
        // Volver al inicio o mostrar recomendaciones
        setTimeout(() => {
            window.location.href = '../index.php';
        }, 3000);
    },
    
    // Tracking
    async trackAd(action) {
        try {
            await fetch('../api/track-usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: 'ad_interaction',
                    action: action,
                    ad_id: this.currentAdData?.id,
                    video_id: this.config.videoId,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Error tracking ad:', error);
        }
    }
};

// Funciones globales para los controles HTML
function togglePlayPause() {
    VideoPlayer.togglePlayPause();
}

function seek(event) {
    VideoPlayer.seek(event);
}

function toggleFullscreen() {
    VideoPlayer.toggleFullscreen();
}

function skipAd() {
    VideoPlayer.skipAd();
}

window.VideoPlayer = VideoPlayer;