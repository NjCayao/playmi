<?php

/**
 * MÓDULO 2.2.4: Gestión específica de música
 * Propósito: Administrar música con reproductor integrado
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

$uploadData = $controller->upload();
$categories = $uploadData['categories']['musica'] ?? [];

// Obtener solo música
$filters = ['tipo' => 'musica'];
$page = (int)($_GET['page'] ?? 1);

$result = $controller->index();
$music = array_filter($result['content'] ?? [], function ($item) {
    return $item['tipo'] === 'musica';
});

// Agrupar por artista/álbum
$musicByArtist = [];
foreach ($music as $song) {
    $artist = $song['artista'] ?? 'Artista Desconocido';
    $album = $song['album'] ?? 'Álbum Desconocido';

    if (!isset($musicByArtist[$artist])) {
        $musicByArtist[$artist] = [];
    }
    if (!isset($musicByArtist[$artist][$album])) {
        $musicByArtist[$artist][$album] = [];
    }
    $musicByArtist[$artist][$album][] = $song;
}

$pageTitle = 'Gestión de Música';
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-music text-warning"></i> Gestión de Música
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Contenido</a></li>
                    <li class="breadcrumb-item active">Música</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo count($music); ?></h3>
                        <p>Total Canciones</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-music"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($musicByArtist); ?></h3>
                        <p>Artistas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php
                            $totalAlbums = 0;
                            foreach ($musicByArtist as $albums) {
                                $totalAlbums += count($albums);
                            }
                            echo $totalAlbums;
                            ?></h3>
                        <p>Álbumes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-compact-disc"></i>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php
                            $totalDuration = array_sum(array_column($music, 'duracion'));
                            echo gmdate("H:i", $totalDuration);
                            ?></h3>
                        <p>Duración Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reproductor de música -->
        <div class="card bg-gradient-dark">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <img id="playerAlbumArt" src="<?php echo ASSETS_URL; ?>images/music-placeholder.png"
                            class="img-fluid rounded"
                            style="max-width: 120px;">
                    </div>
                    <div class="col-md-7">
                        <h5 id="playerTitle" class="mb-1">Selecciona una canción</h5>
                        <p id="playerArtist" class="mb-2 text-muted">-</p>
                        <audio id="audioPlayer" controls class="w-100">
                            Tu navegador no soporta el elemento de audio.
                        </audio>
                    </div>
                    <div class="col-md-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-light" id="prevTrack">
                                <i class="fas fa-step-backward"></i>
                            </button>
                            <button class="btn btn-outline-light" id="playPause">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn btn-outline-light" id="nextTrack">
                                <i class="fas fa-step-forward"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control"
                            placeholder="Buscar por título o artista..."
                            value="<?php echo $_GET['search'] ?? ''; ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="genero" class="form-control">
                            <option value="">Todos los géneros</option>
                            <option value="pop">Pop</option>
                            <option value="rock">Rock</option>
                            <option value="salsa">Salsa</option>
                            <option value="cumbia">Cumbia</option>
                            <option value="reggaeton">Reggaeton</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="vista" class="form-control">
                            <option value="lista">Vista Lista</option>
                            <option value="album" <?php echo ($_GET['vista'] ?? '') === 'album' ? 'selected' : ''; ?>>Vista Álbum</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de música -->
        <?php if (($_GET['vista'] ?? 'lista') === 'album'): ?>
            <!-- Vista por álbum -->
            <div class="row">
                <?php foreach ($musicByArtist as $artist => $albums): ?>
                    <?php foreach ($albums as $album => $songs): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo $songs[0]['thumbnail_path'] ? SITE_URL . 'content/' . $songs[0]['thumbnail_path'] : ASSETS_URL . 'images/album-placeholder.jpg'; ?>"
                                    class="card-img-top"
                                    alt="<?php echo htmlspecialchars($album); ?>"
                                    style="height: 250px; object-fit: cover;">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($album); ?></h6>
                                    <p class="card-text">
                                        <small class="text-muted"><?php echo htmlspecialchars($artist); ?></small><br>
                                        <small><?php echo count($songs); ?> canciones</small>
                                    </p>
                                    <button class="btn btn-sm btn-primary btn-block play-album"
                                        data-artist="<?php echo htmlspecialchars($artist); ?>"
                                        data-album="<?php echo htmlspecialchars($album); ?>">
                                        <i class="fas fa-play"></i> Reproducir
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Vista lista -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Canciones</h3>
                    <div class="card-tools">
                        <a href="upload.php?type=musica" class="btn btn-warning btn-sm">
                            <i class="fas fa-plus"></i> Nueva Canción
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-hover" id="musicTable">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th width="60">Cover</th>
                                <th>Título</th>
                                <th>Artista</th>
                                <th>Estado</th>
                                <th>Duración</th>
                                <th>Género</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $index = 1;
                            foreach ($music as $song): ?>
                                <tr class="song-row" data-id="<?php echo $song['id']; ?>">
                                    <td><?php echo $index++; ?></td>
                                    <td>
                                        <img src="<?php echo $song['thumbnail_path'] ? SITE_URL . 'content/' . $song['thumbnail_path'] : ASSETS_URL . 'images/music-placeholder.jpg'; ?>"
                                            class="img-thumbnail"
                                            style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($song['titulo']); ?></strong>                                        
                                    </td>
                                    <td><?php echo htmlspecialchars($song['artista'] ?? '-'); ?></td>
                                    <td> <?php if ($song['estado'] === 'activo'): ?>
                                            <span class="badge badge-success float-right">Activo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $song['duracion'] ? gmdate("i:s", $song['duracion']) : '-'; ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($song['genero'] ?? 'Sin género'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-warning play-song"
                                                data-id="<?php echo $song['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($song['titulo']); ?>"
                                                data-artist="<?php echo htmlspecialchars($song['artista'] ?? 'Desconocido'); ?>"
                                                data-path="<?php echo $song['archivo_path']; ?>"
                                                data-thumb="<?php echo $song['thumbnail_path'] ?? ''; ?>">
                                                <i class="fas fa-play"></i>
                                            </button>
                                            <button class="btn btn-info edit-metadata"
                                                data-id="<?php echo $song['id']; ?>">
                                                <i class="fas fa-tags"></i>
                                            </button>
                                            <a href="edit.php?id=<?php echo $song['id']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal editar metadatos -->
<div class="modal fade" id="metadataModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Metadatos</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="metadataForm">
                <div class="modal-body">
                    <input type="hidden" id="metaSongId" name="id">
                    <div class="form-group">
                        <label>Artista</label>
                        <input type="text" class="form-control" name="artista" id="metaArtist">
                    </div>
                    <div class="form-group">
                        <label>Álbum</label>
                        <input type="text" class="form-control" name="album" id="metaAlbum">
                    </div>
                    <div class="form-group">
                        <label>Año</label>
                        <input type="number" class="form-control" name="anio_lanzamiento" id="metaYear"
                            min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Género</label>
                        <select class="form-control" name="categoria" id="metaGenre">
                            <option value="pop">Pop</option>
                            <option value="rock">Rock</option>
                            <option value="salsa">Salsa</option>
                            <option value="cumbia">Cumbia</option>
                            <option value="reggaeton">Reggaeton</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../layouts/base.php';
?>

<!-- Scripts específicos -->
<script>
    $(document).ready(function() {
        let currentPlaylist = [];
        let currentIndex = 0;
        const audioPlayer = document.getElementById('audioPlayer');

        // DataTable
        $('#musicTable').DataTable({
            "responsive": true,
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
            }
        });

        // Reproducir canción individual
        $('.play-song').click(function() {
            const $btn = $(this);
            const songData = {
                id: $btn.data('id'),
                title: $btn.data('title'),
                artist: $btn.data('artist'),
                path: $btn.data('path'),
                thumb: $btn.data('thumb')
            };

            playSong(songData);
        });

        // Función para reproducir canción
        function playSong(song) {
            $('#playerTitle').text(song.title);
            $('#playerArtist').text(song.artist);

            if (song.thumb) {
                $('#playerAlbumArt').attr('src', '<?php echo SITE_URL; ?>content/' + song.thumb);
            } else {
                $('#playerAlbumArt').attr('src', '<?php echo ASSETS_URL; ?>images/music-placeholder.jpg');
            }

            audioPlayer.src = '<?php echo SITE_URL; ?>content/' + song.path;
            audioPlayer.play();

            // Actualizar botón play/pause
            $('#playPause i').removeClass('fa-play').addClass('fa-pause');
        }

        // Control play/pause
        $('#playPause').click(function() {
            if (audioPlayer.paused) {
                audioPlayer.play();
                $(this).find('i').removeClass('fa-play').addClass('fa-pause');
            } else {
                audioPlayer.pause();
                $(this).find('i').removeClass('fa-pause').addClass('fa-play');
            }
        });

        // Siguiente canción
        $('#nextTrack').click(function() {
            if (currentPlaylist.length > 0 && currentIndex < currentPlaylist.length - 1) {
                currentIndex++;
                playSong(currentPlaylist[currentIndex]);
            }
        });

        // Canción anterior
        $('#prevTrack').click(function() {
            if (currentPlaylist.length > 0 && currentIndex > 0) {
                currentIndex--;
                playSong(currentPlaylist[currentIndex]);
            }
        });

        // Reproducir álbum completo
        $('.play-album').click(function() {
            const artist = $(this).data('artist');
            const album = $(this).data('album');

            // Aquí cargarías las canciones del álbum
            toastr.info('Reproduciendo álbum: ' + album);
        });

        // Editar metadatos
        $('.edit-metadata').click(function() {
            const songId = $(this).data('id');

            // Cargar datos actuales (simulado)
            $('#metaSongId').val(songId);
            $('#metadataModal').modal('show');
        });

        // Guardar metadatos
        $('#metadataForm').submit(function(e) {
            e.preventDefault();

            const formData = $(this).serialize();

            $.ajax({
                url: '../../api/content/update-metadata.php',
                method: 'POST',
                data: formData,
                success: function(response) {
                    toastr.success('Metadatos actualizados');
                    $('#metadataModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function() {
                    toastr.error('Error al actualizar metadatos');
                }
            });
        });

        // Actualizar estado del reproductor cuando termine
        audioPlayer.addEventListener('ended', function() {
            $('#playPause i').removeClass('fa-pause').addClass('fa-play');

            // Auto-reproducir siguiente si hay playlist
            if (currentPlaylist.length > 0 && currentIndex < currentPlaylist.length - 1) {
                $('#nextTrack').click();
            }
        });
    });
</script>