<?php
/**
 * passenger-portal/search.php
 * Página de búsqueda global del portal
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';

$companyConfig = getCompanyConfig();
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar - <?php echo htmlspecialchars($companyConfig['company_name']); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/netflix-style.css">
    <link rel="stylesheet" href="assets/css/catalog.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    
    <!-- CSS personalizado para búsqueda -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
        }
        
        .search-page {
            min-height: 100vh;
            padding-top: 100px;
            background: var(--bg-primary);
        }
        
        .search-header {
            padding: 2rem 4%;
            max-width: 1920px;
            margin: 0 auto;
        }
        
        .search-input-container {
            position: relative;
            max-width: 800px;
            margin: 0 auto 3rem;
        }
        
        .search-input-large {
            width: 100%;
            background: var(--bg-card);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: white;
            padding: 1.25rem 3.5rem 1.25rem 4rem;
            font-size: 1.5rem;
            border-radius: 50px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-input-large:focus {
            border-color: var(--company-primary);
            background: var(--bg-card-hover);
        }
        
        .search-icon-large {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.5rem;
            color: var(--text-secondary);
        }
        
        .clear-search {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .search-input-large:not(:placeholder-shown) ~ .clear-search {
            opacity: 1;
        }
        
        .clear-search:hover {
            color: white;
        }
        
        .search-stats {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .search-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .filter-chip {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .filter-chip:hover {
            background: var(--hover-bg);
            border-color: var(--company-primary);
        }
        
        .filter-chip.active {
            background: var(--company-primary);
            border-color: var(--company-primary);
            color: white;
        }
        
        .search-results {
            padding: 0 4%;
            max-width: 1920px;
            margin: 0 auto;
        }
        
        .results-section {
            margin-bottom: 3rem;
        }
        
        .results-section-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .results-count {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: normal;
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .no-results-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .no-results-message {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .no-results-suggestion {
            font-size: 1.125rem;
        }
        
        .search-suggestions {
            background: var(--bg-card);
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .suggestions-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .suggestion-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
        
        .suggestion-item {
            background: var(--hover-bg);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .suggestion-item:hover {
            background: var(--company-primary);
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .search-header {
                padding: 1rem 4%;
            }
            
            .search-input-large {
                font-size: 1.125rem;
                padding: 1rem 3rem 1rem 3.5rem;
            }
            
            .search-page {
                padding-top: 80px;
            }
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fonts/font-awesome/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="portal-header scrolled" id="portalHeader">
        <div class="header-content">
            <div class="logo-section">
                <?php if ($companyConfig['logo_url']): ?>
                    <img src="<?php echo $companyConfig['logo_url']; ?>" alt="Logo" class="company-logo">
                <?php else: ?>
                    <h1 class="company-name"><?php echo htmlspecialchars($companyConfig['company_name']); ?></h1>
                <?php endif; ?>
                
                <nav class="nav-menu">
                    <a href="index.php">Inicio</a>
                    <a href="movies.php">Películas</a>
                    <a href="music.php">Música</a>
                    <a href="games.php">Juegos</a>
                </nav>
            </div>
            
            <div class="header-actions">
                <button class="search-btn" onclick="document.getElementById('searchInput').focus()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
    </header>
    
    <!-- Contenido principal -->
    <main class="search-page">
        <div class="search-header">
            <div class="search-input-container">
                <i class="fas fa-search search-icon-large"></i>
                <input 
                    type="text" 
                    id="searchInput"
                    class="search-input-large" 
                    placeholder="Buscar películas, música o juegos..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    autofocus
                >
                <button class="clear-search" onclick="clearSearch()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="search-stats" id="searchStats">
                <?php if ($searchQuery): ?>
                    Buscando "<?php echo htmlspecialchars($searchQuery); ?>"...
                <?php else: ?>
                    Ingresa un término para buscar
                <?php endif; ?>
            </div>
            
            <div class="search-filters">
                <button class="filter-chip active" data-type="all">
                    <i class="fas fa-globe"></i> Todo
                </button>
                <button class="filter-chip" data-type="movies">
                    <i class="fas fa-film"></i> Películas
                </button>
                <button class="filter-chip" data-type="music">
                    <i class="fas fa-music"></i> Música
                </button>
                <button class="filter-chip" data-type="games">
                    <i class="fas fa-gamepad"></i> Juegos
                </button>
            </div>
        </div>
        
        <div class="search-results" id="searchResults">
            <!-- Los resultados se cargarán aquí dinámicamente -->
            <?php if (!$searchQuery): ?>
                <div class="no-results">
                    <div class="search-suggestions">
                        <h3 class="suggestions-title">Búsquedas populares</h3>
                        <div class="suggestion-list">
                            <a href="?q=acción" class="suggestion-item">Acción</a>
                            <a href="?q=comedia" class="suggestion-item">Comedia</a>
                            <a href="?q=aventura" class="suggestion-item">Aventura</a>
                            <a href="?q=música clásica" class="suggestion-item">Música Clásica</a>
                            <a href="?q=rock" class="suggestion-item">Rock</a>
                            <a href="?q=puzzle" class="suggestion-item">Puzzle</a>
                            <a href="?q=arcade" class="suggestion-item">Arcade</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- JavaScript -->
    <script src="assets/js/portal-main.js"></script>
    <script>
        // Configuración
        const config = {
            companyId: <?php echo $companyConfig['company_id']; ?>,
            apiUrl: 'api/'
        };
        
        // Variables
        let searchTimeout;
        let currentFilter = 'all';
        let isSearching = false;
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            Portal.init(config);
            
            // Si hay una búsqueda inicial, ejecutarla
            <?php if ($searchQuery): ?>
                performSearch('<?php echo addslashes($searchQuery); ?>');
            <?php endif; ?>
            
            // Configurar eventos
            setupSearchEvents();
        });
        
        // Configurar eventos de búsqueda
        function setupSearchEvents() {
            const searchInput = document.getElementById('searchInput');
            
            // Búsqueda en tiempo real con debounce
            searchInput.addEventListener('input', function(e) {
                clearTimeout(searchTimeout);
                const query = e.target.value.trim();
                
                // Actualizar URL sin recargar
                const newUrl = query ? `?q=${encodeURIComponent(query)}` : 'search.php';
                window.history.replaceState({}, '', newUrl);
                
                if (query.length >= 2) {
                    searchTimeout = setTimeout(() => {
                        performSearch(query);
                    }, 300);
                } else if (query.length === 0) {
                    clearSearch();
                }
            });
            
            // Enter para buscar inmediatamente
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimeout);
                    const query = e.target.value.trim();
                    if (query) {
                        performSearch(query);
                    }
                }
            });
            
            // Filtros
            document.querySelectorAll('.filter-chip').forEach(chip => {
                chip.addEventListener('click', function() {
                    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');
                    currentFilter = this.dataset.type;
                    
                    const query = searchInput.value.trim();
                    if (query) {
                        performSearch(query);
                    }
                });
            });
        }
        
        // Realizar búsqueda
        async function performSearch(query) {
            if (isSearching) return;
            isSearching = true;
            
            const searchStats = document.getElementById('searchStats');
            const searchResults = document.getElementById('searchResults');
            
            // Mostrar estado de búsqueda
            searchStats.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Buscando "${escapeHtml(query)}"...`;
            searchResults.innerHTML = '<div class="catalog-loading"><div class="loading-spinner"></div></div>';
            
            try {
                // Llamar a la API de búsqueda
                const response = await fetch(`api/search-content.php?q=${encodeURIComponent(query)}&type=${currentFilter}`);
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.results, query);
                } else {
                    throw new Error(data.message || 'Error en la búsqueda');
                }
            } catch (error) {
                console.error('Error:', error);
                searchResults.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2 class="no-results-message">Error al realizar la búsqueda</h2>
                        <p class="no-results-suggestion">Por favor, intenta de nuevo</p>
                    </div>
                `;
            } finally {
                isSearching = false;
            }
        }
        
        // Mostrar resultados
        function displayResults(results, query) {
            const searchStats = document.getElementById('searchStats');
            const searchResults = document.getElementById('searchResults');
            
            // Actualizar estadísticas
            const totalResults = results.movies.length + results.music.length + results.games.length;
            searchStats.textContent = totalResults > 0 
                ? `${totalResults} resultados para "${query}"`
                : `No se encontraron resultados para "${query}"`;
            
            if (totalResults === 0) {
                searchResults.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h2 class="no-results-message">No se encontraron resultados</h2>
                        <p class="no-results-suggestion">Intenta con otros términos de búsqueda</p>
                        
                        <div class="search-suggestions">
                            <h3 class="suggestions-title">También puedes probar</h3>
                            <div class="suggestion-list">
                                <a href="?q=acción" class="suggestion-item">Acción</a>
                                <a href="?q=aventura" class="suggestion-item">Aventura</a>
                                <a href="?q=música" class="suggestion-item">Música</a>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Construir HTML de resultados
            let html = '';
            
            // Películas
            if (results.movies && results.movies.length > 0 && (currentFilter === 'all' || currentFilter === 'movies')) {
                html += `
                    <div class="results-section">
                        <h2 class="results-section-title">
                            <i class="fas fa-film"></i> Películas 
                            <span class="results-count">(${results.movies.length})</span>
                        </h2>
                        <div class="catalog-grid">
                            ${results.movies.map(item => createResultCard(item, 'movies')).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Música
            if (results.music && results.music.length > 0 && (currentFilter === 'all' || currentFilter === 'music')) {
                html += `
                    <div class="results-section">
                        <h2 class="results-section-title">
                            <i class="fas fa-music"></i> Música 
                            <span class="results-count">(${results.music.length})</span>
                        </h2>
                        <div class="catalog-grid">
                            ${results.music.map(item => createResultCard(item, 'music')).join('')}
                        </div>
                    </div>
                `;
            }
            
            // Juegos
            if (results.games && results.games.length > 0 && (currentFilter === 'all' || currentFilter === 'games')) {
                html += `
                    <div class="results-section">
                        <h2 class="results-section-title">
                            <i class="fas fa-gamepad"></i> Juegos 
                            <span class="results-count">(${results.games.length})</span>
                        </h2>
                        <div class="catalog-grid">
                            ${results.games.map(item => createResultCard(item, 'games')).join('')}
                        </div>
                    </div>
                `;
            }
            
            searchResults.innerHTML = html;
            
            // Animar entrada de resultados
            document.querySelectorAll('.results-section').forEach((section, index) => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    section.style.transition = 'all 0.5s ease';
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }
        
        // Crear tarjeta de resultado
        function createResultCard(item, type) {
            const thumbnailUrl = item.thumbnail_path 
                ? `/playmi/content/${item.thumbnail_path}` 
                : Portal.getThumbnailUrl(item);
            
            return `
                <div class="catalog-item content-card" data-id="${item.id}" data-type="${type}">
                    <div class="thumbnail-wrapper">
                        <img src="${thumbnailUrl}" 
                             alt="${escapeHtml(item.titulo)}" 
                             class="item-thumbnail"
                             loading="lazy">
                        <div class="item-overlay">
                            <div class="overlay-actions">
                                <button class="play-button" onclick="Portal.navigateToPlayer('${type}', ${item.id})">
                                    <i class="fas fa-play"></i> Reproducir
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="item-info">
                        <h3 class="item-title">${highlightSearchTerm(escapeHtml(item.titulo), searchInput.value)}</h3>
                        <div class="item-meta">
                            ${item.duracion ? `<span>${formatDuration(item.duracion)}</span>` : ''}
                            ${item.anio_lanzamiento ? `<span class="meta-separator">•</span><span>${item.anio_lanzamiento}</span>` : ''}
                            ${item.categoria ? `<span class="meta-separator">•</span><span>${item.categoria}</span>` : ''}
                        </div>
                        ${item.descripcion ? `<p class="item-description">${highlightSearchTerm(escapeHtml(item.descripcion), searchInput.value)}</p>` : ''}
                    </div>
                </div>
            `;
        }
        
        // Limpiar búsqueda
        function clearSearch() {
            const searchInput = document.getElementById('searchInput');
            searchInput.value = '';
            searchInput.focus();
            
            // Limpiar URL
            window.history.replaceState({}, '', 'search.php');
            
            // Mostrar sugerencias
            document.getElementById('searchStats').textContent = 'Ingresa un término para buscar';
            document.getElementById('searchResults').innerHTML = `
                <div class="no-results">
                    <div class="search-suggestions">
                        <h3 class="suggestions-title">Búsquedas populares</h3>
                        <div class="suggestion-list">
                            <a href="?q=acción" class="suggestion-item">Acción</a>
                            <a href="?q=comedia" class="suggestion-item">Comedia</a>
                            <a href="?q=aventura" class="suggestion-item">Aventura</a>
                            <a href="?q=música clásica" class="suggestion-item">Música Clásica</a>
                            <a href="?q=rock" class="suggestion-item">Rock</a>
                            <a href="?q=puzzle" class="suggestion-item">Puzzle</a>
                            <a href="?q=arcade" class="suggestion-item">Arcade</a>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Utilidades
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function highlightSearchTerm(text, term) {
            if (!term) return text;
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<mark style="background: var(--company-primary); color: white; padding: 0 2px;">$1</mark>');
        }
        
        function formatDuration(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            
            if (hours > 0) {
                return `${hours}h ${minutes}min`;
            } else {
                return `${minutes} min`;
            }
        }
    </script>
</body>
</html>