<?php
/**
 * Dashboard Principal PLAYMI Admin
 * Página de inicio del panel de administración
 */

// Incluir configuración y controladores
require_once 'config/system.php';
require_once 'controllers/DashboardController.php';

// Crear instancia del controlador
$dashboardController = new DashboardController();

// Obtener datos del dashboard
$dashboardData = $dashboardController->index();

// Variables para la vista
$pageTitle = 'Dashboard - PLAYMI Admin';
$contentTitle = 'Dashboard';
$contentSubtitle = 'Resumen general del sistema';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php']
];

// Extraer datos para usar en la vista
$stats = $dashboardData['stats'] ?? [];
$recentActivity = $dashboardData['recent_activity'] ?? [];
$systemAlerts = $dashboardData['system_alerts'] ?? [];
$chartData = $dashboardData['chart_data'] ?? [];
$currentUser = $dashboardData['current_user'] ?? [];

// JavaScript específico de la página
$pageScript = "
// Configurar auto-refresh del dashboard
setInterval(function() {
    updateDashboardStats();
}, 60000); // Actualizar cada minuto

function updateDashboardStats() {
    $.ajax({
        url: PLAYMI.baseUrl + 'api/dashboard-stats.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                updateStatsCards(response.stats);
            }
        }
    });
}

function updateStatsCards(stats) {
    // Actualizar tarjetas de estadísticas sin recargar página
    if (stats.companies) {
        $('#total-companies').text(stats.companies.total);
        $('#active-companies').text(stats.companies.active);
        $('#expiring-companies').text(stats.companies.expiring_soon);
    }
    
    if (stats.content) {
        $('#total-content').text(stats.content.total);
        $('#total-movies').text(stats.content.movies);
        $('#total-music').text(stats.content.music);
        $('#total-games').text(stats.content.games);
    }
    
    if (stats.revenue) {
        $('#monthly-revenue').text('S/ ' + parseFloat(stats.revenue.monthly_total).toLocaleString());
    }
}

// Inicializar gráficos cuando el documento esté listo
$(document).ready(function() {
    initializeCharts();
});
";

// Generar contenido del dashboard
ob_start();
?>

<!-- Tarjetas de estadísticas principales -->
<div class="row">
    <!-- Empresas Activas -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="active-companies"><?php echo $stats['companies']['active'] ?? 0; ?></h3>
                <p>Empresas Activas</p>
            </div>
            <div class="icon">
                <i class="fas fa-building"></i>
            </div>
            <a href="<?php echo BASE_URL; ?>views/companies/index.php" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Contenido Total -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="total-content"><?php echo $stats['content']['total'] ?? 0; ?></h3>
                <p>Contenido Disponible</p>
            </div>
            <div class="icon">
                <i class="fas fa-film"></i>
            </div>
            <a href="<?php echo BASE_URL; ?>views/content/index.php" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Licencias por Vencer -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="expiring-companies"><?php echo $stats['companies']['expiring_soon'] ?? 0; ?></h3>
                <p>Próximas a Vencer</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="<?php echo BASE_URL; ?>views/companies/index.php?filter=expiring" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Ingresos Mensuales -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 id="monthly-revenue">S/ <?php echo number_format($stats['revenue']['monthly_total'] ?? 0, 2); ?></h3>
                <p>Ingresos Mensuales</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <a href="<?php echo BASE_URL; ?>views/reports/revenue.php" class="small-box-footer">
                Ver más <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Gráficos y estadísticas detalladas -->
<div class="row">
    <!-- Gráfico de Empresas por Tipo -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie mr-1"></i>
                    Empresas por Tipo de Paquete
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="companiesChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de Contenido por Tipo -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Contenido por Tipo
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="contentChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Actividad reciente y alertas -->
<div class="row">
    <!-- Actividad Reciente -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Actividad Reciente
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Acción</th>
                                <th>Usuario</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentActivity)): ?>
                                <?php foreach (array_slice($recentActivity, 0, 10) as $activity): ?>
                                    <tr>
                                        <td>
                                            <i class="<?php echo $activity['icon']; ?> text-<?php echo $activity['color']; ?> mr-2"></i>
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                            <?php if ($activity['record_name']): ?>
                                                <strong><?php echo htmlspecialchars($activity['record_name']); ?></strong>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['user']); ?></td>
                                        <td>
                                            <small class="text-muted" title="<?php echo $activity['timestamp']; ?>">
                                                <?php echo $activity['time_ago']; ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        No hay actividad reciente
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="<?php echo BASE_URL; ?>views/logs/index.php" class="btn btn-sm btn-primary">
                    Ver todos los logs
                </a>
            </div>
        </div>
    </div>

    <!-- Alertas del Sistema -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Alertas del Sistema
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($systemAlerts)): ?>
                    <?php foreach ($systemAlerts as $alert): ?>
                        <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h6>
                                <i class="<?php echo $alert['icon']; ?> mr-1"></i>
                                <?php echo htmlspecialchars($alert['title']); ?>
                                <?php if (isset($alert['count']) && $alert['count']): ?>
                                    <span class="badge badge-light ml-1"><?php echo $alert['count']; ?></span>
                                <?php endif; ?>
                            </h6>
                            <p class="mb-2"><?php echo htmlspecialchars($alert['message']); ?></p>
                            <?php if ($alert['action_url']): ?>
                                <a href="<?php echo $alert['action_url']; ?>" class="btn btn-<?php echo $alert['type']; ?> btn-sm">
                                    Ver detalles
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-1"></i>
                        <strong>Todo en orden</strong><br>
                        No hay alertas del sistema en este momento.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Información del Sistema
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><strong>Versión:</strong></td>
                        <td><?php echo SYSTEM_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Usuario:</strong></td>
                        <td><?php echo htmlspecialchars($currentUser['username'] ?? 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Último acceso:</strong></td>
                        <td>
                            <?php 
                            if (isset($currentUser['ultimo_acceso']) && $currentUser['ultimo_acceso']) {
                                echo date('d/m/Y H:i', strtotime($currentUser['ultimo_acceso']));
                            } else {
                                echo 'Primer acceso';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Espacio usado:</strong></td>
                        <td>
                            <?php 
                            $totalSpace = disk_total_space(ROOT_PATH);
                            $freeSpace = disk_free_space(ROOT_PATH);
                            if ($totalSpace && $freeSpace) {
                                $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;
                                echo number_format($usedPercentage, 1) . '%';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Resumen de contenido -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-line mr-1"></i>
                    Resumen de Contenido
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-video"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Películas</span>
                                <span class="info-box-number" id="total-movies"><?php echo $stats['content']['movies'] ?? 0; ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-info" style="width: 70%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php 
                                    $totalContent = $stats['content']['total'] ?? 1;
                                    $movies = $stats['content']['movies'] ?? 0;
                                    $percentage = $totalContent > 0 ? round(($movies / $totalContent) * 100, 1) : 0;
                                    echo $percentage;
                                    ?>% del total
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-music"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Música</span>
                                <span class="info-box-number" id="total-music"><?php echo $stats['content']['music'] ?? 0; ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-success" style="width: 20%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php 
                                    $music = $stats['content']['music'] ?? 0;
                                    $percentage = $totalContent > 0 ? round(($music / $totalContent) * 100, 1) : 0;
                                    echo $percentage;
                                    ?>% del total
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-gamepad"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Juegos</span>
                                <span class="info-box-number" id="total-games"><?php echo $stats['content']['games'] ?? 0; ?></span>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" style="width: 10%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php 
                                    $games = $stats['content']['games'] ?? 0;
                                    $percentage = $totalContent > 0 ? round(($games / $totalContent) * 100, 1) : 0;
                                    echo $percentage;
                                    ?>% del total
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-hdd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Tamaño Total</span>
                                <span class="info-box-number">
                                    <?php 
                                    $totalSize = $stats['content']['total_size'] ?? 0;
                                    if ($totalSize > 0) {
                                        $sizeGB = $totalSize / (1024 * 1024 * 1024);
                                        echo number_format($sizeGB, 1) . ' GB';
                                    } else {
                                        echo '0 GB';
                                    }
                                    ?>
                                </span>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" style="width: 50%"></div>
                                </div>
                                <span class="progress-description">
                                    Contenido multimedia
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initializeCharts() {
    // Datos para gráfico de empresas
    const companiesData = <?php 
        $companiesChartData = [];
        if (isset($chartData['companies_by_package']) && is_array($chartData['companies_by_package'])) {
            foreach ($chartData['companies_by_package'] as $item) {
                $companiesChartData[] = $item['cantidad'] ?? 0;
            }
        }
        // Si no hay datos, usar valores por defecto
        if (empty($companiesChartData)) {
            $companiesChartData = [
                $stats['companies']['basico'] ?? 0,
                $stats['companies']['intermedio'] ?? 0, 
                $stats['companies']['premium'] ?? 0
            ];
        }
        echo json_encode($companiesChartData);
    ?>;

    // Gráfico de empresas por tipo de paquete
    const companiesCtx = document.getElementById('companiesChart').getContext('2d');
    new Chart(companiesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Básico', 'Intermedio', 'Premium'],
            datasets: [{
                data: companiesData,
                backgroundColor: [
                    '#28a745',
                    '#ffc107', 
                    '#dc3545'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'bottom'
            }
        }
    });

    // Gráfico de contenido por tipo
    const contentCtx = document.getElementById('contentChart').getContext('2d');
    new Chart(contentCtx, {
        type: 'bar',
        data: {
            labels: ['Películas', 'Música', 'Juegos'],
            datasets: [{
                label: 'Cantidad',
                data: [
                    <?php echo $stats['content']['movies'] ?? 0; ?>,
                    <?php echo $stats['content']['music'] ?? 0; ?>,
                    <?php echo $stats['content']['games'] ?? 0; ?>
                ],
                backgroundColor: [
                    '#17a2b8',
                    '#28a745',
                    '#ffc107'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            legend: {
                display: false
            }
        }
    });
}
</script>

<?php
$content = ob_get_clean();

// CSS adicional para gráficos
$additionalCSS = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css'
];

// JavaScript adicional para gráficos
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];

// Incluir el layout base
include 'views/layouts/base.php';
?>