<?php
/**
 * MÓDULO 2.3.4: Historial completo de paquetes por empresa
 * Página con timeline visual del historial de paquetes
 * 
 * Funcionalidades:
 * - Timeline visual del historial
 * - Comparación entre versiones
 * - Descarga de versiones anteriores
 * - Ver logs detallados de cada versión
 * - Filtros por empresa y fecha
 * - Exportar historial a PDF
 */

// Incluir configuración y controlador
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

// Crear instancia del controlador
$packageController = new PackageController();

// Obtener datos del controlador
$data = $packageController->history();

// Extraer variables
$packages = $data['packages'] ?? [];
$companies = $data['companies'] ?? [];
$filters = $data['filters'] ?? [];
$stats = $data['stats'] ?? [];

// Configuración de la página
$pageTitle = 'Historial de Paquetes - PLAYMI Admin';
$contentTitle = 'Historial de Paquetes';
$contentSubtitle = 'Timeline completo de versiones generadas';

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Paquetes', 'url' => BASE_URL . 'views/packages/index.php'],
    ['title' => 'Historial', 'url' => '#']
];

// CSS adicional
$additionalCSS = [
    ASSETS_URL . 'plugins/daterangepicker/daterangepicker.css'
];

// JS adicional
$additionalJS = [
    ASSETS_URL . 'plugins/moment/moment.min.js',
    ASSETS_URL . 'plugins/daterangepicker/daterangepicker.js',
    ASSETS_URL . 'plugins/chart.js/Chart.min.js'
];

// Iniciar buffer de contenido
ob_start();

// Agrupar paquetes por empresa para el timeline
$packagesByCompany = [];
foreach ($packages as $package) {
    $companyId = $package['empresa_id'];
    if (!isset($packagesByCompany[$companyId])) {
        $packagesByCompany[$companyId] = [
            'company_name' => $package['empresa_nombre'] ?? 'Empresa #' . $companyId,
            'packages' => []
        ];
    }
    $packagesByCompany[$companyId]['packages'][] = $package;
}
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Historial de paquetes generados</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Paquetes</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y estadísticas -->
<div class="row">
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($stats['total_packages'] ?? 0); ?></h3>
                <p>Total Histórico</p>
            </div>
            <div class="icon">
                <i class="fas fa-history"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?php echo number_format($stats['companies_with_packages'] ?? 0); ?></h3>
                <p>Empresas con Paquetes</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?php echo number_format($stats['avg_package_size'] ?? 0); ?> MB</h3>
                <p>Tamaño Promedio</p>
            </div>
            <div class="icon">
                <i class="fas fa-hdd"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3><?php echo number_format($stats['packages_this_month'] ?? 0); ?></h3>
                <p>Generados este Mes</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filtros
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
                <select name="company_id" class="form-control select2" style="width: 250px;">
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
                <label class="mr-2">Período:</label>
                <input type="text" class="form-control" id="daterange" name="daterange" 
                       value="<?php echo htmlspecialchars($filters['daterange'] ?? ''); ?>"
                       placeholder="Seleccione rango de fechas">
            </div>
            
            <div class="form-group mr-3">
                <label class="mr-2">Versión:</label>
                <input type="text" class="form-control" name="version" 
                       value="<?php echo htmlspecialchars($filters['version'] ?? ''); ?>"
                       placeholder="Ej: 1.0">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
            
            <?php if (!empty($filters['company_id']) || !empty($filters['daterange']) || !empty($filters['version'])): ?>
                <a href="<?php echo BASE_URL; ?>views/packages/history.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            <?php endif; ?>
            
            <div class="ml-auto">
                <button type="button" class="btn btn-danger" onclick="exportPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Gráfico de actividad -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-chart-line"></i> Actividad de Generación
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="activityChart" style="height: 250px;"></canvas>
    </div>
</div>

<!-- Timeline de paquetes -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-stream"></i> Timeline de Paquetes
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($packagesByCompany)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No se encontraron paquetes con los filtros aplicados.
            </div>
        <?php else: ?>
            <?php foreach ($packagesByCompany as $companyId => $companyData): ?>
                <h4 class="text-primary mb-3">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($companyData['company_name']); ?>
                </h4>
                
                <div class="timeline mb-5">
                    <?php 
                    $currentDate = '';
                    foreach ($companyData['packages'] as $package): 
                        $packageDate = date('d/m/Y', strtotime($package['fecha_generacion']));
                        
                        // Mostrar label de fecha si cambió
                        if ($packageDate !== $currentDate):
                            $currentDate = $packageDate;
                    ?>
                        <div class="time-label">
                            <span class="bg-primary"><?php echo $currentDate; ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Item del timeline -->
                    <div>
                        <i class="fas fa-box bg-<?php echo getStatusColor($package['estado']); ?>"></i>
                        <div class="timeline-item">
                            <span class="time">
                                <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($package['fecha_generacion'])); ?>
                            </span>
                            
                            <h3 class="timeline-header">
                                <strong><?php echo htmlspecialchars($package['nombre_paquete']); ?></strong>
                                <span class="badge badge-secondary ml-2">v<?php echo $package['version_paquete']; ?></span>
                                <?php echo renderPackageStatus($package['estado']); ?>
                            </h3>
                            
                            <div class="timeline-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Tamaño:</dt>
                                            <dd class="col-sm-8">
                                                <?php echo $package['tamanio_paquete'] ? formatFileSize($package['tamanio_paquete']) : 'N/A'; ?>
                                            </dd>
                                            <dt class="col-sm-4">Contenido:</dt>
                                            <dd class="col-sm-8">
                                                <?php echo $package['cantidad_contenido'] ? number_format($package['cantidad_contenido']) . ' items' : 'N/A'; ?>
                                            </dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row mb-0">
                                            <dt class="col-sm-4">Generado por:</dt>
                                            <dd class="col-sm-8">
                                                <?php echo htmlspecialchars($package['generado_por_nombre'] ?? 'Sistema'); ?>
                                            </dd>
                                            <dt class="col-sm-4">Vencimiento:</dt>
                                            <dd class="col-sm-8">
                                                <?php 
                                                if ($package['fecha_vencimiento_licencia']) {
                                                    echo date('d/m/Y', strtotime($package['fecha_vencimiento_licencia']));
                                                    $daysLeft = (strtotime($package['fecha_vencimiento_licencia']) - time()) / 86400;
                                                    if ($daysLeft > 0 && $daysLeft < 30) {
                                                        echo ' <span class="badge badge-warning">Vence pronto</span>';
                                                    } elseif ($daysLeft <= 0) {
                                                        echo ' <span class="badge badge-danger">Vencido</span>';
                                                    }
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                                
                                <?php if ($package['notas']): ?>
                                    <div class="mt-2">
                                        <strong>Notas:</strong> <?php echo htmlspecialchars($package['notas']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="timeline-footer">
                                <?php if ($package['estado'] == 'listo'): ?>
                                    <a href="<?php echo API_URL; ?>packages/download-package.php?id=<?php echo $package['id']; ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> Descargar
                                    </a>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-info btn-sm" 
                                        onclick="viewLogs(<?php echo $package['id']; ?>)">
                                    <i class="fas fa-file-alt"></i> Ver Logs
                                </button>
                                
                                <?php if (isset($companyData['packages'][array_search($package, $companyData['packages']) + 1])): ?>
                                    <button type="button" class="btn btn-warning btn-sm" 
                                            onclick="compareVersions(<?php echo $package['id']; ?>, <?php echo $companyData['packages'][array_search($package, $companyData['packages']) + 1]['id']; ?>)">
                                        <i class="fas fa-code-branch"></i> Comparar con anterior
                                    </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-secondary btn-sm" 
                                        onclick="viewDetails(<?php echo $package['id']; ?>)">
                                    <i class="fas fa-info-circle"></i> Detalles
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Fin del timeline -->
                    <div>
                        <i class="fas fa-flag-checkered bg-gray"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para logs -->
<div class="modal fade" id="logsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Logs del Paquete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="logsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando logs...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para comparación -->
<div class="modal fade" id="compareModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comparación de Versiones</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="compareContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando comparación...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Paquete</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Cargando detalles...</p>
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
function getStatusColor($status) {
    $colors = [
        'generando' => 'warning',
        'listo' => 'success',
        'descargado' => 'info',
        'instalado' => 'primary',
        'vencido' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function renderPackageStatus($status) {
    $badges = [
        'generando' => '<span class="badge badge-warning ml-2"><i class="fas fa-cogs"></i> Generando</span>',
        'listo' => '<span class="badge badge-success ml-2"><i class="fas fa-check"></i> Listo</span>',
        'descargado' => '<span class="badge badge-info ml-2"><i class="fas fa-download"></i> Descargado</span>',
        'instalado' => '<span class="badge badge-primary ml-2"><i class="fas fa-server"></i> Instalado</span>',
        'vencido' => '<span class="badge badge-danger ml-2"><i class="fas fa-times"></i> Vencido</span>'
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary ml-2">' . ucfirst($status) . '</span>';
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

<?php
// Capturar contenido
$content = ob_get_clean();

// Incluir layout base
require_once __DIR__ . '/../layouts/base.php';
?>

<script>
$(document).ready(function() {
    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap4'
    });
    
    // Inicializar date range picker
    $('#daterange').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Limpiar',
            applyLabel: 'Aplicar',
            format: 'DD/MM/YYYY',
            daysOfWeek: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
            monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
                        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']
        }
    });
    
    $('#daterange').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
    });
    
    $('#daterange').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
    
    // Inicializar gráfico
    initActivityChart();
});

// Inicializar gráfico de actividad
function initActivityChart() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Datos de ejemplo - en producción vendrían del servidor
    const chartData = <?php echo json_encode($stats['activity_data'] ?? [
        'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'],
        'datasets' => [
            (object)[
                'label' => 'Paquetes Generados',
                'data' => [12, 19, 15, 25, 22, 30],
                'borderColor' => '#2563eb',
                'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                'tension' => 0.4
            ]
        ]
    ]); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 5
                    }
                }
            }
        }
    });
}

// Ver logs del paquete
function viewLogs(packageId) {
    $('#logsModal').modal('show');
    $('#logsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando logs...</p></div>');
    
    $.ajax({
        url: '<?php echo API_URL; ?>packages/get-logs.php',
        method: 'GET',
        data: { package_id: packageId },
        success: function(response) {
            if (response.success && response.logs) {
                let logsHtml = '<div class="timeline timeline-inverse">';
                
                response.logs.forEach(function(log) {
                    logsHtml += `
                        <div>
                            <i class="fas fa-${log.icon} bg-${log.color}"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="fas fa-clock"></i> ${log.datetime}
                                </span>
                                <h3 class="timeline-header no-border">
                                    ${log.action}
                                </h3>
                                ${log.description ? `<div class="timeline-body">${log.description}</div>` : ''}
                            </div>
                        </div>
                    `;
                });
                
                logsHtml += '</div>';
                $('#logsContent').html(logsHtml);
            } else {
                $('#logsContent').html('<div class="alert alert-warning">No se encontraron logs para este paquete</div>');
            }
        },
        error: function() {
            $('#logsContent').html('<div class="alert alert-danger">Error al cargar los logs</div>');
        }
    });
}

// Comparar versiones
function compareVersions(currentId, previousId) {
    $('#compareModal').modal('show');
    $('#compareContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando comparación...</p></div>');
    
    $.ajax({
        url: '<?php echo API_URL; ?>packages/compare-versions.php',
        method: 'GET',
        data: { 
            current_id: currentId,
            previous_id: previousId
        },
        success: function(response) {
            if (response.success) {
                let compareHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Versión Actual</h5>
                            <div class="card">
                                <div class="card-body">
                                    <dl>
                                        <dt>Nombre:</dt>
                                        <dd>${response.current.nombre_paquete}</dd>
                                        <dt>Versión:</dt>
                                        <dd>${response.current.version_paquete}</dd>
                                        <dt>Fecha:</dt>
                                        <dd>${response.current.fecha_generacion}</dd>
                                        <dt>Tamaño:</dt>
                                        <dd>${response.current.tamanio_display}</dd>
                                        <dt>Contenido:</dt>
                                        <dd>${response.current.cantidad_contenido} items</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5>Versión Anterior</h5>
                            <div class="card">
                                <div class="card-body">
                                    <dl>
                                        <dt>Nombre:</dt>
                                        <dd>${response.previous.nombre_paquete}</dd>
                                        <dt>Versión:</dt>
                                        <dd>${response.previous.version_paquete}</dd>
                                        <dt>Fecha:</dt>
                                        <dd>${response.previous.fecha_generacion}</dd>
                                        <dt>Tamaño:</dt>
                                        <dd>${response.previous.tamanio_display}</dd>
                                        <dt>Contenido:</dt>
                                        <dd>${response.previous.cantidad_contenido} items</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Cambios Detectados</h5>
                    <div class="card">
                        <div class="card-body">
                `;
                
                if (response.changes && response.changes.length > 0) {
                    compareHtml += '<ul class="list-unstyled">';
                    response.changes.forEach(function(change) {
                        let icon = change.type === 'added' ? 'plus-circle text-success' : 
                                  (change.type === 'removed' ? 'minus-circle text-danger' : 'edit text-warning');
                        compareHtml += `<li><i class="fas fa-${icon}"></i> ${change.description}</li>`;
                    });
                    compareHtml += '</ul>';
                } else {
                    compareHtml += '<p class="text-muted">No se detectaron cambios significativos</p>';
                }
                
                compareHtml += '</div></div>';
                $('#compareContent').html(compareHtml);
            } else {
                $('#compareContent').html('<div class="alert alert-danger">Error al cargar la comparación</div>');
            }
        },
        error: function() {
            $('#compareContent').html('<div class="alert alert-danger">Error de conexión</div>');
        }
    });
}

// Ver detalles del paquete
function viewDetails(packageId) {
    $('#detailsModal').modal('show');
    $('#detailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Cargando detalles...</p></div>');
    
    $.ajax({
        url: '<?php echo API_URL; ?>packages/get-details.php',
        method: 'GET',
        data: { package_id: packageId },
        success: function(response) {
            if (response.success && response.package) {
                const pkg = response.package;
                let detailsHtml = `
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información General</h5>
                            <dl>
                                <dt>ID del Paquete:</dt>
                                <dd>${pkg.id}</dd>
                                <dt>Nombre:</dt>
                                <dd>${pkg.nombre_paquete}</dd>
                                <dt>Versión:</dt>
                                <dd>${pkg.version_paquete}</dd>
                                <dt>Estado:</dt>
                                <dd>${pkg.estado_badge}</dd>
                                <dt>Clave de Instalación:</dt>
                                <dd><code>${pkg.clave_instalacion || 'N/A'}</code></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h5>Detalles Técnicos</h5>
                            <dl>
                                <dt>Tamaño:</dt>
                                <dd>${pkg.tamanio_display}</dd>
                                <dt>Checksum (SHA256):</dt>
                                <dd><small><code>${pkg.checksum || 'N/A'}</code></small></dd>
                                <dt>Ruta del Paquete:</dt>
                                <dd><small>${pkg.ruta_paquete || 'N/A'}</small></dd>
                            </dl>
                        </div>
                    </div>
                    
                    <h5 class="mt-3">Contenido del Paquete</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box bg-danger">
                                <span class="info-box-icon"><i class="fas fa-film"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Películas</span>
                                    <span class="info-box-number">${pkg.content_stats.movies || 0}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-success">
                                <span class="info-box-icon"><i class="fas fa-music"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Música</span>
                                    <span class="info-box-number">${pkg.content_stats.music || 0}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box bg-warning">
                                <span class="info-box-icon"><i class="fas fa-gamepad"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Juegos</span>
                                    <span class="info-box-number">${pkg.content_stats.games || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    ${pkg.notas ? `
                        <h5 class="mt-3">Notas</h5>
                        <div class="card">
                            <div class="card-body">
                                <p>${pkg.notas}</p>
                            </div>
                        </div>
                    ` : ''}
                `;
                
                $('#detailsContent').html(detailsHtml);
            } else {
                $('#detailsContent').html('<div class="alert alert-danger">Error al cargar los detalles</div>');
            }
        },
        error: function() {
            $('#detailsContent').html('<div class="alert alert-danger">Error de conexión</div>');
        }
    });
}

// Exportar a PDF
function exportPDF() {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'pdf');
    
    window.open('<?php echo API_URL; ?>packages/export-history.php?' + params.toString(), '_blank');
}
</script>
