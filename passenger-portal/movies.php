<?php

/**
 * passenger-portal/movies.php
 * Catálogo de películas estilo Netflix - Con carruseles por género
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

$companyConfig = getCompanyConfig();

// Determinar vista actual
$viewMode = $_GET['view'] ?? 'browse';
$selectedGenre = $_GET['g'] ?? '';

// Inicializar variables
$allMovies = [];
$moviesByGenre = [];
$genres = [];
$years = [];
$featuredMovies = [];

try {
    $db = Database::getInstance()->getConnection();

    // Obtener todas las películas activas
    $sql = "SELECT id, titulo, descripcion, tipo, duracion, anio_lanzamiento, 
            calificacion, genero, categoria, archivo_path, thumbnail_path, created_at,
            descargas_count
            FROM contenido 
            WHERE tipo = 'pelicula' AND estado = 'activo' 
            ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $allMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Organizar películas por género
    foreach ($allMovies as $movie) {
        // Extraer géneros únicos
        if (!empty($movie['genero'])) {
            $genre = $movie['genero'];
            if (!in_array($genre, $genres)) {
                $genres[] = $genre;
            }
            // Agrupar películas por género
            if (!isset($moviesByGenre[$genre])) {
                $moviesByGenre[$genre] = [];
            }
            $moviesByGenre[$genre][] = $movie;
        }

        // Extraer años
        if (!empty($movie['anio_lanzamiento']) && !in_array($movie['anio_lanzamiento'], $years)) {
            $years[] = $movie['anio_lanzamiento'];
        }
    }

    sort($genres);
    rsort($years);

    // Películas destacadas (más vistas)
    $featuredMovies = array_slice($allMovies, 0, 10);
} catch (Exception $e) {
    error_log("Error loading movies: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Películas - <?php echo htmlspecialchars($companyConfig['company_name']); ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/netflix-style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">

    <!-- CSS específico para películas estilo Netflix con carruseles -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
        }

        /* Header siempre visible */
        .portal-header {
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
        }

        /* Contenedor principal */
        .movies-browse-container {
            min-height: 100vh;
            background: #141414;
            padding-top: 68px;
        }

        /* Hero más pequeño para movies */
        .movies-hero {
            position: relative;
            height: 40vh;
            min-height: 300px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .movies-hero-backdrop {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.4);
        }

        .movies-hero-gradient {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom,
                    transparent 0%,
                    rgba(20, 20, 20, 0.7) 50%,
                    #141414 100%);
        }

        .movies-hero-content {
            position: absolute;
            bottom: 2rem;
            left: 60px;
            z-index: 2;
        }

        .movies-hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Navegación y filtros */
        .movies-nav {
            padding: 1.5rem 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 2rem;
        }

        .movies-nav-title {
            font-size: 1.75rem;
            font-weight: 600;
        }

        .movies-filters {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Dropdown mejorado */
        .filter-select {
            background: #000000;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.5rem 2rem 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            border-radius: 2px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='white' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.7rem center;
            min-width: 120px;
        }

        .filter-select:hover {
            border-color: white;
        }

        /* Secciones de género */
        .genre-section {
            margin-bottom: 3rem;
            padding: 0 60px;
        }

        .genre-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .genre-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #e5e5e5;
        }

        .see-all-btn {
            color: #aaa;
            text-decoration: none;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .see-all-btn:hover {
            color: white;
        }

        .see-all-btn i {
            transition: transform 0.2s;
        }

        .see-all-btn:hover i {
            transform: translateX(3px);
        }

        /* Carrusel de películas */
        .genre-carousel {
            position: relative;
            margin: 0 -60px;
            padding: 0 60px;
        }

        .genre-track {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 0.5rem;
        }

        .genre-track::-webkit-scrollbar {
            display: none;
        }

        /* Card de película para carrusel */
        .movie-card-carousel {
            flex: 0 0 auto;
            width: 200px;
            cursor: pointer;
            transition: transform 0.3s ease;
            position: relative;
        }

        .movie-card-carousel:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        .movie-poster-carousel {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
            border-radius: 4px;
        }

        /* Navegación del carrusel */
        .carousel-nav {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(20, 20, 20, 0.7);
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 2;
        }

        .genre-carousel:hover .carousel-nav {
            opacity: 1;
        }

        .carousel-nav.prev {
            left: 0;
            background: linear-gradient(to right, rgba(20, 20, 20, 0.9), transparent);
        }

        .carousel-nav.next {
            right: 0;
            background: linear-gradient(to left, rgba(20, 20, 20, 0.9), transparent);
        }

        .carousel-nav i {
            font-size: 2rem;
            color: white;
        }

        /* Vista expandida de género */
        .genre-expanded-view {
            padding: 2rem 60px;
        }

        .back-to-browse {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #aaa;
            text-decoration: none;
            font-size: 0.875rem;
            margin-bottom: 2rem;
            transition: color 0.2s;
        }

        .back-to-browse:hover {
            color: white;
        }

        .genre-expanded-header {
            margin-bottom: 2rem;
        }

        .genre-expanded-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .genre-expanded-count {
            color: #999;
        }

        /* Grid expandido */
        .movies-expanded-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .movie-card-grid {
            position: relative;
            cursor: pointer;
            transition: transform 0.3s ease;
            border-radius: 4px;
            overflow: hidden;
        }

        .movie-card-grid:hover {
            transform: scale(1.05);
            z-index: 10;
        }

        .movie-card-grid.hidden {
            display: none;
        }

        .movie-poster-grid {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
        }

        /* Botón cargar más */
        .load-more-container {
            text-align: center;
            padding: 2rem;
        }

        .load-more-btn {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .load-more-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        /* Sin resultados */
        .no-movies {
            text-align: center;
            padding: 4rem;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .movies-hero {
                height: 30vh;
                min-height: 200px;
            }

            .movies-hero-content {
                left: 20px;
                right: 20px;
            }

            .movies-hero-title {
                font-size: 1.75rem;
            }

            .movies-nav {
                padding: 1rem 20px;
                flex-direction: column;
                align-items: flex-start;
            }

            .movies-nav-title {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            .movies-filters {
                width: 100%;
                gap: 0.5rem;
            }

            .filter-select {
                flex: 1;
                min-width: 0;
                font-size: 0.75rem;
                padding: 0.5rem 1.5rem 0.5rem 0.75rem;
            }

            .genre-section {
                padding: 0 20px;
                margin-bottom: 2rem;
            }

            .genre-carousel {
                margin: 0 -20px;
                padding: 0 20px;
            }

            .movie-card-carousel {
                width: 140px;
            }

            .genre-expanded-view {
                padding: 1rem 20px;
            }

            .movies-expanded-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }

            .carousel-nav {
                display: none;
            }
        }

        @media (max-width: 500px) {
            .movie-card-carousel {
                width: 120px;
            }

            .genre-title {
                font-size: 1.125rem;
            }

            .see-all-btn {
                font-size: 0.75rem;
            }
        }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fonts/font-awesome/css/all.min.css">
</head>

<body>
    <div class="portal-container">
        <!-- Header -->
        <header class="portal-header" id="portalHeader">
            <div class="header-content">
                <div class="logo-section">
                    <?php if ($companyConfig['logo_url']): ?>
                        <img src="<?php echo $companyConfig['logo_url']; ?>" alt="Logo" class="company-logo">
                    <?php else: ?>
                        <h1 class="company-name"><?php echo htmlspecialchars($companyConfig['company_name']); ?></h1>
                    <?php endif; ?>

                    <nav>
                        <ul class="nav-menu">
                            <li><a href="index.php">Inicio</a></li>
                            <li><a href="movies.php" class="active">Películas</a></li>
                            <li><a href="music.php">Música</a></li>
                            <li><a href="games.php">Juegos</a></li>
                        </ul>
                    </nav>
                </div>

                <div class="header-actions">
                    <button class="search-btn" aria-label="Buscar" onclick="Portal.toggleSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Contenedor principal -->
        <div class="movies-browse-container">

            <?php if ($viewMode === 'browse'): ?>
                <!-- VISTA DE NAVEGACIÓN (Browse) -->

                <!-- Hero pequeño -->
                <?php if (!empty($featuredMovies)):
                    $heroMovie = $allMovies[array_rand($allMovies)];
                ?>
                    <section class="movies-hero">
                        <img src="<?php echo $heroMovie['thumbnail_path'] ? CONTENT_URL . $heroMovie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>"
                            alt="<?php echo htmlspecialchars($heroMovie['titulo']); ?>"
                            class="movies-hero-backdrop">
                        <div class="movies-hero-gradient"></div>
                        <div class="movies-hero-content">
                            <h1 class="movies-hero-title">Películas</h1>
                            <p>Explora nuestra colección completa</p>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Navegación y filtros -->
                <div class="movies-nav">
                    <h2 class="movies-nav-title">Todas las películas</h2>
                    <div class="movies-filters">
                        <select class="filter-select" id="genreFilter" onchange="filterByGenre(this.value)">
                            <option value="">Géneros</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?php echo htmlspecialchars($genre); ?>"><?php echo htmlspecialchars(ucfirst($genre)); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="filter-select" id="yearFilter" onchange="filterByYear(this.value)">
                            <option value="">Año</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Secciones por género -->
                <?php if (!empty($featuredMovies)): ?>
                    <!-- Top 10 -->
                    <section class="genre-section">
                        <div class="genre-header">
                            <h3 class="genre-title">Top 10 de hoy</h3>
                        </div>
                        <div class="genre-carousel">
                            <div class="carousel-nav prev" onclick="scrollCarousel('top10-carousel', 'prev')">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="genre-track" id="top10-carousel">
                                <?php foreach ($featuredMovies as $movie): ?>
                                    <div class="movie-card-carousel" onclick="playMovie(<?php echo $movie['id']; ?>)">
                                        <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>"
                                            alt="<?php echo htmlspecialchars($movie['titulo']); ?>"
                                            class="movie-poster-carousel"
                                            loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-nav next" onclick="scrollCarousel('top10-carousel', 'next')">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Carruseles por género -->
                <?php foreach ($moviesByGenre as $genre => $movies):
                    if (count($movies) < 3) continue; // Solo mostrar géneros con 3+ películas
                    $genreSlug = strtolower(str_replace(' ', '-', $genre));
                ?>
                    <section class="genre-section">
                        <div class="genre-header">
                            <h3 class="genre-title"><?php echo htmlspecialchars(ucfirst($genre)); ?></h3>
                            <a href="?view=genre&g=<?php echo urlencode($genre); ?>" class="see-all-btn">
                                Ver todo <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <div class="genre-carousel">
                            <div class="carousel-nav prev" onclick="scrollCarousel('<?php echo $genreSlug; ?>-carousel', 'prev')">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="genre-track" id="<?php echo $genreSlug; ?>-carousel">
                                <?php
                                $displayMovies = array_slice($movies, 0, 15); // Máximo 15 por carrusel
                                foreach ($displayMovies as $movie):
                                ?>
                                    <div class="movie-card-carousel" onclick="playMovie(<?php echo $movie['id']; ?>)">
                                        <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>"
                                            alt="<?php echo htmlspecialchars($movie['titulo']); ?>"
                                            class="movie-poster-carousel"
                                            loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-nav next" onclick="scrollCarousel('<?php echo $genreSlug; ?>-carousel', 'next')">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </section>
                <?php endforeach; ?>

            <?php else: ?>
                <!-- VISTA EXPANDIDA DE GÉNERO -->
                <?php
                $genreMovies = $moviesByGenre[$selectedGenre] ?? [];
                $totalMovies = count($genreMovies);
                ?>

                <div class="genre-expanded-view">
                    <a href="movies.php" class="back-to-browse">
                        <i class="fas fa-arrow-left"></i> Volver a películas
                    </a>

                    <div class="genre-expanded-header">
                        <h1 class="genre-expanded-title"><?php echo htmlspecialchars(ucfirst($selectedGenre)); ?></h1>
                        <p class="genre-expanded-count"><?php echo $totalMovies; ?> películas</p>
                    </div>

                    <?php if ($totalMovies > 0): ?>
                        <div class="movies-expanded-grid" id="expandedGrid">
                            <?php
                            $counter = 0;
                            foreach ($genreMovies as $movie):
                                $isHidden = $counter >= 20 ? 'hidden' : '';
                                $counter++;
                            ?>
                                <div class="movie-card-grid <?php echo $isHidden; ?>"
                                    data-index="<?php echo $counter; ?>"
                                    onclick="playMovie(<?php echo $movie['id']; ?>)">
                                    <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>"
                                        alt="<?php echo htmlspecialchars($movie['titulo']); ?>"
                                        class="movie-poster-grid"
                                        loading="lazy">
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($totalMovies > 20): ?>
                            <div class="load-more-container" id="loadMoreContainer">
                                <button class="load-more-btn" onclick="loadMoreMovies()">
                                    Cargar más películas
                                </button>
                            </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="no-movies">
                            <i class="fas fa-film" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>No hay películas en este género</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php endif; ?>

        </div>

        <!-- Footer -->
        <footer class="portal-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyConfig['company_name']); ?>. Powered by PLAYMI Entertainment.</p>
            </div>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="assets/js/portal-main.js"></script>
    <script src="assets/js/touch-controls.js"></script>
    <script>
        // Variables globales
        let moviesLoaded = 20;
        const moviesPerPage = 20;

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
        });

        // Scroll de carrusel
        function scrollCarousel(carouselId, direction) {
            const carousel = document.getElementById(carouselId);
            const scrollAmount = carousel.offsetWidth * 0.8;

            if (direction === 'prev') {
                carousel.scrollLeft -= scrollAmount;
            } else {
                carousel.scrollLeft += scrollAmount;
            }
        }

        // Cargar más películas en vista expandida
        function loadMoreMovies() {
            const hiddenCards = document.querySelectorAll('.movie-card-grid.hidden');
            let shown = 0;

            hiddenCards.forEach((card, index) => {
                if (shown < moviesPerPage) {
                    card.classList.remove('hidden');
                    shown++;
                }
            });

            moviesLoaded += shown;

            // Ocultar botón si no hay más películas
            if (hiddenCards.length <= moviesPerPage) {
                document.getElementById('loadMoreContainer').style.display = 'none';
            }
        }

        // Filtrar por año
        function filterByYear(year) {
            if (year) {
                window.location.href = `movies.php?year=${year}`;
            } else {
                window.location.href = 'movies.php';
            }
        }

        // Filtrar por género
        function filterByGenre(genre) {
            if (genre) {
                window.location.href = `movies.php?view=genre&g=${encodeURIComponent(genre)}`;
            } else {
                window.location.href = 'movies.php';
            }
        }

        // Ordenar películas
        function sortMovies(sortType) {
            // Para sistema offline, redirigir con parámetros
            window.location.href = `movies.php?sort=${sortType}`;
        }

        // Reproducir película
        function playMovie(id) {
            window.location.href = `player/video-player.php?id=${id}`;
        }

        // Touch scroll para móvil
        const tracks = document.querySelectorAll('.genre-track');
        tracks.forEach(track => {
            let isDown = false;
            let startX;
            let scrollLeft;

            track.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - track.offsetLeft;
                scrollLeft = track.scrollLeft;
            });

            track.addEventListener('mouseleave', () => {
                isDown = false;
            });

            track.addEventListener('mouseup', () => {
                isDown = false;
            });

            track.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - track.offsetLeft;
                const walk = (x - startX) * 2;
                track.scrollLeft = scrollLeft - walk;
            });
        });
    </script>
</body>

</html>