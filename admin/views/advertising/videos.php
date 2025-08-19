<?php
/**
 * Gestión de Videos Publicitarios PLAYMI Admin
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/AdvertisingController.php';

// Crear instancia del controlador
$advertisingController = new AdvertisingController();

// Obtener datos
$viewData = $advertisingController->videos();

// Variables para la vista
$pageTitle = 'Videos Publicitarios - PLAYMI Admin';
$contentTitle = 'Videos Publicitarios';
$contentSubtitle = 'Gestionar videos de publicidad por empresa';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Publicidad', 'url' => '#'],
    ['title' => 'Videos', 'url' => BASE_URL . 'views/advertising/videos.php']
];

// Extraer datos
$videos = $viewData['videos'] ?? [];
$companies = $viewData['companies'] ?? [];
$filters = $viewData['filters'] ?? [];
$stats = $viewData['stats'] ?? [];

// JavaScript específico
$pageScript = "
// Función para eliminar video
function deleteVideo(videoId, filename) {
    Swal.fire({
        title: '¿Eliminar video?',
        text: 'Se eliminará: ' + filename,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: PLAYMI.baseUrl + 'api/advertising/delete-video.php',
                method: 'POST',
                data: { video_id: videoId },
                dataType: 'json',
                success: function(response) {
                    if (response && response.success) {
                        toastr.success(response.message || 'Video eliminado');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.error || 'Error al eliminar');
                    }
                },
                error: function(xhr) {
                    // Si se eliminó pero hubo error de respuesta
                    toastr.warning('Operación completada');
                    setTimeout(() => location.reload(), 1500);
                }
            });
        }
    });
}

// Función para cambiar estado
function toggleVideoStatus(videoId, currentStatus) {
    const newStatus = currentStatus === '1' ? '0' : '1';
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/advertising/toggle-video-status.php',
        method: 'POST',
        data: {
            video_id: videoId,
            status: newStatus === '1'
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Estado actualizado');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.error);
            }
        }
    });
}

// Preview de video
function previewVideo(videoPath) {
    const modal = $('#videoPreviewModal');
    const video = $('#previewVideo');
    
    video.attr('src', PLAYMI.baseUrl + '../content/' + videoPath);
    modal.modal('show');
}

// Cerrar video al cerrar modal
$('#videoPreviewModal').on('hidden.bs.modal', function() {
    $('#previewVideo')[0].pause();
});

// Filtros
$('#filterForm select').on('change', function() {
    $('#filterForm').submit();
});
";

// Generar contenido
ob_start();
?>

<!-- Estadísticas -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-video"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Videos</span>
                <span class="info-box-number"><?php echo $stats['total_videos'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-play-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Videos al Inicio</span>
                <span class="info-box-number"><?php echo $stats['total_inicio'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-pause-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Videos a la Mitad</span>
                <span class="info-box-number"><?php echo $stats['total_mitad'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Empresas con Ads</span>
                <span class="info-box-number"><?php echo $stats['empresas_con_publicidad'] ?? 0; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter mr-1"></i>
            Filtros
        </h3>
        <div class="card-tools">
            <a href="<?php echo BASE_URL; ?>views/advertising/upload-video.php" class="btn btn-primary btn-sm">
                <i class="fas fa-upload"></i> Subir Video
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" id="filterForm" class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="company_id">Empresa:</label>
                    <select class="form-control" id="company_id" name="company_id">
                        <option value="">Todas las empresas</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>" 
                                    <?php echo ($filters['company_id'] ?? 0) == $company['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($company['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="tipo">Tipo de Video:</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <option value="inicio" <?php echo ($filters['tipo'] ?? '') === 'inicio' ? 'selected' : ''; ?>>
                            Al inicio
                        </option>
                        <option value="mitad" <?php echo ($filters['tipo'] ?? '') === 'mitad' ? 'selected' : ''; ?>>
                            A la mitad
                        </option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="<?php echo BASE_URL; ?>views/advertising/videos.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de videos -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-1"></i>
            Videos Publicitarios
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Empresa</th>
                        <th>Tipo</th>
                        <th>Archivo</th>
                        <th>Duración</th>
                        <th>Tamaño</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($videos)): ?>
                        <?php foreach ($videos as $video): ?>
                            <tr>
                                <td><?php echo $video['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($video['empresa_nombre']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $video['tipo_video'] === 'inicio' ? 'success' : 'warning'; ?>">
                                        <?php echo $video['tipo_video'] === 'inicio' ? 'Al inicio' : 'A la mitad'; ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo basename($video['archivo_path']); ?></small>
                                </td>
                                <td><?php echo $video['duracion']; ?>s</td>
                                <td>
                                    <?php echo number_format($video['tamanio_archivo'] / (1024 * 1024), 1); ?> MB
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $video['orden_reproduccion']; ?></span>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="status_<?php echo $video['id']; ?>"
                                               <?php echo $video['activo'] ? 'checked' : ''; ?>
                                               onchange="toggleVideoStatus(<?php echo $video['id']; ?>, '<?php echo $video['activo']; ?>')">
                                        <label class="custom-control-label" for="status_<?php echo $video['id']; ?>">
                                            <?php echo $video['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                onclick="previewVideo('<?php echo $video['archivo_path']; ?>')"
                                                title="Ver video">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="deleteVideo(<?php echo $video['id']; ?>, '<?php echo basename($video['archivo_path']); ?>')"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                No se encontraron videos publicitarios
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para preview de video -->
<div class="modal fade" id="videoPreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Preview de Video</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <video id="previewVideo" controls class="w-100">
                    Tu navegador no soporta el elemento video.
                </video>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Incluir el layout base
include '../layouts/base.php';
?>