<?php
/**
 * passenger-portal/games.php
 * Página de juegos HTML5 con paginación
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

$companyConfig = getCompanyConfig();

// Obtener juegos de la BD
$games = [];
$categories = [];

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener juegos activos
    $sql = "SELECT id, titulo, descripcion, categoria, thumbnail_path, metadata 
            FROM contenido 
            WHERE tipo = 'juego' AND estado = 'activo' 
            ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraer categorías únicas
    foreach ($games as $game) {
        if (!empty($game['categoria']) && !in_array($game['categoria'], $categories)) {
            $categories[] = $game['categoria'];
        }
    }
    
} catch (Exception $e) {
    error_log("Error loading games: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juegos - <?php echo htmlspecialchars($companyConfig['company_name']); ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/netflix-style.css">
    <link rel="stylesheet" href="assets/css/mobile.css">
    <link rel="stylesheet" href="assets/css/catalog.css">
    
    <!-- CSS personalizado -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --company-secondary: <?php echo $companyConfig['secondary_color']; ?>;
            --game-gradient: linear-gradient(135deg, #000c43 0%, #410f74 100%);
        }
        
        /* Hero mejorado */
        .games-hero {
            background: var(--game-gradient);
            padding: 4rem 4% 3rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .games-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-30px, -30px); }
        }
        
        .games-hero h1 {
            font-size: 3rem;
            font-weight: 900;
            margin-bottom: 1rem;
            position: relative;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .games-hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            position: relative;
        }
        
        .stat-card {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255,255,255,0.1);
            padding: 1.5rem 2rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2rem;
            color: white;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: rgba(255,255,255,0.8);
        }
        
        /* Búsqueda y filtros */
        .games-filters {
            padding: 0 4%;
            margin-bottom: 3rem;
        }
        
        .search-bar {
            position: relative;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .search-bar input {
            width: 100%;
            padding: 1rem 1rem 1rem 3.5rem;
            background: var(--bg-card);
            border: 2px solid transparent;
            border-radius: 50px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .search-bar input:focus {
            border-color: var(--company-primary);
            outline: none;
            background: var(--hover-bg);
        }
        
        .search-bar input::placeholder {
            color: var(--text-secondary);
        }
        
        .search-bar i {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        /* Chips de filtro */
        .filter-chips {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .chip {
            padding: 0.6rem 1.5rem;
            background: var(--bg-card);
            border: 2px solid transparent;
            border-radius: 50px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .chip:hover {
            background: var(--hover-bg);
            color: white;
        }
        
        .chip.active {
            background: var(--company-primary);
            color: white;
            border-color: var(--company-primary);
        }
        
        /* Grid de juegos mejorado */
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 2rem;
            padding: 0 4%;
        }
        
        .game-card {
            background: var(--bg-card);
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.6);
        }
        
        .game-card:hover .game-overlay {
            opacity: 1;
        }
        
        .game-thumbnail-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 16/9;
            overflow: hidden;
        }
        
        .game-thumbnail {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .game-card:hover .game-thumbnail {
            transform: scale(1.05);
        }
        
        /* Badges */
        .play-count-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            backdrop-filter: blur(10px);
        }
        
        .game-info {
            padding: 1.25rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .game-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }
        
        .game-meta {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .game-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .high-score {
            font-size: 0.75rem;
            color: #ffd700;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .game-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex: 1;
        }
        
        .game-controls-info {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        
        .control-type,
        .game-duration {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .game-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            backdrop-filter: blur(5px);
        }
        
        .play-game-btn {
            background: var(--company-primary);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        .play-game-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 30px rgba(0,0,0,0.5);
        }
        
        .play-game-btn:active {
            transform: scale(1.05);
        }
        
        .last-played {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        /* Paginación */
        .pagination-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin: 3rem 0;
            padding: 0 4%;
        }
        
        .pagination-btn {
            background: var(--bg-card);
            border: 2px solid transparent;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .pagination-btn:hover:not(:disabled) {
            background: var(--hover-bg);
            border-color: var(--company-primary);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-numbers {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-number {
            background: var(--bg-card);
            border: 2px solid transparent;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .page-number:hover {
            background: var(--hover-bg);
            border-color: var(--company-primary);
        }
        
        .page-number.active {
            background: var(--company-primary);
            border-color: var(--company-primary);
        }
        
        .pagination-dots {
            color: var(--text-secondary);
            padding: 0 0.5rem;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--text-secondary);
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .games-hero h1 {
                font-size: 2rem;
            }
            
            .games-hero p {
                font-size: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-number {
                font-size: 1.75rem;
            }
            
            .games-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .pagination-controls {
                margin: 2rem 0;
            }
            
            .page-number {
                width: 36px;
                height: 36px;
                font-size: 0.875rem;
            }
        }
        
        @media (max-width: 480px) {
            .stat-card {
                width: 100%;
            }
        }
    </style>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fonts/font-awesome/css/all.min.css">
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
                            <li><a href="movies.php">Películas</a></li>
                            <li><a href="music.php">Música</a></li>
                            <li><a href="games.php" class="active">Juegos</a></li>
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
        
        <!-- Contenido principal -->
        <main class="main-content" style="margin-top: 68px;">
            <!-- Hero Section -->
            <section class="games-hero">
                <h1>Zona de Juegos</h1>
                <p>Tu viaje es más divertido con nuestros juegos.</p>  
            </section>
            
            <!-- Filtros y búsqueda -->
            <div class="games-filters">
                <!-- Barra de búsqueda -->
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Buscar juegos..." id="gameSearch">
                </div>
                
                <!-- Chips de filtro con categorías dinámicas -->
                <div class="filter-chips">
                    <button class="chip active" data-filter="all">
                        <i class="fas fa-gamepad"></i> Todos
                    </button>
                    <?php foreach($categories as $category): ?>
                    <button class="chip" data-filter="<?php echo strtolower($category); ?>">
                        <i class="fas <?php 
                            switch(strtolower($category)) {
                                case 'puzzle': echo 'fa-puzzle-piece'; break;
                                case 'arcade': echo 'fa-rocket'; break;
                                case 'cartas': echo 'fa-dice'; break;
                                case 'estrategia': echo 'fa-chess'; break;
                                case 'aventura': echo 'fa-map'; break;
                                default: echo 'fa-star';
                            }
                        ?>"></i> <?php echo ucfirst($category); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Grid de juegos -->
            <section class="games-grid" id="gamesGrid">
                <?php if (empty($games)): ?>
                <div class="empty-state">
                    <i class="fas fa-gamepad"></i>
                    <h3>No hay juegos disponibles</h3>
                    <p>Vuelve pronto para disfrutar de nuestra colección</p>
                </div>
                <?php endif; ?>
            </section>
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
        // Datos de juegos
        const gamesData = <?php echo json_encode($games); ?>;
        let filteredGames = [...gamesData];
        let gameStats = {};
        let currentFilter = 'all';
        
        // Variables para paginación
        const GAMES_PER_PAGE = 12;
        let currentPage = 1;
        let totalPages = 1;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar portal
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
            
            // Cargar estadísticas locales
            loadGameStats();
            
            // Renderizar juegos
            renderGamesGrid(gamesData);
            
            // Configurar eventos
            setupEventListeners();
            
            // Actualizar estadísticas UI
            updateStatsUI();
        });
        
        function setupEventListeners() {
            // Búsqueda
            const searchInput = document.getElementById('gameSearch');
            searchInput.addEventListener('input', debounce(handleSearch, 300));
            
            // Filtros de categorías
            document.querySelectorAll('.chip').forEach(chip => {
                chip.addEventListener('click', function() {
                    document.querySelector('.chip.active').classList.remove('active');
                    this.classList.add('active');
                    currentFilter = this.dataset.filter;
                    applyFilters();
                });
            });
        }
        
        function handleSearch(e) {
            const query = e.target.value.toLowerCase();
            currentPage = 1; // Reset a primera página
            
            if (query === '') {
                applyFilters();
            } else {
                filteredGames = gamesData.filter(game => 
                    game.titulo.toLowerCase().includes(query) ||
                    (game.descripcion && game.descripcion.toLowerCase().includes(query))
                );
                renderGamesGrid(filteredGames);
            }
        }
        
        function applyFilters() {
            currentPage = 1; // Reset a primera página
            filteredGames = [...gamesData];
            
            // Filtrar por categoría seleccionada
            if (currentFilter !== 'all') {
                filteredGames = filteredGames.filter(game => 
                    game.categoria && game.categoria.toLowerCase() === currentFilter
                );
            }
            
            renderGamesGrid(filteredGames);
        }
        
        function renderGamesGrid(games) {
            const grid = document.getElementById('gamesGrid');
            
            if (games.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h3>No se encontraron juegos</h3>
                        <p>Intenta con otros filtros o términos de búsqueda</p>
                    </div>
                `;
                hidePagination();
                return;
            }
            
            // Calcular paginación
            totalPages = Math.ceil(games.length / GAMES_PER_PAGE);
            const startIndex = (currentPage - 1) * GAMES_PER_PAGE;
            const endIndex = startIndex + GAMES_PER_PAGE;
            const paginatedGames = games.slice(startIndex, endIndex);
            
            // Renderizar solo los juegos de la página actual
            grid.innerHTML = paginatedGames.map(game => {
                const stats = gameStats[game.id] || { playCount: 0, lastPlayed: null, highScore: 0 };
                const metadata = game.metadata ? JSON.parse(game.metadata) : {};
                
                return `
                <div class="game-card" data-id="${game.id}" data-category="${game.categoria || 'otros'}">
                    <div class="game-thumbnail-wrapper">
                        <img src="${getGameThumbnail(game)}" 
                             alt="${game.titulo}" 
                             class="game-thumbnail"
                             loading="lazy"
                             onerror="this.src='<?php echo CONTENT_URL; ?>thumbnails/default-game.jpg'">
                        ${stats.playCount > 0 ? `
                        <div class="play-count-badge">
                            <i class="fas fa-play"></i> ${stats.playCount}
                        </div>` : ''}
                    </div>
                    
                    <div class="game-info">
                        <h3 class="game-title">${game.titulo}</h3>
                        <div class="game-meta">
                            <span class="game-category">${game.categoria || 'Casual'}</span>
                            ${stats.highScore > 0 ? `
                            <span class="high-score">
                                <i class="fas fa-trophy"></i> ${formatNumber(stats.highScore)}
                            </span>` : ''}
                        </div>
                        <p class="game-description">${game.descripcion || 'Un juego divertido para pasar el tiempo'}</p>
                        
                        <div class="game-controls-info">
                            ${metadata.controles ? `
                            <span class="control-type">
                                <i class="fas fa-keyboard"></i> ${metadata.controles}
                            </span>` : ''}
                            ${metadata.instrucciones ? `
                            <span class="game-duration" title="${metadata.instrucciones}">
                                <i class="fas fa-info-circle"></i> Info
                            </span>` : ''}
                        </div>
                    </div>
                    
                    <div class="game-overlay">
                        <button class="play-game-btn" onclick="launchGame(${game.id})">
                            <i class="fas fa-play"></i> Jugar ahora
                        </button>
                        ${stats.lastPlayed ? `
                        <p class="last-played">
                            Última vez: ${formatLastPlayed(stats.lastPlayed)}
                        </p>` : ''}
                    </div>
                </div>
                `;
            }).join('');
            
            // Mostrar/actualizar paginación
            updatePagination();
        }
        
        function updatePagination() {
            // Crear o actualizar controles de paginación
            let paginationContainer = document.getElementById('paginationControls');
            if (!paginationContainer) {
                paginationContainer = document.createElement('div');
                paginationContainer.id = 'paginationControls';
                paginationContainer.className = 'pagination-controls';
                document.querySelector('.games-grid').insertAdjacentElement('afterend', paginationContainer);
            }
            
            if (totalPages <= 1) {
                paginationContainer.style.display = 'none';
                return;
            }
            
            paginationContainer.style.display = 'flex';
            paginationContainer.innerHTML = `
                <button class="pagination-btn" onclick="goToPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
                
                <div class="pagination-numbers">
                    ${generatePageNumbers()}
                </div>
                
                <button class="pagination-btn" onclick="goToPage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }
        
        function generatePageNumbers() {
            let html = '';
            const maxVisible = 5;
            
            if (totalPages <= maxVisible) {
                // Mostrar todas las páginas
                for (let i = 1; i <= totalPages; i++) {
                    html += `<button class="page-number ${i === currentPage ? 'active' : ''}" 
                             onclick="goToPage(${i})">${i}</button>`;
                }
            } else {
                // Lógica para mostrar páginas con puntos suspensivos
                if (currentPage <= 3) {
                    for (let i = 1; i <= 4; i++) {
                        html += `<button class="page-number ${i === currentPage ? 'active' : ''}" 
                                 onclick="goToPage(${i})">${i}</button>`;
                    }
                    html += `<span class="pagination-dots">...</span>`;
                    html += `<button class="page-number" onclick="goToPage(${totalPages})">${totalPages}</button>`;
                } else if (currentPage >= totalPages - 2) {
                    html += `<button class="page-number" onclick="goToPage(1)">1</button>`;
                    html += `<span class="pagination-dots">...</span>`;
                    for (let i = totalPages - 3; i <= totalPages; i++) {
                        html += `<button class="page-number ${i === currentPage ? 'active' : ''}" 
                                 onclick="goToPage(${i})">${i}</button>`;
                    }
                } else {
                    html += `<button class="page-number" onclick="goToPage(1)">1</button>`;
                    html += `<span class="pagination-dots">...</span>`;
                    for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                        html += `<button class="page-number ${i === currentPage ? 'active' : ''}" 
                                 onclick="goToPage(${i})">${i}</button>`;
                    }
                    html += `<span class="pagination-dots">...</span>`;
                    html += `<button class="page-number" onclick="goToPage(${totalPages})">${totalPages}</button>`;
                }
            }
            
            return html;
        }
        
        function goToPage(page) {
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderGamesGrid(filteredGames);
            
            // Scroll suave al inicio de la grid
            document.getElementById('gamesGrid').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
        
        function hidePagination() {
            const paginationContainer = document.getElementById('paginationControls');
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
        }
        
        function getGameThumbnail(game) {
            if (game.thumbnail_path) {
                return '<?php echo CONTENT_URL; ?>' + game.thumbnail_path;
            }
            return '<?php echo CONTENT_URL; ?>thumbnails/default-game.jpg';
        }
        
        function launchGame(id) {
            // Guardar estadística
            if (!gameStats[id]) {
                gameStats[id] = { playCount: 0, lastPlayed: null, highScore: 0 };
            }
            gameStats[id].playCount++;
            gameStats[id].lastPlayed = Date.now();
            saveGameStats();
            
            // Abrir juego
            window.location.href = `player/game-launcher.php?id=${id}`;
        }
        
        function loadGameStats() {
            try {
                gameStats = JSON.parse(localStorage.getItem('gameStats') || '{}');
            } catch(e) {
                gameStats = {};
            }
        }
        
        function saveGameStats() {
            try {
                localStorage.setItem('gameStats', JSON.stringify(gameStats));
                updateStatsUI();
            } catch(e) {
                console.error('Error saving game stats:', e);
            }
        }
        
        function updateStatsUI() {
            let totalPlays = 0;
            let highestScore = 0;
            
            Object.values(gameStats).forEach(stat => {
                totalPlays += stat.playCount || 0;
                if (stat.highScore > highestScore) {
                    highestScore = stat.highScore;
                }
            });
            
            document.getElementById('gamesPlayed').textContent = totalPlays;
            document.getElementById('highScore').textContent = formatNumber(highestScore);
        }
        
        function formatLastPlayed(timestamp) {
            const diff = Date.now() - timestamp;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Hace un momento';
            if (minutes < 60) return `Hace ${minutes} min`;
            if (hours < 24) return `Hace ${hours}h`;
            if (days < 30) return `Hace ${days}d`;
            return new Date(timestamp).toLocaleDateString();
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    </script>
</body>
</html>