<?php
/**
 * passenger-portal/movies.php
 * Catálogo de películas estilo Netflix - Interfaz de categoría
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

$companyConfig = getCompanyConfig();

// Inicializar variables
$allMovies = [];
$genres = [];
$years = [];
$ratings = [];
$featuredMovies = [];

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener todas las películas activas
    $sql = "SELECT id, titulo, descripcion, tipo, duracion, anio_lanzamiento, 
            calificacion, genero, archivo_path, thumbnail_path, created_at,
            descargas_count
            FROM contenido 
            WHERE tipo = 'pelicula' AND estado = 'activo' 
            ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $allMovies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraer géneros, años y calificaciones únicos
    foreach ($allMovies as $movie) {
        if (!empty($movie['genero']) && !in_array($movie['genero'], $genres)) {
            $genres[] = $movie['genero'];
        }
        if (!empty($movie['anio_lanzamiento']) && !in_array($movie['anio_lanzamiento'], $years)) {
            $years[] = $movie['anio_lanzamiento'];
        }
        if (!empty($movie['calificacion']) && !in_array($movie['calificacion'], $ratings)) {
            $ratings[] = $movie['calificacion'];
        }
    }
    
    sort($genres);
    rsort($years);
    sort($ratings);
    
    // Seleccionar películas destacadas (las 6 más vistas)
    $featuredMovies = array_slice($allMovies, 0, 6);
    
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
    
    <!-- CSS específico para la página de películas estilo Netflix -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
        }
        
        /* Override del header para esta página */
        .portal-header {
            background: rgb(20,20,20);
            background: linear-gradient(180deg, rgba(0,0,0,0.7) 10%, transparent);
        }
        
        /* Contenedor principal */
        .browse-container {
            min-height: 100vh;
            background: #141414;
            padding-top: 68px;
        }
        
        /* Hero Section estilo Netflix Browse */
        .browse-hero {
            position: relative;
            height: 100vh;
            min-height: 500px;
            margin-top: -68px;
            overflow: hidden;
        }
        
        .hero-featured {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .hero-backdrop {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .hero-gradient {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, 
                rgba(0,0,0,0.8) 0%,
                rgba(0,0,0,0.4) 60%,
                transparent 100%);
        }
        
        .hero-bottom-gradient {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 40%;
            background: linear-gradient(to top, 
                #141414 0%,
                rgba(20,20,20,0.7) 50%,
                transparent 100%);
        }
        
        .hero-content {
            position: absolute;
            top: 35%;
            left: 60px;
            max-width: 500px;
            z-index: 10;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.75);
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 1.5rem;
            font-weight: 400;
        }
        
        .hero-description {
            font-size: 1.125rem;
            line-height: 1.6;
            color: #fff;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.75);
        }
        
        /* Navegación secundaria estilo Netflix */
        .secondary-nav {
            position: absolute;
            top: 80px;
            left: 60px;
            z-index: 20;
            display: flex;
            align-items: center;
            gap: 3rem;
        }
        
        .nav-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
        }
        
        .genre-selector-nav {
            position: relative;
        }
        
        .genre-dropdown-toggle {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 0.5rem 2rem 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 2rem;
            transition: all 0.2s;
            position: relative;
            font-weight: 500;
        }
        
        .genre-dropdown-toggle:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
        }
        
        .genre-dropdown-toggle::after {
            content: '';
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid white;
            transition: transform 0.2s;
        }
        
        .genre-dropdown-toggle.active::after {
            transform: translateY(-50%) rotate(180deg);
        }
        
        .genre-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 2px;
            background: rgba(0,0,0,0.9);
            border: 1px solid rgba(255,255,255,0.15);
            min-width: 300px;
            max-height: 450px;
            overflow-y: auto;
            display: none;
            z-index: 1000;
            border-radius: 2px;
        }
        
        .genre-dropdown.active {
            display: block;
        }
        
        .genre-columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            padding: 1rem 0;
        }
        
        .genre-item {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
            font-size: 0.875rem;
            color: #e5e5e5;
        }
        
        .genre-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        /* Contenido principal */
        .browse-content {
            position: relative;
            z-index: 1;
            margin-top: -200px;
            padding-bottom: 4rem;
        }
        
        /* Grid de películas más grande para browse */
        .browse-row {
            margin-bottom: 3rem;
            padding: 0 60px;
        }
        
        .row-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .row-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #e5e5e5;
        }
        
        /* Grid especial para Top 10 */
        .top10-row {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            scrollbar-width: none;
        }
        
        .top10-row::-webkit-scrollbar {
            display: none;
        }
        
        .top10-item {
            position: relative;
            flex: 0 0 auto;
            width: 230px;
            cursor: pointer;
        }
        
        .rank-number {
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 40%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 140px;
            font-weight: 900;
            color: black;
            text-shadow: 
                -4px -4px 0 #fff,
                4px -4px 0 #fff,
                -4px 4px 0 #fff,
                4px 4px 0 #fff;
            z-index: 1;
            font-family: Arial Black, sans-serif;
        }
        
        .top10-thumbnail {
            width: 70%;
            margin-left: 30%;
            aspect-ratio: 2/3;
            object-fit: cover;
            border-radius: 4px;
        }
        
        /* Filtros adicionales */
        .sort-selector {
            position: absolute;
            top: 80px;
            right: 60px;
            z-index: 20;
        }
        
        .sort-dropdown {
            background: #141414;
            border: 1px solid rgba(255,255,255,0.4);
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .browse-hero {
                height: 70vh;
            }
            
            .secondary-nav {
                top: 70px;
                left: 20px;
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .nav-title {
                font-size: 2rem;
            }
            
            .hero-content {
                top: 45%;
                left: 20px;
                right: 20px;
                max-width: 100%;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.25rem;
            }
            
            .hero-description {
                font-size: 1rem;
            }
            
            .browse-row {
                padding: 0 20px;
            }
            
            .top10-item {
                width: 180px;
            }
            
            .rank-number {
                font-size: 100px;
            }
            
            .sort-selector {
                display: none;
            }
            
            .content-carousel {
                margin: 0 -20px;
                padding: 0 20px;
            }
        }
        
        @media (max-width: 500px) {
            .genre-columns {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .genre-dropdown {
                min-width: 250px;
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
        
        <!-- Contenedor principal Browse -->
        <div class="browse-container">
            <!-- Hero Section -->
            <section class="browse-hero">
                <?php if (!empty($featuredMovies)): 
                    $heroMovie = $featuredMovies[0];
                ?>
                    <div class="hero-featured">
                        <img src="<?php echo $heroMovie['thumbnail_path'] ? CONTENT_URL . $heroMovie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($heroMovie['titulo']); ?>" 
                             class="hero-backdrop">
                        <div class="hero-gradient"></div>
                        <div class="hero-bottom-gradient"></div>
                    </div>
                    
                    <div class="hero-content">
                        <h1 class="hero-title"><?php echo htmlspecialchars($heroMovie['titulo']); ?></h1>
                        <?php if ($heroMovie['descripcion']): ?>
                            <p class="hero-description">
                                <?php echo htmlspecialchars(substr($heroMovie['descripcion'], 0, 200)); ?>...
                            </p>
                        <?php endif; ?>
                        <div class="hero-buttons">
                            <a href="player/video-player.php?id=<?php echo $heroMovie['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-play"></i> Reproducir
                            </a>
                            <button class="btn btn-secondary">
                                <i class="fas fa-info-circle"></i> Más información
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Navegación secundaria -->
                <div class="secondary-nav">
                    <h2 class="nav-title">Películas</h2>
                    
                    <div class="genre-selector-nav">
                        <button class="genre-dropdown-toggle" onclick="toggleGenreDropdown()">
                            Géneros
                        </button>
                        <div class="genre-dropdown" id="genreDropdown">
                            <div class="genre-columns">
                                <div class="genre-item" onclick="filterByGenre('')">Todas</div>
                                <?php foreach ($genres as $genre): ?>
                                    <div class="genre-item" onclick="filterByGenre('<?php echo htmlspecialchars($genre); ?>')">
                                        <?php echo htmlspecialchars(ucfirst($genre)); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Selector de ordenamiento -->
                <div class="sort-selector">
                    <select class="sort-dropdown" onchange="sortMovies(this.value)">
                        <option value="suggestions">Sugerencias para ti</option>
                        <option value="year">Año de lanzamiento</option>
                        <option value="az">A-Z</option>
                        <option value="za">Z-A</option>
                    </select>
                </div>
            </section>
            
            <!-- Contenido principal -->
            <main class="browse-content">
                <!-- Top 10 películas -->
                <?php if (count($featuredMovies) >= 6): ?>
                <section class="browse-row">
                    <div class="row-header">
                        <h3 class="row-title">Top 10 películas de hoy</h3>
                    </div>
                    <div class="top10-row">
                        <?php for ($i = 0; $i < min(10, count($featuredMovies)); $i++): 
                            $movie = $featuredMovies[$i];
                        ?>
                            <div class="top10-item" onclick="playMovie(<?php echo $movie['id']; ?>)">
                                <div class="rank-number"><?php echo $i + 1; ?></div>
                                <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($movie['titulo']); ?>" 
                                     class="top10-thumbnail">
                            </div>
                        <?php endfor; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Populares -->
                <section class="browse-row">
                    <div class="row-header">
                        <h3 class="row-title">Populares en <?php echo htmlspecialchars($companyConfig['company_name']); ?></h3>
                    </div>
                    <div class="content-carousel">
                        <div class="carousel-nav prev" onclick="scrollCarousel('popular-carousel', 'prev')">
                            <i class="fas fa-chevron-left"></i>
                        </div>
                        <div class="carousel-track" id="popular-carousel">
                            <?php foreach (array_slice($allMovies, 0, 20) as $movie): ?>
                                <div class="content-card" onclick="playMovie(<?php echo $movie['id']; ?>)">
                                    <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($movie['titulo']); ?>" 
                                         class="card-thumbnail"
                                         loading="lazy">
                                    <div class="card-expanded-info">
                                        <h4 class="card-title"><?php echo htmlspecialchars($movie['titulo']); ?></h4>
                                        <div class="card-meta">
                                            <span class="match-score"><?php echo rand(85, 99); ?>% para ti</span>
                                            <?php if ($movie['anio_lanzamiento']): ?>
                                                <span><?php echo $movie['anio_lanzamiento']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-nav next" onclick="scrollCarousel('popular-carousel', 'next')">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </div>
                </section>
                
                <!-- Por géneros -->
                <?php foreach ($genres as $genre): 
                    $genreMovies = array_filter($allMovies, function($m) use ($genre) {
                        return strcasecmp($m['genero'], $genre) === 0;
                    });
                    
                    if (count($genreMovies) >= 3):
                ?>
                    <section class="browse-row genre-row" data-genre="<?php echo htmlspecialchars($genre); ?>">
                        <div class="row-header">
                            <h3 class="row-title"><?php echo htmlspecialchars(ucfirst($genre)); ?></h3>
                        </div>
                        <div class="content-carousel">
                            <div class="carousel-nav prev" onclick="scrollCarousel('genre-<?php echo htmlspecialchars($genre); ?>', 'prev')">
                                <i class="fas fa-chevron-left"></i>
                            </div>
                            <div class="carousel-track" id="genre-<?php echo htmlspecialchars($genre); ?>">
                                <?php foreach (array_slice($genreMovies, 0, 20) as $movie): ?>
                                    <div class="content-card" onclick="playMovie(<?php echo $movie['id']; ?>)">
                                        <img src="<?php echo $movie['thumbnail_path'] ? CONTENT_URL . $movie['thumbnail_path'] : CONTENT_URL . 'thumbnails/default-movie.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($movie['titulo']); ?>" 
                                             class="card-thumbnail"
                                             loading="lazy">
                                        <div class="card-expanded-info">
                                            <h4 class="card-title"><?php echo htmlspecialchars($movie['titulo']); ?></h4>
                                            <div class="card-meta">
                                                <span class="match-score"><?php echo rand(85, 99); ?>% para ti</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="carousel-nav next" onclick="scrollCarousel('genre-<?php echo htmlspecialchars($genre); ?>', 'next')">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </section>
                <?php endif; endforeach; ?>
            </main>
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
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
            
            // Cerrar dropdown al hacer click fuera
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.genre-selector-nav')) {
                    document.getElementById('genreDropdown').classList.remove('active');
                    document.querySelector('.genre-dropdown-toggle').classList.remove('active');
                }
            });
        });
        
        // Toggle dropdown de géneros
        function toggleGenreDropdown() {
            const dropdown = document.getElementById('genreDropdown');
            const toggle = document.querySelector('.genre-dropdown-toggle');
            
            dropdown.classList.toggle('active');
            toggle.classList.toggle('active');
        }
        
        // Filtrar por género
        function filterByGenre(genre) {
            // Cerrar dropdown
            document.getElementById('genreDropdown').classList.remove('active');
            document.querySelector('.genre-dropdown-toggle').classList.remove('active');
            
            // Mostrar/ocultar filas según el género
            const allRows = document.querySelectorAll('.genre-row');
            
            if (genre === '') {
                // Mostrar todas
                allRows.forEach(row => row.style.display = 'block');
            } else {
                // Mostrar solo el género seleccionado
                allRows.forEach(row => {
                    if (row.dataset.genre.toLowerCase() === genre.toLowerCase()) {
                        row.style.display = 'block';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        }
        
        // Ordenar películas
        function sortMovies(sortType) {
            console.log('Ordenar por:', sortType);
            // Aquí implementarías la lógica de ordenamiento
        }
        
        // Reproducir película
        function playMovie(id) {
            window.location.href = `player/video-player.php?id=${id}`;
        }
        
        // Función para carrusel
        function scrollCarousel(carouselId, direction) {
            const carousel = document.getElementById(carouselId);
            const scrollAmount = carousel.offsetWidth * 0.8;
            
            if (direction === 'prev') {
                carousel.scrollLeft -= scrollAmount;
            } else {
                carousel.scrollLeft += scrollAmount;
            }
        }
    </script>
</body>
</html>