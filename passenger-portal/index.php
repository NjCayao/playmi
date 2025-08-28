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
                <!-- Video de fondo con autoplay muted - Para todos los dispositivos -->
                <?php if (isset($featured['archivo_path']) && $featured['archivo_path']): ?>
                    <video class="hero-backdrop"
                        autoplay
                        muted
                        loop
                        playsinline
                        preload="metadata"
                        poster="<?php echo $featuredContent['backdrop']; ?>"
                        onloadedmetadata="this.currentTime = 900">
                        <source src="<?php echo CONTENT_URL . $featured['archivo_path']; ?>#t=900,1200" type="video/mp4">
                    </video>
                <?php else: ?>
                    <img src="<?php echo $featuredContent['backdrop']; ?>"
                         alt="<?php echo $featuredContent['title']; ?>"
                         class="hero-backdrop">
                <?php endif; ?>

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
            <!-- Continuar Viendo -->
            <section class="content-section fade-in" id="continueWatchingSection" style="display: none;">
                <div class="section-header">
                    <h3 class="section-title">Continuar Viendo</h3>
                </div>

                <div class="content-carousel" data-type="continue">
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

            <!-- Películas Populares -->
            <section class="content-section fade-in">
                <div class="section-header">
                    <h3 class="section-title">Películas Recientes</h3>
                    <a href="movies.php" class="see-all">Ver todas <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="content-carousel" data-type="movies">
                    <div class="carousel-nav prev" onclick="scrollCarousel('moviesCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="moviesCarousel">
                        <!-- Contenido cargado dinámicamente -->
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 150px; height: 225px; border-radius: 4px;"></div>
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

                <div class="content-carousel" data-type="music">
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

            <!-- Banner de Catálogo (después de 3 secciones) -->
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

                <div class="content-carousel" data-type="games">
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

                <div class="content-carousel" data-type="trending">
                    <div class="carousel-nav prev" onclick="scrollCarousel('trendingCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="trendingCarousel">
                        <!-- Contenido trending -->
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                        <div class="loading-skeleton" style="width: 200px; height: 112px; border-radius: 4px;"></div>
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('trendingCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            </section>
            
            <!-- Botón Ver Más Contenido -->
            <section class="view-more-section">
                <div class="view-more-container">
                    <h3>¿Quieres ver más contenido?</h3>
                    <p>Explora nuestro catálogo completo</p>
                    <div class="view-more-buttons">
                        <a href="movies.php" class="btn btn-primary">
                            <i class="fas fa-film"></i> Ver Todas las Películas
                        </a>
                        <a href="music.php" class="btn btn-secondary">
                            <i class="fas fa-music"></i> Explorar Música
                        </a>
                        <a href="games.php" class="btn btn-secondary">
                            <i class="fas fa-gamepad"></i> Ver Juegos
                        </a>
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
            
            // Cargar continuar viendo
            loadContinueWatching();
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

                // Cargar películas - Dividir en secciones
                const moviesData = await Portal.loadContentData('movies');
                if (moviesData && moviesData.success) {
                    const allMovies = moviesData.data || [];
                    
                    // 1. Películas Recientes (últimas 10 agregadas)
                    // Asumiendo que vienen ordenadas por fecha de creación descendente
                    const recentMovies = allMovies.slice(0, 10);
                    
                    // 2. Películas Populares (9 aleatorias del resto)
                    const remainingMovies = allMovies.slice(10);
                    const popularMovies = [...remainingMovies]
                        .sort(() => Math.random() - 0.5)
                        .slice(0, 9);
                    
                    // Renderizar ambas secciones
                    renderEnhancedCarousel(recentMovies, 'moviesCarousel', 'movies');
                    
                    // Crear nueva sección para populares si no existe
                    if (popularMovies.length > 0) {
                        createPopularMoviesSection();
                        renderEnhancedCarousel(popularMovies, 'popularMoviesCarousel', 'movies');
                    }
                }

                // Cargar música - mostrar 12 aleatorias
                const musicData = await Portal.loadContentData('music');
                if (musicData && musicData.success) {
                    const shuffledMusic = [...(musicData.data || [])]
                        .sort(() => Math.random() - 0.5)
                        .slice(0, 12);
                    renderEnhancedCarousel(shuffledMusic, 'musicCarousel', 'music');
                }

                // Cargar juegos - mostrar 10 aleatorios
                const gamesData = await Portal.loadContentData('games');
                if (gamesData && gamesData.success) {
                    const shuffledGames = [...(gamesData.data || [])]
                        .sort(() => Math.random() - 0.5)
                        .slice(0, 10);
                    renderEnhancedCarousel(shuffledGames, 'gamesCarousel', 'games');
                }
                
                // Cargar tendencias (mezcla de todo)
                loadTrendingContent();

            } catch (error) {
                console.error('Error cargando contenido:', error);
                // Reintentar una vez después de un delay
                setTimeout(() => {
                    loadContentWithCards();
                }, 1000);
            }
        }
        
        // Crear sección de películas populares dinámicamente
        function createPopularMoviesSection() {
            // Verificar si ya existe
            if (document.getElementById('popularMoviesSection')) return;
            
            // Buscar dónde insertar (después de películas recientes)
            const moviesSection = document.querySelector('.content-section:has(#moviesCarousel)');
            if (!moviesSection) return;
            
            // Crear nueva sección
            const popularSection = document.createElement('section');
            popularSection.className = 'content-section fade-in';
            popularSection.id = 'popularMoviesSection';
            popularSection.innerHTML = `
                <div class="section-header">
                    <h3 class="section-title">Más Películas para Ti</h3>
                    <a href="movies.php" class="see-all">Ver catálogo completo <i class="fas fa-chevron-right"></i></a>
                </div>

                <div class="content-carousel" data-type="movies">
                    <div class="carousel-nav prev" onclick="scrollCarousel('popularMoviesCarousel', 'prev')">
                        <i class="fas fa-chevron-left"></i>
                    </div>

                    <div class="carousel-track" id="popularMoviesCarousel">
                        <!-- Contenido cargado dinámicamente -->
                    </div>

                    <div class="carousel-nav next" onclick="scrollCarousel('popularMoviesCarousel', 'next')">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
            `;
            
            // Insertar después de la sección de películas
            moviesSection.insertAdjacentElement('afterend', popularSection);
        }

        // Renderizar carrusel mejorado estilo Netflix
        function renderEnhancedCarousel(items, containerId, type) {
            const container = document.getElementById(containerId);
            if (!container || !items.length) return;
            
            // Agregar tipo de contenido al contenedor padre para estilos CSS
            container.parentElement.setAttribute('data-type', type);

            container.innerHTML = items.map((item, index) => {
                // Generar datos aleatorios para hacer más realista
                const match = 85 + Math.floor(Math.random() * 14); // 85-99% match
                const isNew = Math.random() > 0.7; // 30% probabilidad de ser nuevo

                // Crear SVG fallback con dimensiones correctas según tipo
                const isMovie = type === 'movies' || type === 'movie';
                const fallbackWidth = isMovie ? 200 : 300;
                const fallbackHeight = isMovie ? 300 : 169;
                const fallbackSvg = `data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='${fallbackWidth}' height='${fallbackHeight}' viewBox='0 0 ${fallbackWidth} ${fallbackHeight}'%3E%3Crect fill='%23222' width='${fallbackWidth}' height='${fallbackHeight}'/%3E%3Ctext x='50%25' y='50%25' text-anchor='middle' dy='.3em' fill='white' font-family='Arial' font-size='12' opacity='0.5'%3E${encodeURIComponent(item.titulo.substring(0, 15))}%3C/text%3E%3C/svg%3E`;

                return `
                    <div class="content-card" 
                         data-id="${item.id}" 
                         data-type="${type}">
                        <img src="${Portal.getThumbnailUrl(item)}" 
                             alt="${item.titulo}" 
                             class="card-thumbnail"
                             loading="lazy"
                             onerror="this.src='${fallbackSvg}'">
                        
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
        
        // Cargar continuar viendo - Simular con películas de la BD
        async function loadContinueWatching() {
            try {
                // Siempre mostrar la sección con películas "en progreso"
                const moviesData = await Portal.loadContentData('movies');
                
                if (moviesData && moviesData.success && moviesData.data.length > 0) {
                    // Tomar 6 películas aleatorias y simular que están en progreso
                    const shuffled = [...moviesData.data].sort(() => Math.random() - 0.5);
                    const continueItems = shuffled.slice(0, 6).map(item => ({
                        ...item,
                        progress: 15 + Math.random() * 70, // Progreso entre 15-85%
                        currentTime: Math.floor(10 + Math.random() * 60), // Entre 10-70 minutos
                        type: 'movies'
                    }));
                    
                    // Mostrar la sección
                    const section = document.getElementById('continueWatchingSection');
                    if (section) {
                        section.style.display = 'block';
                    }
                    
                    renderContinueWatching(continueItems, 'continueCarousel');
                }
            } catch (error) {
                console.error('Error cargando continuar viendo:', error);
                // En caso de error, ocultar la sección
                const section = document.getElementById('continueWatchingSection');
                if (section) {
                    section.style.display = 'none';
                }
            }
        }

        // Renderizar continuar viendo
        function renderContinueWatching(items, containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            if (items.length === 0) {
                container.innerHTML = '<p style="color: #666; padding: 2rem;">No hay contenido para continuar viendo</p>';
                return;
            }

            // Agregar tipo al contenedor padre
            container.parentElement.setAttribute('data-type', 'continue');

            // Crear SVG fallback
            const fallbackSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='169' viewBox='0 0 300 169'%3E%3Crect fill='%23222' width='300' height='169'/%3E%3C/svg%3E";

            container.innerHTML = items.map(item => {
                // Usar Portal.getThumbnailUrl si está disponible
                const thumbnailUrl = window.Portal && window.Portal.getThumbnailUrl 
                    ? Portal.getThumbnailUrl(item)
                    : (item.thumbnail_path 
                        ? '<?php echo CONTENT_URL; ?>' + item.thumbnail_path 
                        : fallbackSvg);
                
                const progress = Math.floor(item.progress || 50);
                const currentTime = Math.floor(item.currentTime || 30);
                
                return `
                    <div class="content-card" data-id="${item.id}" data-type="${item.type || 'movies'}">
                        <div style="position: relative;">
                            <img src="${thumbnailUrl}" 
                                 alt="${item.titulo || 'Contenido'}" 
                                 class="card-thumbnail"
                                 loading="lazy"
                                 onerror="this.src='${fallbackSvg}'">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progress}%"></div>
                            </div>
                        </div>
                        
                        <div class="card-expanded-info">
                            <h4 class="card-title">${item.titulo || 'Sin título'}</h4>
                            <div class="card-meta">
                                <span>Continuar desde ${currentTime} min</span>
                                <span class="match-score">${85 + Math.floor(Math.random() * 14)}% para ti</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Agregar event listeners
            container.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    playContent(type, id);
                });
            });
        }
        
        // Cargar contenido tendencias
        async function loadTrendingContent() {
            try {
                // Obtener una mezcla de todo el contenido
                const allContent = [];
                
                // Cargar todo tipo de contenido
                const [movies, music, games] = await Promise.all([
                    Portal.loadContentData('movies'),
                    Portal.loadContentData('music'),
                    Portal.loadContentData('games')
                ]);
                
                // Agregar tipo a cada elemento
                if (movies.success && movies.data) {
                    movies.data.slice(0, 7).forEach(item => {
                        allContent.push({...item, contentType: 'movies'});
                    });
                }
                
                if (music.success && music.data) {
                    music.data.slice(0, 7).forEach(item => {
                        allContent.push({...item, contentType: 'music'});
                    });
                }
                
                if (games.success && games.data) {
                    games.data.slice(0, 6).forEach(item => {
                        allContent.push({...item, contentType: 'games'});
                    });
                }
                
                // Mezclar aleatoriamente
                const shuffled = allContent.sort(() => Math.random() - 0.5).slice(0, 20);
                
                // Renderizar
                const container = document.getElementById('trendingCarousel');
                if (container && shuffled.length > 0) {
                    renderMixedContentCarousel(shuffled, 'trendingCarousel');
                }
                
            } catch (error) {
                console.error('Error cargando tendencias:', error);
            }
        }
        
        // Renderizar carrusel con contenido mixto
        function renderMixedContentCarousel(items, containerId) {
            const container = document.getElementById(containerId);
            if (!container || !items.length) return;

            // Crear SVG fallback
            const fallbackSvg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='169' viewBox='0 0 300 169'%3E%3Crect fill='%23222' width='300' height='169'/%3E%3C/svg%3E";

            container.innerHTML = items.map((item, index) => {
                const match = 90 + Math.floor(Math.random() * 9); // 90-99% para tendencias
                const typeIcon = {
                    'movies': '<i class="fas fa-film"></i>',
                    'music': '<i class="fas fa-music"></i>',
                    'games': '<i class="fas fa-gamepad"></i>'
                }[item.contentType] || '';

                return `
                    <div class="content-card" 
                         data-id="${item.id}" 
                         data-type="${item.contentType}">
                        <div style="position: relative;">
                            <img src="${Portal.getThumbnailUrl(item)}" 
                                 alt="${item.titulo}" 
                                 class="card-thumbnail"
                                 loading="lazy"
                                 onerror="this.src='${fallbackSvg}'">
                            <div class="trending-number">${index + 1}</div>
                        </div>
                        
                        <div class="card-expanded-info">
                            <h4 class="card-title">${typeIcon} ${item.titulo}</h4>
                            <div class="card-meta">
                                <span class="match-score">${match}% para ti</span>
                                <span>Top ${index + 1} en tendencias</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Agregar event listeners
            container.querySelectorAll('.content-card').forEach(card => {
                card.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const type = this.dataset.type;
                    playContent(type, id);
                });
            });
        }

        // Reproducir contenido
        function playContent(type, id) {
            let path = '';

            // Mapear el tipo correctamente - usar rutas relativas
            switch (type) {
                case 'movies':
                case 'movie':
                    path = `player/video-player.php?id=${id}`;
                    break;
                case 'music':
                    path = `player/music-player.php?id=${id}`;
                    break;
                case 'games':
                case 'game':
                    path = `player/game-launcher.php?id=${id}`;
                    break;
            }

            if (path) {
                Portal.trackInteraction('content_play', {
                    id,
                    type
                });
                // Usar ruta relativa
                window.location.href = path;
            } else {
                console.error('Tipo de contenido no reconocido:', type);
            }
        }
    </script>
    
    <!-- Estilos adicionales para mejoras -->
    <style>
        /* Video hero mejorado para móvil */
        .hero-backdrop {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        /* Optimización de video para móvil */
        @media (max-width: 768px) {
            video.hero-backdrop {
                /* En móvil, hacer el video más ligero */
                filter: brightness(0.8);
            }
            
            .hero-banner {
                height: 60vh;
                min-height: 400px;
            }
        }
        
        /* Número de tendencia */
        .trending-number {
            position: absolute;
            bottom: 0;
            left: 0;
            font-size: 3rem;
            font-weight: 900;
            color: #ffffff; /* Cambia este color - Blanco */
            text-shadow: 
                -2px -2px 0 #000,  
                2px -2px 0 #000,
                -2px 2px 0 #000,
                2px 2px 0 #000,
                0 0 10px rgba(0,0,0,0.8); /* Borde negro para mejor legibilidad */
            padding: 0.5rem;
            line-height: 1;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        /* Colores alternativos para los números de tendencia */
        .trending-number.gold {
            color: #FFD700; /* Dorado */
        }
        
        .trending-number.netflix-red {
            color: #E50914; /* Rojo Netflix */
        }
        
        .trending-number.gradient {
            background: linear-gradient(to bottom, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Estilo especial para top 3 */
        .content-card:nth-child(1) .trending-number {
            color: #FFD700; /* Oro para el #1 */
            font-size: 3.5rem;
        }
        
        .content-card:nth-child(2) .trending-number {
            color: #C0C0C0; /* Plata para el #2 */
            font-size: 3.25rem;
        }
        
        .content-card:nth-child(3) .trending-number {
            color: #CD7F32; /* Bronce para el #3 */
            font-size: 3rem;
        }
        
        /* Tarjetas verticales estilo Netflix para películas */
        .content-carousel[data-type="movies"] .content-card,
        .content-carousel[data-type="movie"] .content-card {
            width: calc(100vw / 7 - 0.5rem); /* 7 tarjetas en desktop */
            min-width: 150px;
            max-width: 200px;
        }
        
        .content-carousel[data-type="movies"] .card-thumbnail,
        .content-carousel[data-type="movie"] .card-thumbnail {
            aspect-ratio: 2/3; /* Proporción vertical tipo póster */
            object-fit: cover;
        }
        
        /* Barra de progreso para continuar viendo */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            z-index: 10;
        }
        
        .progress-fill {
            height: 100%;
            background: #E50914; /* Rojo Netflix */
            width: 0%;
            transition: width 0.3s ease;
        }
        
        /* Hover effect para continuar viendo */
        .content-card:hover .progress-fill {
            background: #f40612;
        }
        
        /* Ajustes responsive para tarjetas verticales */
        @media (max-width: 1600px) {
            .content-carousel[data-type="movies"] .content-card,
            .content-carousel[data-type="movie"] .content-card {
                width: calc(100vw / 6 - 0.5rem);
            }
        }
        
        @media (max-width: 1200px) {
            .content-carousel[data-type="movies"] .content-card,
            .content-carousel[data-type="movie"] .content-card {
                width: calc(100vw / 5 - 0.5rem);
            }
        }
        
        @media (max-width: 900px) {
            .content-carousel[data-type="movies"] .content-card,
            .content-carousel[data-type="movie"] .content-card {
                width: calc(100vw / 4 - 0.5rem);
            }
        }
        
        @media (max-width: 600px) {
            .content-carousel[data-type="movies"] .content-card,
            .content-carousel[data-type="movie"] .content-card {
                width: calc(100vw / 3 - 0.5rem);
                min-width: 120px;
            }
        }
        
        /* Mantener aspecto 16:9 para música y juegos */
        .content-carousel[data-type="music"] .card-thumbnail,
        .content-carousel[data-type="games"] .card-thumbnail {
            aspect-ratio: 16/9;
        }
        
        /* Hover mejorado para tarjetas verticales */
        @media (min-width: 800px) {
            .content-carousel[data-type="movies"] .content-card:hover,
            .content-carousel[data-type="movie"] .content-card:hover {
                transform: scale(1.3);
                z-index: 100;
            }
            
            .content-carousel[data-type="movies"] .content-card:hover ~ .content-card,
            .content-carousel[data-type="movie"] .content-card:hover ~ .content-card {
                transform: translateX(20px);
            }
        }
        
        /* Información expandida ajustada para tarjetas verticales */
        .content-carousel[data-type="movies"] .card-expanded-info,
        .content-carousel[data-type="movie"] .card-expanded-info {
            padding: 0.5rem;
        }
        
        .content-carousel[data-type="movies"] .card-title,
        .content-carousel[data-type="movie"] .card-title {
            font-size: 0.8rem;
        }
        
        /* Sección Ver Más */
        .view-more-section {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(0, 0, 0, 0.3);
            margin-top: 3rem;
        }
        
        .view-more-container h3 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .view-more-container p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .view-more-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .view-more-buttons .btn {
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            .view-more-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .view-more-buttons .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>
</body>

</html>