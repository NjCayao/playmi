<?php
/**
 * MÓDULO 2.3.1: Lista principal de paquetes generados
 * Página para visualizar todos los paquetes generados para empresas
 * 
 * Funcionalidades:
 * - Lista con DataTables (paginación, ordenamiento, búsqueda)
 * - Filtros por empresa y estado
 * - Descarga directa de paquetes listos
 * - Regenerar paquete con contenido actualizado
 * - Eliminar paquetes obsoletos
 * - Ver log de instalación
 */

// Incluir configuración y controlador
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

// Crear instancia del controlador
$packageController = new PackageController();

// Obtener datos del controlador
$data = $packageController->index();

// Extraer variables
$packages = $data['packages'] ?? [];
$companies = $data['companies'] ?? [];
$filters = $data['filters'] ?? [];
$stats = $data['stats'] ?? [];
$pagination = $data['pagination'] ?? [];

// Configuración de la página
$pageTitle = 'Gestión de Paquetes - PLAYMI Admin';
$contentTitle = 'Gestión de Paquetes';
$contentSubtitle = 'Administrar paquetes generados para empresas';

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Paquetes', 'url' => '#']
];

// CSS adicional
$additionalCSS = [];

// JS adicional
$additionalJS = [];

// Iniciar buffer de contenido
ob_start();
?>

<!-- Estadísticas superiores -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($stats['total'] ?? 0); ?></h3>
                <p>Total Paquetes</p>
            </div>
            <div class="icon">
                <i class="fas fa-box"></i>
            </div>
            <a href="#" class="small-box-footer">
                Ver todos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($stats['ready'] ?? 0); ?></h3>
                <p>Listos para Descargar</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="?estado=listo" class="small-box-footer">
                Ver listos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($stats['generating'] ?? 0); ?></h3>
                <p>En Generación</p>
            </div>
            <div class="icon">
                <i class="fas fa-cogs"></i>
            </div>
            <a href="?estado=generando" class="small-box-footer">
                Ver en proceso <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?php echo number_format($stats['installed'] ?? 0); ?></h3>
                <p>Instalados</p>
            </div>
            <div class="icon">
                <i class="fas fa-server"></i>
            </div>
            <a href="?estado=instalado" class="small-box-footer">
                Ver instalados <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filtros y Acciones
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="form-inline">
            <div class="form-group mr-3">
                <label class="mr-2">Empresa:</label>
                <select name="company_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas las empresas</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>" 
                                <?php echo ($filters['company_id'] ?? '') == $company['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($company['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group mr-3">
                <label class="mr-2">Estado:</label>
                <select name="estado" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="generando" <?php echo ($filters['estado'] ?? '') == 'generando' ? 'selected' : ''; ?>>
                        Generando
                    </option>
                    <option value="listo" <?php echo ($filters['estado'] ?? '') == 'listo' ? 'selected' : ''; ?>>
                        Listo
                    </option>
                    <option value="descargado" <?php echo ($filters['estado'] ?? '') == 'descargado' ? 'selected' : ''; ?>>
                        Descargado
                    </option>
                    <option value="instalado" <?php echo ($filters['estado'] ?? '') == 'instalado' ? 'selected' : ''; ?>>
                        Instalado
                    </option>
                    <option value="vencido" <?php echo ($filters['estado'] ?? '') == 'vencido' ? 'selected' : ''; ?>>
                        Vencido
                    </option>
                </select>
            </div>
            
            <?php if (!empty($filters['company_id']) || !empty($filters['estado'])): ?>
                <a href="<?php echo BASE_URL; ?>views/packages/index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar filtros
                </a>
            <?php endif; ?>
            
            <div class="ml-auto">
                <a href="<?php echo BASE_URL; ?>views/packages/generate.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Generar Nuevo Paquete
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Lista de paquetes -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i> Lista de Paquetes Generados
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($packages)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No se encontraron paquetes con los filtros aplicados.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="packagesTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="20%">Empresa</th>
                            <th width="15%">Nombre Paquete</th>
                            <th width="8%">Versión</th>
                            <th width="12%">Fecha Generación</th>
                            <th width="10%">Tamaño</th>
                            <th width="10%">Estado</th>
                            <th width="8%">Contenido</th>
                            <th width="12%">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                            <tr>
                                <td><?php echo $package['id']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>views/companies/view.php?id=<?php echo $package['empresa_id']; ?>">
                                        <?php echo htmlspecialchars($package['empresa_nombre'] ?? 'Empresa #' . $package['empresa_id']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($package['nombre_paquete']); ?></strong>
                                    <?php if ($package['notas']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($package['notas']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-secondary">
                                        v<?php echo htmlspecialchars($package['version_paquete']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y H:i', strtotime($package['fecha_generacion'])); ?>
                                    <?php if ($package['fecha_vencimiento_licencia']): ?>
                                        <br><small class="text-muted">
                                            Vence: <?php echo date('d/m/Y', strtotime($package['fecha_vencimiento_licencia'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($package['tamanio_paquete']): ?>
                                        <?php echo formatFileSize($package['tamanio_paquete']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo renderPackageStatus($package['estado']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($package['cantidad_contenido']): ?>
                                        <span class="badge badge-info">
                                            <?php echo number_format($package['cantidad_contenido']); ?> items
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($package['estado'] == 'listo'): ?>
                                            <a href="<?php echo API_URL; ?>packages/download-package.php?id=<?php echo $package['id']; ?>" 
                                               class="btn btn-success" 
                                               data-toggle="tooltip" 
                                               title="Descargar paquete">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if (in_array($package['estado'], ['listo', 'instalado'])): ?>
                                            <button type="button" 
                                                    class="btn btn-info btn-regenerate" 
                                                    data-id="<?php echo $package['id']; ?>"
                                                    data-company="<?php echo $package['empresa_id']; ?>"
                                                    data-toggle="tooltip" 
                                                    title="Regenerar paquete">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button type="button" 
                                                class="btn btn-warning btn-view-log" 
                                                data-id="<?php echo $package['id']; ?>"
                                                data-toggle="tooltip" 
                                                title="Ver logs">
                                            <i class="fas fa-file-alt"></i>
                                        </button>
                                        
                                        <?php if (!in_array($package['estado'], ['generando', 'instalado'])): ?>
                                            <button type="button" 
                                                    class="btn btn-danger btn-delete" 
                                                    data-id="<?php echo $package['id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($package['nombre_paquete']); ?>"
                                                    data-toggle="tooltip" 
                                                    title="Eliminar paquete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <div class="mt-3">
                    <nav aria-label="Paginación de paquetes">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $pagination['previous_page']; ?>&amp;<?php echo http_build_query($filters); ?>">
                                    Anterior
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                <?php if ($i == 1 || $i == $pagination['total_pages'] || ($i >= $pagination['current_page'] - 2 && $i <= $pagination['current_page'] + 2)): ?>
                                    <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&amp;<?php echo http_build_query($filters); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php elseif ($i == $pagination['current_page'] - 3 || $i == $pagination['current_page'] + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $pagination['next_page']; ?>&amp;<?php echo http_build_query($filters); ?>">
                                    Siguiente
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para ver logs -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logs del Paquete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="logsContent">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Cargando logs...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
// Funciones auxiliares
function renderPackageStatus($status) {
    $badges = [
        'generando' => '<span class="badge badge-warning"><i class="fas fa-cogs"></i> Generando</span>',
        'listo' => '<span class="badge badge-success"><i class="fas fa-check"></i> Listo</span>',
        'descargado' => '<span class="badge badge-info"><i class="fas fa-download"></i> Descargado</span>',
        'instalado' => '<span class="badge badge-primary"><i class="fas fa-server"></i> Instalado</span>',
        'vencido' => '<span class="badge badge-danger"><i class="fas fa-times"></i> Vencido</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

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

<script>
$(document).ready(function() {
    // Inicializar DataTable si hay datos
    <?php if (!empty($packages)): ?>
    $('#packagesTable').DataTable({
        responsive: true,
        lengthChange: false,
        autoWidth: false,
        pageLength: 25,
        order: [[4, 'desc']], // Ordenar por fecha de generación
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }
        ]
    });
    <?php endif; ?>
    
    // Ver logs
    $('.btn-view-log').on('click', function() {
        const packageId = $(this).data('id');
        
        $('#logsModal').modal('show');
        $('#logsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando logs...</p></div>');
        
        // Cargar logs via AJAX
        $.ajax({
            url: '<?php echo API_URL; ?>packages/get-logs.php',
            method: 'GET',
            data: { package_id: packageId },
            success: function(response) {
                if (response.success) {
                    let logsHtml = '<div class="timeline">';
                    
                    if (response.logs && response.logs.length > 0) {
                        response.logs.forEach(function(log) {
                            logsHtml += `
                                <div class="time-label">
                                    <span class="bg-blue">${log.date}</span>
                                </div>
                                <div>
                                    <i class="fas fa-${log.icon} bg-${log.color}"></i>
                                    <div class="timeline-item">
                                        <span class="time"><i class="fas fa-clock"></i> ${log.time}</span>
                                        <h3 class="timeline-header">${log.action}</h3>
                                        <div class="timeline-body">${log.description}</div>
                                    </div>
                                </div>
                            `;
                        });
                    } else {
                        logsHtml += '<p class="text-muted text-center">No hay logs disponibles</p>';
                    }
                    
                    logsHtml += '</div>';
                    $('#logsContent').html(logsHtml);
                } else {
                    $('#logsContent').html('<div class="alert alert-danger">Error al cargar logs</div>');
                }
            },
            error: function() {
                $('#logsContent').html('<div class="alert alert-danger">Error de conexión</div>');
            }
        });
    });
    
    // Regenerar paquete
    $('.btn-regenerate').on('click', function() {
        const packageId = $(this).data('id');
        const companyId = $(this).data('company');
        
        Swal.fire({
            title: '¿Regenerar paquete?',
            text: 'Se creará una nueva versión del paquete con el contenido actual',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, regenerar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirigir a generar con empresa preseleccionada
                window.location.href = '<?php echo BASE_URL; ?>views/packages/generate.php?company_id=' + companyId + '&regenerate=' + packageId;
            }
        });
    });
    
    // Eliminar paquete
    $('.btn-delete').on('click', function() {
        const packageId = $(this).data('id');
        const packageName = $(this).data('name');
        
        Swal.fire({
            title: '¿Eliminar paquete?',
            text: `¿Está seguro de eliminar el paquete "${packageName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo API_URL; ?>packages/delete-package.php',
                    method: 'POST',
                    data: { package_id: packageId },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Paquete eliminado exitosamente');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            toastr.error(response.error || 'Error al eliminar el paquete');
                        }
                    },
                    error: function() {
                        toastr.error('Error de conexión');
                    }
                });
            }
        });
    });
    
    // Auto-refresh para paquetes en generación
    <?php if ($stats['generating'] > 0): ?>
    setInterval(function() {
        // Solo actualizar si hay paquetes generándose
        $.ajax({
            url: '<?php echo API_URL; ?>packages/check-status.php',
            method: 'GET',
            data: { estado: 'generando' },
            success: function(response) {
                if (response.updated) {
                    toastr.info('Un paquete ha terminado de generarse');
                    setTimeout(() => location.reload(), 2000);
                }
            }
        });
    }, 30000); // Cada 30 segundos
    <?php endif; ?>
});
</script>

<?php
// Capturar contenido
$content = ob_get_clean();

// Incluir layout base
require_once __DIR__ . '/../layouts/base.php';
?>