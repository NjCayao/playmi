<?php
/**
 * passenger-portal/music.php
 * Página de música estilo YouTube/Spotify
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
    <title>Música - <?php echo htmlspecialchars($companyConfig['company_name']); ?></title>
    
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
        
        .music-featured {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 4%;
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        
        .featured-album {
            width: 200px;
            height: 200px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        
        .featured-info h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .featured-artist {
            color: var(--text-secondary);
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }
        
        .play-all-btn {
            background: var(--company-primary);
            color: white;
            padding: 12px 32px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        
        .play-all-btn:hover {
            transform: scale(1.05);
        }
        
        .music-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 0 4%;
        }
        
        .music-card {
            background: var(--bg-card);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .music-card:hover {
            transform: translateY(-4px);
            background: var(--hover-bg);
        }
        
        .music-card:hover .play-overlay {
            opacity: 1;
        }
        
        .album-cover {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.75rem;
        }
        
        .song-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .artist-name {
            color: var(--text-secondary);
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .play-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8);
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .play-overlay i {
            color: var(--company-primary);
            font-size: 24px;
            margin-left: 3px;
        }
        
        .filter-tabs {
            display: flex;
            gap: 1rem;
            padding: 0 4%;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1.5rem;
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--text-secondary);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: var(--company-primary);
            color: white;
            border-color: var(--company-primary);
        }
        
        @media (max-width: 768px) {
            .music-featured {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }
            
            .featured-album {
                width: 150px;
                height: 150px;
            }
            
            .music-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
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
                            <li><a href="music.php" class="active">Música</a></li>
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
        
        <!-- Contenido principal -->
        <main class="main-content" style="margin-top: 68px;">
            <!-- Álbum destacado -->
            <section class="music-featured">
                <img src="<?php echo CONTENT_URL; ?>thumbnails/featured-album.jpg" alt="Álbum destacado" class="featured-album">
                <div class="featured-info">
                    <h2>Mix del Viaje</h2>
                    <p class="featured-artist">Las mejores canciones para tu trayecto</p>
                    <button class="play-all-btn" onclick="playFeatured()">
                        <i class="fas fa-play"></i> Reproducir todo
                    </button>
                </div>
            </section>
            
            <!-- Filtros -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">Todo</button>
                <button class="filter-tab" data-filter="pop">Pop</button>
                <button class="filter-tab" data-filter="rock">Rock</button>
                <button class="filter-tab" data-filter="reggaeton">Reggaeton</button>
                <button class="filter-tab" data-filter="electronica">Electrónica</button>
                <button class="filter-tab" data-filter="regional">Regional</button>
            </div>
            
            <!-- Grid de música -->
            <section class="music-grid" id="musicGrid">
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
            
            // Cargar música
            loadMusic('all');
            
            // Configurar filtros
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    document.querySelector('.filter-tab.active').classList.remove('active');
                    this.classList.add('active');
                    loadMusic(this.dataset.filter);
                });
            });
        });
        
        async function loadMusic(filter) {
            try {
                const response = await fetch(`api/get-content.php?type=music&category=${filter}&limit=50`);
                const data = await response.json();
                
                if (data.success) {
                    renderMusicGrid(data.data);
                }
            } catch (error) {
                console.error('Error loading music:', error);
            }
        }
        
        function renderMusicGrid(songs) {
            const grid = document.getElementById('musicGrid');
            
            grid.innerHTML = songs.map(song => `
                <div class="music-card" onclick="playMusic(${song.id})">
                    <img src="${Portal.getThumbnailUrl(song)}" 
                         alt="${song.titulo}" 
                         class="album-cover"
                         loading="lazy">
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                    <h3 class="song-title">${song.titulo}</h3>
                    <p class="artist-name">${song.metadata?.artist || 'Artista'}</p>
                </div>
            `).join('');
        }
        
        function playMusic(id) {
            window.location.href = `player/music-player.php?id=${id}`;
        }
        
        function playFeatured() {
            // Reproducir playlist destacada
            window.location.href = `player/music-player.php?playlist=featured`;
        }
    </script>
</body>
</html>