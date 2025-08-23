<?php
/**
 * passenger-portal/movies.php
 * Catálogo completo de películas estilo Netflix
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';

$companyConfig = getCompanyConfig();
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
    <link rel="stylesheet" href="assets/css/catalog.css">
    
    <!-- CSS personalizado -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="portal-container">
        <!-- Header -->
        <header class="portal-header scrolled" id="portalHeader">
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
                    <button class="search-btn" aria-label="Buscar" onclick="toggleSearch()">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Contenido principal -->
        <main class="catalog-container" style="margin-top: 68px;">
            <!-- Header del catálogo -->
            <div class="catalog-header">
                <h1 class="catalog-title">Películas</h1>
                <p class="catalog-description">Disfruta de nuestra selección de películas durante tu viaje</p>
            </div>
            
            <!-- Controles del catálogo -->
            <div class="catalog-controls">
                <div class="filter-group">
                    <select class="filter-dropdown" id="genreFilter" onchange="filterMovies()">
                        <option value="">Todos los géneros</option>
                        <option value="accion">Acción</option>
                        <option value="comedia">Comedia</option>
                        <option value="drama">Drama</option>
                        <option value="terror">Terror</option>
                        <option value="scifi">Ciencia Ficción</option>
                        <option value="animacion">Animación</option>
                        <option value="documental">Documental</option>
                    </select>
                    
                    <select class="filter-dropdown" id="yearFilter" onchange="filterMovies()">
                        <option value="">Todos los años</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                        <option value="2022">2022</option>
                        <option value="2021">2021</option>
                        <option value="2020">2020</option>
                        <option value="old">Clásicas</option>
                    </select>
                    
                    <select class="filter-dropdown" id="sortFilter" onchange="sortMovies()">
                        <option value="popular">Más populares</option>
                        <option value="recent">Más recientes</option>
                        <option value="title">Alfabético</option>
                        <option value="duration">Duración</option>
                    </select>
                </div>
                
                <div class="view-toggles">
                    <button class="view-toggle active" data-view="grid" onclick="changeView('grid')">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-toggle" data-view="list" onclick="changeView('list')">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <!-- Búsqueda -->
            <div class="catalog-search" id="searchBox" style="display: none; margin-bottom: 2rem;">
                <i class="fas fa-search search-icon"></i>
                <input type="text" 
                       class="search-input" 
                       placeholder="Buscar películas..." 
                       id="searchInput"
                       onkeyup="searchMovies()">
            </div>
            
            <!-- Grid de películas -->
            <div class="catalog-grid" id="moviesGrid">
                <!-- Contenido cargado dinámicamente -->
            </div>
            
            <!-- Paginación -->
            <div class="catalog-pagination" id="pagination" style="display: none;">
                <button class="pagination-button" onclick="prevPage()" id="prevBtn">
                    <i class="fas fa-chevron-left"></i> Anterior
                </button>
                <span class="pagination-info" id="pageInfo">Página 1 de 5</span>
                <button class="pagination-button" onclick="nextPage()" id="nextBtn">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Estado de carga -->
            <div class="catalog-loading" id="loadingState" style="display: none;">
                <div class="loading-spinner"></div>
            </div>
            
            <!-- Estado vacío -->
            <div class="catalog-empty" id="emptyState" style="display: none;">
                <i class="fas fa-film empty-icon"></i>
                <h2 class="empty-message">No se encontraron películas</h2>
                <p>Intenta ajustar los filtros o realizar una nueva búsqueda</p>
            </div>
        </main>
        
        <!-- Footer -->
        <footer class="portal-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyConfig['company_name']); ?>. Powered by PLAYMI Entertainment.</p>
            </div>
        </footer>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/portal-main.js"></script>
    <script>
        // Variables globales
        let allMovies = [];
        let filteredMovies = [];
        let currentPage = 1;
        const itemsPerPage = 20;
        let currentView = 'grid';
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
            
            loadMovies();
        });
        
        // Cargar películas
        async function loadMovies() {
            showLoading(true);
            
            try {
                const response = await fetch('api/get-content.php?type=movies&limit=100');
                const data = await response.json();
                
                if (data.success) {
                    allMovies = data.data;
                    filteredMovies = [...allMovies];
                    renderMovies();
                } else {
                    showEmpty();
                }
            } catch (error) {
                console.error('Error loading movies:', error);
                showEmpty();
            }
            
            showLoading(false);
        }
        
        // Renderizar películas
        function renderMovies() {
            const grid = document.getElementById('moviesGrid');
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const moviesToShow = filteredMovies.slice(start, end);
            
            if (moviesToShow.length === 0) {
                showEmpty();
                return;
            }
            
            grid.innerHTML = moviesToShow.map(movie => {
                const duration = movie.duracion ? Math.floor(movie.duracion / 60) + ' min' : '';
                const year = movie.anio_lanzamiento || '';
                const rating = movie.calificacion || '';
                
                return `
                    <div class="catalog-item" onclick="playMovie(${movie.id})">
                        <div class="thumbnail-wrapper">
                            <img src="${Portal.getThumbnailUrl(movie)}" 
                                 alt="${movie.titulo}" 
                                 class="item-thumbnail"
                                 loading="lazy">
                            <div class="item-overlay">
                                <div class="overlay-actions">
                                    <button class="play-button">
                                        <i class="fas fa-play"></i> Reproducir
                                    </button>
                                    <button class="info-button">
                                        <i class="fas fa-info"></i>
                                    </button>
                                </div>
                            </div>
                            ${movie.calificacion === 'HD' ? '<div class="item-badges"><span class="badge hd">HD</span></div>' : ''}
                        </div>
                        <div class="item-info">
                            <h3 class="item-title">${movie.titulo}</h3>
                            <div class="item-meta">
                                ${duration ? `<span>${duration}</span>` : ''}
                                ${duration && year ? '<span class="meta-separator">•</span>' : ''}
                                ${year ? `<span>${year}</span>` : ''}
                                ${rating && (duration || year) ? '<span class="meta-separator">•</span>' : ''}
                                ${rating ? `<span>${rating}</span>` : ''}
                            </div>
                            ${movie.descripcion ? `<p class="item-description">${movie.descripcion}</p>` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            // Actualizar paginación
            updatePagination();
            
            // Ocultar estado vacío
            document.getElementById('emptyState').style.display = 'none';
        }
        
        // Filtrar películas
        function filterMovies() {
            const genre = document.getElementById('genreFilter').value;
            const year = document.getElementById('yearFilter').value;
            
            filteredMovies = allMovies.filter(movie => {
                let matchGenre = !genre || movie.genero === genre;
                let matchYear = !year;
                
                if (year && year !== 'old') {
                    matchYear = movie.anio_lanzamiento == year;
                } else if (year === 'old') {
                    matchYear = movie.anio_lanzamiento < 2020;
                }
                
                return matchGenre && matchYear;
            });
            
            currentPage = 1;
            renderMovies();
        }
        
        // Ordenar películas
        function sortMovies() {
            const sortBy = document.getElementById('sortFilter').value;
            
            switch (sortBy) {
                case 'popular':
                    filteredMovies.sort((a, b) => (b.descargas_count || 0) - (a.descargas_count || 0));
                    break;
                case 'recent':
                    filteredMovies.sort((a, b) => (b.anio_lanzamiento || 0) - (a.anio_lanzamiento || 0));
                    break;
                case 'title':
                    filteredMovies.sort((a, b) => a.titulo.localeCompare(b.titulo));
                    break;
                case 'duration':
                    filteredMovies.sort((a, b) => (b.duracion || 0) - (a.duracion || 0));
                    break;
            }
            
            currentPage = 1;
            renderMovies();
        }
        
        // Buscar películas
        function searchMovies() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            if (searchTerm) {
                filteredMovies = allMovies.filter(movie => 
                    movie.titulo.toLowerCase().includes(searchTerm) ||
                    (movie.descripcion && movie.descripcion.toLowerCase().includes(searchTerm))
                );
            } else {
                filteredMovies = [...allMovies];
            }
            
            currentPage = 1;
            renderMovies();
        }
        
        // Cambiar vista
        function changeView(view) {
            currentView = view;
            const grid = document.getElementById('moviesGrid');
            const buttons = document.querySelectorAll('.view-toggle');
            
            buttons.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
        }
        
        // Toggle búsqueda
        function toggleSearch() {
            const searchBox = document.getElementById('searchBox');
            const isVisible = searchBox.style.display !== 'none';
            
            searchBox.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                document.getElementById('searchInput').focus();
            }
        }
        
        // Paginación
        function updatePagination() {
            const totalPages = Math.ceil(filteredMovies.length / itemsPerPage);
            const pagination = document.getElementById('pagination');
            const pageInfo = document.getElementById('pageInfo');
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (totalPages <= 1) {
                pagination.style.display = 'none';
                return;
            }
            
            pagination.style.display = 'flex';
            pageInfo.textContent = `Página ${currentPage} de ${totalPages}`;
            prevBtn.disabled = currentPage === 1;
            nextBtn.disabled = currentPage === totalPages;
        }
        
        function prevPage() {
            if (currentPage > 1) {
                currentPage--;
                renderMovies();
                window.scrollTo(0, 0);
            }
        }
        
        function nextPage() {
            const totalPages = Math.ceil(filteredMovies.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                renderMovies();
                window.scrollTo(0, 0);
            }
        }
        
        // Estados UI
        function showLoading(show) {
            document.getElementById('loadingState').style.display = show ? 'flex' : 'none';
            document.getElementById('moviesGrid').style.display = show ? 'none' : 'grid';
        }
        
        function showEmpty() {
            document.getElementById('emptyState').style.display = 'block';
            document.getElementById('moviesGrid').style.display = 'none';
            document.getElementById('pagination').style.display = 'none';
        }
        
        // Reproducir película
        function playMovie(id) {
            Portal.trackInteraction('content_click', { id: id, type: 'movie' });
            window.location.href = `player/video-player.php?id=${id}`;
        }
    </script>
</body>
</html>