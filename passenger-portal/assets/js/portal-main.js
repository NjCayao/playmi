/**
 * passenger-portal/assets/js/portal-main.js
 * JavaScript principal del portal de pasajeros - MEJORADO
 */

const Portal = {
    config: {
        companyId: null,
        packageType: null,
        adsEnabled: true,
        apiUrl: '/playmi/passenger-portal/api/',
        contentUrl: '/playmi/content/'
    },
    
    // Inicializar portal
    init(config) {
        Object.assign(this.config, config);
        
        // Configurar eventos
        this.setupEvents();
        
        // Iniciar tracking de sesión
        this.startSessionTracking();
        
        // Configurar header scroll
        this.setupHeaderScroll();
        
        // Inicializar efectos visuales
        this.initVisualEffects();
    },
    
    // Configurar eventos
    setupEvents() {
        // Touch/swipe para carruseles
        document.querySelectorAll('.carousel-track').forEach(carousel => {
            this.enableTouchScroll(carousel);
        });
        
        // Click en cards de contenido
        document.addEventListener('click', (e) => {
            const card = e.target.closest('.content-card');
            if (card && !e.target.closest('.card-action-btn')) {
                this.handleContentClick(card);
            }
        });
        
        // Búsqueda
        const searchBtn = document.querySelector('.search-btn');
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.toggleSearch());
        }
        
        // Hover effects para cards
        this.setupCardHoverEffects();
    },
    
    // Header que cambia al hacer scroll
    setupHeaderScroll() {
        const header = document.getElementById('portalHeader');
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 10) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    },
    
    // Efectos visuales adicionales
    initVisualEffects() {
        // Lazy loading mejorado para imágenes
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Animación de entrada para secciones
        const sections = document.querySelectorAll('.content-section');
        const sectionObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.1
        });
        
        sections.forEach(section => {
            sectionObserver.observe(section);
        });
    },
    
    // Configurar efectos hover para cards
    setupCardHoverEffects() {
        // Agregar delay para evitar hover accidental
        let hoverTimeout;
        
        document.addEventListener('mouseover', (e) => {
            const card = e.target.closest('.content-card');
            if (card) {
                clearTimeout(hoverTimeout);
                hoverTimeout = setTimeout(() => {
                    this.preloadContentDetails(card.dataset.id);
                }, 300);
            }
        });
        
        document.addEventListener('mouseout', (e) => {
            const card = e.target.closest('.content-card');
            if (card) {
                clearTimeout(hoverTimeout);
            }
        });
    },
    
    // Precargar detalles del contenido
    async preloadContentDetails(contentId) {
        // Implementar precarga de detalles si es necesario
        console.log('Preloading content:', contentId);
    },
    
    // Cargar contenido
    async loadContent(type, containerId) {
        try {
            const response = await fetch(`${this.config.apiUrl}get-content.php?type=${type}&limit=20`);
            const data = await response.json();
            
            if (data.success) {
                this.renderCarousel(data.data, containerId, type);
            }
        } catch (error) {
            console.error('Error loading content:', error);
            this.showError(containerId);
        }
    },
    
    // Cargar solo datos de contenido (sin renderizar)
    async loadContentData(type) {
        try {
            const response = await fetch(`${this.config.apiUrl}get-content.php?type=${type}&limit=20`);
            return await response.json();
        } catch (error) {
            console.error('Error loading content data:', error);
            return { success: false, data: [] };
        }
    },
    
    // Renderizar carrusel básico (compatibilidad)
    renderCarousel(items, containerId, type) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        // Eliminar skeletons de carga
        container.querySelectorAll('.loading-skeleton').forEach(el => el.remove());
        
        container.innerHTML = items.map(item => `
            <div class="content-card" data-id="${item.id}" data-type="${type}">
                <img src="${this.getThumbnailUrl(item)}" 
                     alt="${item.titulo}" 
                     class="card-thumbnail"
                     loading="lazy">
                <div class="card-info">
                    <h4 class="card-title">${item.titulo}</h4>
                    <p class="card-meta">${this.getContentMeta(item, type)}</p>
                </div>
            </div>
        `).join('');
        
        // Re-aplicar eventos a las nuevas cards
        this.setupCardHoverEffects();
    },
    
    // Obtener URL de thumbnail con fallback mejorado
    getThumbnailUrl(item) {
        if (item.thumbnail_path) {
            return this.config.contentUrl + item.thumbnail_path;
        }
        
        // Generar placeholder SVG con el título
        const title = item.titulo || 'Contenido';
        const type = item.tipo || 'pelicula';
        const colors = {
            'pelicula': '#b71c1c',
            'musica': '#1a237e',
            'juego': '#2e7d32'
        };
        const bgColor = colors[type] || '#333';
        
        // SVG placeholder
        return `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='169' viewBox='0 0 300 169'%3E%3Crect fill='${encodeURIComponent(bgColor)}' width='300' height='169'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-family='Arial' font-size='14' opacity='0.8'%3E${encodeURIComponent(title.substring(0, 20))}%3C/text%3E%3C/svg%3E`;
    },
    
    // Obtener metadata del contenido mejorada
    getContentMeta(item, type) {
        const parts = [];
        
        switch(type) {
            case 'movies':
                if (item.duracion) {
                    const hours = Math.floor(item.duracion / 3600);
                    const minutes = Math.floor((item.duracion % 3600) / 60);
                    if (hours > 0) {
                        parts.push(`${hours}h ${minutes}min`);
                    } else {
                        parts.push(`${minutes} min`);
                    }
                }
                if (item.anio_lanzamiento) parts.push(item.anio_lanzamiento);
                if (item.calificacion) parts.push(item.calificacion);
                break;
                
            case 'music':
                if (item.artista) parts.push(item.artista);
                if (item.album) parts.push(item.album);
                if (item.duracion) {
                    const minutes = Math.floor(item.duracion / 60);
                    const seconds = item.duracion % 60;
                    parts.push(`${minutes}:${seconds.toString().padStart(2, '0')}`);
                }
                break;
                
            case 'games':
                if (item.categoria) parts.push(item.categoria);
                if (item.dificultad) parts.push(item.dificultad);
                break;
        }
        
        return parts.join(' • ');
    },
    
    // Manejar click en contenido
    handleContentClick(card) {
        const id = card.dataset.id;
        const type = card.dataset.type;
        
        // Efecto visual de click
        card.style.transform = 'scale(0.95)';
        setTimeout(() => {
            card.style.transform = '';
        }, 100);
        
        // Registrar interacción
        this.trackInteraction('content_click', { id, type });
        
        // Navegar al reproductor correspondiente
        this.navigateToPlayer(type, id);
    },
    
    // Navegar al reproductor
    navigateToPlayer(type, id) {
        let url = '';
        
        switch(type) {
            case 'movies':
            case 'movie':
            case 'continue':
            case 'trending':
                url = `player/video-player.php?id=${id}`;
                break;
            case 'music':
                url = `player/music-player.php?id=${id}`;
                break;
            case 'games':
            case 'game':
                url = `player/game-launcher.php?id=${id}`;
                break;
        }
        
        if (url) {
            // Guardar en "continuar viendo"
            this.saveToContinueWatching(type, id);
            
            // Transición suave
            document.body.style.opacity = '0.8';
            setTimeout(() => {
                window.location.href = url;
            }, 200);
        }
    },
    
    // Guardar en continuar viendo
    saveToContinueWatching(type, id) {
        try {
            const continueWatching = JSON.parse(localStorage.getItem('continueWatching') || '[]');
            const item = {
                type,
                id,
                timestamp: Date.now(),
                progress: 0
            };
            
            // Eliminar si ya existe
            const index = continueWatching.findIndex(i => i.id === id && i.type === type);
            if (index > -1) {
                continueWatching.splice(index, 1);
            }
            
            // Agregar al principio
            continueWatching.unshift(item);
            
            // Mantener solo los últimos 10
            continueWatching.splice(10);
            
            localStorage.setItem('continueWatching', JSON.stringify(continueWatching));
        } catch (error) {
            console.error('Error saving to continue watching:', error);
        }
    },
    
    // Toggle búsqueda
    toggleSearch() {
        const searchOverlay = document.getElementById('searchOverlay');
        
        if (!searchOverlay) {
            this.createSearchOverlay();
        } else {
            searchOverlay.classList.toggle('active');
            if (searchOverlay.classList.contains('active')) {
                searchOverlay.querySelector('input').focus();
            }
        }
    },
    
    // Crear overlay de búsqueda
    createSearchOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'searchOverlay';
        overlay.className = 'search-overlay';
        overlay.innerHTML = `
            <div class="search-container">
                <button class="close-search" onclick="Portal.toggleSearch()">
                    <i class="fas fa-times"></i>
                </button>
                <input type="text" 
                       class="search-input" 
                       placeholder="Buscar películas, música o juegos..."
                       onkeyup="Portal.performSearch(this.value)">
                <div class="search-results" id="searchResults"></div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Agregar estilos
        const style = document.createElement('style');
        style.textContent = `
            .search-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.95);
                z-index: 9999;
                display: none;
                opacity: 0;
                transition: opacity 0.3s;
            }
            
            .search-overlay.active {
                display: flex;
                opacity: 1;
                align-items: flex-start;
                padding-top: 100px;
            }
            
            .search-container {
                width: 90%;
                max-width: 800px;
                margin: 0 auto;
            }
            
            .close-search {
                position: absolute;
                top: 20px;
                right: 20px;
                background: none;
                border: none;
                color: white;
                font-size: 2rem;
                cursor: pointer;
            }
            
            .search-input {
                width: 100%;
                background: transparent;
                border: none;
                border-bottom: 2px solid white;
                color: white;
                font-size: 2rem;
                padding: 1rem 0;
                outline: none;
            }
            
            .search-results {
                margin-top: 2rem;
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }
        `;
        document.head.appendChild(style);
        
        setTimeout(() => overlay.classList.add('active'), 10);
    },
    
    // Realizar búsqueda
    async performSearch(query) {
        if (query.length < 2) {
            document.getElementById('searchResults').innerHTML = '';
            return;
        }
        
        try {
            const response = await fetch(`${this.config.apiUrl}get-content.php?search=${encodeURIComponent(query)}&limit=20`);
            const data = await response.json();
            
            if (data.success) {
                this.renderSearchResults(data.data);
            }
        } catch (error) {
            console.error('Error searching:', error);
        }
    },
    
    // Renderizar resultados de búsqueda
    renderSearchResults(results) {
        const container = document.getElementById('searchResults');
        
        if (results.length === 0) {
            container.innerHTML = '<p style="color: #666; grid-column: 1/-1;">No se encontraron resultados</p>';
            return;
        }
        
        container.innerHTML = results.map(item => `
            <div class="search-result-item content-card" 
                 data-id="${item.id}" 
                 data-type="${this.mapContentType(item.tipo)}"
                 onclick="Portal.handleContentClick(this)">
                <img src="${this.getThumbnailUrl(item)}" 
                     alt="${item.titulo}" 
                     class="card-thumbnail">
                <h4 class="card-title">${item.titulo}</h4>
            </div>
        `).join('');
    },
    
    // Mapear tipo de contenido
    mapContentType(tipo) {
        const typeMap = {
            'pelicula': 'movies',
            'musica': 'music',
            'juego': 'games'
        };
        return typeMap[tipo] || tipo;
    },
    
    // Habilitar scroll táctil mejorado
    enableTouchScroll(element) {
        let isDown = false;
        let startX;
        let scrollLeft;
        let velocity = 0;
        let momentumID;
        
        const startDragging = (pageX) => {
            isDown = true;
            element.classList.add('dragging');
            startX = pageX - element.offsetLeft;
            scrollLeft = element.scrollLeft;
            cancelMomentumTracking();
        };
        
        const stopDragging = () => {
            isDown = false;
            element.classList.remove('dragging');
            beginMomentumTracking();
        };
        
        const move = (pageX) => {
            if (!isDown) return;
            const x = pageX - element.offsetLeft;
            const walk = (x - startX) * 2;
            const prevScrollLeft = element.scrollLeft;
            element.scrollLeft = scrollLeft - walk;
            velocity = element.scrollLeft - prevScrollLeft;
        };
        
        // Momentum scrolling
        const momentumLoop = () => {
            element.scrollLeft += velocity;
            velocity *= 0.95;
            if (Math.abs(velocity) > 0.5) {
                momentumID = requestAnimationFrame(momentumLoop);
            }
        };
        
        const beginMomentumTracking = () => {
            cancelMomentumTracking();
            momentumID = requestAnimationFrame(momentumLoop);
        };
        
        const cancelMomentumTracking = () => {
            if (momentumID) {
                cancelAnimationFrame(momentumID);
            }
        };
        
        // Mouse events
        element.addEventListener('mousedown', (e) => startDragging(e.pageX));
        element.addEventListener('mouseleave', stopDragging);
        element.addEventListener('mouseup', stopDragging);
        element.addEventListener('mousemove', (e) => move(e.pageX));
        
        // Touch events
        element.addEventListener('touchstart', (e) => startDragging(e.touches[0].pageX));
        element.addEventListener('touchend', stopDragging);
        element.addEventListener('touchmove', (e) => move(e.touches[0].pageX));
        
        // Prevenir selección de texto mientras arrastra
        element.addEventListener('selectstart', (e) => {
            if (isDown) e.preventDefault();
        });
    },
    
    // Mostrar error
    showError(containerId) {
        const container = document.getElementById(containerId);
        if (container) {
            container.innerHTML = `
                <div style="padding: 2rem; color: #666; text-align: center; grid-column: 1/-1;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Error al cargar el contenido. Por favor, intenta de nuevo.</p>
                </div>
            `;
        }
    },
    
    // Tracking de sesión mejorado
    startSessionTracking() {
        // Registrar inicio de sesión
        this.trackInteraction('session_start', {
            userAgent: navigator.userAgent,
            screenSize: `${window.innerWidth}x${window.innerHeight}`,
            timestamp: new Date().toISOString()
        });
        
        // Heartbeat cada 30 segundos
        setInterval(() => {
            this.trackInteraction('heartbeat', {
                timestamp: new Date().toISOString(),
                currentPath: window.location.pathname
            });
        }, 30000);
        
        // Registrar cuando el usuario abandona
        window.addEventListener('beforeunload', () => {
            this.trackInteraction('session_end', {
                timestamp: new Date().toISOString(),
                duration: Date.now() - window.performance.timing.navigationStart
            });
        });
    },
    
    // Registrar interacciones mejorado
    async trackInteraction(action, data) {
        try {
            // Usar sendBeacon para mejor confiabilidad
            const payload = JSON.stringify({
                action: action,
                data: data,
                company_id: this.config.companyId,
                timestamp: new Date().toISOString()
            });
            
            if (navigator.sendBeacon) {
                navigator.sendBeacon(`${this.config.apiUrl}track-usage.php`, payload);
            } else {
                // Fallback a fetch
                await fetch(`${this.config.apiUrl}track-usage.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: payload
                });
            }
        } catch (error) {
            console.error('Error tracking interaction:', error);
        }
    }
};

// Exportar para uso global
window.Portal = Portal;