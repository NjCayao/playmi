<?php

/**
 * Lista de Empresas PLAYMI Admin
 * Página principal para gestionar empresas
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/CompanyController.php';

// Crear instancia del controlador
$companyController = new CompanyController();

// Obtener datos de empresas con filtros
$companiesData = $companyController->index();

// Variables para la vista
$pageTitle = 'Gestión de Empresas - PLAYMI Admin';
$contentTitle = 'Gestión de Empresas';
$contentSubtitle = 'Administrar empresas clientes del sistema';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Empresas', 'url' => BASE_URL . 'views/companies/index.php']
];

// Extraer datos
$companies = $companiesData['companies'] ?? [];
$pagination = $companiesData['pagination'] ?? [];
$filters = $companiesData['filters'] ?? [];
$stats = $companiesData['stats'] ?? [];

// JavaScript específico de la página
$pageScript = "

// Función para cambiar estado de empresa
function changeStatus(companyId, newStatus) {
    Swal.fire({
        title: '¿Cambiar estado?',
        text: 'Esta acción cambiará el estado de la empresa',
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
                    company_id: companyId,
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
function extendLicense(companyId) {
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
                    company_id: companyId,
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

// Función para exportar datos
function exportData(format) {
    const table = $('#companiesTable').DataTable();
    
    if (format === 'excel') {
        table.button('.buttons-excel').trigger();
    } else if (format === 'pdf') {
        table.button('.buttons-pdf').trigger();
    }
}

// Confirmar eliminación
$(document).on('click', '.btn-delete', function(e) {
    e.preventDefault();
    const url = $(this).attr('href');
    const message = $(this).data('message');
    
    Swal.fire({
        title: '¿Eliminar empresa?',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
});

// Función para import masivo
function showImportModal() {
    $('#importModal').modal('show');
}

// Función para generar reporte
function generateReport() {
    window.open(PLAYMI.baseUrl + 'views/companies/report.php', '_blank');
}

// Función para filtros avanzados
function showAdvancedFilters() {
    $('#advancedFiltersCollapse').collapse('toggle');
}
";

// Generar contenido
ob_start();
?>

<!-- Estadísticas rápidas -->
<div class="row mb-3">
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-building"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total</span>
                <span class="info-box-number"><?php echo $stats['total'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Activas</span>
                <span class="info-box-number"><?php echo $stats['active'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-pause-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Suspendidas</span>
                <span class="info-box-number"><?php echo $stats['suspended'] ?? 0; ?></span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6 col-12">
        <div class="info-box">
            <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Vencidas</span>
                <span class="info-box-number"><?php echo $stats['expired'] ?? 0; ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y acciones -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-filter mr-1"></i>
            Filtros y Búsqueda
        </h3>
        <div class="card-tools">
            <a href="<?php echo BASE_URL; ?>views/companies/create.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nueva Empresa
            </a>

            <button type="button" class="btn btn-info btn-sm" onclick="showImportModal()">
                <i class="fas fa-upload"></i> Importar
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="generateReport()">
                <i class="fas fa-file-pdf"></i> Reporte
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="search">Buscar:</label>
                    <input type="text"
                        class="form-control"
                        id="search"
                        name="search"
                        placeholder="Nombre o email..."
                        value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="estado">Estado:</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?php echo ($filters['estado'] ?? '') === 'activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="suspendido" <?php echo ($filters['estado'] ?? '') === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                        <option value="vencido" <?php echo ($filters['estado'] ?? '') === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label for="tipo_paquete">Paquete:</label>
                    <select class="form-control" id="tipo_paquete" name="tipo_paquete">
                        <option value="">Todos</option>
                        <option value="basico" <?php echo ($filters['tipo_paquete'] ?? '') === 'basico' ? 'selected' : ''; ?>>Básico</option>
                        <option value="intermedio" <?php echo ($filters['tipo_paquete'] ?? '') === 'intermedio' ? 'selected' : ''; ?>>Intermedio</option>
                        <option value="premium" <?php echo ($filters['tipo_paquete'] ?? '') === 'premium' ? 'selected' : ''; ?>>Premium</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input"
                            type="checkbox"
                            id="proximas_vencer"
                            name="proximas_vencer"
                            value="1"
                            <?php echo isset($filters['proximas_vencer']) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="proximas_vencer">
                            Próximas a vencer
                        </label>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                        <a href="<?php echo BASE_URL; ?>views/companies/index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de empresas -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-building mr-1"></i>
            Lista de Empresas
        </h3>
        <div class="card-tools">
            <span class="badge badge-secondary">
                <?php echo count($companies); ?> empresa(s)
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Paquete</th>
                        <th>Vencimiento</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($companies)): ?>
                        <?php foreach ($companies as $company): ?>
                            <?php
                            // Calcular días restantes
                            $diasRestantes = isset($company['dias_restantes']) ? $company['dias_restantes'] : 0;
                            $isExpiring = $diasRestantes <= 30 && $diasRestantes >= 0;
                            $isExpired = $diasRestantes < 0;
                            ?>
                            <tr class="<?php echo $isExpiring ? 'table-warning' : ($isExpired ? 'table-danger' : ''); ?>">
                                <td class="text-center">
                                    <?php if ($company['logo_path']): ?>
                                        <img src="<?php echo BASE_URL; ?>../companies/data/<?php echo htmlspecialchars($company['logo_path']); ?>"
                                            alt="Logo"
                                            class="img-circle"
                                            style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center"
                                            style="width: 40px; height: 40px;">
                                            <i class="fas fa-building text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($company['nombre']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo $company['total_buses']; ?> buses
                                    </small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($company['persona_contacto'] ?? 'N/A'); ?></div>
                                    <small class="text-muted">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($company['email_contacto']); ?>
                                    </small>
                                    <?php if ($company['telefono']): ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($company['telefono']); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php
                                                                echo $company['tipo_paquete'] === 'basico' ? 'secondary' : ($company['tipo_paquete'] === 'intermedio' ? 'warning' : 'success');
                                                                ?>">
                                        <?php echo ucfirst($company['tipo_paquete']); ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        S/ <?php echo number_format($company['costo_mensual'], 2); ?>/mes
                                    </small>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($company['fecha_vencimiento'])); ?></div>
                                    <?php if ($isExpired): ?>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Vencida hace <?php echo abs($diasRestantes); ?> día(s)
                                        </small>
                                    <?php elseif ($isExpiring): ?>
                                        <small class="text-warning">
                                            <i class="fas fa-clock"></i>
                                            Vence en <?php echo $diasRestantes; ?> día(s)
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">
                                            Vence en <?php echo $diasRestantes; ?> día(s)
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <span class="badge badge-<?php
                                                                    echo $company['estado'] === 'activo' ? 'success' : ($company['estado'] === 'suspendido' ? 'warning' : 'danger');
                                                                    ?> dropdown-toggle"
                                            data-toggle="dropdown"
                                            style="cursor: pointer;">
                                            <?php echo ucfirst($company['estado']); ?>
                                        </span>
                                        <div class="dropdown-menu">
                                            <?php if ($company['estado'] !== 'activo'): ?>
                                                <a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $company['id']; ?>, 'activo')">
                                                    <i class="fas fa-check text-success"></i> Activar
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($company['estado'] !== 'suspendido'): ?>
                                                <a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $company['id']; ?>, 'suspendido')">
                                                    <i class="fas fa-pause text-warning"></i> Suspender
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($company['estado'] !== 'vencido'): ?>
                                                <a class="dropdown-item" href="#" onclick="changeStatus(<?php echo $company['id']; ?>, 'vencido')">
                                                    <i class="fas fa-times text-danger"></i> Marcar Vencida
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo BASE_URL; ?>views/companies/view.php?id=<?php echo $company['id']; ?>"
                                            class="btn btn-info btn-sm"
                                            title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>views/companies/edit.php?id=<?php echo $company['id']; ?>"
                                            class="btn btn-warning btn-sm"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($isExpiring || $isExpired): ?>
                                            <button type="button"
                                                class="btn btn-success btn-sm"
                                                onclick="extendLicense(<?php echo $company['id']; ?>)"
                                                title="Extender licencia">
                                                <i class="fas fa-calendar-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="<?php echo BASE_URL; ?>api/companies/delete.php?id=<?php echo $company['id']; ?>"
                                            class="btn btn-danger btn-sm btn-delete"
                                            title="Eliminar"
                                            data-message="¿Está seguro que desea eliminar la empresa '<?php echo htmlspecialchars($company['nombre']); ?>'?">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                No se encontraron empresas con los filtros aplicados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (!empty($companies)): ?>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <small class="text-muted">
                        Mostrando <?php echo count($companies); ?> de <?php echo $pagination['total']; ?> empresas
                        (Página <?php echo $pagination['current_page']; ?> de <?php echo $pagination['total_pages']; ?>)
                    </small>
                </div>
                <div class="col-sm-6">
                    <div class="float-right">
                        <!-- Paginación -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                            <nav aria-label="Paginación de empresas">
                                <ul class="pagination pagination-sm mb-0">
                                    <!-- Anterior -->
                                    <?php if ($pagination['has_previous']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['previous_page']])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-left"></i></span>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Números de página -->
                                    <?php
                                    $start = max(1, $pagination['current_page'] - 2);
                                    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);

                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                        <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Siguiente -->
                                    <?php if ($pagination['has_next']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $pagination['next_page']])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li class="page-item disabled">
                                            <span class="page-link"><i class="fas fa-chevron-right"></i></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                        <a href="<?php echo BASE_URL; ?>views/companies/create.php" class="btn btn-primary btn-sm ml-2">
                            <i class="fas fa-plus"></i> Nueva Empresa
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


<!-- Modal para import masivo -->
<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Empresas</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Archivo CSV</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" name="csv_file" accept=".csv" required>
                            <label class="custom-file-label">Seleccionar archivo CSV...</label>
                        </div>
                        <small class="text-muted">
                            Formato: nombre,email_contacto,tipo_paquete,fecha_inicio,fecha_vencimiento
                        </small>
                    </div>
                    <div class="form-group">
                        <a href="<?php echo BASE_URL; ?>assets/templates/empresas_template.csv" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download"></i> Descargar Template
                        </a>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Importar</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
$content = ob_get_clean();



// Incluir el layout base
include '../layouts/base.php';
?>