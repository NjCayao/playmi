<?php
/**
 * passenger-portal/music.php
 * Página de música estilo Spotify mejorada
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

$companyConfig = getCompanyConfig();

// Obtener estadísticas de música
$musicStats = ['total' => 0, 'albums' => 0, 'artists' => 0];
try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT COUNT(*) as total FROM contenido WHERE tipo = 'musica' AND estado = 'activo'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $musicStats['total'] = $result['total'];
} catch (Exception $e) {
    // Continuar sin estadísticas
}
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
            --music-gradient: linear-gradient(180deg, #1a1a1a 0%, var(--bg-primary) 100%);
        }
        
        /* Hero mejorado */
        .music-hero {
            background: var(--music-gradient);
            padding: 3rem 4% 2rem;
            margin-top: 68px;
        }
        
        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .stat-item {
            color: var(--text-secondary);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            display: block;
        }
        
        /* Navegación de categorías */
        .music-nav {
            background: rgba(0,0,0,0.5);
            padding: 1rem 4%;
            position: sticky;
            top: 68px;
            z-index: 100;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-tabs {
            display: flex;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            overflow-x: auto;
            scrollbar-width: none;
        }
        
        .nav-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .nav-tab {
            color: var(--text-secondary);
            text-decoration: none;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            font-weight: 500;
        }
        
        .nav-tab.active,
        .nav-tab:hover {
            color: var(--text-primary);
            border-color: var(--company-primary);
        }
        
        /* Secciones */
        .music-section {
            padding: 2rem 4%;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        /* Grid de álbumes */
        .albums-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .album-card {
            background: var(--bg-card);
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .album-card:hover {
            background: var(--hover-bg);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        
        .album-cover {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 0.75rem;
            position: relative;
        }
        
        .play-button-overlay {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 48px;
            height: 48px;
            background: var(--company-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }
        
        .album-card:hover .play-button-overlay {
            opacity: 1;
            transform: translateY(0);
        }
        
        .play-button-overlay i {
            color: white;
            font-size: 20px;
            margin-left: 2px;
        }
        
        .album-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .album-artist {
            color: var(--text-secondary);
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Lista de canciones */
        .songs-list {
            background: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .list-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: grid;
            grid-template-columns: 50px 1fr 200px 100px;
            gap: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .song-row {
            display: grid;
            grid-template-columns: 50px 1fr 200px 100px;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .song-row:hover {
            background: rgba(255,255,255,0.05);
        }
        
        .song-number {
            text-align: center;
            color: var(--text-secondary);
        }
        
        .song-row:hover .song-number {
            display: none;
        }
        
        .song-row .play-icon {
            display: none;
            text-align: center;
            color: var(--company-primary);
        }
        
        .song-row:hover .play-icon {
            display: block;
        }
        
        .song-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }
        
        .song-thumb {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
            flex-shrink: 0;
        }
        
        .song-details {
            min-width: 0;
        }
        
        .song-name {
            font-weight: 500;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .song-artist {
            color: var(--text-secondary);
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .song-album {
            color: var(--text-secondary);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .song-duration {
            color: var(--text-secondary);
            text-align: right;
        }
        
        /* Botón cargar más */
        .load-more {
            text-align: center;
            padding: 2rem;
        }
        
        .load-more-btn {
            background: transparent;
            border: 1px solid var(--text-secondary);
            color: var(--text-primary);
            padding: 0.75rem 2rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 1rem;
        }
        
        .load-more-btn:hover {
            background: var(--company-primary);
            border-color: var(--company-primary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .music-hero {
                padding: 2rem 4% 1rem;
            }
            
            .hero-stats {
                gap: 1rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .albums-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 1rem;
            }
            
            .album-card {
                padding: 0.75rem;
            }
            
            .list-header {
                display: none;
            }
            
            .song-row {
                grid-template-columns: 40px 1fr 60px;
                padding: 0.75rem 1rem;
            }
            
            .song-album {
                display: none;
            }
            
            .nav-tabs {
                gap: 1rem;
            }
            
            .nav-tab {
                font-size: 0.875rem;
            }
        }
        
        /* Loading skeleton */
        .skeleton-album {
            background: var(--bg-card);
            border-radius: 8px;
            padding: 1rem;
        }
        
        .skeleton-cover {
            width: 100%;
            aspect-ratio: 1;
            background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 0.75rem;
        }
        
        .skeleton-text {
            height: 16px;
            background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }
        
        .skeleton-text.small {
            width: 60%;
            height: 14px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Sin resultados */
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .no-results i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
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
        
        <!-- Hero Section -->
        <section class="music-hero">
            <div class="hero-content">
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Música</h1>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $musicStats['total']; ?></span>
                        <span>Canciones</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="albumCount">0</span>
                        <span>Álbumes</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="artistCount">0</span>
                        <span>Artistas</span>
                    </div>
                </div>
                <p style="color: var(--text-secondary); max-width: 600px;">
                    Disfruta de la mejor música durante tu viaje. Desde los éxitos del momento hasta los clásicos de siempre.
                </p>
            </div>
        </section>
        
        <!-- Navegación por categorías -->
        <nav class="music-nav">
            <div class="nav-tabs">
                <a href="#albums" class="nav-tab active" data-section="albums">Álbumes</a>
                <a href="#playlists" class="nav-tab" data-section="playlists">Playlists</a>
                <a href="#artists" class="nav-tab" data-section="artists">Artistas</a>
                <a href="#songs" class="nav-tab" data-section="songs">Todas las canciones</a>
                <a href="#genres" class="nav-tab" data-section="genres">Géneros</a>
            </div>
        </nav>
        
        <!-- Contenido principal -->
        <main class="main-content">
            <!-- Álbumes populares -->
            <section class="music-section" id="albums-section">
                <div class="section-header">
                    <h2 class="section-title">Álbumes populares</h2>
                    <button class="filter-dropdown">
                        Todos los géneros <i class="fas fa-chevron-down"></i>
                    </button>
                </div>
                
                <div class="albums-grid" id="albumsGrid">
                    <!-- Loading skeletons -->
                    <?php for($i = 0; $i < 8; $i++): ?>
                    <div class="skeleton-album">
                        <div class="skeleton-cover"></div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text small"></div>
                    </div>
                    <?php endfor; ?>
                </div>
            </section>
            
            <!-- Lista de todas las canciones -->
            <section class="music-section" id="songs-section" style="display: none;">
                <div class="section-header">
                    <h2 class="section-title">Todas las canciones</h2>
                    <button class="play-all-btn" onclick="playAllSongs()">
                        <i class="fas fa-play"></i> Reproducir todo
                    </button>
                </div>
                
                <div class="songs-list">
                    <div class="list-header">
                        <div>#</div>
                        <div>TÍTULO</div>
                        <div>ÁLBUM</div>
                        <div>DURACIÓN</div>
                    </div>
                    <div id="songsList">
                        <!-- Canciones cargadas dinámicamente -->
                    </div>
                </div>
                
                <div class="load-more" id="loadMoreSection" style="display: none;">
                    <button class="load-more-btn" onclick="loadMoreSongs()">
                        Cargar más canciones
                    </button>
                </div>
            </section>
            
            <!-- Sección sin resultados -->
            <section class="no-results" id="noResults" style="display: none;">
                <i class="fas fa-music"></i>
                <h3>No hay música disponible</h3>
                <p>Parece que no hay canciones en esta categoría</p>
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
        // Estado de la aplicación
        let currentView = 'albums';
        let allSongs = [];
        let displayedSongs = 0;
        const songsPerPage = 30;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar portal
            Portal.init({
                companyId: <?php echo $companyConfig['company_id']; ?>,
                packageType: '<?php echo $companyConfig['package_type']; ?>',
                adsEnabled: <?php echo $companyConfig['ads_enabled'] ? 'true' : 'false'; ?>
            });
            
            // Configurar navegación
            setupNavigation();
            
            // Cargar contenido inicial
            loadAlbums();
            loadAllSongs();
        });
        
        // Configurar navegación entre secciones
        function setupNavigation() {
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Actualizar tabs activos
                    document.querySelector('.nav-tab.active').classList.remove('active');
                    this.classList.add('active');
                    
                    // Cambiar sección
                    const section = this.dataset.section;
                    switchSection(section);
                });
            });
        }
        
        // Cambiar entre secciones
        function switchSection(section) {
            // Ocultar todas las secciones
            document.querySelectorAll('.music-section').forEach(s => {
                s.style.display = 'none';
            });
            
            // Mostrar sección correspondiente
            switch(section) {
                case 'albums':
                    document.getElementById('albums-section').style.display = 'block';
                    if (!document.querySelector('.album-card:not(.skeleton-album)')) {
                        loadAlbums();
                    }
                    break;
                    
                case 'songs':
                    document.getElementById('songs-section').style.display = 'block';
                    if (displayedSongs === 0) {
                        displaySongs();
                    }
                    break;
                    
                case 'playlists':
                    // TODO: Implementar playlists
                    showComingSoon('Playlists');
                    break;
                    
                case 'artists':
                    // TODO: Implementar artistas
                    showComingSoon('Artistas');
                    break;
                    
                case 'genres':
                    // TODO: Implementar géneros
                    showComingSoon('Géneros');
                    break;
            }
            
            currentView = section;
        }
        
        // Cargar álbumes (simulado como agrupaciones)
        async function loadAlbums() {
            try {
                const response = await fetch('api/get-content.php?type=music&limit=24');
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    // Simular álbumes agrupando canciones
                    const albums = groupSongsIntoAlbums(data.data);
                    renderAlbums(albums);
                    
                    // Actualizar contadores
                    document.getElementById('albumCount').textContent = albums.length;
                    document.getElementById('artistCount').textContent = getUniqueArtists(data.data).length;
                } else {
                    showNoResults();
                }
            } catch (error) {
                console.error('Error loading albums:', error);
            }
        }
        
        // Cargar todas las canciones
        async function loadAllSongs() {
            try {
                const response = await fetch('api/get-content.php?type=music&limit=200');
                const data = await response.json();
                
                if (data.success) {
                    allSongs = data.data || [];
                }
            } catch (error) {
                console.error('Error loading songs:', error);
            }
        }
        
        // Agrupar canciones en álbumes simulados
        function groupSongsIntoAlbums(songs) {
            const albums = [];
            const albumSize = 12; // Canciones por álbum
            
            for (let i = 0; i < songs.length; i += albumSize) {
                const albumSongs = songs.slice(i, i + albumSize);
                if (albumSongs.length > 0) {
                    albums.push({
                        id: `album-${i}`,
                        title: `Álbum ${Math.floor(i / albumSize) + 1}`,
                        artist: albumSongs[0].metadata?.artist || 'Varios Artistas',
                        cover: albumSongs[0].thumbnail_path,
                        songs: albumSongs,
                        year: new Date().getFullYear()
                    });
                }
            }
            
            return albums;
        }
        
        // Obtener artistas únicos
        function getUniqueArtists(songs) {
            const artists = new Set();
            songs.forEach(song => {
                if (song.metadata?.artist) {
                    artists.add(song.metadata.artist);
                }
            });
            return Array.from(artists);
        }
        
        // Renderizar álbumes
        function renderAlbums(albums) {
            const grid = document.getElementById('albumsGrid');
            
            grid.innerHTML = albums.map(album => `
                <div class="album-card" onclick="playAlbum('${album.id}')">
                    <div class="album-cover">
                        <img src="${Portal.getThumbnailUrl(album.songs[0])}" 
                             alt="${album.title}" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 4px;"
                             loading="lazy">
                        <div class="play-button-overlay">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                    <h3 class="album-title">${album.title}</h3>
                    <p class="album-artist">${album.artist}</p>
                </div>
            `).join('');
        }
        
        // Mostrar canciones con paginación
        function displaySongs() {
            const songsList = document.getElementById('songsList');
            const startIdx = displayedSongs;
            const endIdx = Math.min(startIdx + songsPerPage, allSongs.length);
            
            const songsHTML = allSongs.slice(startIdx, endIdx).map((song, idx) => `
                <div class="song-row" onclick="playSong(${song.id})">
                    <div class="song-number">${startIdx + idx + 1}</div>
                    <div class="play-icon"><i class="fas fa-play"></i></div>
                    <div class="song-info">
                        <img src="${Portal.getThumbnailUrl(song)}" 
                             alt="${song.titulo}" 
                             class="song-thumb"
                             loading="lazy">
                        <div class="song-details">
                            <div class="song-name">${song.titulo}</div>
                            <div class="song-artist">${song.metadata?.artist || 'Artista'}</div>
                        </div>
                    </div>
                    <div class="song-album">${song.metadata?.album || 'Álbum'}</div>
                    <div class="song-duration">${song.duracion_formato || '3:45'}</div>
                </div>
            `).join('');
            
            if (displayedSongs === 0) {
                songsList.innerHTML = songsHTML;
            } else {
                songsList.insertAdjacentHTML('beforeend', songsHTML);
            }
            
            displayedSongs = endIdx;
            
            // Mostrar/ocultar botón de cargar más
            const loadMoreSection = document.getElementById('loadMoreSection');
            if (displayedSongs < allSongs.length) {
                loadMoreSection.style.display = 'block';
            } else {
                loadMoreSection.style.display = 'none';
            }
        }
        
        // Cargar más canciones
        function loadMoreSongs() {
            displaySongs();
            
            // Hacer scroll suave al final de las canciones nuevas
            setTimeout(() => {
                const lastSong = document.querySelector('.song-row:last-child');
                if (lastSong) {
                    lastSong.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }
        
        // Funciones de reproducción
        function playAlbum(albumId) {
            // TODO: Implementar playlist del álbum
            window.location.href = `player/music-player.php?album=${albumId}`;
        }
        
        function playSong(songId) {
            window.location.href = `player/music-player.php?id=${songId}`;
        }
        
        function playAllSongs() {
            // Reproducir todas las canciones en orden
            if (allSongs.length > 0) {
                window.location.href = `player/music-player.php?playlist=all`;
            }
        }
        
        // Mostrar "Próximamente"
        function showComingSoon(section) {
            const noResults = document.getElementById('noResults');
            noResults.innerHTML = `
                <i class="fas fa-clock"></i>
                <h3>Próximamente</h3>
                <p>${section} estará disponible pronto</p>
            `;
            noResults.style.display = 'block';
        }
        
        // Mostrar sin resultados
        function showNoResults() {
            document.getElementById('albumsGrid').style.display = 'none';
            document.getElementById('noResults').style.display = 'block';
        }
    </script>
</body>
</html>