<?php
/**
 * Página de Login PLAYMI Admin
 * Sistema de autenticación principal
 */

// Incluir configuración del sistema
require_once 'config/system.php';
require_once 'controllers/AuthController.php';

// Crear instancia del controlador de autenticación
$authController = new AuthController();

// Verificar si ya está autenticado
if ($authController->isAuthenticated()) {
    $authController->redirect('index.php');
}

// Verificar cookie "recordar" si existe
$authController->checkRememberMe();

// Procesar formulario de login si es POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $authController->processLogin();
}

// Obtener mensaje flash si existe
$flashMessage = $authController->getMessage();

// Verificar si viene de timeout
$showTimeoutMessage = isset($_GET['timeout']) && $_GET['timeout'] == '1';

// Variables para la página
$pageTitle = 'Iniciar Sesión - PLAYMI Admin';
$pageDescription = 'Accede al panel de administración de PLAYMI Entertainment';
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
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/login.css">
    
    <!-- Preload de recursos críticos -->
    <link rel="preload" href="<?php echo ASSETS_URL; ?>plugins/jquery/jquery.min.js" as="script">
    <link rel="preload" href="<?php echo ASSETS_URL; ?>images/playmi-logo.png" as="image">
</head>
<body class="hold-transition login-page">

<div class="login-box">
    <!-- Logo -->
    <div class="login-logo">
        <img src="<?php echo ASSETS_URL; ?>images/playmi-logo.png" alt="PLAYMI Logo" class="login-logo-img">
        <br>
        <a href="<?php echo BASE_URL; ?>"><strong>PLAYMI</strong> Admin</a>
    </div>
    
    <!-- Card de Login -->
    <div class="card card-outline card-primary">
        <div class="card-header text-center">
            <h1 class="h4">Iniciar Sesión</h1>
        </div>
        
        <div class="card-body login-card-body">
            <p class="login-box-msg">Inicia sesión para acceder al panel de administración</p>

            <!-- Mostrar mensaje de timeout si aplica -->
            <?php if ($showTimeoutMessage): ?>
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-clock"></i>
                    Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.
                </div>
            <?php endif; ?>

            <!-- Mostrar mensaje flash si existe -->
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type'] === MSG_SUCCESS ? 'success' : ($flashMessage['type'] === MSG_ERROR ? 'danger' : ($flashMessage['type'] === MSG_WARNING ? 'warning' : 'info')); ?> alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <i class="icon fas fa-<?php echo $flashMessage['type'] === MSG_SUCCESS ? 'check' : ($flashMessage['type'] === MSG_ERROR ? 'ban' : ($flashMessage['type'] === MSG_WARNING ? 'exclamation-triangle' : 'info')); ?>"></i>
                    <?php echo htmlspecialchars($flashMessage['text']); ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form id="loginForm" method="post" novalidate>
                <div class="input-group mb-3">
                    <input type="text" 
                           name="username" 
                           id="username" 
                           class="form-control" 
                           placeholder="Usuario" 
                           required 
                           autocomplete="username"
                           autofocus>
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-user"></span>
                        </div>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="input-group mb-3">
                    <input type="password" 
                           name="password" 
                           id="password" 
                           class="form-control" 
                           placeholder="Contraseña" 
                           required 
                           autocomplete="current-password">
                    <div class="input-group-append">
                        <div class="input-group-text">
                            <span class="fas fa-lock"></span>
                        </div>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="row">
                    <div class="col-8">
                        <div class="icheck-primary">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            <label for="remember">
                                Recordarme
                            </label>
                        </div>
                    </div>
                    
                    <div class="col-4">
                        <button type="submit" id="loginBtn" class="btn btn-primary btn-block">
                            <span class="btn-text">Ingresar</span>
                            <span class="btn-loading d-none">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Enlaces adicionales -->
            <div class="mt-3 text-center">
                <small class="text-muted">
                    ¿Problemas para acceder? 
                    <a href="mailto:soporte@playmi.com" class="text-primary">Contactar soporte</a>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Footer del login -->
    <div class="login-footer">
        <p class="mb-1">
            <small>&copy; <?php echo date('Y'); ?> PLAYMI Entertainment. Todos los derechos reservados.</small>
        </p>
        <p class="mb-0">
            <small class="text-muted">Versión <?php echo SYSTEM_VERSION ?? '1.0.0'; ?></small>
        </p>
    </div>
</div>

<!-- JavaScript Core -->
<script src="<?php echo ASSETS_URL; ?>plugins/jquery/jquery.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo ASSETS_URL; ?>js/adminlte.min.js"></script>

<script>
$(document).ready(function() {
    // Configuración global
    window.PLAYMI = {
        baseUrl: '<?php echo BASE_URL; ?>',
        assetsUrl: '<?php echo ASSETS_URL; ?>'
    };
    
    // Inicializar formulario de login
    initLoginForm();
    
    // Auto-focus en el primer campo
    $('#username').focus();
    
    // Limpiar mensajes después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});

/**
 * Inicializar formulario de login
 */
function initLoginForm() {
    const form = $('#loginForm');
    const loginBtn = $('#loginBtn');
    const btnText = loginBtn.find('.btn-text');
    const btnLoading = loginBtn.find('.btn-loading');
    
    // Validación en tiempo real
    form.find('input[required]').on('blur', function() {
        validateField($(this));
    });
    
    // Envío del formulario
    form.on('submit', function(e) {
        e.preventDefault();
        
        // Validar formulario
        if (!validateForm()) {
            return false;
        }
        
        // Mostrar loading
        showLoading(true);
        
        // Enviar por AJAX
        $.ajax({
            url: window.location.href,
            method: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showMessage('Login exitoso. Redirigiendo...', 'success');
                    
                    // Redirigir después de 1 segundo
                    setTimeout(function() {
                        window.location.href = response.redirect || 'index.php';
                    }, 1000);
                } else {
                    showMessage(response.error || 'Error en el login', 'error');
                    showLoading(false);
                    
                    // Mostrar errores específicos si existen
                    if (response.errors) {
                        showFormErrors(response.errors);
                    }
                }
            },
            error: function(xhr) {
                let message = 'Error interno del servidor';
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    message = xhr.responseJSON.error;
                }
                
                showMessage(message, 'error');
                showLoading(false);
            }
        });
    });
    
    /**
     * Mostrar/ocultar loading en botón
     */
    function showLoading(show) {
        if (show) {
            btnText.addClass('d-none');
            btnLoading.removeClass('d-none');
            loginBtn.prop('disabled', true);
        } else {
            btnText.removeClass('d-none');
            btnLoading.addClass('d-none');
            loginBtn.prop('disabled', false);
        }
    }
}

/**
 * Validar formulario completo
 */
function validateForm() {
    let isValid = true;
    
    $('#loginForm input[required]').each(function() {
        if (!validateField($(this))) {
            isValid = false;
        }
    });
    
    return isValid;
}

/**
 * Validar campo individual
 */
function validateField(field) {
    const value = field.val().trim();
    const fieldName = field.attr('name');
    
    // Limpiar clases previas
    field.removeClass('is-valid is-invalid');
    field.siblings('.invalid-feedback').text('');
    
    // Validación por campo
    switch (fieldName) {
        case 'username':
            if (!value) {
                showFieldError(field, 'El usuario es requerido');
                return false;
            } else if (value.length < 3) {
                showFieldError(field, 'El usuario debe tener al menos 3 caracteres');
                return false;
            }
            break;
            
        case 'password':
            if (!value) {
                showFieldError(field, 'La contraseña es requerida');
                return false;
            } else if (value.length < 3) {
                showFieldError(field, 'La contraseña debe tener al menos 3 caracteres');
                return false;
            }
            break;
    }
    
    // Si llegamos aquí, el campo es válido
    field.addClass('is-valid');
    return true;
}

/**
 * Mostrar error en campo
 */
function showFieldError(field, message) {
    field.addClass('is-invalid');
    field.siblings('.invalid-feedback').text(message);
}

/**
 * Mostrar errores específicos del formulario
 */
function showFormErrors(errors) {
    Object.keys(errors).forEach(function(fieldName) {
        const field = $(`[name="${fieldName}"]`);
        if (field.length) {
            showFieldError(field, errors[fieldName]);
        }
    });
}

/**
 * Mostrar mensaje de notificación
 */
function showMessage(message, type) {
    // Remover alertas existentes
    $('.alert').remove();
    
    // Crear nueva alerta
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const iconClass = type === 'success' ? 'fa-check' : 'fa-exclamation-triangle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <i class="icon fas ${iconClass}"></i>
            ${message}
        </div>
    `;
    
    $('.login-box-msg').after(alertHtml);
    
    // Auto-ocultar después de 5 segundos
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}

// Detectar Enter en campos
$('#loginForm input').on('keypress', function(e) {
    if (e.which === 13) { // Enter
        $('#loginForm').submit();
    }
});

// Prevenir envío múltiple
let isSubmitting = false;
$('#loginForm').on('submit', function() {
    if (isSubmitting) {
        return false;
    }
    isSubmitting = true;
    
    // Resetear después de 3 segundos
    setTimeout(function() {
        isSubmitting = false;
    }, 3000);
});

// Efecto de enfoque en campos
$('.form-control').on('focus', function() {
    $(this).parent().addClass('input-focus');
}).on('blur', function() {
    $(this).parent().removeClass('input-focus');
});
</script>

<!-- Estilos adicionales inline para optimización -->
<style>
.input-focus {
    transform: scale(1.02);
    transition: transform 0.2s ease;
}

.login-logo-img {
    max-width: 80px;
    margin-bottom: 10px;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

.btn-loading .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .login-box {
        width: 90%;
    }
    
    .login-logo a {
        font-size: 1.5rem;
    }
}
</style>

</body>
</html>