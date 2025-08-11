<?php
/**
 * Navbar PLAYMI Admin
 * Barra de navegación superior
 */

// Obtener notificaciones y estadísticas rápidas
$pendingNotifications = 0; // Se calculará con los controladores correspondientes
$systemAlerts = []; // Se calculará con los controladores correspondientes

try {
    // Obtener empresas próximas a vencer
    require_once __DIR__ . '/../../models/Company.php';
    $companyModel = new Company();
    $expiringCompanies = $companyModel->getExpiringCompanies();
    $pendingNotifications = count($expiringCompanies);
} catch (Exception $e) {
    // Si hay error, no mostrar notificaciones
    $pendingNotifications = 0;
}
?>

<!-- Navbar -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo BASE_URL; ?>index.php" class="nav-link">
                <i class="fas fa-home"></i> Inicio
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo BASE_URL; ?>views/companies/index.php" class="nav-link">
                <i class="fas fa-building"></i> Empresas
            </a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        
        <!-- Notifications Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Notificaciones">
                <i class="far fa-bell"></i>
                <?php if ($pendingNotifications > 0): ?>
                    <span class="badge badge-warning navbar-badge"><?php echo $pendingNotifications; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    <?php echo $pendingNotifications; ?> Notificaciones
                </span>
                
                <?php if (!empty($expiringCompanies)): ?>
                    <div class="dropdown-divider"></div>
                    <a href="<?php echo BASE_URL; ?>views/companies/index.php?filter=expiring" class="dropdown-item">
                        <i class="fas fa-exclamation-triangle mr-2 text-warning"></i>
                        <?php echo count($expiringCompanies); ?> empresas próximas a vencer
                        <span class="float-right text-muted text-sm">Hoy</span>
                    </a>
                <?php endif; ?>
                
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item dropdown-footer">Ver todas las notificaciones</a>
            </div>
        </li>

        <!-- Quick Actions -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#" title="Acciones rápidas">
                <i class="fas fa-plus-circle"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Acciones Rápidas</span>
                <div class="dropdown-divider"></div>
                
                <a href="<?php echo BASE_URL; ?>views/companies/create.php" class="dropdown-item">
                    <i class="fas fa-building mr-2 text-info"></i>
                    Nueva Empresa
                </a>
                
                <a href="<?php echo BASE_URL; ?>views/content/upload.php" class="dropdown-item">
                    <i class="fas fa-upload mr-2 text-success"></i>
                    Subir Contenido
                </a>
                
                <a href="<?php echo BASE_URL; ?>views/packages/generate.php" class="dropdown-item">
                    <i class="fas fa-box mr-2 text-primary"></i>
                    Generar Paquete
                </a>
            </div>
        </li>

        <!-- User Account Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown" title="Perfil de usuario">
                <img src="<?php echo ASSETS_URL; ?>images/default-avatar.png" 
                     class="user-image img-circle elevation-2" 
                     alt="<?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>">
                <span class="d-none d-md-inline">
                    <?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <!-- User image -->
                <li class="user-header bg-primary">
                    <img src="<?php echo ASSETS_URL; ?>images/default-avatar.png" 
                         class="img-circle elevation-2" 
                         alt="<?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>">
                    <p>
                        <?php echo htmlspecialchars($currentUser['nombre_completo'] ?? $currentUser['username']); ?>
                        <small><?php echo htmlspecialchars($currentUser['email']); ?></small>
                        <small>Miembro desde <?php echo date('M Y', strtotime($currentUser['created_at'] ?? 'now')); ?></small>
                    </p>
                </li>
                
                <!-- Menu Footer-->
                <li class="user-footer">
                    <a href="#" class="btn btn-default btn-flat" onclick="changePassword()">
                        <i class="fas fa-key"></i> Cambiar Contraseña
                    </a>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-default btn-flat float-right">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </li>

        <!-- Fullscreen Toggle -->
        <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button" title="Pantalla completa">
                <i class="fas fa-expand-arrows-alt"></i>
            </a>
        </li>

        <!-- Settings -->
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button" title="Configuración">
                <i class="fas fa-cogs"></i>
            </a>
        </li>
    </ul>
</nav>

<!-- Modal para cambiar contraseña -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Contraseña</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="changePasswordForm" class="ajax-form needs-validation" action="<?php echo BASE_URL; ?>api/change-password.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_password">Contraseña Actual</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <small class="text-muted">Mínimo 6 caracteres</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function changePassword() {
    $('#changePasswordModal').modal('show');
}

// Validar que las contraseñas coincidan
$('#confirm_password').on('keyup', function() {
    const newPassword = $('#new_password').val();
    const confirmPassword = $(this).val();
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Las contraseñas no coinciden');
    } else {
        this.setCustomValidity('');
    }
});
</script>