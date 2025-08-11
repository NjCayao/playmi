/**
 * JavaScript Personalizado PLAYMI Admin
 */

// Variables globales
window.PLAYMI = {
    baseUrl: 'http://localhost/PLAYMI/admin/',
    apiUrl: 'http://localhost/PLAYMI/admin/api/',
    currentUser: null,
    csrfToken: null
};

// Función principal que se ejecuta cuando el DOM está listo
$(document).ready(function() {
    initializeAdmin();
});

/**
 * Inicializar funciones del admin
 */
function initializeAdmin() {
    initializeTooltips();
    initializeDataTables();
    initializeFormValidation();
    initializeAjaxHandlers();
    initializeNotifications();
    checkSessionStatus();
    
    console.log('✅ PLAYMI Admin inicializado');
}

/**
 * Inicializar tooltips de Bootstrap
 */
function initializeTooltips() {
    $('[data-toggle="tooltip"]').tooltip();
}

/**
 * Inicializar DataTables
 */
function initializeDataTables() {
    if ($.fn.DataTable) {
        $('.data-table').DataTable({
            responsive: true,
            lengthChange: false,
            autoWidth: false,
            pageLength: 25,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger btn-sm'
                }
            ]
        });
    }
}

/**
 * Inicializar validación de formularios
 */
function initializeFormValidation() {
    // Validación en tiempo real
    $('form').on('submit', function(e) {
        const form = $(this);
        
        if (form.hasClass('needs-validation')) {
            e.preventDefault();
            e.stopPropagation();
            
            if (validateForm(form)) {
                // Si la validación pasa, enviar por AJAX si tiene clase ajax-form
                if (form.hasClass('ajax-form')) {
                    submitFormAjax(form);
                } else {
                    // Envío normal del formulario
                    form.off('submit').submit();
                }
            }
            
            form.addClass('was-validated');
        }
    });
    
    // Validación en tiempo real de campos
    $('.form-control').on('blur', function() {
        validateField($(this));
    });
}

/**
 * Validar formulario completo
 */
function validateForm(form) {
    let isValid = true;
    
    form.find('.form-control[required]').each(function() {
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
    const type = field.attr('type');
    const required = field.prop('required');
    
    // Limpiar clases previas
    field.removeClass('is-valid is-invalid');
    field.siblings('.invalid-feedback').remove();
    
    // Campo requerido
    if (required && !value) {
        showFieldError(field, 'Este campo es requerido');
        return false;
    }
    
    // Si no es requerido y está vacío, es válido
    if (!required && !value) {
        field.addClass('is-valid');
        return true;
    }
    
    // Validaciones específicas por tipo
    switch (type) {
        case 'email':
            if (!isValidEmail(value)) {
                showFieldError(field, 'Ingrese un email válido');
                return false;
            }
            break;
            
        case 'tel':
            if (!isValidPhone(value)) {
                showFieldError(field, 'Ingrese un teléfono válido');
                return false;
            }
            break;
            
        case 'url':
            if (!isValidUrl(value)) {
                showFieldError(field, 'Ingrese una URL válida');
                return false;
            }
            break;
            
        case 'number':
            if (!isValidNumber(value)) {
                showFieldError(field, 'Ingrese un número válido');
                return false;
            }
            break;
    }
    
    // Validaciones personalizadas por data attributes
    const minLength = field.data('min-length');
    if (minLength && value.length < minLength) {
        showFieldError(field, `Mínimo ${minLength} caracteres`);
        return false;
    }
    
    const maxLength = field.data('max-length');
    if (maxLength && value.length > maxLength) {
        showFieldError(field, `Máximo ${maxLength} caracteres`);
        return false;
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
    field.after(`<div class="invalid-feedback">${message}</div>`);
}

/**
 * Enviar formulario por AJAX
 */
function submitFormAjax(form) {
    const url = form.attr('action') || window.location.href;
    const method = form.attr('method') || 'POST';
    const formData = new FormData(form[0]);
    
    // Mostrar loading en botón de envío
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Procesando...').prop('disabled', true);
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showNotification(response.message || 'Operación exitosa', 'success');
                
                // Redireccionar si se especifica
                if (response.redirect) {
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    // Recargar página si no hay redirección
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                }
            } else {
                showNotification(response.error || 'Error en la operación', 'error');
                
                // Mostrar errores específicos de campos
                if (response.errors) {
                    showFormErrors(form, response.errors);
                }
            }
        },
        error: function(xhr) {
            let message = 'Error interno del servidor';
            
            if (xhr.responseJSON && xhr.responseJSON.error) {
                message = xhr.responseJSON.error;
            }
            
            showNotification(message, 'error');
        },
        complete: function() {
            // Restaurar botón
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
}

/**
 * Mostrar errores específicos de formulario
 */
function showFormErrors(form, errors) {
    Object.keys(errors).forEach(fieldName => {
        const field = form.find(`[name="${fieldName}"]`);
        if (field.length) {
            showFieldError(field, errors[fieldName]);
        }
    });
}

/**
 * Inicializar manejadores AJAX globales
 */
function initializeAjaxHandlers() {
    // Configurar CSRF token para todas las peticiones AJAX
    $.ajaxSetup({
        beforeSend: function(xhr, settings) {
            if (!/^(GET|HEAD|OPTIONS|TRACE)$/i.test(settings.type) && !this.crossDomain) {
                xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            }
        }
    });
    
    // Manejar errores AJAX globalmente
    $(document).ajaxError(function(event, xhr, settings) {
        if (xhr.status === 401) {
            // No autorizado - redirigir al login
            showNotification('Sesión expirada. Redirigiendo al login...', 'warning');
            setTimeout(() => {
                window.location.href = PLAYMI.baseUrl + 'login.php';
            }, 2000);
        }
    });
}

/**
 * Inicializar sistema de notificaciones
 */
function initializeNotifications() {
    // Configurar Toastr si está disponible
    if (typeof toastr !== 'undefined') {
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            preventDuplicates: false,
            onclick: null,
            showDuration: 300,
            hideDuration: 1000,
            timeOut: 5000,
            extendedTimeOut: 1000,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut'
        };
    }
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
    } else {
        // Fallback a alert nativo
        alert(message);
    }
}

/**
 * Verificar estado de sesión periódicamente
 */
function checkSessionStatus() {
    setInterval(function() {
        $.ajax({
            url: PLAYMI.baseUrl + 'api/check-session.php',
            method: 'GET',
            success: function(response) {
                if (!response.authenticated) {
                    showNotification('Su sesión ha expirado', 'warning');
                    setTimeout(() => {
                        window.location.href = PLAYMI.baseUrl + 'login.php';
                    }, 2000);
                }
            }
        });
    }, 300000); // Verificar cada 5 minutos
}

/**
 * Funciones de validación
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function isValidPhone(phone) {
    const regex = /^[\+]?[1-9][\d]{0,15}$/;
    return regex.test(phone.replace(/\s/g, ''));
}

function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

function isValidNumber(num) {
    return !isNaN(parseFloat(num)) && isFinite(num);
}

/**
 * Confirmar eliminación con SweetAlert2
 */
function confirmDelete(url, message = '¿Está seguro que desea eliminar este elemento?') {
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
}

/**
 * Formatear números como moneda
 */
function formatCurrency(amount, currency = 'PEN') {
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

/**
 * Formatear fechas
 */
function formatDate(date, format = 'dd/mm/yyyy') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    switch (format) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return d.toLocaleDateString();
    }
}

/**
 * Función para hacer elementos sortables
 */
function makeSortable(selector, options = {}) {
    if (typeof Sortable !== 'undefined') {
        return Sortable.create(document.querySelector(selector), options);
    }
}

/**
 * Exportar funciones globalmente
 */
window.PLAYMI.functions = {
    showNotification,
    confirmDelete,
    formatCurrency,
    formatDate,
    validateForm,
    submitFormAjax,
    makeSortable
};