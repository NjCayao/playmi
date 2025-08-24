<?php
/**
 * passenger-portal/includes/carousel-component.php
 * Componente de carrusel reutilizable para contenido
 * 
 * Uso:
 * $carouselData = [
 *     'id' => 'trending-movies',
 *     'title' => 'Tendencias',
 *     'type' => 'movies',
 *     'items' => $arrayDeItems,
 *     'showSeeAll' => true
 * ];
 * renderCarousel($carouselData);
 */

// Asegurar que solo se acceda desde el portal
if (!defined('PORTAL_ACCESS')) {
    die('Acceso directo no permitido');
}

/**
 * Renderizar un carrusel de contenido
 * @param array $config Configuración del carrusel
 */
function renderCarousel($config) {
    // Valores por defecto
    $defaults = [
        'id' => 'carousel-' . uniqid(),
        'title' => 'Contenido',
        'type' => 'movies',
        'items' => [],
        'showSeeAll' => true,
        'seeAllLink' => null,
        'itemsPerView' => 'auto',
        'lazyLoad' => true,
        'showBadges' => true,
        'autoScroll' => false
    ];
    
    $config = array_merge($defaults, $config);
    
    // Si no hay items, no renderizar nada
    if (empty($config['items'])) {
        return;
    }
    
    // Determinar enlace "Ver todo"
    if ($config['showSeeAll'] && !$config['seeAllLink']) {
        $typeMap = [
            'movies' => 'movies.php',
            'music' => 'music.php',
            'games' => 'games.php'
        ];
        $config['seeAllLink'] = $typeMap[$config['type']] ?? '#';
    }
    
    ?>
    <section class="content-section carousel-section" id="<?php echo htmlspecialchars($config['id']); ?>">
        <div class="section-header">
            <h2 class="section-title"><?php echo htmlspecialchars($config['title']); ?></h2>
            <?php if ($config['showSeeAll']): ?>
                <a href="<?php echo htmlspecialchars($config['seeAllLink']); ?>" class="see-all">
                    Ver todo <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="content-carousel">
            <button class="carousel-nav prev" onclick="scrollCarousel('<?php echo $config['id']; ?>', 'prev')">
                <i class="fas fa-chevron-left"></i>
            </button>
            
            <div class="carousel-track" id="<?php echo $config['id']; ?>-track">
                <?php foreach ($config['items'] as $index => $item): ?>
                    <?php echo renderCarouselItem($item, $config['type'], $config, $index); ?>
                <?php endforeach; ?>
            </div>
            
            <button class="carousel-nav next" onclick="scrollCarousel('<?php echo $config['id']; ?>', 'next')">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </section>
    
    <?php if ($config['autoScroll']): ?>
    <script>
        // Auto-scroll opcional
        (function() {
            const track = document.getElementById('<?php echo $config['id']; ?>-track');
            let scrollInterval;
            let isHovering = false;
            
            track.addEventListener('mouseenter', () => {
                isHovering = true;
                clearInterval(scrollInterval);
            });
            
            track.addEventListener('mouseleave', () => {
                isHovering = false;
                startAutoScroll();
            });
            
            function startAutoScroll() {
                scrollInterval = setInterval(() => {
                    if (!isHovering) {
                        const maxScroll = track.scrollWidth - track.clientWidth;
                        if (track.scrollLeft >= maxScroll) {
                            track.scrollLeft = 0;
                        } else {
                            track.scrollLeft += 2;
                        }
                    }
                }, 50);
            }
            
            // Iniciar auto-scroll después de 3 segundos
            setTimeout(startAutoScroll, 3000);
        })();
    </script>
    <?php endif;
}

/**
 * Renderizar un item individual del carrusel
 */
function renderCarouselItem($item, $type, $config, $index) {
    // Generar thumbnail URL
    $thumbnailUrl = '';
    if (!empty($item['thumbnail_path'])) {
        $thumbnailUrl = CONTENT_URL . $item['thumbnail_path'];
    } else {
        // Placeholder según tipo
        $placeholders = [
            'movies' => 'assets/images/placeholder-movie.jpg',
            'music' => 'assets/images/placeholder-music.jpg',
            'games' => 'assets/images/placeholder-game.jpg'
        ];
        $thumbnailUrl = $placeholders[$type] ?? 'assets/images/placeholder.jpg';
    }
    
    // Preparar metadata según tipo
    $metadata = getItemMetadata($item, $type);
    
    // Preparar badges
    $badges = [];
    if ($config['showBadges']) {
        $badges = getItemBadges($item, $type);
    }
    
    ob_start();
    ?>
    <div class="content-card" 
         data-id="<?php echo $item['id']; ?>" 
         data-type="<?php echo $type; ?>"
         data-index="<?php echo $index; ?>">
        
        <div class="card-thumbnail-wrapper">
            <?php if ($config['lazyLoad'] && $index > 5): ?>
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='169'%3E%3Crect fill='%23333' width='300' height='169'/%3E%3C/svg%3E"
                     data-src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                     alt="<?php echo htmlspecialchars($item['titulo']); ?>" 
                     class="card-thumbnail lazy"
                     loading="lazy">
            <?php else: ?>
                <img src="<?php echo htmlspecialchars($thumbnailUrl); ?>" 
                     alt="<?php echo htmlspecialchars($item['titulo']); ?>" 
                     class="card-thumbnail">
            <?php endif; ?>
            
            <?php if (!empty($badges)): ?>
                <div class="item-badges">
                    <?php foreach ($badges as $badge): ?>
                        <span class="badge <?php echo $badge['class']; ?>">
                            <?php echo htmlspecialchars($badge['text']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="card-hover-overlay">
                <button class="card-play-btn" onclick="playContent('<?php echo $type; ?>', <?php echo $item['id']; ?>)">
                    <i class="fas fa-play"></i>
                </button>
            </div>
        </div>
        
        <div class="card-expanded-info">
            <h4 class="card-title"><?php echo htmlspecialchars($item['titulo']); ?></h4>
            <div class="card-meta">
                <?php echo implode(' <span class="meta-separator">•</span> ', array_map('htmlspecialchars', $metadata)); ?>
            </div>
            
            <div class="card-actions">
                <button class="card-action-btn play" onclick="playContent('<?php echo $type; ?>', <?php echo $item['id']; ?>)">
                    <i class="fas fa-play"></i>
                </button>
                <button class="card-action-btn" onclick="addToList(<?php echo $item['id']; ?>)">
                    <i class="fas fa-plus"></i>
                </button>
                <button class="card-action-btn" onclick="showInfo(<?php echo $item['id']; ?>)">
                    <i class="fas fa-info"></i>
                </button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Obtener metadata del item según tipo
 */
function getItemMetadata($item, $type) {
    $metadata = [];
    
    switch ($type) {
        case 'movies':
            // Duración
            if (!empty($item['duracion'])) {
                $hours = floor($item['duracion'] / 3600);
                $minutes = floor(($item['duracion'] % 3600) / 60);
                $metadata[] = $hours > 0 ? "{$hours}h {$minutes}min" : "{$minutes} min";
            }
            // Año
            if (!empty($item['anio_lanzamiento'])) {
                $metadata[] = $item['anio_lanzamiento'];
            }
            // Calificación
            if (!empty($item['calificacion'])) {
                $metadata[] = $item['calificacion'];
            }
            // Match score (simulado)
            $metadata[] = rand(85, 98) . '% coincidencia';
            break;
            
        case 'music':
            // Artista (si existiera en la BD)
            if (!empty($item['artista'])) {
                $metadata[] = $item['artista'];
            }
            // Duración
            if (!empty($item['duracion'])) {
                $minutes = floor($item['duracion'] / 60);
                $seconds = $item['duracion'] % 60;
                $metadata[] = sprintf('%d:%02d', $minutes, $seconds);
            }
            // Género
            if (!empty($item['genero'])) {
                $metadata[] = $item['genero'];
            }
            break;
            
        case 'games':
            // Categoría
            if (!empty($item['categoria'])) {
                $metadata[] = $item['categoria'];
            }
            // Jugadores (simulado)
            $metadata[] = '1-4 jugadores';
            break;
    }
    
    return $metadata;
}

/**
 * Obtener badges del item
 */
function getItemBadges($item, $type) {
    $badges = [];
    
    // Badge HD/4K según calidad
    if ($type === 'movies' && !empty($item['calidad'])) {
        if (stripos($item['calidad'], '4k') !== false) {
            $badges[] = ['text' => '4K', 'class' => '4k'];
        } elseif (stripos($item['calidad'], 'hd') !== false) {
            $badges[] = ['text' => 'HD', 'class' => 'hd'];
        }
    }
    
    // Badge NUEVO si es reciente (últimos 7 días)
    if (!empty($item['created_at'])) {
        $createdTime = strtotime($item['created_at']);
        $daysAgo = (time() - $createdTime) / (60 * 60 * 24);
        if ($daysAgo <= 7) {
            $badges[] = ['text' => 'NUEVO', 'class' => 'new'];
        }
    }
    
    // Badge POPULAR si tiene muchas vistas
    if (!empty($item['descargas_count']) && $item['descargas_count'] > 100) {
        $badges[] = ['text' => 'POPULAR', 'class' => 'popular'];
    }
    
    return $badges;
}
?>

<!-- JavaScript del carrusel -->
<script>
// Función para scroll del carrusel
function scrollCarousel(carouselId, direction) {
    const track = document.getElementById(carouselId + '-track');
    const cardWidth = track.querySelector('.content-card').offsetWidth;
    const gap = 8; // Gap entre cards
    const scrollAmount = (cardWidth + gap) * 3; // Scroll 3 cards a la vez
    
    if (direction === 'prev') {
        track.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        track.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }
}

// Función para reproducir contenido
function playContent(type, id) {
    if (window.Portal) {
        Portal.navigateToPlayer(type, id);
    } else {
        // Fallback
        let url = '';
        switch(type) {
            case 'movies':
                url = `player/video-player.php?id=${id}`;
                break;
            case 'music':
                url = `player/music-player.php?id=${id}`;
                break;
            case 'games':
                url = `player/game-launcher.php?id=${id}`;
                break;
        }
        if (url) {
            window.location.href = url;
        }
    }
}

// Función para agregar a lista (placeholder)
function addToList(id) {
    console.log('Add to list:', id);
    // Mostrar confirmación
    showToast('Agregado a tu lista');
}

// Función para mostrar información (placeholder)
function showInfo(id) {
    console.log('Show info:', id);
    // Aquí podrías abrir un modal con más información
}

// Función helper para mostrar toast
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'carousel-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2000);
}

// Lazy loading de imágenes
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('img.lazy');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '100px'
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback para navegadores sin IntersectionObserver
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
});
</script>

<!-- Estilos específicos del carrusel -->
<style>
/* Toast notification */
.carousel-toast {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    font-size: 0.875rem;
    transition: transform 0.3s;
    z-index: 1000;
}

.carousel-toast.show {
    transform: translateX(-50%) translateY(0);
}

/* Hover overlay para cards */
.card-thumbnail-wrapper {
    position: relative;
    overflow: hidden;
}

.card-hover-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
}

.content-card:hover .card-hover-overlay {
    opacity: 1;
}

.card-play-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: black;
    font-size: 20px;
    cursor: pointer;
    transition: transform 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.card-play-btn:hover {
    transform: scale(1.1);
    background: white;
}

/* Loading state para imágenes lazy */
img.lazy {
    filter: blur(5px);
    transition: filter 0.3s;
}

img.loaded {
    filter: blur(0);
}

/* Responsive */
@media (max-width: 768px) {
    .carousel-nav {
        display: none;
    }
    
    .card-hover-overlay {
        opacity: 1;
        background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, transparent 50%);
    }
    
    .card-play-btn {
        width: 40px;
        height: 40px;
        font-size: 16px;
        position: absolute;
        bottom: 10px;
        right: 10px;
    }
}
</style>