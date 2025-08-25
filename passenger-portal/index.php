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
    <title><?php echo htmlspecialchars($companyConfig['company_name']); ?> - Entretenimiento a Bordo</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/netflix-style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    
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
    
    <!-- CSS adicional para el hero y mejoras móviles -->
    <style>
        /* Botones de categorías para móvil */
        .mobile-categories {
            display: none;
            background: var(--bg-primary);
            padding: 1.5rem 20px 1rem;
            margin-top: -30px;
            position: relative;
            z-index: 10;
        }
        
        .category-buttons {
            display: flex;
            justify-content: space-around;
            gap: 1rem;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .category-button {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: white;
            padding: 0.75rem;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .category-button:active {
            transform: scale(0.95);
        }
        
        .category-icon {
            width: 50px;
            height: 50px;
            background: var(--company-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
            transition: transform 0.3s ease;
        }
        
        .category-button:hover .category-icon {
            transform: scale(1.1);
        }
        
        .category-button span {
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        /* Alternativa: Botones flotantes estilo bubble */
        .floating-categories {
            display: none;
            position: fixed;
            bottom: 80px;
            right: 20px;
            z-index: 100;
            flex-direction: column;
            gap: 1rem;
        }
        
        .floating-button {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--company-primary);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .floating-button:active {
            transform: scale(0.9);
        }
        
        .floating-button.main {
            width: 64px;
            height: 64px;
            font-size: 1.5rem;
        }
        
        /* Mostrar solo en móvil */
        @media (max-width: 768px) {
            .mobile-categories {
                display: block;
            }
            
            /* Ajustar hero para hacer espacio */
            .hero-banner {
                margin-bottom: -80px;
            }
        }
        
        /* Para pantallas muy pequeñas */
        @media (max-width: 375px) {
            .category-buttons {
                gap: 0.5rem;
            }
            
            .category-button {
                padding: 0.5rem;
            }
            
            .category-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .category-button span {
                font-size: 0.75rem;
            }
        }
        
        /* Estilos para banners publicitarios */
        .banner-container {
            width: 100%;
            text-align: center;
            overflow: hidden;
        }
        
        .banner-header {
            background: #000;
            padding: 10px 0;
            position: relative;
            z-index: 999;
        }
        
        .banner-header .banner-image {
            max-height: 80px;
            width: auto;
            max-width: 100%;
            display: inline-block;
        }
        
        .banner-catalog {
            margin: 2rem 0;
            padding: 0 4%;
        }
        
        .banner-catalog-image {
            width: 100%;
            max-width: 970px;
            height: auto;
            margin: 0 auto;
            display: block;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .banner-catalog-image:hover {
            transform: scale(1.02);
        }
        
        .banner-footer {
            margin-bottom: 2rem;
            padding: 0 4%;
        }
        
        .banner-footer-image {
            width: 100%;
            max-width: 728px;
            height: auto;
            margin: 0 auto;
            display: block;
        }
        
        /* Ajustes responsive para banners */
        @media (max-width: 768px) {
            .banner-header .banner-image {
                max-height: 60px;
            }
            
            .banner-catalog {
                padding: 0 20px;
            }
            
            .banner-footer {
                padding: 0 20px;
            }
            
            .banner-item {
                margin-bottom: 10px;
            }
        }
        
        /* Hero video mejorado */
        .hero-banner video {
            filter: brightness(0.7);
        }
        
        .hero-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
            font-size: 1rem;
            flex-wrap: wrap;
        }
        
        .hero-meta .match-score {
            color: #46d369;
            font-weight: 700;
        }
        
        .hero-meta .rating-badge {
            padding: 0.125rem 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.4);
            border-radius: 3px;
            font-size: 0.875rem;
        }
        
        /* Progress bar para continuar viendo */
        .progress-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
        }
        
        .progress-fill {
            height: 100%;
            background: var(--accent-color);
            width: 45%;
        }
        
        /* Mejoras para móvil */
        @media (max-width: 768px) {
            .hero-banner {
                height: 65vh;
                min-height: 400px;
                margin-bottom: -80px;
            }
            
            .hero-content {
                bottom: 15%;
                left: 20px;
                right: 20px;
            }
            
            .hero-info {
                max-width: 100%;
            }
            
            .hero-title {
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
            }
            
            .hero-meta {
                font-size: 0.875rem;
                gap: 0.5rem;
                margin: 0.5rem 0;
            }
            
            .hero-meta .rating-badge {
                font-size: 0.75rem;
                padding: 1px 6px;
            }
            
            .hero-description {
                display: -webkit-box;
                -webkit-line-clamp: 3;
                -webkit-box-orient: vertical;
                overflow: hidden;
                font-size: 0.875rem;
                line-height: 1.4;
                margin-bottom: 1rem;
            }
            
            /* Botones estilo Netflix móvil */
            .hero-buttons {
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                width: 100%;
                max-width: 300px;
            }
            
            .btn {
                width: 100%;
                padding: 0.875rem 1.5rem;
                font-size: 1rem;
                font-weight: 600;
                justify-content: center;
                border-radius: 4px;
            }
            
            .btn-primary {
                background-color: white;
                color: black;
            }
            
            .btn-secondary {
                background-color: rgba(109, 109, 110, 0.7);
                color: white;
                backdrop-filter: blur(4px);
            }
            
            .btn i {
                font-size: 1.125rem;
                margin-right: 0.5rem;
            }
            
            .content-carousel {
                margin: 0 -20px;
                padding: 0 20px;
            }
            
            .content-section {
                padding: 1rem 20px;
                margin-bottom: 1rem;
            }
            
            .section-header {
                margin-bottom: 0.75rem;
            }
            
            .section-title {
                font-size: 1.125rem;
            }
            
            .see-all {
                font-size: 0.75rem;
            }
        }
        
        /* Pantallas muy pequeñas */
        @media (max-width: 375px) {
            .hero-banner {
                height: 70vh;
            }
            
            .hero-title {
                font-size: 1.5rem;
            }
            
            .hero-meta {
                font-size: 0.75rem;
            }
            
            .hero-description {
                font-size: 0.8125rem;
                -webkit-line-clamp: 2;
            }
            
            .hero-buttons {
                max-width: 100%;
            }
            
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.9375rem;
            }
        }
        
        /* Asegurar que el contenido no se desborde en móvil */
        .content-card {
            flex-shrink: 0;
        }
        
        /* Mejorar visibilidad en móvil */
        @media (max-width: 500px) {
            .card-expanded-info {
                padding: 0.5rem 0.75rem;
            }
            
            .portal-footer {
                font-size: 0.75rem;
                padding: 1.5rem 20px;
            }
        }
        
        /* Fix para el overlay del hero en móvil */
        @media (max-width: 768px) {
            .hero-overlay {
                background: linear-gradient(180deg, 
                    rgba(0,0,0,0.3) 0%, 
                    rgba(0,0,0,0.5) 50%, 
                    rgba(0,0,0,0.9) 100%);
            }
            
            .hero-fade-bottom {
                height: 100px;
            }
        }
    </style>
    
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
        
        // Cargar contenido con el nuevo diseño de cards
        async function loadContentWithCards() {
            // Cargar películas
            const moviesData = await Portal.loadContentData('movies');
            renderEnhancedCarousel(moviesData.data || [], 'moviesCarousel', 'movie');
            
            // Cargar música
            const musicData = await Portal.loadContentData('music');
            renderEnhancedCarousel(musicData.data || [], 'musicCarousel', 'music');
            
            // Cargar juegos
            const gamesData = await Portal.loadContentData('games');
            renderEnhancedCarousel(gamesData.data || [], 'gamesCarousel', 'game');
            
            // Simular contenido para continuar viendo
            const continueData = moviesData.data ? moviesData.data.slice(0, 5) : [];
            renderContinueWatching(continueData, 'continueCarousel');
            
            // Simular tendencias
            const trendingData = [...(moviesData.data || []), ...(musicData.data || [])]
                .sort(() => Math.random() - 0.5)
                .slice(0, 10);
            renderEnhancedCarousel(trendingData, 'trendingCarousel', 'trending');
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
                Portal.trackInteraction('content_play', { id, type });
                window.location.href = url;
            } else {
                console.error('Tipo de contenido no reconocido:', type);
            }
        }
    </script>
</body>
</html>