<?php
/**
 * Layout Base PLAYMI Admin
 * Plantilla común para todas las páginas del admin
 */

// Requerir configuración y funciones si no están cargadas
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/system.php';
}
if (!class_exists('BaseController')) {
    require_once __DIR__ . '/../../controllers/BaseController.php';
}

// Crear instancia del controlador base para funciones comunes
$baseController = new BaseController();
$baseController->requireAuth(); // Requerir autenticación

// Obtener usuario actual
$currentUser = $baseController->getCurrentUser();

// Obtener mensaje flash si existe
$flashMessage = $baseController->getMessage();

// Variables por defecto que pueden ser sobrescritas
$pageTitle = $pageTitle ?? 'PLAYMI Admin';
$pageDescription = $pageDescription ?? 'Panel de administración PLAYMI Entertainment';
$bodyClass = $bodyClass ?? 'hold-transition sidebar-mini layout-fixed';
$additionalCSS = $additionalCSS ?? [];
$additionalJS = $additionalJS ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>images/favicon.png">
    
    <!-- CSS Core -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/adminlte.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/custom-admin.css">
    
    <!-- DataTables -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/sweetalert2/sweetalert2.min.css">
    
    <!-- Toastr -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>plugins/toastr/toastr.min.css">
    
    <!-- CSS Adicional -->
    <?php if (!empty($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <style>
        /* Estilos inline críticos */
        .content-wrapper { min-height: calc(100vh - 57px); }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="<?php echo $bodyClass; ?>">

<!-- Loading Overlay -->
<div id="loading-overlay" class="loading-overlay">
    <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
        <div class="mt-2">Cargando...</div>
    </div>
</div>

<div class="wrapper">
    
    <!-- Navbar -->
    <?php include __DIR__ . '/navbar.php'; ?>
    
    <!-- Main Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        
        <!-- Content Header (Page header) -->
        <?php if (isset($showContentHeader) && $showContentHeader !== false): ?>
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1 class="m-0"><?php echo $contentTitle ?? $pageTitle; ?></h1>
                            <?php if (isset($contentSubtitle)): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($contentSubtitle); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                                    <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                                        <?php if ($index === array_key_last($breadcrumbs)): ?>
                                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($breadcrumb['title']); ?></li>
                                        <?php else: ?>
                                            <li class="breadcrumb-item">
                                                <a href="<?php echo htmlspecialchars($breadcrumb['url']); ?>">
                                                    <?php echo htmlspecialchars($breadcrumb['title']); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Inicio</a></li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                <?php endif; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                
                <!-- Mostrar mensaje flash si existe -->
                <?php if ($flashMessage): ?>
                    <div class="alert alert-<?php echo $flashMessage['type'] === MSG_SUCCESS ? 'success' : ($flashMessage['type'] === MSG_ERROR ? 'danger' : ($flashMessage['type'] === MSG_WARNING ? 'warning' : 'info')); ?> alert-dismissible fade show">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <i class="icon fas fa-<?php echo $flashMessage['type'] === MSG_SUCCESS ? 'check' : ($flashMessage['type'] === MSG_ERROR ? 'ban' : ($flashMessage['type'] === MSG_WARNING ? 'exclamation-triangle' : 'info')); ?>"></i>
                        <?php echo htmlspecialchars($flashMessage['text']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Contenido principal de la página -->
                <?php if (isset($content)): ?>
                    <?php echo $content; ?>
                <?php endif; ?>
                
            </div>
        </section>
    </div>
    
    <!-- Footer -->
    <?php include __DIR__ . '/footer.php'; ?>
    
</div>

<!-- JavaScript Core -->
<script src="<?php echo ASSETS_URL; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>js/adminlte.min.js"></script>

<!-- DataTables -->
<script src="<?php echo ASSETS_URL; ?>plugins/datatables/jquery.dataTables.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/datatables-buttons/js/buttons.print.min.js"></script>

<!-- SweetAlert2 -->
<script src="<?php echo ASSETS_URL; ?>plugins/sweetalert2/sweetalert2.min.js"></script>

<!-- Toastr -->
<script src="<?php echo ASSETS_URL; ?>plugins/toastr/toastr.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?php echo ASSETS_URL; ?>js/admin-functions.js"></script>

<!-- JavaScript Adicional -->
<?php if (!empty($additionalJS)): ?>
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
// Configuración global de JavaScript
window.PLAYMI = window.PLAYMI || {};
window.PLAYMI.baseUrl = '<?php echo BASE_URL; ?>';
window.PLAYMI.assetsUrl = '<?php echo ASSETS_URL; ?>';
window.PLAYMI.currentUser = <?php echo json_encode([
    'id' => $currentUser['id'] ?? null,
    'username' => $currentUser['username'] ?? null,
    'email' => $currentUser['email'] ?? null,
    'nombre_completo' => $currentUser['nombre_completo'] ?? null
]); ?>;

// Ejecutar cuando el documento esté listo
$(document).ready(function() {
    // Ocultar loading overlay
    $('#loading-overlay').hide();
    
    // Configurar tooltips globalmente
    $('[data-toggle="tooltip"]').tooltip();
    
    // Configurar confirmaciones de eliminación
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const message = $(this).data('message') || '¿Está seguro que desea eliminar este elemento?';
        
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Confirmar eliminación?',
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
        } else {
            if (confirm(message)) {
                window.location.href = url;
            }
        }
    });
    
    // Auto-ocultar alertas después de 5 segundos
    setTimeout(function() {
        $('.alert').not('.alert-important').fadeOut();
    }, 5000);
});

// JavaScript específico de la página
<?php if (isset($pageScript)): ?>
    <?php echo $pageScript; ?>
<?php endif; ?>
</script>

</body>
</html>