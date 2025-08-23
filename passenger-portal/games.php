<?php
/**
 * passenger-portal/games.php
 * Página de juegos HTML5
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
        }
        
        .games-hero {
            background: linear-gradient(135deg, #2a1a3a 0%, #1a1a2a 100%);
            padding: 3rem 4%;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .games-hero h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            background: linear-gradient(45deg, var(--company-primary), #ff6b35);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .games-hero p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .category-selector {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding: 0 4%;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .category-btn {
            padding: 0.75rem 1.5rem;
            background: var(--bg-card);
            color: var(--text-primary);
            border: 2px solid transparent;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .category-btn:hover {
            background: var(--hover-bg);
            border-color: var(--company-primary);
        }
        
        .category-btn.active {
            background: var(--company-primary);
            border-color: var(--company-primary);
        }
        
        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        }
        
        .game-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.6);
        }
        
        .game-card:hover .game-overlay {
            opacity: 1;
        }
        
        .game-thumbnail {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
        }
        
        .game-info {
            padding: 1.25rem;
        }
        
        .game-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .game-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .game-description {
            color: var(--text-secondary);
            font-size: 0.875rem;
            line-height: 1.4;
            margin-bottom: 0.75rem;
        }
        
        .game-rating {
            display: flex;
            gap: 0.25rem;
            font-size: 0.875rem;
        }
        
        .game-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.85);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .play-game-btn {
            background: var(--company-primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            transition: transform 0.2s;
        }
        
        .play-game-btn:hover {
            transform: scale(1.1);
        }
        
        .game-controls {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .games-hero h1 {
                font-size: 1.75rem;
            }
            
            .games-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
            
            .category-btn {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }
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
                <p>Diviértete con nuestra colección de juegos HTML5 sin necesidad de descargas</p>
            </section>
            
            <!-- Selector de categorías -->
            <div class="category-selector">
                <button class="category-btn active" data-category="all">
                    <i class="fas fa-gamepad"></i> Todos
                </button>
                <button class="category-btn" data-category="puzzle">
                    <i class="fas fa-puzzle-piece"></i> Puzzle
                </button>
                <button class="category-btn" data-category="arcade">
                    <i class="fas fa-rocket"></i> Arcade
                </button>
                <button class="category-btn" data-category="cartas">
                    <i class="fas fa-dice"></i> Cartas
                </button>
                <button class="category-btn" data-category="estrategia">
                    <i class="fas fa-chess"></i> Estrategia
                </button>
                <button class="category-btn" data-category="casual">
                    <i class="fas fa-star"></i> Casual
                </button>
            </div>
            
            <!-- Grid de juegos -->
            <section class="games-grid" id="gamesGrid">
                <!-- Contenido cargado dinámicamente -->
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
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar portal
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
            
            // Cargar juegos
            loadGames('all');
            
            // Configurar selectores de categoría
            document.querySelectorAll('.category-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelector('.category-btn.active').classList.remove('active');
                    this.classList.add('active');
                    loadGames(this.dataset.category);
                });
            });
        });
        
        async function loadGames(category) {
            try {
                const response = await fetch(`api/get-content.php?type=games&category=${category}&limit=50`);
                const data = await response.json();
                
                if (data.success) {
                    renderGamesGrid(data.data);
                }
            } catch (error) {
                console.error('Error loading games:', error);
            }
        }
        
        function renderGamesGrid(games) {
            const grid = document.getElementById('gamesGrid');
            
            // Simular algunos juegos para demostración
            const demoGames = games.length > 0 ? games : [
                {
                    id: 1,
                    titulo: 'Tetris Classic',
                    descripcion: 'El clásico juego de bloques que todos conocemos',
                    categoria: 'puzzle',
                    thumbnail_path: 'games/tetris-thumb.jpg'
                },
                {
                    id: 2,
                    titulo: 'Space Invaders',
                    descripcion: 'Defiende la tierra de los invasores espaciales',
                    categoria: 'arcade',
                    thumbnail_path: 'games/space-thumb.jpg'
                },
                {
                    id: 3,
                    titulo: 'Solitario',
                    descripcion: 'El clásico juego de cartas para un jugador',
                    categoria: 'cartas',
                    thumbnail_path: 'games/solitaire-thumb.jpg'
                }
            ];
            
            grid.innerHTML = (games.length > 0 ? games : demoGames).map(game => `
                <div class="game-card" onclick="launchGame(${game.id})">
                    <img src="${game.thumbnail_path ? '/playmi/content/' + game.thumbnail_path : '/playmi/content/thumbnails/default-game.jpg'}" 
                         alt="${game.titulo}" 
                         class="game-thumbnail"
                         loading="lazy">
                    <div class="game-info">
                        <h3 class="game-title">${game.titulo}</h3>
                        <span class="game-category">${game.categoria || 'Casual'}</span>
                        <p class="game-description">${game.descripcion || 'Juego divertido para pasar el tiempo'}</p>
                        <div class="game-rating">
                            <i class="fas fa-star" style="color: gold"></i>
                            <i class="fas fa-star" style="color: gold"></i>
                            <i class="fas fa-star" style="color: gold"></i>
                            <i class="fas fa-star" style="color: gold"></i>
                            <i class="far fa-star" style="color: gold"></i>
                        </div>
                    </div>
                    <div class="game-overlay">
                        <button class="play-game-btn">
                            <i class="fas fa-play"></i> Jugar ahora
                        </button>
                        <p class="game-controls">
                            <i class="fas fa-mouse"></i> Mouse / <i class="fas fa-hand-pointer"></i> Touch
                        </p>
                    </div>
                </div>
            `).join('');
        }
        
        function launchGame(id) {
            window.location.href = `player/game-launcher.php?id=${id}`;
        }
    </script>
</body>
</html>