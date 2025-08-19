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
$viewData = $advertisingController->banners();


// Variables para la vista
$pageTitle = 'Banners Publicitarios - PLAYMI Admin';
$contentTitle = 'Banners Publicitarios';
$contentSubtitle = 'Gestionar banners de publicidad por empresa';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Publicidad', 'url' => '#'],
    ['title' => 'Banners', 'url' => BASE_URL . 'views/advertising/banners.php']
];

// Extraer datos
$banners = $viewData['banners'] ?? [];
$companies = $viewData['companies'] ?? [];
$filters = $viewData['filters'] ?? [];
$stats = $viewData['stats'] ?? [];

// JavaScript específico
$pageScript = "
// Función para eliminar banner
function deleteBanner(bannerId, filename) {
    Swal.fire({
        title: '¿Eliminar banner?',
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
                url: PLAYMI.baseUrl + 'api/advertising/delete-banner.php',
                method: 'POST',
                data: { banner_id: bannerId },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function() {
                    toastr.error('Error al eliminar el banner');
                }
            });
        }
    });
}

// Función para cambiar estado
function toggleBannerStatus(bannerId, currentStatus) {
    const newStatus = currentStatus === '1' ? '0' : '1';
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/advertising/toggle-banner-status.php',
        method: 'POST',
        data: {
            banner_id: bannerId,
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

// Preview de banner
function previewBanner(bannerPath) {
    const modal = $('#bannerPreviewModal');
    const img = $('#previewBanner');

    img.attr('src', PLAYMI.baseUrl + '../content/' + bannerPath);
    modal.modal('show');
}

// Cerrar banner al cerrar modal
$('#bannerPreviewModal').on('hidden.bs.modal', function() {
    $('#previewBanner')[0].pause();
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
            <span class="info-box-icon bg-info"><i class="fas fa-image"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Banners</span>
                <span class="info-box-number"><?php echo $stats['total_banners'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Headers</span>
                <span class="info-box-number"><?php echo $stats['total_header'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-th"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Catálogo</span>
                <span class="info-box-number"><?php echo $stats['total_catalogo'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Footers</span>
                <span class="info-box-number"><?php echo $stats['total_footer'] ?? 0; ?></span>
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
            <a href="<?php echo BASE_URL; ?>views/advertising/upload-banner.php" class="btn btn-primary btn-sm">
                <i class="fas fa-upload"></i> Subir Banner
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
                    <label for="tipo">Tipo de Banner:</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <option value="header" <?php echo ($filters['tipo'] ?? '') === 'header' ? 'selected' : ''; ?>>
                            Header
                        </option>
                        <option value="footer" <?php echo ($filters['tipo'] ?? '') === 'footer' ? 'selected' : ''; ?>>
                            Footer
                        </option>
                        <option value="catalogo" <?php echo ($filters['tipo'] ?? '') === 'catalogo' ? 'selected' : ''; ?>>
                            Catálogo
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
                        <a href="<?php echo BASE_URL; ?>views/advertising/banners.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de banners -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-1"></i>
            Banners Publicitarios
        </h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Vista Previa</th>
                        <th>Empresa</th>
                        <th>Tipo</th>
                        <th>Dimensiones</th>
                        <th>Tamaño</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($banners)): ?>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><?php echo $banner['id']; ?></td>
                                <td>
                                    <img src="<?php echo UPLOADS_URL . $banner['imagen_path']; ?>" 
                                         alt="Banner" 
                                         style="max-width: 100px; max-height: 50px; cursor: pointer;"
                                         onclick="previewBanner('<?php echo $banner['imagen_path']; ?>')">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($banner['empresa_nombre']); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $banner['tipo_banner'] === 'header' ? 'primary' : 
                                            ($banner['tipo_banner'] === 'footer' ? 'success' : 'warning'); 
                                    ?>">
                                        <?php 
                                        $tipoLabels = [
                                            'header' => 'Header',
                                            'footer' => 'Footer',
                                            'catalogo' => 'Catálogo'
                                        ];
                                        echo $tipoLabels[$banner['tipo_banner']] ?? $banner['tipo_banner']; 
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo $banner['ancho']; ?>x<?php echo $banner['alto']; ?>px</td>
                                <td>
                                    <?php echo number_format($banner['tamanio_archivo'] / 1024, 1); ?> KB
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $banner['orden_visualizacion']; ?></span>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="status_<?php echo $banner['id']; ?>"
                                               <?php echo $banner['activo'] ? 'checked' : ''; ?>
                                               onchange="toggleBannerStatus(<?php echo $banner['id']; ?>, '<?php echo $banner['activo']; ?>')">
                                        <label class="custom-control-label" for="status_<?php echo $banner['id']; ?>">
                                            <?php echo $banner['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                onclick="previewBanner('<?php echo $banner['imagen_path']; ?>')"
                                                title="Ver banner">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="deleteBanner(<?php echo $banner['id']; ?>, '<?php echo basename($banner['imagen_path']); ?>')"
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
                                No se encontraron banners publicitarios
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para preview de banner -->
<div class="modal fade" id="bannerPreviewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa del Banner</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="previewBanner" src="" alt="Banner" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// IMPORTANTE: Asegurarse de que no haya output antes de incluir base.php
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Incluir el layout base
include '../layouts/base.php';
?>