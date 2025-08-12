<?php
/**
 * MÓDULO 2.2.1: Lista principal de contenido multimedia
 * Propósito: Mostrar y gestionar todo el contenido del sistema
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

// Obtener datos
$page = (int)($_GET['page'] ?? 1);
$filters = [
    'search' => $_GET['search'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'estado' => $_GET['estado'] ?? ''
];

$result = $controller->index();
$content = $result['content'] ?? [];
$stats = $result['stats'] ?? [];
$pagination = $result['pagination'] ?? [];

// Incluir el layout base
$pageTitle = 'Gestión de Contenido';
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Gestión de Contenido</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Contenido</li>
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
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $stats['total'] ?? 0; ?></h3>
                        <p>Total Contenido</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-folder"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['movies'] ?? 0; ?></h3>
                        <p>Películas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-film"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $stats['music'] ?? 0; ?></h3>
                        <p>Música</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-music"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo $stats['games'] ?? 0; ?></h3>
                        <p>Juegos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filtros</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="form-inline">
                    <div class="form-group mr-3">
                        <label class="mr-2">Buscar:</label>
                        <input type="text" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($filters['search']); ?>" 
                               placeholder="Título...">
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">Tipo:</label>
                        <select name="tipo" class="form-control">
                            <option value="">Todos</option>
                            <option value="pelicula" <?php echo $filters['tipo'] === 'pelicula' ? 'selected' : ''; ?>>Películas</option>
                            <option value="musica" <?php echo $filters['tipo'] === 'musica' ? 'selected' : ''; ?>>Música</option>
                            <option value="juego" <?php echo $filters['tipo'] === 'juego' ? 'selected' : ''; ?>>Juegos</option>
                        </select>
                    </div>
                    
                    <div class="form-group mr-3">
                        <label class="mr-2">Estado:</label>
                        <select name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="activo" <?php echo $filters['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                            <option value="inactivo" <?php echo $filters['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                            <option value="procesando" <?php echo $filters['estado'] === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mr-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="index.php" class="btn btn-default">
                        <i class="fas fa-undo"></i> Limpiar
                    </a>
                </form>
            </div>
        </div>

        <!-- Tabla de contenido -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Contenido</h3>
                <div class="card-tools">
                    <a href="upload.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Contenido
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table id="contentTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="60">Thumb</th>
                            <th>Título</th>
                            <th width="100">Tipo</th>
                            <th width="100">Tamaño</th>
                            <th width="100">Estado</th>
                            <th width="130">Fecha</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($content as $item): ?>
                        <tr>
                            <td class="text-center">
                                <?php if ($item['thumbnail_path']): ?>
                                    <img src="<?php echo SITE_URL . 'content/' . $item['thumbnail_path']; ?>" 
                                         alt="Thumbnail" 
                                         class="img-thumbnail" 
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-gray" style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['titulo']); ?></strong>
                                <?php if ($item['descripcion']): ?>
                                    <br><small class="text-muted"><?php echo substr(htmlspecialchars($item['descripcion']), 0, 50); ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $tipoClass = [
                                    'pelicula' => 'badge-success',
                                    'musica' => 'badge-warning',
                                    'juego' => 'badge-danger'
                                ];
                                $tipoIcon = [
                                    'pelicula' => 'fa-film',
                                    'musica' => 'fa-music',
                                    'juego' => 'fa-gamepad'
                                ];
                                ?>
                                <span class="badge <?php echo $tipoClass[$item['tipo']] ?? 'badge-secondary'; ?>">
                                    <i class="fas <?php echo $tipoIcon[$item['tipo']] ?? ''; ?> mr-1"></i>
                                    <?php echo ucfirst($item['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($item['tamanio_archivo']) {
                                    echo formatFileSize($item['tamanio_archivo']);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <select class="form-control form-control-sm status-select" data-id="<?php echo $item['id']; ?>">
                                    <option value="activo" <?php echo $item['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo $item['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="procesando" <?php echo $item['estado'] === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                </select>
                            </td>
                            <td>
                                <small><?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?></small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-info preview-btn" 
                                            data-id="<?php echo $item['id']; ?>" 
                                            data-tipo="<?php echo $item['tipo']; ?>"
                                            title="Vista previa">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="edit.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-warning" 
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger delete-btn" 
                                            data-id="<?php echo $item['id']; ?>" 
                                            data-title="<?php echo htmlspecialchars($item['titulo']); ?>"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal de preview -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Incluir el layout
require_once '../layouts/base.php';
?>

<!-- Scripts específicos de la página -->
<script>
$(document).ready(function() {
    // Inicializar DataTable
    $('#contentTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": false,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });

    // Cambiar estado
    $('.status-select').on('change', function() {
        const id = $(this).data('id');
        const status = $(this).val();
        
        $.ajax({
            url: '../../api/content/update-status.php',
            method: 'POST',
            data: { id: id, status: status },
            success: function(response) {
                toastr.success('Estado actualizado correctamente');
            },
            error: function() {
                toastr.error('Error al actualizar el estado');
            }
        });
    });

    // Eliminar contenido
    $('.delete-btn').on('click', function() {
        const id = $(this).data('id');
        const title = $(this).data('title');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseas eliminar "${title}"? Esta acción no se puede deshacer.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../api/content/delete-content.php',
                    method: 'POST',
                    data: { id: id },
                    success: function(response) {
                        Swal.fire('Eliminado!', 'El contenido ha sido eliminado.', 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo eliminar el contenido', 'error');
                    }
                });
            }
        });
    });

    // Vista previa
    $('.preview-btn').on('click', function() {
        const id = $(this).data('id');
        const tipo = $(this).data('tipo');
        
        // Aquí cargarías el contenido de preview según el tipo
        $('#previewContent').html('<p>Cargando vista previa...</p>');
        $('#previewModal').modal('show');
        
        // TODO: Implementar carga de preview real
    });
});
</script>

<?php
// Función helper para formatear tamaño de archivo
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