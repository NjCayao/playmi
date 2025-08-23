/**
 * passenger-portal/assets/js/music-player.js
 * Control del reproductor de música con visualizador y publicidad
 */

const MusicPlayer = {
    // Configuración
    config: null,
    
    // Elementos del DOM
    audio: null,
    albumArt: null,
    playPauseBtn: null,
    progressBar: null,
    progressFill: null,
    currentTimeEl: null,
    durationEl: null,
    volumeFill: null,
    
    // Estado del reproductor
    isPlaying: false,
    currentTrackIndex: 0,
    playlist: [],
    shuffle: false,
    repeat: false,
    volume: 0.7,
    
    // Sistema de publicidad
    adTimer: null,
    isAdPlaying: false,
    lastAdTime: 0,
    
    // Visualizador
    audioContext: null,
    analyser: null,
    visualizerCanvas: null,
    visualizerCtx: null,
    animationId: null,
    
    // Inicializar reproductor
    init(config) {
        this.config = config;
        this.playlist = config.playlist || [];
        
        // Obtener elementos del DOM
        this.audio = document.getElementById('audioPlayer');
        this.albumArt = document.getElementById('albumArt');
        this.playPauseBtn = document.getElementById('playPauseBtn');
        this.progressBar = document.getElementById('progressBar');
        this.progressFill = document.getElementById('progressFill');
        this.currentTimeEl = document.getElementById('currentTime');
        this.durationEl = document.getElementById('duration');
        this.volumeFill = document.getElementById('volumeFill');
        this.visualizerCanvas = document.getElementById('visualizer');
        
        // Configurar eventos
        this.setupAudioEvents();
        this.setupControlEvents();
        
        // Inicializar visualizador
        this.initVisualizer();
        
        // Configurar volumen inicial
        this.audio.volume = this.volume;
        this.updateVolumeUI();
        
        // Iniciar timer de publicidad si está habilitado
        if (this.config.adsEnabled) {
            this.startAdTimer();
        }
        
        // Registrar inicio de sesión
        this.trackEvent('session_start');
    },
    
    // Configurar eventos del audio
    setupAudioEvents() {
        this.audio.addEventListener('loadedmetadata', () => {
            this.updateDuration();
        });
        
        this.audio.addEventListener('timeupdate', () => {
            this.updateProgress();
        });
        
        this.audio.addEventListener('ended', () => {
            this.onTrackEnded();
        });
        
        this.audio.addEventListener('error', (e) => {
            console.error('Error loading audio:', e);
            this.nextTrack();
        });
    },
    
    // Configurar eventos de controles
    setupControlEvents() {
        // Teclas de acceso directo
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    this.togglePlayPause();
                    break;
                case 'ArrowLeft':
                    this.skip(-10);
                    break;
                case 'ArrowRight':
                    this.skip(10);
                    break;
                case 'ArrowUp':
                    this.changeVolume(0.1);
                    break;
                case 'ArrowDown':
                    this.changeVolume(-0.1);
                    break;
            }
        });
        
        // Drag para la barra de progreso
        let isDragging = false;
        this.progressBar.addEventListener('mousedown', () => isDragging = true);
        document.addEventListener('mouseup', () => isDragging = false);
        document.addEventListener('mousemove', (e) => {
            if (isDragging) {
                const rect = this.progressBar.getBoundingClientRect();
                const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
                this.audio.currentTime = percent * this.audio.duration;
            }
        });
    },
    
    // Inicializar visualizador
    initVisualizer() {
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            this.analyser = this.audioContext.createAnalyser();
            this.analyser.fftSize = 256;
            
            const source = this.audioContext.createMediaElementSource(this.audio);
            source.connect(this.analyser);
            this.analyser.connect(this.audioContext.destination);
            
            this.visualizerCtx = this.visualizerCanvas.getContext('2d');
            this.startVisualization();
        } catch (error) {
            console.warn('Visualizer not available:', error);
        }
    },
    
    // Animación del visualizador
    startVisualization() {
        const bufferLength = this.analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        
        const draw = () => {
            this.animationId = requestAnimationFrame(draw);
            
            this.analyser.getByteFrequencyData(dataArray);
            
            const canvas = this.visualizerCanvas;
            const ctx = this.visualizerCtx;
            
            // Ajustar canvas al tamaño de la ventana
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
            
            ctx.fillStyle = 'rgba(10, 10, 10, 0.2)';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            const barWidth = (canvas.width / bufferLength) * 2.5;
            let barHeight;
            let x = 0;
            
            for (let i = 0; i < bufferLength; i++) {
                barHeight = dataArray[i] * 2;
                
                const gradient = ctx.createLinearGradient(0, canvas.height, 0, canvas.height - barHeight);
                gradient.addColorStop(0, '#1db954');
                gradient.addColorStop(0.5, '#1ed760');
                gradient.addColorStop(1, '#1db954');
                
                ctx.fillStyle = gradient;
                ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);
                
                x += barWidth + 1;
            }
        };
        
        draw();
    },
    
    // Control de reproducción
    play() {
        if (this.isAdPlaying) return;
        
        this.audio.play();
        this.isPlaying = true;
        this.updatePlayPauseButton();
        this.albumArt.classList.add('playing');
        
        // Reanudar contexto de audio si está suspendido
        if (this.audioContext?.state === 'suspended') {
            this.audioContext.resume();
        }
        
        this.trackEvent('content_play');
    },
    
    pause() {
        if (this.isAdPlaying) return;
        
        this.audio.pause();
        this.isPlaying = false;
        this.updatePlayPauseButton();
        this.albumArt.classList.remove('playing');
        
        this.trackEvent('content_pause');
    },
    
    togglePlayPause() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    },
    
    // Navegación de pistas
    nextTrack() {
        if (this.shuffle) {
            this.currentTrackIndex = Math.floor(Math.random() * this.playlist.length);
        } else {
            this.currentTrackIndex = (this.currentTrackIndex + 1) % this.playlist.length;
        }
        
        this.loadTrack(this.currentTrackIndex);
        this.play();
    },
    
    previousTrack() {
        if (this.audio.currentTime > 3) {
            // Si han pasado más de 3 segundos, reiniciar la canción actual
            this.audio.currentTime = 0;
        } else {
            // Ir a la canción anterior
            this.currentTrackIndex = (this.currentTrackIndex - 1 + this.playlist.length) % this.playlist.length;
            this.loadTrack(this.currentTrackIndex);
            this.play();
        }
    },
    
    // Cargar pista
    loadTrack(index) {
        const track = this.playlist[index];
        if (!track) return;
        
        // Actualizar UI
        document.getElementById('trackTitle').textContent = track.title;
        document.getElementById('trackArtist').textContent = track.artist;
        
        // Actualizar playlist activa
        document.querySelectorAll('.playlist-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
        
        // Cargar nueva pista
        this.audio.src = `/playmi/content/music/${track.file || 'example.mp3'}`;
        
        // Si estaba reproduciendo, continuar
        if (this.isPlaying) {
            this.play();
        }
    },
    
    // Reproducir pista específica
    playTrack(index) {
        this.currentTrackIndex = index;
        this.loadTrack(index);
        this.play();
    },
    
    // Control de progreso
    updateProgress() {
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressFill.style.width = progress + '%';
        this.currentTimeEl.textContent = this.formatTime(this.audio.currentTime);
    },
    
    updateDuration() {
        this.durationEl.textContent = this.formatTime(this.audio.duration);
    },
    
    seek(event) {
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        this.audio.currentTime = percent * this.audio.duration;
    },
    
    skip(seconds) {
        this.audio.currentTime = Math.max(0, Math.min(
            this.audio.currentTime + seconds,
            this.audio.duration
        ));
    },
    
    // Control de volumen
    setVolume(event) {
        const rect = event.currentTarget.getBoundingClientRect();
        const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
        
        this.volume = percent;
        this.audio.volume = this.volume;
        this.updateVolumeUI();
    },
    
    changeVolume(delta) {
        this.volume = Math.max(0, Math.min(1, this.volume + delta));
        this.audio.volume = this.volume;
        this.updateVolumeUI();
    },
    
    updateVolumeUI() {
        this.volumeFill.style.width = (this.volume * 100) + '%';
    },
    
    // Controles de reproducción
    toggleShuffle() {
        this.shuffle = !this.shuffle;
        document.getElementById('shuffleBtn').style.color = this.shuffle ? 'var(--company-primary)' : 'white';
    },
    
    toggleRepeat() {
        this.repeat = !this.repeat;
        document.getElementById('repeatBtn').style.color = this.repeat ? 'var(--company-primary)' : 'white';
    },
    
    // Eventos
    onTrackEnded() {
        this.trackEvent('content_complete');
        
        if (this.repeat) {
            this.audio.currentTime = 0;
            this.play();
        } else {
            this.nextTrack();
        }
    },
    
    updatePlayPauseButton() {
        const icon = this.playPauseBtn.querySelector('i');
        icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
    },
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    },
    
    // Sistema de publicidad
    startAdTimer() {
        this.adTimer = setInterval(() => {
            this.playAd();
        }, this.config.adInterval);
    },
    
    async playAd() {
        if (this.isAdPlaying) return;
        
        // Pausar música
        this.pause();
        this.isAdPlaying = true;
        
        // Mostrar overlay de publicidad
        const adOverlay = document.getElementById('adOverlay');
        adOverlay.classList.add('active');
        
        // Countdown de publicidad
        let countdown = 30;
        const countdownEl = document.getElementById('adCountdown');
        
        const countdownTimer = setInterval(() => {
            countdown--;
            countdownEl.textContent = countdown;
            
            if (countdown <= 25) {
                document.getElementById('skipButton').style.display = 'block';
            }
            
            if (countdown <= 0) {
                clearInterval(countdownTimer);
                this.endAd();
            }
        }, 1000);
        
        // Registrar reproducción de ad
        this.trackEvent('ad_play');
    },
    
    skipAd() {
        this.trackEvent('ad_skip');
        this.endAd();
    },
    
    endAd() {
        // Ocultar overlay
        document.getElementById('adOverlay').classList.remove('active');
        document.getElementById('skipButton').style.display = 'none';
        document.getElementById('adCountdown').textContent = '30';
        
        // Reanudar música
        this.isAdPlaying = false;
        this.play();
    },
    
    // Tracking de eventos
    async trackEvent(action, data = {}) {
        try {
            await fetch('../api/track-usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: action,
                    data: {
                        ...data,
                        music_id: this.config.musicId,
                        track_index: this.currentTrackIndex,
                        timestamp: this.audio.currentTime
                    },
                    company_id: this.config.companyId,
                    timestamp: new Date().toISOString()
                })
            });
        } catch (error) {
            console.error('Error tracking event:', error);
        }
    }
};

// Funciones globales para los controles HTML
function togglePlayPause() {
    MusicPlayer.togglePlayPause();
}

function previousTrack() {
    MusicPlayer.previousTrack();
}

function nextTrack() {
    MusicPlayer.nextTrack();
}

function toggleShuffle() {
    MusicPlayer.toggleShuffle();
}

function toggleRepeat() {
    MusicPlayer.toggleRepeat();
}

function seek(event) {
    MusicPlayer.seek(event);
}

function setVolume(event) {
    MusicPlayer.setVolume(event);
}

function playTrack(index) {
    MusicPlayer.playTrack(index);
}

function skipAd() {
    MusicPlayer.skipAd();
}

// Exportar para uso global
window.MusicPlayer = MusicPlayer;