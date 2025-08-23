/**
 * passenger-portal/assets/js/touch-controls.js
 * Gestos táctiles mejorados para dispositivos móviles
 */

const TouchControls = {
    // Configuración
    config: {
        swipeThreshold: 50,      // Píxeles mínimos para detectar swipe
        swipeTimeout: 300,       // Tiempo máximo para completar swipe (ms)
        tapDelay: 200,          // Delay para detectar double tap
        longPressDelay: 500,    // Tiempo para long press
        pinchThreshold: 0.2     // Cambio mínimo de escala para pinch
    },
    
    // Estado de gestos
    touchState: {
        startX: 0,
        startY: 0,
        startTime: 0,
        lastTap: 0,
        isPinching: false,
        initialDistance: 0,
        currentElement: null
    },
    
    // Inicializar controles táctiles
    init() {
        // Detectar capacidades táctiles
        this.isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        
        if (!this.isTouchDevice) {
            console.log('Touch controls: Device does not support touch');
            return;
        }
        
        // Aplicar a carruseles
        this.initCarousels();
        
        // Aplicar a reproductores
        this.initPlayers();
        
        // Aplicar a elementos interactivos
        this.initInteractiveElements();
        
        // Prevenir comportamientos por defecto problemáticos
        this.preventDefaults();
    },
    
    // Inicializar gestos para carruseles
    initCarousels() {
        const carousels = document.querySelectorAll('.carousel-track');
        
        carousels.forEach(carousel => {
            // Variables para el momentum scrolling
            let isScrolling = false;
            let startX, scrollLeft, velocity = 0;
            let lastX, lastTime;
            let animationId;
            
            // Touch start
            carousel.addEventListener('touchstart', (e) => {
                isScrolling = true;
                startX = e.touches[0].pageX - carousel.offsetLeft;
                scrollLeft = carousel.scrollLeft;
                lastX = startX;
                lastTime = Date.now();
                velocity = 0;
                
                // Cancelar animación previa
                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
                
                carousel.style.scrollBehavior = 'auto';
            }, { passive: true });
            
            // Touch move
            carousel.addEventListener('touchmove', (e) => {
                if (!isScrolling) return;
                
                const x = e.touches[0].pageX - carousel.offsetLeft;
                const walk = (x - startX) * 1.5; // Multiplicador para sensibilidad
                
                // Calcular velocidad
                const now = Date.now();
                const timeDelta = now - lastTime;
                const distance = x - lastX;
                
                if (timeDelta > 0) {
                    velocity = distance / timeDelta;
                }
                
                lastX = x;
                lastTime = now;
                
                carousel.scrollLeft = scrollLeft - walk;
            }, { passive: true });
            
            // Touch end - momentum scrolling
            carousel.addEventListener('touchend', () => {
                isScrolling = false;
                carousel.style.scrollBehavior = 'smooth';
                
                // Aplicar momentum
                const momentum = velocity * 150;
                let currentMomentum = momentum;
                
                const applyMomentum = () => {
                    if (Math.abs(currentMomentum) > 0.5) {
                        carousel.scrollLeft -= currentMomentum;
                        currentMomentum *= 0.95; // Fricción
                        animationId = requestAnimationFrame(applyMomentum);
                    }
                };
                
                if (Math.abs(momentum) > 1) {
                    applyMomentum();
                }
                
                // Snap to nearest item
                setTimeout(() => {
                    this.snapToNearestItem(carousel);
                }, 100);
            });
            
            // Prevenir scroll vertical mientras se hace swipe horizontal
            let touchStartY = 0;
            carousel.addEventListener('touchstart', (e) => {
                touchStartY = e.touches[0].pageY;
            }, { passive: true });
            
            carousel.addEventListener('touchmove', (e) => {
                const touchY = e.touches[0].pageY;
                const diffY = Math.abs(touchY - touchStartY);
                
                // Si el movimiento es principalmente horizontal, prevenir scroll vertical
                if (diffY < 10) {
                    e.preventDefault();
                }
            }, { passive: false });
        });
    },
    
    // Snap al item más cercano
    snapToNearestItem(carousel) {
        const items = carousel.querySelectorAll('.content-card, .music-card, .game-card');
        if (items.length === 0) return;
        
        const containerRect = carousel.getBoundingClientRect();
        const containerCenter = containerRect.left + containerRect.width / 2;
        
        let closestItem = null;
        let closestDistance = Infinity;
        
        items.forEach(item => {
            const itemRect = item.getBoundingClientRect();
            const itemCenter = itemRect.left + itemRect.width / 2;
            const distance = Math.abs(containerCenter - itemCenter);
            
            if (distance < closestDistance) {
                closestDistance = distance;
                closestItem = item;
            }
        });
        
        if (closestItem) {
            closestItem.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    },
    
    // Inicializar gestos para reproductores
    initPlayers() {
        const videoContainers = document.querySelectorAll('.video-container');
        const musicContainers = document.querySelectorAll('.music-player-container');
        
        // Video player gestos
        videoContainers.forEach(container => {
            this.addSwipeGestures(container, {
                onSwipeLeft: () => this.skipVideo(10),
                onSwipeRight: () => this.skipVideo(-10),
                onSwipeUp: () => this.changeVolume(0.1),
                onSwipeDown: () => this.changeVolume(-0.1),
                onDoubleTap: () => this.togglePlayPause(),
                onLongPress: () => this.showVideoOptions()
            });
            
            // Pinch to zoom para video
            this.addPinchGesture(container, (scale) => {
                const video = container.querySelector('video');
                if (video) {
                    video.style.transform = `scale(${scale})`;
                }
            });
        });
        
        // Music player gestos
        musicContainers.forEach(container => {
            this.addSwipeGestures(container, {
                onSwipeLeft: () => MusicPlayer?.nextTrack(),
                onSwipeRight: () => MusicPlayer?.previousTrack(),
                onSwipeUp: () => this.showPlaylist(),
                onSwipeDown: () => this.hidePlaylist()
            });
        });
    },
    
    // Inicializar elementos interactivos
    initInteractiveElements() {
        // Botones con feedback táctil
        const buttons = document.querySelectorAll('.btn, .control-btn, button');
        buttons.forEach(button => {
            button.addEventListener('touchstart', () => {
                button.style.transform = 'scale(0.95)';
                if ('vibrate' in navigator) {
                    navigator.vibrate(10);
                }
            }, { passive: true });
            
            button.addEventListener('touchend', () => {
                button.style.transform = '';
            }, { passive: true });
        });
        
        // Cards con efecto de presión
        const cards = document.querySelectorAll('.content-card, .music-card, .game-card, .catalog-item');
        cards.forEach(card => {
            let pressTimer;
            
            card.addEventListener('touchstart', (e) => {
                pressTimer = setTimeout(() => {
                    card.style.transform = 'scale(0.98)';
                    this.showQuickActions(card);
                }, 200);
            }, { passive: true });
            
            card.addEventListener('touchend', () => {
                clearTimeout(pressTimer);
                card.style.transform = '';
            }, { passive: true });
            
            card.addEventListener('touchmove', () => {
                clearTimeout(pressTimer);
                card.style.transform = '';
            }, { passive: true });
        });
    },
    
    // Agregar gestos de swipe a un elemento
    addSwipeGestures(element, handlers) {
        let startX = 0, startY = 0, startTime = 0;
        
        element.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
            
            // Detectar double tap
            const timeSinceLastTap = startTime - this.touchState.lastTap;
            if (timeSinceLastTap < this.config.tapDelay && handlers.onDoubleTap) {
                handlers.onDoubleTap();
                this.touchState.lastTap = 0;
            } else {
                this.touchState.lastTap = startTime;
            }
            
            // Long press
            if (handlers.onLongPress) {
                this.touchState.longPressTimer = setTimeout(() => {
                    handlers.onLongPress();
                    if ('vibrate' in navigator) {
                        navigator.vibrate(50);
                    }
                }, this.config.longPressDelay);
            }
        }, { passive: true });
        
        element.addEventListener('touchmove', (e) => {
            // Cancelar long press si hay movimiento
            if (this.touchState.longPressTimer) {
                clearTimeout(this.touchState.longPressTimer);
            }
        }, { passive: true });
        
        element.addEventListener('touchend', (e) => {
            clearTimeout(this.touchState.longPressTimer);
            
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();
            
            const diffX = endX - startX;
            const diffY = endY - startY;
            const timeDiff = endTime - startTime;
            
            // Solo detectar swipe si fue rápido
            if (timeDiff > this.config.swipeTimeout) return;
            
            // Detectar dirección del swipe
            if (Math.abs(diffX) > Math.abs(diffY)) {
                // Swipe horizontal
                if (Math.abs(diffX) > this.config.swipeThreshold) {
                    if (diffX > 0 && handlers.onSwipeRight) {
                        handlers.onSwipeRight();
                    } else if (diffX < 0 && handlers.onSwipeLeft) {
                        handlers.onSwipeLeft();
                    }
                }
            } else {
                // Swipe vertical
                if (Math.abs(diffY) > this.config.swipeThreshold) {
                    if (diffY > 0 && handlers.onSwipeDown) {
                        handlers.onSwipeDown();
                    } else if (diffY < 0 && handlers.onSwipeUp) {
                        handlers.onSwipeUp();
                    }
                }
            }
        }, { passive: true });
    },
    
    // Agregar gesto de pinch
    addPinchGesture(element, onPinch) {
        let initialDistance = 0;
        let currentScale = 1;
        
        element.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                initialDistance = this.getDistance(e.touches[0], e.touches[1]);
                this.touchState.isPinching = true;
            }
        }, { passive: true });
        
        element.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && this.touchState.isPinching) {
                const currentDistance = this.getDistance(e.touches[0], e.touches[1]);
                currentScale = currentDistance / initialDistance;
                
                if (Math.abs(currentScale - 1) > this.config.pinchThreshold) {
                    onPinch(currentScale);
                }
            }
        }, { passive: true });
        
        element.addEventListener('touchend', () => {
            this.touchState.isPinching = false;
        }, { passive: true });
    },
    
    // Calcular distancia entre dos puntos
    getDistance(touch1, touch2) {
        const dx = touch1.clientX - touch2.clientX;
        const dy = touch1.clientY - touch2.clientY;
        return Math.sqrt(dx * dx + dy * dy);
    },
    
    // Prevenir comportamientos por defecto problemáticos
    preventDefaults() {
        // Prevenir zoom con double tap en iOS
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Prevenir pull-to-refresh accidental
        let startY = 0;
        document.addEventListener('touchstart', (e) => {
            startY = e.touches[0].pageY;
        }, { passive: true });
        
        document.addEventListener('touchmove', (e) => {
            const y = e.touches[0].pageY;
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            
            // Si está en el top y hace swipe down, prevenir
            if (scrollTop === 0 && y > startY) {
                e.preventDefault();
            }
        }, { passive: false });
    },
    
    // Funciones auxiliares para acciones
    skipVideo(seconds) {
        if (window.VideoPlayer) {
            VideoPlayer.skip(seconds);
            this.showToast(`${seconds > 0 ? '+' : ''}${seconds}s`);
        }
    },
    
    changeVolume(delta) {
        if (window.VideoPlayer) {
            VideoPlayer.changeVolume(delta);
            const newVolume = Math.round((VideoPlayer.volume || 0.7) * 100);
            this.showToast(`Volumen: ${newVolume}%`);
        }
    },
    
    togglePlayPause() {
        if (window.VideoPlayer) {
            VideoPlayer.togglePlayPause();
        } else if (window.MusicPlayer) {
            MusicPlayer.togglePlayPause();
        }
    },
    
    showVideoOptions() {
        // Mostrar opciones de video (calidad, subtítulos, etc.)
        console.log('Showing video options...');
    },
    
    showPlaylist() {
        const playlist = document.querySelector('.playlist-panel');
        if (playlist) {
            playlist.style.transform = 'translateY(0)';
        }
    },
    
    hidePlaylist() {
        const playlist = document.querySelector('.playlist-panel');
        if (playlist) {
            playlist.style.transform = 'translateY(100%)';
        }
    },
    
    showQuickActions(element) {
        // Mostrar acciones rápidas para el elemento
        console.log('Quick actions for:', element);
    },
    
    // Mostrar toast notification
    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'touch-toast';
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.25rem;
            z-index: 10000;
            pointer-events: none;
            animation: fadeInOut 1s ease-out;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 1000);
    }
};

// Estilos para animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeInOut {
        0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
        50% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        100% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    }
    
    /* Mejorar touch responsiveness */
    * {
        -webkit-tap-highlight-color: transparent;
        -webkit-touch-callout: none;
    }
    
    /* Scroll más suave en iOS */
    .carousel-track {
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
    }
    
    /* Prevenir selección de texto accidental */
    .content-card,
    .music-card,
    .game-card,
    .btn,
    .control-btn {
        -webkit-user-select: none;
        user-select: none;
    }
`;
document.head.appendChild(style);

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => TouchControls.init());
} else {
    TouchControls.init();
}

// Exportar para uso global
window.TouchControls = TouchControls;