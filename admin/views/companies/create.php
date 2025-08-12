<?php
/**
 * Crear Nueva Empresa PLAYMI Admin
 * Formulario para registrar nueva empresa
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/CompanyController.php';

// Crear instancia del controlador
$companyController = new CompanyController();

// Obtener datos para el formulario
$formData = $companyController->create();

// Variables para la vista
$pageTitle = 'Nueva Empresa - PLAYMI Admin';
$contentTitle = 'Nueva Empresa';
$contentSubtitle = 'Registrar una nueva empresa cliente';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Empresas', 'url' => BASE_URL . 'views/companies/index.php'],
    ['title' => 'Nueva Empresa', 'url' => BASE_URL . 'views/companies/create.php']
];

// JavaScript específico de la página
$pageScript = "
// Validación del formulario
$('#companyForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validar campos requeridos
    let isValid = true;
    $(this).find('[required]').each(function() {
        if (!$(this).val().trim()) {
            isValid = false;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    if (!isValid) {
        toastr.error('Por favor complete todos los campos requeridos');
        return;
    }
    
    // Enviar formulario por AJAX
    const formData = new FormData(this);
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/companies/create.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('#submitBtn').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Creando...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.href = response.redirect || PLAYMI.baseUrl + 'views/companies/index.php';
                }, 1500);
            } else {
                toastr.error(response.error);
                if (response.errors) {
                    showFormErrors(response.errors);
                }
            }
        },
        error: function(xhr) {
            toastr.error('Error interno del servidor');
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html('<i class=\"fas fa-save\"></i> Crear Empresa');
        }
    });
});

// Función para mostrar errores específicos
function showFormErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = $('[name=\"' + field + '\"]');
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').remove();
        input.after('<div class=\"invalid-feedback\">' + errors[field] + '</div>');
    });
}

// Validación del RUC
$('#ruc').on('input', function() {
    const ruc = $(this).val();
    $(this).val(ruc.replace(/[^0-9]/g, '')); // Solo números
    
    if (ruc.length === 11) {
        validateRuc(ruc);
    }
});

function validateRuc(ruc) {
    // Validación básica del RUC peruano
    if (!/^[0-9]{11}$/.test(ruc)) {
        $('#ruc').addClass('is-invalid');
        return false;
    }
    
    const tipoEmpresa = ruc.substring(0, 2);
    if (!['10', '15', '17', '20'].includes(tipoEmpresa)) {
        $('#ruc').addClass('is-invalid');
        $('#ruc-feedback').text('RUC inválido: debe comenzar con 10, 15, 17 o 20');
        return false;
    }
    
    $('#ruc').removeClass('is-invalid').addClass('is-valid');
    return true;
}

// Calcular fecha de vencimiento automáticamente
$('#fecha_inicio, #duracion_meses').on('change', function() {
    const fechaInicio = $('#fecha_inicio').val();
    const duracionMeses = $('#duracion_meses').val();
    
    if (fechaInicio && duracionMeses) {
        const fecha = new Date(fechaInicio);
        fecha.setMonth(fecha.getMonth() + parseInt(duracionMeses));
        
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0');
        const day = String(fecha.getDate()).padStart(2, '0');
        
        $('#fecha_vencimiento').val(year + '-' + month + '-' + day);
    }
});

// Calcular costo según tipo de paquete
$('#tipo_paquete').on('change', function() {
    const precios = {
        'basico': " . DEFAULT_BASIC_PRICE . ",
        'intermedio': " . DEFAULT_INTERMEDIATE_PRICE . ",
        'premium': " . DEFAULT_PREMIUM_PRICE . "
    };
    
    const selectedType = $(this).val();
    if (selectedType && precios[selectedType]) {
        $('#costo_mensual').val(precios[selectedType].toFixed(2));
    }
});

// Validación en tiempo real
$('.form-control').on('blur', function() {
    const field = $(this);
    const value = field.val().trim();
    
    if (field.prop('required') && !value) {
        field.addClass('is-invalid');
    } else if (field.attr('type') === 'email' && value && !isValidEmail(value)) {
        field.addClass('is-invalid');
    } else {
        field.removeClass('is-invalid').addClass('is-valid');
    }
});

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}
";

// Generar contenido
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-plus mr-1"></i>
                    Información de la Empresa
                </h3>
            </div>
            
            <form id="companyForm" enctype="multipart/form-data">
                <div class="card-body">
                    <div class="row">
                        <!-- Información básica -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nombre">Nombre de la Empresa <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="nombre" 
                                       name="nombre" 
                                       required 
                                       maxlength="100"
                                       placeholder="Ingrese el nombre de la empresa">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ruc">RUC <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ruc" 
                                       name="ruc" 
                                       required
                                       maxlength="11"
                                       pattern="[0-9]{11}"
                                       placeholder="20123456789"
                                       data-msg-pattern="El RUC debe tener 11 dígitos numéricos">
                                <div class="invalid-feedback" id="ruc-feedback">
                                    El RUC debe tener 11 dígitos numéricos
                                </div>
                                <small class="text-muted">RUC de 11 dígitos de la empresa</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_contacto">Email de Contacto <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email_contacto" 
                                       name="email_contacto" 
                                       required 
                                       maxlength="100"
                                       placeholder="contacto@empresa.com">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="telefono">Teléfono</label>
                                <input type="tel" 
                                       class="form-control" 
                                       id="telefono" 
                                       name="telefono" 
                                       maxlength="20"
                                       placeholder="+51 999 999 999">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="persona_contacto">Persona de Contacto</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="persona_contacto" 
                                       name="persona_contacto" 
                                       maxlength="100"
                                       placeholder="Nombre del responsable">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logo y branding -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="logo">Logo de la Empresa</label>
                                <div class="custom-file">
                                    <input type="file" 
                                           class="custom-file-input" 
                                           id="logo" 
                                           name="logo" 
                                           accept="image/*">
                                    <label class="custom-file-label" for="logo">Seleccionar archivo...</label>
                                </div>
                                <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 10MB</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="color_primario">Color Primario</label>
                                <input type="color" 
                                       class="form-control" 
                                       id="color_primario" 
                                       name="color_primario" 
                                       value="#000000"
                                       style="height: 38px;">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="color_secundario">Color Secundario</label>
                                <input type="color" 
                                       class="form-control" 
                                       id="color_secundario" 
                                       name="color_secundario" 
                                       value="#FFFFFF"
                                       style="height: 38px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre_servicio">Nombre del Servicio</label>
                        <input type="text" 
                               class="form-control" 
                               id="nombre_servicio" 
                               name="nombre_servicio" 
                               maxlength="100"
                               placeholder="Ej: EntretenimientoTransPeru">
                        <small class="text-muted">Nombre que aparecerá en la interfaz del Pi</small>
                    </div>
                    
                    <!-- Configuración del paquete -->
                    <h5 class="mt-4 mb-3">
                        <i class="fas fa-box mr-1"></i>
                        Configuración del Paquete
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo_paquete">Tipo de Paquete <span class="text-danger">*</span></label>
                                <select class="form-control" id="tipo_paquete" name="tipo_paquete" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="basico">Básico (S/ <?php echo number_format(DEFAULT_BASIC_PRICE, 2); ?>)</option>
                                    <option value="intermedio">Intermedio (S/ <?php echo number_format(DEFAULT_INTERMEDIATE_PRICE, 2); ?>)</option>
                                    <option value="premium">Premium (S/ <?php echo number_format(DEFAULT_PREMIUM_PRICE, 2); ?>)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_buses">Total de Buses</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="total_buses" 
                                       name="total_buses" 
                                       min="1" 
                                       max="999"
                                       placeholder="0">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="costo_mensual">Costo Mensual (S/)</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="costo_mensual" 
                                       name="costo_mensual" 
                                       step="0.01" 
                                       min="0"
                                       placeholder="0.00">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fechas de licencia -->
                    <h5 class="mt-4 mb-3">
                        <i class="fas fa-calendar mr-1"></i>
                        Licencia
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_inicio">Fecha de Inicio <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_inicio" 
                                       name="fecha_inicio" 
                                       required
                                       value="<?php echo $formData['default_start_date']; ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="duracion_meses">Duración (meses)</label>
                                <select class="form-control" id="duracion_meses">
                                    <option value="1">1 mes</option>
                                    <option value="3">3 meses</option>
                                    <option value="6">6 meses</option>
                                    <option value="12" selected>12 meses</option>
                                    <option value="24">24 meses</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="fecha_vencimiento">Fecha de Vencimiento <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control" 
                                       id="fecha_vencimiento" 
                                       name="fecha_vencimiento" 
                                       required
                                       value="<?php echo $formData['default_end_date']; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notas adicionales -->
                    <div class="form-group">
                        <label for="notas">Notas Adicionales</label>
                        <textarea class="form-control" 
                                  id="notas" 
                                  name="notas" 
                                  rows="3"
                                  placeholder="Información adicional sobre la empresa..."></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?php echo BASE_URL; ?>views/companies/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Empresa
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript adicional para manejar el archivo
$additionalJS = [
    'https://cdn.jsdelivr.net/npm/bs-custom-file-input/dist/bs-custom-file-input.min.js'
];

$pageScript .= "
// Inicializar custom file input
$(document).ready(function () {
    bsCustomFileInput.init();
});
";

// Incluir el layout base
include '../layouts/base.php';
?>