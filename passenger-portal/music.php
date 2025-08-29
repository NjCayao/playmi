<?php

/**
 * passenger-portal/music.php
 * Catálogo de música estilo Spotify/Netflix
 */

define('PORTAL_ACCESS', true);
require_once 'config/portal-config.php';
require_once '../admin/config/database.php';

$companyConfig = getCompanyConfig();

// Variables
$allMusic = [];
$genres = [];
$featuredMusic = [];

try {
    $db = Database::getInstance()->getConnection();

    // Obtener toda la música activa
    $sql = "SELECT id, titulo, descripcion, tipo, duracion, anio_lanzamiento, 
            calificacion, genero, categoria, archivo_path, thumbnail_path, created_at,
            metadata
            FROM contenido 
            WHERE tipo = 'musica' AND estado = 'activo' 
            ORDER BY created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $allMusic = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar música
    foreach ($allMusic as &$song) {
        // Decodificar metadata
        if ($song['metadata']) {
            $song['metadata'] = json_decode($song['metadata'], true);
        }

        // Determinar si es video o audio
        $extension = strtolower(pathinfo($song['archivo_path'], PATHINFO_EXTENSION));
        $song['is_video'] = in_array($extension, ['mp4', 'webm', 'mov']);

        // Extraer géneros
        if (!empty($song['genero']) && !in_array($song['genero'], $genres)) {
            $genres[] = $song['genero'];
        }
    }

    sort($genres);

    // Música destacada (primeras 10)
    $featuredMusic = array_slice($allMusic, 0, 10);
} catch (Exception $e) {
    error_log("Error loading music: " . $e->getMessage());
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

    <!-- CSS específico -->
    <style>
        :root {
            --company-primary: <?php echo $companyConfig['primary_color']; ?>;
            --spotify-green: #1db954;
            --spotify-black: #191414;
            --spotify-dark: #121212;
        }

        body {
            background: var(--spotify-dark);
            color: white;
        }

        /* Contenedor principal */
        .music-container {
            min-height: 100vh;
            padding-top: 68px;
        }

        /* Hero Section */
        .music-hero {
            background: linear-gradient(to bottom, #1a1a1a 0%, var(--spotify-dark) 100%);
            padding: 3rem 4%;
            text-align: center;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: #b3b3b3;
            margin-bottom: 2rem;
        }

        /* Botones de acción */
        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .btn-hero {
            padding: 12px 32px;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: var(--spotify-green);
            color: white;
        }

        .btn-primary:hover {
            background: #1ed760;
            transform: scale(1.05);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            border: 1px solid white;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Filtros */
        .filters-section {
            padding: 1.5rem 4%;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-chip {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .filter-chip:hover,
        .filter-chip.active {
            background: var(--spotify-green);
        }

        /* Vista de contenido */
        .content-view {
            padding: 2rem 4%;
        }

        .section-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        /* Grid de música */
        .music-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        /* Card de música */
        .music-card {
            background: #181818;
            border-radius: 8px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .music-card:hover {
            background: #282828;
        }

        .music-card-cover {
            position: relative;
            width: 100%;
            aspect-ratio: 1;
            margin-bottom: 1rem;
            overflow: hidden;
            border-radius: 4px;
        }

        .music-card-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Play button overlay */
        .play-button-overlay {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 48px;
            height: 48px;
            background: var(--spotify-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }

        .music-card:hover .play-button-overlay {
            opacity: 1;
            transform: translateY(0);
        }

        .play-button-overlay i {
            color: white;
            font-size: 18px;
            margin-left: 2px;
        }

        /* Badge para videos */
        .media-type-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #e50914;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        /* Info de la canción */
        .music-card-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .music-card-artist {
            color: #b3b3b3;
            font-size: 0.875rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Botón cargar más */
        .load-more-section {
            text-align: center;
            padding: 2rem;
        }

        .load-more-btn {
            background: transparent;
            border: 1px solid white;
            color: white;
            padding: 12px 32px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .load-more-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }

            .hero-actions {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
                margin: 0 auto;
            }

            .btn-hero {
                width: 100%;
            }

            .music-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.5rem;
            }

            .music-card {
                padding: 0.8rem;
            }

            .music-card-artist {
                font-size: 0.7rem;                
            }

            .music-card-title {
                font-size: 0.8rem;                
            }

            .play-button-overlay {
                width: 36px;                
                height: 36px;
            }

            .filters-section {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* Sin contenido */
        .no-content {
            text-align: center;
            padding: 4rem;
            color: #b3b3b3;
        }

        .no-content i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="assets/fonts/font-awesome/css/all.min.css">
</head>

<body>
    <div class="music-container">
        <!-- Header -->
        <header class="portal-header scrolled">
            <div class="header-content">
                <div class="logo-section">
                    <?php if ($companyConfig['logo_url']): ?>
                        <img src="<?php echo $companyConfig['logo_url']; ?>" alt="Logo" class="company-logo">
                    <?php else: ?>
                        <h1 class="company-name">PLAYMI</h1>
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
            <h1 class="hero-title">Música para tu viaje</h1>
            <p class="hero-subtitle">
                <?php echo count($allMusic); ?> canciones y videos musicales disponibles
            </p>

            <div class="hero-actions">
                <button class="btn-hero btn-primary" onclick="playRandom()">
                    <i class="fas fa-play"></i> Reproducir aleatorio
                </button>
                <button class="btn-hero btn-secondary" onclick="showAllMusic()">
                    <i class="fas fa-music"></i> Ver toda la música
                </button>
            </div>
        </section>

        <!-- Filtros -->
        <section class="filters-section">
            <button class="filter-chip active" onclick="filterContent('all')">
                Todos
            </button>
            <button class="filter-chip" onclick="filterContent('video')">
                <i class="fas fa-video"></i> Videos
            </button>
            <button class="filter-chip" onclick="filterContent('audio')">
                <i class="fas fa-headphones"></i> Audio
            </button>
            <?php foreach ($genres as $genre): ?>
                <button class="filter-chip" onclick="filterByGenre('<?php echo $genre; ?>')">
                    <?php echo ucfirst($genre); ?>
                </button>
            <?php endforeach; ?>
        </section>

        <!-- Contenido -->
        <div class="content-view">
            <?php if (!empty($featuredMusic)): ?>
                <!-- Destacados -->
                <section>
                    <h2 class="section-title">Lo más escuchado</h2>
                    <div class="music-grid">
                        <?php foreach ($featuredMusic as $item): ?>
                            <div class="music-card"
                                data-id="<?php echo $item['id']; ?>"
                                data-type="<?php echo $item['is_video'] ? 'video' : 'audio'; ?>"
                                data-genre="<?php echo $item['genero']; ?>"
                                onclick="playMedia(<?php echo $item['id']; ?>)">

                                <div class="music-card-cover">
                                    <img src="<?php echo CONTENT_URL . ($item['thumbnail_path'] ?? 'thumbnails/default-music.jpg'); ?>"
                                        alt="<?php echo htmlspecialchars($item['titulo']); ?>">

                                    <?php if ($item['is_video']): ?>
                                        <span class="media-type-badge">VIDEO</span>
                                    <?php endif; ?>

                                    <div class="play-button-overlay">
                                        <i class="fas fa-play"></i>
                                    </div>
                                </div>

                                <h3 class="music-card-title"><?php echo htmlspecialchars($item['titulo']); ?></h3>
                                <p class="music-card-artist">
                                    <?php echo htmlspecialchars($item['metadata']['artist'] ?? 'Artista'); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Toda la música -->
                <section id="allMusicSection" style="display: none;">
                    <h2 class="section-title">Toda la música</h2>
                    <div class="music-grid" id="allMusicGrid">
                        <!-- Se carga dinámicamente -->
                    </div>

                    <div class="load-more-section" id="loadMoreSection" style="display: none;">
                        <button class="load-more-btn" onclick="loadMore()">
                            Cargar más
                        </button>
                    </div>
                </section>

            <?php else: ?>
                <div class="no-content">
                    <i class="fas fa-music"></i>
                    <h3>No hay música disponible</h3>
                    <p>Vuelve más tarde para disfrutar de nuestra colección</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Variables globales
        let allMusicData = <?php echo json_encode($allMusic); ?>;
        let currentFilter = 'all';
        let currentGenre = '';
        let displayedItems = 10;

        // Filtrar contenido
        function filterContent(type) {
            currentFilter = type;
            currentGenre = '';

            // Actualizar botones
            document.querySelectorAll('.filter-chip').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filtrar cards
            document.querySelectorAll('.music-card').forEach(card => {
                if (type === 'all') {
                    card.style.display = 'block';
                } else {
                    const cardType = card.dataset.type;
                    card.style.display = cardType === type ? 'block' : 'none';
                }
            });
        }

        // Filtrar por género
        function filterByGenre(genre) {
            currentGenre = genre;

            document.querySelectorAll('.music-card').forEach(card => {
                const cardGenre = card.dataset.genre;
                card.style.display = cardGenre === genre ? 'block' : 'none';
            });
        }

        // Reproducir media - TODO LLEVA A music-player.php
        function playMedia(id) {
            window.location.href = `player/music-player.php?id=${id}`;
        }

        // Reproducir aleatorio
        function playRandom() {
            if (allMusicData.length > 0) {
                const randomIndex = Math.floor(Math.random() * allMusicData.length);
                const item = allMusicData[randomIndex];
                window.location.href = `player/music-player.php?id=${item.id}`;
            }
        }

        // Mostrar toda la música
        function showAllMusic() {
            document.getElementById('allMusicSection').style.display = 'block';
            loadMore();

            // Scroll suave
            document.getElementById('allMusicSection').scrollIntoView({
                behavior: 'smooth'
            });
        }

        // Cargar más items
        function loadMore() {
            const grid = document.getElementById('allMusicGrid');
            const endIndex = Math.min(displayedItems + 20, allMusicData.length);

            for (let i = displayedItems; i < endIndex; i++) {
                const item = allMusicData[i];
                const card = createMusicCard(item);
                grid.appendChild(card);
            }

            displayedItems = endIndex;

            // Mostrar/ocultar botón cargar más
            const loadMoreBtn = document.getElementById('loadMoreSection');
            loadMoreBtn.style.display = displayedItems < allMusicData.length ? 'block' : 'none';
        }

        // Crear card de música
        function createMusicCard(item) {
            const div = document.createElement('div');
            div.className = 'music-card';
            div.dataset.id = item.id;
            div.dataset.type = item.is_video ? 'video' : 'audio';
            div.dataset.genre = item.genero;
            div.onclick = () => playMedia(item.id);

            div.innerHTML = `
                <div class="music-card-cover">
                    <img src="<?php echo CONTENT_URL; ?>${item.thumbnail_path || 'thumbnails/default-music.jpg'}" 
                         alt="${item.titulo}">
                    ${item.is_video ? '<span class="media-type-badge">VIDEO</span>' : ''}
                    <div class="play-button-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <h3 class="music-card-title">${item.titulo}</h3>
                <p class="music-card-artist">${item.metadata?.artist || 'Artista'}</p>
            `;

            return div;
        }
    </script>
</body>

</html>