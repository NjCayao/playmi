<?php
/**
 * Editar Empresa PLAYMI Admin
 * Formulario para modificar empresa existente
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/CompanyController.php';

// Crear instancia del controlador
$companyController = new CompanyController();

// Obtener datos de la empresa a editar
$editData = $companyController->edit();

// Variables para la vista
$pageTitle = 'Editar Empresa - PLAYMI Admin';
$contentTitle = 'Editar Empresa';
$contentSubtitle = 'Modificar información de la empresa';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Empresas', 'url' => BASE_URL . 'views/companies/index.php'],
    ['title' => 'Editar Empresa', 'url' => '']
];

// Extraer datos
$company = $editData['company'] ?? [];
$companyId = $company['id'] ?? 0;

// Si no existe la empresa, redirigir
if (!$company) {
    header('Location: ' . BASE_URL . 'views/companies/index.php');
    exit;
}

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
    formData.append('company_id', '{$companyId}');
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/companies/update.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('#submitBtn').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Actualizando...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.href = response.redirect || PLAYMI.baseUrl + 'views/companies/view.php?id={$companyId}';
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
            $('#submitBtn').prop('disabled', false).html('<i class=\"fas fa-save\"></i> Guardar Cambios');
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

// Función para cambiar logo
function changeLogo() {
    $('#logoModal').modal('show');
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
                    <i class="fas fa-edit mr-1"></i>
                    Editar: <?php echo htmlspecialchars($company['nombre']); ?>
                </h3>
                <div class="card-tools">
                    <a href="<?php echo BASE_URL; ?>views/companies/view.php?id=<?php echo $companyId; ?>" 
                       class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Ver Detalles
                    </a>
                </div>
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
                                       value="<?php echo htmlspecialchars($company['nombre']); ?>"
                                       placeholder="Ingrese el nombre de la empresa">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email_contacto">Email de Contacto <span class="text-danger">*</span></label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email_contacto" 
                                       name="email_contacto" 
                                       required 
                                       maxlength="100"
                                       value="<?php echo htmlspecialchars($company['email_contacto']); ?>"
                                       placeholder="contacto@empresa.com">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="persona_contacto">Persona de Contacto</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="persona_contacto" 
                                       name="persona_contacto" 
                                       maxlength="100"
                                       value="<?php echo htmlspecialchars($company['persona_contacto'] ?? ''); ?>"
                                       placeholder="Nombre del responsable">
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
                                       value="<?php echo htmlspecialchars($company['telefono'] ?? ''); ?>"
                                       placeholder="+51 999 999 999">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logo y branding -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Logo Actual</label>
                                <div class="text-center mb-2">
                                    <?php if ($company['logo_path']): ?>
                                        <img src="<?php echo BASE_URL; ?>../companies/data/<?php echo htmlspecialchars($company['logo_path']); ?>" 
                                             alt="Logo actual" 
                                             class="img-thumbnail" 
                                             style="max-width: 150px; max-height: 100px;">
                                        <br>
                                        <button type="button" class="btn btn-sm btn-warning mt-2" onclick="changeLogo()">
                                            <i class="fas fa-camera"></i> Cambiar Logo
                                        </button>
                                    <?php else: ?>
                                        <div class="bg-light p-3 rounded">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                            <br>
                                            <small class="text-muted">Sin logo</small>
                                            <br>
                                            <button type="button" class="btn btn-sm btn-primary mt-2" onclick="changeLogo()">
                                                <i class="fas fa-upload"></i> Subir Logo
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="color_primario">Color Primario</label>
                                <input type="color" 
                                       class="form-control" 
                                       id="color_primario" 
                                       name="color_primario" 
                                       value="<?php echo htmlspecialchars($company['color_primario'] ?? '#000000'); ?>"
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
                                       value="<?php echo htmlspecialchars($company['color_secundario'] ?? '#FFFFFF'); ?>"
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
                               value="<?php echo htmlspecialchars($company['nombre_servicio'] ?? ''); ?>"
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
                                    <option value="basico" <?php echo $company['tipo_paquete'] === 'basico' ? 'selected' : ''; ?>>
                                        Básico (S/ <?php echo number_format(DEFAULT_BASIC_PRICE, 2); ?>)
                                    </option>
                                    <option value="intermedio" <?php echo $company['tipo_paquete'] === 'intermedio' ? 'selected' : ''; ?>>
                                        Intermedio (S/ <?php echo number_format(DEFAULT_INTERMEDIATE_PRICE, 2); ?>)
                                    </option>
                                    <option value="premium" <?php echo $company['tipo_paquete'] === 'premium' ? 'selected' : ''; ?>>
                                        Premium (S/ <?php echo number_format(DEFAULT_PREMIUM_PRICE, 2); ?>)
                                    </option>
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
                                       value="<?php echo htmlspecialchars($company['total_buses'] ?? ''); ?>"
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
                                       value="<?php echo htmlspecialchars($company['costo_mensual'] ?? ''); ?>"
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
                                       value="<?php echo date('Y-m-d', strtotime($company['fecha_inicio'])); ?>">
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
                                       value="<?php echo date('Y-m-d', strtotime($company['fecha_vencimiento'])); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estado y notas -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="activo" <?php echo $company['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="suspendido" <?php echo $company['estado'] === 'suspendido' ? 'selected' : ''; ?>>Suspendido</option>
                                    <option value="vencido" <?php echo $company['estado'] === 'vencido' ? 'selected' : ''; ?>>Vencido</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Última Actualización</label>
                                <input type="text" 
                                       class="form-control" 
                                       readonly 
                                       value="<?php echo date('d/m/Y H:i', strtotime($company['updated_at'])); ?>">
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
                                  placeholder="Información adicional sobre la empresa..."><?php echo htmlspecialchars($company['notas'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?php echo BASE_URL; ?>views/companies/view.php?id=<?php echo $companyId; ?>" 
                               class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para cambiar logo -->
<div class="modal fade" id="logoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Logo</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="logoForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="logo_file">Seleccionar nuevo logo</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="logo_file" name="logo" accept="image/*" required>
                            <label class="custom-file-label" for="logo_file">Seleccionar archivo...</label>
                        </div>
                        <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 10MB</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Logo
                    </button>
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

// Manejar envío del formulario de logo
$('#logoForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('company_id', '{$companyId}');
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/companies/upload-logo.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#logoModal').modal('hide');
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(response.error);
            }
        },
        error: function() {
            toastr.error('Error al subir el logo');
        }
    });
});
";

// Incluir el layout base
include '../layouts/base.php';
?>