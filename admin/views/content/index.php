<?php
/**
 * Gestión de Contenido PLAYMI Admin
 * Vista principal para administrar películas, música y juegos
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

// Crear instancia del controlador
$contentController = new ContentController();

// Obtener datos
$contentData = $contentController->index();

// Variables para la vista
$pageTitle = 'Gestión de Contenido - PLAYMI Admin';
$contentTitle = 'Gestión de Contenido';
$contentSubtitle = 'Administrar películas, música y juegos';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Contenido', 'url' => BASE_URL . 'views/content/index.php']
];

// Extraer datos
$content = $contentData['content'] ?? [];
$pagination = $contentData['pagination'] ?? [];
$filters = $contentData['filters'] ?? [];
$stats = $contentData['stats'] ?? [];

// JavaScript específico
$pageScript = "
// Configurar DataTables
$('#contentTable').DataTable({
    responsive: true,
    order: [[0, 'desc']],
    columnDefs: [
        { targets: [-1], orderable: false },
        { targets: [3], className: 'text-center' }
    ],
    language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
    }
});

// Función para ver detalles
function viewDetails(contentId) {
    window.location.href = PLAYMI.baseUrl + 'views/content/view.php?id=' + contentId;
}

// Función para editar
function editContent(contentId) {
    window.location.href = PLAYMI.baseUrl + 'views/content/edit.php?id=' + contentId;
}

// Función para eliminar
function deleteContent(contentId, title) {
    Swal.fire({
        title: '¿Eliminar contenido?',
        text: 'Se eliminará: ' + title,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: PLAYMI.baseUrl + 'api/content/delete.php',
                method: 'POST',
                data: { content_id: contentId },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function() {
                    toastr.error('Error al eliminar el contenido');
                }
            });
        }
    });
}

// Función para cambiar estado
function toggleStatus(contentId, currentStatus) {
    const newStatus = currentStatus === 'activo' ? 'inactivo' : 'activo';
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/content/toggle-status.php',
        method: 'POST',
        data: {
            content_id: contentId,
            status: newStatus
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

// Filtro dinámico
$('#filterForm select, #filterForm input').on('change', function() {
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
            <span class="info-box-icon bg-info"><i class="fas fa-film"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Contenido</span>
                <span class="info-box-number"><?php echo $stats['total'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-video"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Películas</span>
                <span class="info-box-number"><?php echo $stats['movies'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-music"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Música</span>
                <span class="info-box-number"><?php echo $stats['music'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-gamepad"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Juegos</span>
                <span class="info-box-number"><?php echo $stats['games'] ?? 0; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card collapsed-card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter mr-1"></i>
            Filtros y Búsqueda
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-plus"></i>
            </button>
            <a href="<?php echo BASE_URL; ?>views/content/upload.php" class="btn btn-primary btn-sm">
                <i class="fas fa-upload"></i> Subir Contenido
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" id="filterForm" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="search">Buscar:</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Título..." 
                           value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="tipo">Tipo:</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <option value="pelicula" <?php echo ($filters['tipo'] ?? '') === 'pelicula' ? 'selected' : ''; ?>>Películas</option>
                        <option value="musica" <?php echo ($filters['tipo'] ?? '') === 'musica' ? 'selected' : ''; ?>>Música</option>
                        <option value="juego" <?php echo ($filters['tipo'] ?? '') === 'juego' ? 'selected' : ''; ?>>Juegos</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo ($filters['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="inactivo" <?php echo ($filters['estado'] ?? '') === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                        <option value="procesando" <?php echo ($filters['estado'] ?? '') === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <!-- Se llenarán dinámicamente según el tipo -->
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="<?php echo BASE_URL; ?>views/content/index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de contenido -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-1"></i>
            Lista de Contenido
        </h3>
        <div class="card-tools">
            <span class="badge badge-secondary">
                <?php echo count($content); ?> elemento(s)
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="contentTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Miniatura</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Categoría</th>
                        <th>Tamaño</th>
                        <th>Duración</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($content)): ?>
                        <?php foreach ($content as $item): ?>
                            <tr>
                                <td><?php echo $item['id']; ?></td>
                                <td class="text-center">
                                    <?php if ($item['thumbnail_path']): ?>
                                        <img src="<?php echo BASE_URL; ?>../content/<?php echo htmlspecialchars($item['thumbnail_path']); ?>" 
                                             alt="Miniatura" 
                                             class="img-thumbnail" 
                                             style="width: 60px; height: 60px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded d-inline-flex align-items-center justify-content-center" 
                                             style="width: 60px; height: 60px;">
                                            <i class="fas fa-<?php 
                                                echo $item['tipo'] === 'pelicula' ? 'film' : 
                                                    ($item['tipo'] === 'musica' ? 'music' : 'gamepad'); 
                                            ?> text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['titulo']); ?></strong>
                                    <?php if ($item['año_lanzamiento']): ?>
                                        <br><small class="text-muted">(<?php echo $item['año_lanzamiento']; ?>)</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $item['tipo'] === 'pelicula' ? 'primary' : 
                                            ($item['tipo'] === 'musica' ? 'success' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($item['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['categoria'] ?? 'Sin categoría'); ?></td>
                                <td>
                                    <?php 
                                    $sizeInMB = $item['tamaño_archivo'] / (1024 * 1024);
                                    echo number_format($sizeInMB, 1) . ' MB';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($item['duracion']) {
                                        $minutes = floor($item['duracion'] / 60);
                                        $seconds = $item['duracion'] % 60;
                                        echo sprintf('%d:%02d', $minutes, $seconds);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="status_<?php echo $item['id']; ?>"
                                               <?php echo $item['estado'] === 'activo' ? 'checked' : ''; ?>
                                               onchange="toggleStatus(<?php echo $item['id']; ?>, '<?php echo $item['estado']; ?>')">
                                        <label class="custom-control-label" for="status_<?php echo $item['id']; ?>">
                                            <?php if ($item['estado'] === 'procesando'): ?>
                                                <span class="text-warning">Procesando...</span>
                                            <?php else: ?>
                                                <?php echo ucfirst($item['estado']); ?>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" 
                                                class="btn btn-info btn-sm" 
                                                onclick="viewDetails(<?php echo $item['id']; ?>)"
                                                title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-warning btn-sm" 
                                                onclick="editContent(<?php echo $item['id']; ?>)"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" 
                                                class="btn btn-danger btn-sm" 
                                                onclick="deleteContent(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['titulo'], ENT_QUOTES); ?>')"
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
                                No se encontró contenido con los filtros aplicados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($content)): ?>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <small class="text-muted">
                        Mostrando <?php echo count($content); ?> de <?php echo $pagination['total'] ?? 0; ?> elementos
                    </small>
                </div>
                <div class="col-sm-6">
                    <div class="float-right">
                        <a href="<?php echo BASE_URL; ?>views/content/upload.php" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Subir Contenido
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// CSS adicional
$additionalCSS = [
    ASSETS_URL . 'plugins/datatables-bs4/css/dataTables.bootstrap4.min.css',
    ASSETS_URL . 'plugins/datatables-responsive/css/responsive.bootstrap4.min.css'
];

// JavaScript adicional
$additionalJS = [
    ASSETS_URL . 'plugins/datatables/jquery.dataTables.min.js',
    ASSETS_URL . 'plugins/datatables-bs4/js/dataTables.bootstrap4.min.js',
    ASSETS_URL . 'plugins/datatables-responsive/js/dataTables.responsive.min.js',
    ASSETS_URL . 'plugins/datatables-responsive/js/responsive.bootstrap4.min.js'
];

// Incluir el layout base
include '../layouts/base.php';
?>