<?php
/**
 * MÓDULO 2.2.3: Gestión específica de películas
 * Propósito: Administrar películas con funciones especializadas
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

// Obtener solo películas
$filters = ['tipo' => 'pelicula'];
$page = (int)($_GET['page'] ?? 1);

// Simular datos específicos de películas (ajustar cuando esté el método movies())
$result = $controller->index();
$movies = array_filter($result['content'] ?? [], function($item) {
    return $item['tipo'] === 'pelicula';
});

// Estadísticas de películas
$movieStats = [
    'total' => count($movies),
    'hd' => 0,
    'fullhd' => 0,
    '4k' => 0,
    'total_size' => array_sum(array_column($movies, 'tamanio_archivo'))
];

$pageTitle = 'Gestión de Películas';
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-film text-success"></i> Gestión de Películas
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Contenido</a></li>
                    <li class="breadcrumb-item active">Películas</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Estadísticas de películas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $movieStats['total']; ?></h3>
                        <p>Total Películas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-film"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo formatFileSize($movieStats['total_size']); ?></h3>
                        <p>Espacio Usado</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>HD</h3>
                        <p>Calidad Promedio</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tv"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>2.5h</h3>
                        <p>Duración Promedio</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros específicos de películas -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Filtros Avanzados</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="form-row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Género</label>
                            <select name="genero" class="form-control">
                                <option value="">Todos</option>
                                <option value="accion">Acción</option>
                                <option value="comedia">Comedia</option>
                                <option value="drama">Drama</option>
                                <option value="terror">Terror</option>
                                <option value="ciencia-ficcion">Ciencia Ficción</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Clasificación</label>
                            <select name="clasificacion" class="form-control">
                                <option value="">Todas</option>
                                <option value="G">G - General</option>
                                <option value="PG">PG - Guía Parental</option>
                                <option value="PG-13">PG-13</option>
                                <option value="R">R - Restringido</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Año</label>
                            <select name="anio" class="form-control">
                                <option value="">Todos</option>
                                <?php for($y = date('Y'); $y >= 2000; $y--): ?>
                                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Lista de películas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Películas</h3>
                <div class="card-tools">
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="viewGrid">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary active" id="viewList">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <a href="upload.php?type=pelicula" class="btn btn-success btn-sm ml-2">
                        <i class="fas fa-plus"></i> Nueva Película
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Vista Lista -->
                <div id="listView">
                    <table class="table table-hover" id="moviesTable">
                        <thead>
                            <tr>
                                <th width="80">Poster</th>
                                <th>Título</th>
                                <th>Director</th>
                                <th>Año</th>
                                <th>Duración</th>
                                <th>Calidad</th>
                                <th>Estado</th>
                                <th width="150">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movies as $movie): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $movie['thumbnail_path'] ? SITE_URL . 'content/' . $movie['thumbnail_path'] : ASSETS_URL . 'images/movie-placeholder.jpg'; ?>" 
                                         alt="Poster" 
                                         class="img-thumbnail"
                                         style="width: 60px; height: 90px; object-fit: cover;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($movie['titulo']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <span class="badge badge-secondary"><?php echo $movie['categoria'] ?? 'Sin categoría'; ?></span>
                                        <span class="badge badge-info"><?php echo $movie['calificacion'] ?? 'NR'; ?></span>
                                    </small>
                                </td>
                                <td><?php echo $movie['director'] ?? '-'; ?></td>
                                <td><?php echo $movie['anio_lanzamiento'] ?? '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($movie['duracion']) {
                                        echo gmdate("H:i:s", $movie['duracion']);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge badge-primary">HD</span>
                                </td>
                                <td>
                                    <?php if ($movie['estado'] === 'activo'): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php elseif ($movie['estado'] === 'procesando'): ?>
                                        <span class="badge badge-warning">Procesando</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info play-movie" 
                                                data-id="<?php echo $movie['id']; ?>"
                                                title="Reproducir">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="btn btn-warning compress-video" 
                                                data-id="<?php echo $movie['id']; ?>"
                                                title="Recomprimir"
                                                <?php echo $movie['estado'] === 'procesando' ? 'disabled' : ''; ?>>
                                            <i class="fas fa-compress"></i>
                                        </button>
                                        <button class="btn btn-secondary regenerate-thumb" 
                                                data-id="<?php echo $movie['id']; ?>"
                                                title="Regenerar thumbnail">
                                            <i class="fas fa-image"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $movie['id']; ?>" 
                                           class="btn btn-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Vista Grid (oculta por defecto) -->
                <div id="gridView" style="display: none;">
                    <div class="row">
                        <?php foreach ($movies as $movie): ?>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="card movie-card">
                                <img src="<?php echo $movie['thumbnail_path'] ? SITE_URL . 'content/' . $movie['thumbnail_path'] : ASSETS_URL . 'images/movie-placeholder.jpg'; ?>" 
                                     class="card-img-top movie-poster" 
                                     alt="<?php echo htmlspecialchars($movie['titulo']); ?>">
                                <div class="card-body">
                                    <h6 class="card-title text-truncate"><?php echo htmlspecialchars($movie['titulo']); ?></h6>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <?php echo $movie['anio_lanzamiento'] ?? 'Año desconocido'; ?> • 
                                            <?php echo $movie['duracion'] ? gmdate("H:i", $movie['duracion']) : 'Duración desconocida'; ?>
                                        </small>
                                    </p>
                                    <div class="btn-group btn-group-sm w-100">
                                        <button class="btn btn-info play-movie" data-id="<?php echo $movie['id']; ?>">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <a href="edit.php?id=<?php echo $movie['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de reproducción -->
<div class="modal fade" id="playerModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reproducir Película</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <video id="videoPlayer" controls style="width: 100%; height: auto;">
                    Tu navegador no soporta el elemento de video.
                </video>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../layouts/base.php';
?>

<!-- Estilos adicionales -->
<style>
.movie-card {
    transition: transform 0.2s;
    cursor: pointer;
}
.movie-card:hover {
    transform: scale(1.05);
}
.movie-poster {
    height: 300px;
    object-fit: cover;
}
</style>

<!-- Scripts específicos -->
<script>
$(document).ready(function() {
    // DataTable
    $('#moviesTable').DataTable({
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });

    // Cambiar vista
    $('#viewGrid').click(function() {
        $('#listView').hide();
        $('#gridView').show();
        $(this).addClass('active');
        $('#viewList').removeClass('active');
    });

    $('#viewList').click(function() {
        $('#gridView').hide();
        $('#listView').show();
        $(this).addClass('active');
        $('#viewGrid').removeClass('active');
    });

    // Reproducir película
    $('.play-movie').click(function() {
        const movieId = $(this).data('id');
        // Aquí cargarías la URL del video
        $('#videoPlayer').attr('src', '<?php echo SITE_URL; ?>content/movies/movie-' + movieId + '.mp4');
        $('#playerModal').modal('show');
    });

    // Detener video al cerrar modal
    $('#playerModal').on('hidden.bs.modal', function() {
        $('#videoPlayer')[0].pause();
    });

    // Recomprimir video
    $('.compress-video').click(function() {
        const movieId = $(this).data('id');
        const $btn = $(this);
        
        Swal.fire({
            title: '¿Recomprimir video?',
            text: 'Este proceso puede tomar varios minutos',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, recomprimir',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.ajax({
                    url: '../../api/content/process-video.php',
                    method: 'POST',
                    data: { id: movieId },
                    success: function(response) {
                        toastr.success('Video enviado a procesar');
                        setTimeout(() => location.reload(), 2000);
                    },
                    error: function() {
                        toastr.error('Error al procesar video');
                        $btn.prop('disabled', false).html('<i class="fas fa-compress"></i>');
                    }
                });
            }
        });
    });

    // Regenerar thumbnail
    $('.regenerate-thumb').click(function() {
        const movieId = $(this).data('id');
        const $btn = $(this);
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: '../../api/content/generate-thumbnail.php',
            method: 'POST',
            data: { id: movieId, type: 'movie' },
            success: function(response) {
                toastr.success('Thumbnail regenerado');
                setTimeout(() => location.reload(), 1500);
            },
            error: function() {
                toastr.error('Error al generar thumbnail');
                $btn.prop('disabled', false).html('<i class="fas fa-image"></i>');
            }
        });
    });
});
</script>

<?php
// Helper function
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>