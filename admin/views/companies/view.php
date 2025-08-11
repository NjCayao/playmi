<?php
/**
 * Ver Detalles de Empresa PLAYMI Admin
 * Página para mostrar información completa de la empresa
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/CompanyController.php';

// Crear instancia del controlador
$companyController = new CompanyController();

// Obtener datos de la empresa
$viewData = $companyController->show();

// Variables para la vista
$pageTitle = 'Detalles de Empresa - PLAYMI Admin';
$contentTitle = 'Detalles de Empresa';
$showContentHeader = true;

// Extraer datos
$company = $viewData['company'] ?? [];
$companyId = $company['id'] ?? 0;
$logs = $viewData['logs'] ?? [];
$stats = $viewData['stats'] ?? [];

// Si no existe la empresa, redirigir
if (!$company) {
    header('Location: ' . BASE_URL . 'views/companies/index.php');
    exit;
}

$contentSubtitle = htmlspecialchars($company['nombre']);

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Empresas', 'url' => BASE_URL . 'views/companies/index.php'],
    ['title' => $company['nombre'], 'url' => '']
];

// Calcular días restantes
$diasRestantes = 0;
if ($company['fecha_vencimiento']) {
    $fechaVencimiento = new DateTime($company['fecha_vencimiento']);
    $fechaActual = new DateTime();
    $interval = $fechaActual->diff($fechaVencimiento);
    $diasRestantes = $fechaVencimiento >= $fechaActual ? $interval->days : -$interval->days;
}

$isExpiring = $diasRestantes <= 30 && $diasRestantes >= 0;
$isExpired = $diasRestantes < 0;

// JavaScript específico de la página
$pageScript = "
// Función para cambiar estado
function changeStatus(newStatus) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: 'Esta acción cambiará el estado de la empresa a: ' + newStatus,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: PLAYMI.baseUrl + 'api/companies/update-status.php',
                method: 'POST',
                data: {
                    company_id: {$companyId},
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function() {
                    toastr.error('Error al cambiar el estado');
                }
            });
        }
    });
}

// Función para extender licencia
function extendLicense() {
    Swal.fire({
        title: 'Extender Licencia',
        input: 'select',
        inputOptions: {
            '1': '1 mes',
            '3': '3 meses',
            '6': '6 meses',
            '12': '12 meses'
        },
        inputPlaceholder: 'Seleccionar duración',
        showCancelButton: true,
        confirmButtonText: 'Extender',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            $.ajax({
                url: PLAYMI.baseUrl + 'api/companies/extend-license.php',
                method: 'POST',
                data: {
                    company_id: {$companyId},
                    months: result.value
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        toastr.error(response.error);
                    }
                },
                error: function() {
                    toastr.error('Error al extender la licencia');
                }
            });
        }
    });
}
";

// Generar contenido
ob_start();
?>

<!-- Información principal -->
<div class="row">
    <div class="col-md-4">
        <!-- Card principal de la empresa -->
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <?php if ($company['logo_path']): ?>
                        <img class="profile-user-img img-fluid img-circle" 
                             src="<?php echo BASE_URL; ?>../companies/data/<?php echo htmlspecialchars($company['logo_path']); ?>" 
                             alt="Logo de <?php echo htmlspecialchars($company['nombre']); ?>"
                             style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <div class="profile-user-img img-fluid img-circle bg-secondary d-inline-flex align-items-center justify-content-center" 
                             style="width: 100px; height: 100px;">
                            <i class="fas fa-building fa-3x text-white"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="profile-username text-center"><?php echo htmlspecialchars($company['nombre']); ?></h3>

                <p class="text-muted text-center">
                    <span class="badge badge-<?php 
                        echo $company['estado'] === 'activo' ? 'success' : 
                            ($company['estado'] === 'suspendido' ? 'warning' : 'danger'); 
                    ?> badge-lg">
                        <?php echo ucfirst($company['estado']); ?>
                    </span>
                </p>

                <!-- Estado de la licencia -->
                <div class="text-center mb-3">
                    <?php if ($isExpired): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>Licencia Vencida</strong><br>
                            Hace <?php echo abs($diasRestantes); ?> día(s)
                        </div>
                    <?php elseif ($isExpiring): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock"></i>
                            <strong>Próxima a Vencer</strong><br>
                            En <?php echo $diasRestantes; ?> día(s)
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <strong>Licencia Vigente</strong><br>
                            <?php echo $diasRestantes; ?> día(s) restantes
                        </div>
                    <?php endif; ?>
                </div>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Tipo de Paquete</b>
                        <span class="float-right">
                            <span class="badge badge-<?php 
                                echo $company['tipo_paquete'] === 'basico' ? 'secondary' : 
                                    ($company['tipo_paquete'] === 'intermedio' ? 'warning' : 'success'); 
                            ?>">
                                <?php echo ucfirst($company['tipo_paquete']); ?>
                            </span>
                        </span>
                    </li>
                    <li class="list-group-item">
                        <b>Total Buses</b>
                        <span class="float-right"><?php echo $company['total_buses'] ?? 'N/A'; ?></span>
                    </li>
                    <li class="list-group-item">
                        <b>Costo Mensual</b>
                        <span class="float-right">S/ <?php echo number_format($company['costo_mensual'], 2); ?></span>
                    </li>
                    <li class="list-group-item">
                        <b>Fecha Inicio</b>
                        <span class="float-right"><?php echo date('d/m/Y', strtotime($company['fecha_inicio'])); ?></span>
                    </li>
                    <li class="list-group-item">
                        <b>Fecha Vencimiento</b>
                        <span class="float-right"><?php echo date('d/m/Y', strtotime($company['fecha_vencimiento'])); ?></span>
                    </li>
                </ul>

                <!-- Acciones -->
                <div class="text-center">
                    <a href="<?php echo BASE_URL; ?>views/companies/edit.php?id=<?php echo $companyId; ?>" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    
                    <?php if ($company['estado'] !== 'activo'): ?>
                        <button class="btn btn-success btn-sm" onclick="changeStatus('activo')">
                            <i class="fas fa-check"></i> Activar
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($company['estado'] === 'activo'): ?>
                        <button class="btn btn-warning btn-sm" onclick="changeStatus('suspendido')">
                            <i class="fas fa-pause"></i> Suspender
                        </button>
                    <?php endif; ?>
                    
                    <?php if ($isExpiring || $isExpired): ?>
                        <button class="btn btn-primary btn-sm" onclick="extendLicense()">
                            <i class="fas fa-calendar-plus"></i> Extender
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información de contacto -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Información de Contacto</h3>
            </div>
            <div class="card-body">
                <strong><i class="fas fa-user mr-1"></i> Persona de Contacto</strong>
                <p class="text-muted">
                    <?php echo htmlspecialchars($company['persona_contacto'] ?? 'No especificado'); ?>
                </p>

                <hr>

                <strong><i class="fas fa-envelope mr-1"></i> Email</strong>
                <p class="text-muted">
                    <a href="mailto:<?php echo htmlspecialchars($company['email_contacto']); ?>">
                        <?php echo htmlspecialchars($company['email_contacto']); ?>
                    </a>
                </p>

                <?php if ($company['telefono']): ?>
                    <hr>
                    <strong><i class="fas fa-phone mr-1"></i> Teléfono</strong>
                    <p class="text-muted">
                        <a href="tel:<?php echo htmlspecialchars($company['telefono']); ?>">
                            <?php echo htmlspecialchars($company['telefono']); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <?php if ($company['nombre_servicio']): ?>
                    <hr>
                    <strong><i class="fas fa-desktop mr-1"></i> Nombre del Servicio</strong>
                    <p class="text-muted"><?php echo htmlspecialchars($company['nombre_servicio']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <!-- Estadísticas de uso -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar mr-1"></i>
                    Estadísticas de Uso
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-info"><i class="fas fa-film"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Contenido</span>
                                <span class="info-box-number"><?php echo $stats['total_content'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-download"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Descargas</span>
                                <span class="info-box-number"><?php echo $stats['total_downloads'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-hdd"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Espacio</span>
                                <span class="info-box-number">
                                    <?php 
                                    $totalSize = $stats['total_size'] ?? 0;
                                    echo $totalSize > 0 ? number_format($totalSize / (1024 * 1024 * 1024), 1) . ' GB' : '0 GB';
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-calendar"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Último Acceso</span>
                                <span class="info-box-number">
                                    <small>
                                        <?php 
                                        echo $stats['last_access'] ? 
                                            date('d/m/Y', strtotime($stats['last_access'])) : 
                                            'Nunca';
                                        ?>
                                    </small>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de branding -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-palette mr-1"></i>
                    Configuración de Branding
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Color Primario</label>
                            <div class="d-flex align-items-center">
                                <div class="color-preview mr-3" 
                                     style="width: 40px; height: 40px; background-color: <?php echo htmlspecialchars($company['color_primario'] ?? '#000000'); ?>; border: 1px solid #ccc; border-radius: 4px;"></div>
                                <code><?php echo htmlspecialchars($company['color_primario'] ?? '#000000'); ?></code>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Color Secundario</label>
                            <div class="d-flex align-items-center">
                                <div class="color-preview mr-3" 
                                     style="width: 40px; height: 40px; background-color: <?php echo htmlspecialchars($company['color_secundario'] ?? '#FFFFFF'); ?>; border: 1px solid #ccc; border-radius: 4px;"></div>
                                <code><?php echo htmlspecialchars($company['color_secundario'] ?? '#FFFFFF'); ?></code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de actividad -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-1"></i>
                    Historial de Actividad
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($logs)): ?>
                    <div class="timeline">
                        <?php foreach (array_slice($logs, 0, 10) as $log): ?>
                            <div class="time-label">
                                <span class="bg-<?php echo $log['color']; ?>">
                                    <?php echo date('d M Y', strtotime($log['created_at'])); ?>
                                </span>
                            </div>
                            <div>
                                <i class="<?php echo $log['icon']; ?> bg-<?php echo $log['color']; ?>"></i>
                                <div class="timeline-item">
                                    <span class="time">
                                        <i class="fas fa-clock"></i> 
                                        <?php echo date('H:i', strtotime($log['created_at'])); ?>
                                    </span>
                                    <h3 class="timeline-header">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </h3>
                                    <div class="timeline-body">
                                        <?php echo htmlspecialchars($log['description']); ?>
                                        <?php if ($log['user']): ?>
                                            <br><small class="text-muted">Por: <?php echo htmlspecialchars($log['user']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div>
                            <i class="fas fa-clock bg-gray"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>No hay actividad registrada para esta empresa</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($logs) > 10): ?>
                <div class="card-footer text-center">
                    <a href="<?php echo BASE_URL; ?>views/logs/index.php?company_id=<?php echo $companyId; ?>" 
                       class="btn btn-sm btn-primary">
                        Ver todo el historial
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Notas adicionales -->
        <?php if ($company['notas']): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sticky-note mr-1"></i>
                        Notas Adicionales
                    </h3>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($company['notas'])); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

// CSS adicional para timeline
$additionalCSS = [
    BASE_URL . 'assets/css/timeline.css'
];

// Incluir el layout base
include '../layouts/base.php';
?>