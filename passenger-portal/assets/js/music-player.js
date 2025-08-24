/**
 * passenger-portal/assets/js/music-player.js
 * Control del reproductor de música/video con visualizador
 */

const MusicPlayer = {
    // Configuración
    config: null,
    
    // Elementos del DOM
    audio: null,
    video: null,
    currentMedia: null, // El elemento actualmente en uso (audio o video)
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
    isVideo: false,
    
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
        this.video = document.getElementById('musicVideo');
        this.albumArt = document.getElementById('albumArt');
        this.playPauseBtn = document.getElementById('playPauseBtn');
        this.progressBar = document.getElementById('progressBar');
        this.progressFill = document.getElementById('progressFill');
        this.currentTimeEl = document.getElementById('currentTime');
        this.durationEl = document.getElementById('duration');
        this.volumeFill = document.getElementById('volumeFill');
        this.visualizerCanvas = document.getElementById('visualizer');
        
        // Establecer elemento multimedia actual
        this.isVideo = config.isVideo;
        this.currentMedia = this.isVideo ? this.video : this.audio;
        
        // Configurar eventos
        this.setupMediaEvents();
        this.setupControlEvents();
        
        // Inicializar visualizador solo para audio
        if (!this.isVideo && this.visualizerCanvas) {
            this.initVisualizer();
        }
        
        // Configurar volumen inicial
        if (this.currentMedia) {
            this.currentMedia.volume = this.volume;
            this.updateVolumeUI();
        }
        
        // Registrar inicio de sesión
        this.trackEvent('session_start');
    },
    
    // Configurar eventos del media (audio o video)
    setupMediaEvents() {
        // Configurar eventos para ambos elementos si existen
        [this.audio, this.video].forEach(media => {
            if (!media) return;
            
            media.addEventListener('loadedmetadata', () => {
                if (media === this.currentMedia) {
                    this.updateDuration();
                }
            });
            
            media.addEventListener('timeupdate', () => {
                if (media === this.currentMedia) {
                    this.updateProgress();
                }
            });
            
            media.addEventListener('ended', () => {
                if (media === this.currentMedia) {
                    this.onTrackEnded();
                }
            });
            
            media.addEventListener('play', () => {
                if (media === this.currentMedia) {
                    this.isPlaying = true;
                    this.updatePlayPauseButton();
                }
            });
            
            media.addEventListener('pause', () => {
                if (media === this.currentMedia) {
                    this.isPlaying = false;
                    this.updatePlayPauseButton();
                }
            });
            
            media.addEventListener('error', (e) => {
                if (media === this.currentMedia) {
                    console.error('Error loading media:', e);
                    // No auto-skip en caso de error para permitir debug
                }
            });
        });
    },
    
    // Cambiar elemento multimedia
    switchMedia(useVideo) {
        console.log('Switching media to:', useVideo ? 'video' : 'audio');
        
        // Pausar el medio actual
        if (this.currentMedia && !this.currentMedia.paused) {
            this.currentMedia.pause();
        }
        
        // Cambiar el medio
        this.isVideo = useVideo;
        this.currentMedia = useVideo ? this.video : this.audio;
        
        // Si cambiamos a audio y no existe el visualizador, iniciarlo
        if (!useVideo && !this.audioContext && this.visualizerCanvas) {
            this.initVisualizer();
        }
    },
    
    // Configurar eventos de controles
    setupControlEvents() {
        // Click en barra de progreso
        if (this.progressBar) {
            this.progressBar.addEventListener('click', (e) => this.seek(e));
        }
        
        // Teclas de acceso directo
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT') return;
            
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
                    e.preventDefault();
                    this.changeVolume(0.1);
                    break;
                case 'ArrowDown':
                    e.preventDefault();
                    this.changeVolume(-0.1);
                    break;
            }
        });
    },
    
    // Inicializar visualizador (solo para audio)
    initVisualizer() {
        if (!this.audio || !this.visualizerCanvas) return;
        
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
        if (!this.analyser || !this.visualizerCtx) return;
        
        const bufferLength = this.analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);
        
        const draw = () => {
            this.animationId = requestAnimationFrame(draw);
            
            // Solo animar si estamos reproduciendo audio
            if (!this.isVideo && this.isPlaying) {
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
            }
        };
        
        draw();
    },
    
    // Control de reproducción
    play() {
        if (!this.currentMedia) return;
        
        const playPromise = this.currentMedia.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
                this.isPlaying = true;
                this.updatePlayPauseButton();
                
                if (this.albumArt && !this.isVideo) {
                    this.albumArt.classList.add('playing');
                }
                
                // Reanudar contexto de audio si está suspendido
                if (this.audioContext?.state === 'suspended') {
                    this.audioContext.resume();
                }
                
                this.trackEvent('content_play');
            }).catch(error => {
                console.error('Error playing media:', error);
            });
        }
    },
    
    pause() {
        if (!this.currentMedia) return;
        
        this.currentMedia.pause();
        this.isPlaying = false;
        this.updatePlayPauseButton();
        
        if (this.albumArt) {
            this.albumArt.classList.remove('playing');
        }
        
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
        
        // Llamar a playTrack desde el reproductor principal
        if (window.playTrack) {
            window.playTrack(this.currentTrackIndex);
        }
    },
    
    previousTrack() {
        if (this.currentMedia && this.currentMedia.currentTime > 3) {
            // Si han pasado más de 3 segundos, reiniciar la canción actual
            this.currentMedia.currentTime = 0;
        } else {
            // Ir a la canción anterior
            this.currentTrackIndex = (this.currentTrackIndex - 1 + this.playlist.length) % this.playlist.length;
            
            if (window.playTrack) {
                window.playTrack(this.currentTrackIndex);
            }
        }
    },
    
    // Control de progreso
    updateProgress() {
        if (!this.currentMedia || !this.progressFill || !this.currentTimeEl) return;
        
        const progress = (this.currentMedia.currentTime / this.currentMedia.duration) * 100;
        this.progressFill.style.width = progress + '%';
        this.currentTimeEl.textContent = this.formatTime(this.currentMedia.currentTime);
    },
    
    updateDuration() {
        if (!this.currentMedia || !this.durationEl) return;
        
        this.durationEl.textContent = this.formatTime(this.currentMedia.duration);
    },
    
    seek(event) {
        if (!this.currentMedia || !this.progressBar) return;
        
        const rect = this.progressBar.getBoundingClientRect();
        const percent = (event.clientX - rect.left) / rect.width;
        this.currentMedia.currentTime = percent * this.currentMedia.duration;
    },
    
    skip(seconds) {
        if (!this.currentMedia) return;
        
        this.currentMedia.currentTime = Math.max(0, Math.min(
            this.currentMedia.currentTime + seconds,
            this.currentMedia.duration
        ));
    },
    
    // Control de volumen
    setVolume(event) {
        if (!this.currentMedia) return;
        
        const rect = event.currentTarget.getBoundingClientRect();
        const percent = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
        
        this.volume = percent;
        this.currentMedia.volume = this.volume;
        this.updateVolumeUI();
    },
    
    changeVolume(delta) {
        if (!this.currentMedia) return;
        
        this.volume = Math.max(0, Math.min(1, this.volume + delta));
        this.currentMedia.volume = this.volume;
        this.updateVolumeUI();
    },
    
    updateVolumeUI() {
        if (!this.volumeFill) return;
        
        this.volumeFill.style.width = (this.volume * 100) + '%';
        
        // Actualizar ícono de volumen
        const volumeIcon = document.getElementById('volumeIcon');
        if (volumeIcon) {
            if (this.volume === 0 || (this.currentMedia && this.currentMedia.muted)) {
                volumeIcon.className = 'fas fa-volume-mute';
            } else if (this.volume < 0.5) {
                volumeIcon.className = 'fas fa-volume-down';
            } else {
                volumeIcon.className = 'fas fa-volume-up';
            }
        }
    },
    
    toggleMute() {
        if (!this.currentMedia) return;
        
        this.currentMedia.muted = !this.currentMedia.muted;
        this.updateVolumeUI();
    },
    
    // Controles de reproducción
    toggleShuffle() {
        this.shuffle = !this.shuffle;
        const shuffleBtn = document.getElementById('shuffleBtn');
        if (shuffleBtn) {
            shuffleBtn.style.color = this.shuffle ? 'var(--company-primary, #1db954)' : '';
        }
    },
    
    toggleRepeat() {
        this.repeat = !this.repeat;
        const repeatBtn = document.getElementById('repeatBtn');
        if (repeatBtn) {
            repeatBtn.style.color = this.repeat ? 'var(--company-primary, #1db954)' : '';
        }
    },
    
    // Eventos
    onTrackEnded() {
        this.trackEvent('content_complete');
        
        if (this.repeat) {
            this.currentMedia.currentTime = 0;
            this.play();
        } else {
            this.nextTrack();
        }
    },
    
    updatePlayPauseButton() {
        if (!this.playPauseBtn) return;
        
        const icon = this.playPauseBtn.querySelector('i');
        if (icon) {
            icon.className = this.isPlaying ? 'fas fa-pause' : 'fas fa-play';
        }
    },
    
    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
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
                        content_id: this.config.musicId,
                        content_type: this.isVideo ? 'video' : 'music',
                        track_index: this.currentTrackIndex,
                        timestamp: this.currentMedia ? this.currentMedia.currentTime : 0
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

function toggleMute() {
    MusicPlayer.toggleMute();
}

function skipAd() {
    // Ya no usamos publicidad en música
}

// Exportar para uso global
window.MusicPlayer = MusicPlayer;