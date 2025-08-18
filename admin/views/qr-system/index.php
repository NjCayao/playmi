<?php
/**
 * Vista: Lista de Códigos QR
 * Módulo 3.1: Gestión central de códigos QR
 */
require_once '../../config/system.php';
require_once '../../controllers/QRController.php';
$qrController = new QRController();
$data = $qrController->index();
// Extraer datos
$qrCodes = $data['qr_codes'];
$companies = $data['companies'];
$filters = $data['filters'];
$stats = $data['stats'];
$pagination = $data['pagination'];
// Configurar página
$pageTitle = 'Gestión de Códigos QR';
$contentTitle = 'Sistema QR';
$contentSubtitle = 'Gestión de códigos QR para buses';
$breadcrumbs = [
['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
['title' => 'Sistema QR', 'url' => '#']
];
// CSS adicional
$additionalCSS = [
ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.css'
];
// JS adicional
$additionalJS = [
ASSETS_URL . 'plugins/sweetalert2/sweetalert2.min.js',
'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'
];
// Iniciar buffer de contenido
ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Lista de Códigos QR Generados</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Códigos QR</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?php echo number_format($stats['total']); ?></h3>
                <p>Total QR Generados</p>
            </div>
            <div class="icon">
                <i class="fas fa-qrcode"></i>
            </div>
            <a href="#" class="small-box-footer">
                Ver todos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
<div class="col-lg-3 col-6">
    <div class="small-box bg-success">
        <div class="inner">
            <h3><?php echo number_format($stats['active']); ?></h3>
            <p>QR Activos</p>
        </div>
        <div class="icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <a href="?status=activo" class="small-box-footer">
            Ver activos <i class="fas fa-arrow-circle-right"></i>
        </a>
    </div>
</div>

<div class="col-lg-3 col-6">
    <div class="small-box bg-warning">
        <div class="inner">
            <h3><?php echo number_format($stats['today_scans']); ?></h3>
            <p>Escaneos Hoy</p>
        </div>
        <div class="icon">
            <i class="fas fa-mobile-alt"></i>
        </div>
        <a href="#" class="small-box-footer">
            Ver estadísticas <i class="fas fa-arrow-circle-right"></i>
        </a>
    </div>
</div>

<div class="col-lg-3 col-6">
    <div class="small-box bg-primary">
        <div class="inner">
            <h3><?php echo number_format($stats['active_companies']); ?></h3>
            <p>Empresas con QR</p>
        </div>
        <div class="icon">
            <i class="fas fa-building"></i>
        </div>
        <a href="#" class="small-box-footer">
            Ver empresas <i class="fas fa-arrow-circle-right"></i>
        </a>
    </div>
</div>
</div>
<!-- Filtros y acciones -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter"></i> Filtros
        </h3>
        <div class="card-tools">
            <a href="generate.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Generar QR
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="" class="form-inline">
            <div class="form-group mr-3">
                <label for="company_id" class="mr-2">Empresa:</label>
                <select name="company_id" id="company_id" class="form-control">
                    <option value="">Todas las empresas</option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>" 
                                <?php echo ($filters['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($company['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <div class="form-group mr-3">
            <label for="bus_number" class="mr-2">Nº Bus:</label>
            <input type="text" name="bus_number" id="bus_number" 
                   class="form-control" placeholder="Buscar por número"
                   value="<?php echo htmlspecialchars($filters['bus_number'] ?? ''); ?>">
        </div>
        
        <div class="form-group mr-3">
            <label for="status" class="mr-2">Estado:</label>
            <select name="status" id="status" class="form-control">
                <option value="">Todos</option>
                <option value="activo" <?php echo ($filters['status'] == 'activo') ? 'selected' : ''; ?>>
                    Activo
                </option>
                <option value="inactivo" <?php echo ($filters['status'] == 'inactivo') ? 'selected' : ''; ?>>
                    Inactivo
                </option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-info mr-2">
            <i class="fas fa-search"></i> Filtrar
        </button>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-redo"></i> Limpiar
        </a>
    </form>
</div>
</div>
<!-- Lista de QR Codes -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Códigos QR Generados</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th style="width: 50px">ID</th>
                        <th>Empresa</th>
                        <th>Nº Bus</th>
                        <th>SSID WiFi</th>
                        <th>Estado</th>
                        <th>Escaneos</th>
                        <th>Creado</th>
                        <th style="width: 180px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($qrCodes)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <i class="fas fa-info-circle"></i> No se encontraron códigos QR
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($qrCodes as $qr): ?>
                            <tr>
                                <td><?php echo $qr['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($qr['empresa_nombre'] ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($qr['numero_bus']); ?>
                                    </span>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($qr['wifi_ssid']); ?></code>
                                </td>
                                <td>
                                    <?php if ($qr['estado'] == 'activo'): ?>
                                        <span class="badge badge-success">Activo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-muted">
                                        <?php echo number_format($qr['total_escaneos'] ?? 0); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($qr['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="print.php?id=<?php echo $qr['id']; ?>" 
                                           class="btn btn-info" 
                                           data-toggle="tooltip" 
                                           title="Imprimir">
                                            <i class="fas fa-print"></i>
                                        </a>
                                        <a href="<?php echo API_URL; ?>qr/download.php?id=<?php echo $qr['id']; ?>" 
                                           class="btn btn-success" 
                                           data-toggle="tooltip" 
                                           title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>                                        
                                        <button type="button" 
                                                class="btn btn-primary btn-preview" 
                                                data-id="<?php echo $qr['id']; ?>"
                                                data-toggle="tooltip" 
                                                title="Vista previa">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <!-- Paginación -->
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="mt-3">
            <nav aria-label="Paginación de QR codes">
                <ul class="pagination justify-content-center">
                    <!-- Anterior -->
                    <li class="page-item <?php echo !$pagination['has_previous'] ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?page=<?php echo $pagination['previous_page']; ?>&<?php echo http_build_query(array_diff_key($filters, ['page' => ''])); ?>">
                            Anterior
                        </a>
                    </li>
                    
                    <!-- Páginas -->
                    <?php 
                    $start = max(1, $pagination['current_page'] - 2);
                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                    
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <li class="page-item <?php echo $i == $pagination['current_page'] ? 'active' : ''; ?>">
                            <a class="page-link" 
                               href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($filters, ['page' => ''])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <!-- Siguiente -->
                    <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                        <a class="page-link" 
                           href="?page=<?php echo $pagination['next_page']; ?>&<?php echo http_build_query(array_diff_key($filters, ['page' => ''])); ?>">
                            Siguiente
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>
</div>
<!-- Gráfico de actividad (opcional) -->
<?php if (!empty($stats['most_scanned'])): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">QR Más Escaneados</h3>
    </div>
    <div class="card-body">
        <canvas id="scanChart" style="height: 300px;"></canvas>
    </div>
</div>
<?php endif; ?>
<!-- Modal de Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa del QR</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="qrPreviewImage" src="" alt="QR Code" class="img-fluid mb-3">
                <div id="qrInfo"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" id="downloadQRBtn" class="btn btn-success">
                    <i class="fas fa-download"></i> Descargar
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Capturar contenido del buffer
$content = ob_get_clean();

// Incluir layout base
require_once '../layouts/base.php';
?>

<script>
$(document).ready(function() {   
    // Vista previa de QR
    $('.btn-preview').on('click', function() {
        const qrId = $(this).data('id');
        
        // Aquí deberías cargar la información del QR
        $('#qrPreviewImage').attr('src', '<?php echo API_URL; ?>qr/preview.php?id=' + qrId);
        $('#downloadQRBtn').attr('href', '<?php echo API_URL; ?>qr/download.php?id=' + qrId);
        
        $('#previewModal').modal('show');
    });
    
    // Gráfico de escaneos
    <?php if (!empty($stats['most_scanned'])): ?>
    const ctx = document.getElementById('scanChart').getContext('2d');
    const scanData = <?php echo json_encode($stats['most_scanned']); ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: scanData.map(item => item.numero_bus),
            datasets: [{
                label: 'Escaneos',
                data: scanData.map(item => item.scan_count),
                backgroundColor: 'rgba(37, 99, 235, 0.8)',
                borderColor: 'rgba(37, 99, 235, 1)',
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
            }
        }
    });
    <?php endif; ?>
});
</script>
