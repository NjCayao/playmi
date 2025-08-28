<?php

/**
 * passenger-portal/index.php
 * Página principal del portal de pasajeros - Estilo Netflix MEJORADO
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

// Obtener configuración de la empresa
$companyConfig = getCompanyConfig();

// Obtener banners de la empresa
$banners = ['header' => [], 'footer' => [], 'catalogo' => []];
try {
    $db = Database::getInstance()->getConnection();

    // Obtener banners activos
    $sql = "SELECT * FROM banners_empresa 
            WHERE empresa_id = ? AND activo = 1 
            ORDER BY tipo_banner, orden_visualizacion";
    $stmt = $db->prepare($sql);
    $stmt->execute([$companyConfig['company_id']]);
    $bannersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar por tipo
    foreach ($bannersData as $banner) {
        $banners[$banner['tipo_banner']][] = $banner;
    }
} catch (Exception $e) {
    // Si hay error, continuar sin banners
}

// Obtener contenido destacado aleatorio de la base de datos
$featuredContent = null;
$featured = null; // Variable para tener acceso a todos los datos
try {
    $db = Database::getInstance()->getConnection();

    // Obtener una película aleatoria activa
    $sql = "SELECT id, titulo, descripcion, tipo, duracion, anio_lanzamiento, calificacion, genero, archivo_path 
            FROM contenido 
            WHERE tipo = 'pelicula' AND estado = 'activo' 
            ORDER BY RAND() 
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $featured = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($featured) {
        $featuredContent = [
            'id' => $featured['id'],
            'title' => $featured['titulo'],
            'description' => $featured['descripcion'],
            'backdrop' => CONTENT_URL . 'thumbnails/movie-backdrop-' . $featured['id'] . '.jpg',
            'type' => 'movie',
            'duration' => $featured['duracion'] ? floor($featured['duracion'] / 60) . ' min' : '',
            'year' => $featured['anio_lanzamiento'],
            'rating' => $featured['calificacion'],
            'genre' => $featured['genero'],
            'match' => rand(85, 99)
        ];
    }
} catch (Exception $e) {
    // Si hay error, usar datos por defecto
    $featuredContent = [
        'id' => 1,
        'title' => 'Contenido Destacado',
        'description' => 'Disfruta del mejor entretenimiento durante tu viaje.',
        'backdrop' => CONTENT_URL . 'thumbnails/default-backdrop.jpg',
        'type' => 'movie',
        'duration' => '120 min',
        'year' => date('Y'),
        'rating' => 'PG',
        'genre' => 'Acción',
        'match' => 95
    ];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo htmlspecialchars($companyConfig['company_name']); ?> - Entretenimiento a Bordo</title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/netflix-style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="assets/css/index.css">

    <!-- CSS personalizado de empresa -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
        }
    </style>

    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="assets/fonts/font-awesome/css/all.min.css">
</head>

<body>
    <div class="portal-container">
        <!-- Banner Header (si existe) -->
        <?php if (!empty($banners['header'])): ?>
            <div class="banner-container banner-header">
                <?php foreach ($banners['header'] as $banner): ?>
                    <div class="banner-item">
                        <img src="<?php echo CONTENT_URL . 'banners/' . $banner['imagen_path']; ?>"
                            alt="Banner"
                            class="banner-image"
                            loading="lazy">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Header/Navegación -->
        <header class="portal-header" id="portalHeader">
            <div class="header-content">
                <div class="logo-section">
                    <?php if ($companyConfig['logo_url']): ?>
                        <img src="<?php echo $companyConfig['logo_url']; ?>" alt="Logo" class="company-logo">
                    <?php else: ?>
                        <h1 class="company-name">PLAYMI</h1>
                    <?php endif; ?>

                    <nav>
                        <ul class="nav-menu">
                            <li><a href="index.php" class="active">Inicio</a></li>
                            <li><a href="movies.php">Películas</a></li>
                            <li><a href="music.php">Música</a></li>
                            <li><a href="games.php">Juegos</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="header-actions">
                    <button class="search-btn" aria-label="Buscar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Hero Banner -->
        <section class="hero-banner">
            <?php if ($featuredContent): ?>
                <!-- Video de fondo con autoplay muted - primeros 5 minutos -->
                <video class="hero-backdrop"
                    autoplay
                    muted
                    loop
                    playsinline
                    preload="metadata"
                    poster="<?php echo $featuredContent['backdrop']; ?>"
                    onloadedmetadata="this.currentTime = 10">
                    <?php
                    // Usar el archivo de la película directamente
                    if (isset($featured['archivo_path']) && $featured['archivo_path']) {
                        $videoPath = CONTENT_URL . $featured['archivo_path'] . '#t=10,310'; // Del segundo 10 al 310 (5 min)
                        echo '<source src="' . $videoPath . '" type="video/mp4">';
                    }
                    ?>
                    <!-- Fallback a imagen si no hay video -->
                    <img src="<?php echo $featuredContent['backdrop']; ?>"
                        alt="<?php echo $featuredContent['title']; ?>">
                </video>

                <!-- Overlay oscuro para mejor legibilidad -->
                <div class="hero-overlay"></div>
            <?php else: ?>
                <img src="<?php echo CONTENT_URL; ?>thumbnails/default-backdrop.jpg"
                    alt="Contenido destacado"
                    class="hero-backdrop">
            <?php endif; ?>

            <div class="hero-content">
                <div class="hero-info">
                    <h2 class="hero-title"><?php echo $featuredContent['title']; ?></h2>
                    <div class="hero-meta">
                        <span class="match-score"><?php echo $featuredContent['match']; ?>% para ti</span>
                        <?php if ($featuredContent['year']): ?>
                            <span><?php echo $featuredContent['year']; ?></span>
                        <?php endif; ?>
                        <?php if ($featuredContent['duration']): ?>
                            <span><?php echo $featuredContent['duration']; ?></span>
                        <?php endif; ?>
                        <?php if ($featuredContent['rating']): ?>
                            <span class="rating-badge"><?php echo $featuredContent['rating']; ?></span>
                        <?php endif; ?>
                        <?php if ($featuredContent['genre']): ?>
                            <span><?php echo $featuredContent['genre']; ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="hero-description"><?php echo $featuredContent['description']; ?></p>

                    <div class="hero-buttons">
                        <a href="player/video-player.php?id=<?php echo $featuredContent['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-play"></i> Reproducir
                        </a>
                        <button class="btn btn-secondary">
                            <i class="fas fa-info-circle"></i> Más información
                        </button>
                    </div>
                </div>
            </div>
            <div class="hero-fade-bottom"></div>
        </section>

        <!-- Botones de categorías móvil (solo visible en móvil) -->
        <section class="mobile-categories">
            <div class="category-buttons">
                <a href="movies.php" class="category-button">
                    <div class="category-icon">
                        <i class="fas fa-film"></i>
                    </div>
                    <span>Películas</span>
                </a>
                <a href="music.php" class="category-button">
                    <div class="category-icon">
                        <i class="fas fa-music"></i>
                    </div>
                    <span>Música</span>
                </a>
                <a href="games.php" class="category-button">
                    <div class="category-icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <span>Juegos</span>
                </a>
            </div>
        </section>

        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Películas Populares -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Películas Populares</h3>
                    <a href="movies.php" class="see-all">Ver todas <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="content-carousel">
                    <div class="carousel-nav prev" onclick="scrollCarousel('moviesCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="moviesCarousel">
                        <!-- Contenido cargado dinámicamente -->
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('moviesCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>

            <!-- Música Destacada -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Música Destacada</h3>
                    <a href="music.php" class="see-all">Ver toda <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="content-carousel">
                    <div class="carousel-nav prev" onclick="scrollCarousel('musicCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="musicCarousel">
                        <!-- Contenido cargado dinámicamente -->
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('musicCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>

            <!-- Continuar Viendo -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Continuar Viendo</h3>
                </div>

                <div class="content-carousel">
                    <div class="carousel-nav prev" onclick="scrollCarousel('continueCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="continueCarousel">
                        <!-- Contenido con progreso -->
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('continueCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>

            <!-- Banner de Catálogo (después de 2 secciones) -->
            <?php if (!empty($banners['catalogo'])): ?>
                <div class="banner-container banner-catalog">
                    <?php
                    // Mostrar un banner de catálogo aleatorio
                    $catalogBanner = $banners['catalogo'][array_rand($banners['catalogo'])];
                    ?>
                    <div class="banner-item">
                        <img src="<?php echo CONTENT_URL . 'banners/' . $catalogBanner['imagen_path']; ?>"
                            alt="Banner"
                            class="banner-image banner-catalog-image"
                            loading="lazy">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Juegos Nuevos -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Juegos Nuevos</h3>
                    <a href="games.php" class="see-all">Ver todos <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="content-carousel">
                    <div class="carousel-nav prev" onclick="scrollCarousel('gamesCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="gamesCarousel">
                        <!-- Contenido cargado dinámicamente -->
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('gamesCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>

            <!-- Tendencias -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Tendencias Ahora</h3>
                </div>

                <div class="content-carousel">
                    <div class="carousel-nav prev" onclick="scrollCarousel('trendingCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="trendingCarousel">
                        <!-- Contenido trending -->
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('trendingCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="portal-footer">
            <?php if (!empty($banners['footer'])): ?>
                <div class="banner-container banner-footer">
                    <?php foreach ($banners['footer'] as $banner): ?>
                        <div class="banner-item">
                            <img src="<?php echo CONTENT_URL . 'banners/' . $banner['imagen_path']; ?>"
                                alt="Banner"
                                class="banner-image banner-footer-image"
                                loading="lazy">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyConfig['company_name']); ?>. Powered by PLAYMI Entertainment.</p>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="assets/js/portal-main.js"></script>
    <script src="assets/js/touch-controls.js"></script>
    <script>
        // Funciones del carrusel
        function scrollCarousel(carouselId, direction) {
            const carousel = document.getElementById(carouselId);
            const scrollAmount = carousel.offsetWidth * 0.8;

            if (direction === 'prev') {
                carousel.scrollLeft -= scrollAmount;
            } else {
                carousel.scrollLeft += scrollAmount;
            }
        }

        // Inicializar portal
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar header scroll
            let lastScrollY = window.scrollY;
            window.addEventListener('scroll', () => {
                const header = document.getElementById('portalHeader');
                if (window.scrollY > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });

            // Inicializar portal
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });

            // Cargar contenido con el nuevo formato
            loadContentWithCards();
        });

        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // La página viene del cache del navegador
                console.log('Página cargada desde cache');
                // Recargar el contenido
                setTimeout(() => {
                    if (typeof loadContentWithCards === 'function') {
                        loadContentWithCards();
                    }
                }, 100);
            }
        });

        // Cargar contenido con el nuevo diseño de cards
        async function loadContentWithCards() {
            try {
                // Verificar que Portal esté inicializado
                if (!window.Portal || !window.Portal.loadContentData) {
                    console.log('Portal no está listo, esperando...');
                    setTimeout(loadContentWithCards, 500);
                    return;
                }

                // Cargar películas
                const moviesData = await Portal.loadContentData('movies');
                if (moviesData && moviesData.success) {
                    const limitedMovies = (moviesData.data || []).slice(0, 20);
                    renderEnhancedCarousel(limitedMovies, 'moviesCarousel', 'movie');
                }

                // Cargar juegos
                const gamesData = await Portal.loadContentData('games');
                if (gamesData && gamesData.success) {
                    const limitedGames = (gamesData.data || []).slice(0, 20);
                    renderEnhancedCarousel(limitedGames, 'gamesCarousel', 'movie');
                }

                // Cargar musica
                const musicData = await Portal.loadContentData('music');
                if (musicData && musicData.success) {
                    const limitedMusic = (musicData.data || []).slice(0, 20);
                    renderEnhancedCarousel(limitedMusic, 'musicCarousel', 'movie');
                }

            } catch (error) {
                console.error('Error cargando contenido:', error);
                // Reintentar una vez después de un delay
                setTimeout(() => {
                    loadContentWithCards();
                }, 1000);
            }
        }

        // Renderizar carrusel mejorado estilo Netflix
        function renderEnhancedCarousel(items, containerId, type) {
            const container = document.getElementById(containerId);
            if (!container || !items.length) return;

            container.innerHTML = items.map((item, index) => {
                // Generar datos aleatorios para hacer más realista
                const match = 85 + Math.floor(Math.random() * 14); // 85-99% match
                const isNew = Math.random() > 0.7; // 30% probabilidad de ser nuevo

                return `
                    <div class="content-card" 
                         data-id="${item.id}" 
                         data-type="${type}">
                        <img src="${Portal.getThumbnailUrl(item)}" 
                             alt="${item.titulo}" 
                             class="card-thumbnail"
                             loading="lazy"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="300" height="169" viewBox="0 0 300 169"%3E%3Crect fill="%23222" width="300" height="169"/%3E%3C/svg%3E'">
                        
                        <div class="card-expanded-info">
                            <h4 class="card-title">${item.titulo}</h4>
                            <div class="card-meta">
                                <span class="match-score">${match}% para ti</span>
                                ${item.calificacion ? `<span class="rating-badge">${item.calificacion}</span>` : ''}
                                ${item.genero ? `<span>${item.genero}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Agregar event listeners a las cards después de renderizar
            container.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const cardType = this.dataset.type;
                    playContent(cardType, id);
                });
            });
        }

        // Renderizar continuar viendo
        function renderContinueWatching(items, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (items.length === 0) {
                container.innerHTML = '<p style="color: #666; padding: 2rem;">No hay contenido para continuar viendo</p>';
                return;
            }

            container.innerHTML = items.map(item => `
                <div class="content-card" data-id="${item.id}" data-type="movies">
                    <div style="position: relative;">
                        <img src="${Portal.getThumbnailUrl(item)}" 
                             alt="${item.titulo}" 
                             class="card-thumbnail"
                             loading="lazy">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${20 + Math.random() * 60}%"></div>
                        </div>
                    </div>
                    
                    <div class="card-expanded-info">
                        <h4 class="card-title">${item.titulo}</h4>
                        <div class="card-meta">
                            <span>Continuar desde ${Math.floor(15 + Math.random() * 45)} min</span>
                        </div>
                    </div>
                </div>
            `).join('');

            // Agregar event listeners
            container.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('click', function() {
                    const id = this.dataset.id;
                    playContent('movies', id);
                });
            });
        }

        // Reproducir contenido
        function playContent(type, id) {
            let url = '';

            // Mapear el tipo correctamente
            switch (type) {
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
                Portal.trackInteraction('content_play', {
                    id,
                    type
                });
                window.location.href = url;
            } else {
                console.error('Tipo de contenido no reconocido:', type);
            }
        }
    </script>
</body>

</html>