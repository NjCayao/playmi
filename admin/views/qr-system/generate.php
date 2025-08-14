<?php

/**
 * Vista: Generar Códigos QR
 * Módulo 3.2: Formulario para generar QR codes personalizados
 */
require_once '../../config/system.php';
require_once '../../controllers/QRController.php';
$qrController = new QRController();
$data = $qrController->generate();
// Extraer datos
$companies = $data['companies'];
$defaultConfig = $data['default_config'];
// Configurar página
$pageTitle = 'Generar Código QR';
$contentTitle = 'Generar Código QR';
$contentSubtitle = 'Crear nuevos códigos QR para buses';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Sistema QR', 'url' => BASE_URL . 'views/qr-system/'],
    ['title' => 'Generar QR', 'url' => '#']
];
// CSS adicional
$additionalCSS = [
    ASSETS_URL . 'plugins/select2/css/select2.min.css',
    ASSETS_URL . 'plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css'
];
// JS adicional
$additionalJS = [
    ASSETS_URL . 'plugins/select2/js/select2.full.min.js'
];
// Iniciar buffer de contenido
ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Generar Códigos QR</h1>
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

<div class="row">
    <!-- Formulario principal -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-qrcode"></i> Información del QR
                </h3>
            </div>
            <form id="generateQRForm" method="POST" action="<?php echo API_URL; ?>qr/generate-qr.php">
                <div class="card-body">
                    <!-- Selección de empresa -->
                    <div class="form-group">
                        <label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="empresa_id" name="empresa_id" required>
                            <option value="">Seleccione una empresa</option>
                            <?php foreach ($companies as $company): ?>
                                <option value="<?php echo $company['id']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($company['nombre']); ?>">
                                    <?php echo htmlspecialchars($company['nombre']); ?>
                                    (<?php echo $company['total_buses']; ?> buses)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Tipo de generación -->
                    <div class="form-group">
                        <label>Tipo de Generación</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="generation_type"
                                id="single_qr" value="single" checked>
                            <label class="form-check-label" for="single_qr">
                                QR Individual (Un bus específico)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="generation_type"
                                id="bulk_qr" value="bulk">
                            <label class="form-check-label" for="bulk_qr">
                                QR Masivo (Múltiples buses)
                            </label>
                        </div>
                    </div>

                    <!-- Campos para QR individual -->
                    <div id="single_fields">
                        <div class="form-group">
                            <label for="numero_bus">Número de Bus <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="numero_bus" name="numero_bus"
                                placeholder="Ej: BUS-001" maxlength="20">
                            <small class="form-text text-muted">
                                Identificador único del bus
                            </small>
                        </div>
                    </div>

                    <!-- Campos para QR masivo -->
                    <div id="bulk_fields" style="display: none;">
                        <div class="form-group">
                            <label for="bulk_quantity">Cantidad de QR a generar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="bulk_quantity" name="bulk_quantity"
                                min="1" max="100" value="10">
                            <small class="form-text text-muted">
                                Se generarán códigos con numeración automática (máximo 100)
                            </small>
                        </div>
                    </div>

                    <!-- Configuración WiFi -->
                    <h5 class="mt-4 mb-3">Configuración WiFi</h5>

                    <div class="form-group">
                        <label>Tipo de Configuración</label>
                        <select class="form-control" id="wifi_config_type" name="wifi_config_type">
                            <option value="automatic">Automática (basada en nombre de empresa)</option>
                            <option value="custom">Personalizada</option>
                            <option value="secure">Segura (contraseña generada)</option>
                        </select>
                    </div>

                    <div id="wifi_fields">
                        <div class="form-group">
                            <label for="wifi_ssid">Nombre de Red (SSID) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="wifi_ssid" name="wifi_ssid"
                                maxlength="32" required>
                            <small class="form-text text-muted">
                                Máximo 32 caracteres. Este es el nombre que verán los pasajeros
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="wifi_password">Contraseña WiFi <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="wifi_password" name="wifi_password"
                                    minlength="8" maxlength="63" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-secondary" id="generatePassword">
                                        <i class="fas fa-key"></i> Generar
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Entre 8 y 63 caracteres. Use una contraseña segura
                            </small>
                        </div>
                    </div>

                    <!-- Configuración avanzada -->
                    <h5 class="mt-4 mb-3">Configuración del QR</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qr_size">Tamaño del QR (píxeles)</label>
                                <select class="form-control" id="qr_size" name="qr_size">
                                    <option value="200">200x200 (Pequeño)</option>
                                    <option value="300" selected>300x300 (Mediano)</option>
                                    <option value="400">400x400 (Grande)</option>
                                    <option value="500">500x500 (Extra Grande)</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="error_correction">Nivel de Corrección</label>
                                <select class="form-control" id="error_correction" name="error_correction">
                                    <option value="L">Bajo (7%)</option>
                                    <option value="M" selected>Medio (15%)</option>
                                    <option value="Q">Alto (25%)</option>
                                    <option value="H">Muy Alto (30%)</option>
                                </select>
                                <small class="form-text text-muted">
                                    Mayor corrección = QR más resistente a daños
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="include_logo" name="include_logo">
                            <label class="custom-control-label" for="include_logo">
                                Incluir logo de la empresa en el centro del QR
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-qrcode"></i> Generar QR
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel de vista previa -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-eye"></i> Vista Previa
                </h3>
            </div>
            <div class="card-body text-center">
                <div id="preview-container" class="mb-3">
                    <img src="<?php echo ASSETS_URL; ?>images/qr-placeholder.png"
                        alt="Vista previa del QR"
                        class="img-fluid"
                        id="qr-preview"
                        style="max-width: 250px;">
                </div>

                <div id="preview-info" class="text-left">
                    <h6>Información del QR:</h6>
                    <ul class="list-unstyled">
                        <li><strong>Empresa:</strong> <span id="preview-empresa">-</span></li>
                        <li><strong>Bus:</strong> <span id="preview-bus">-</span></li>
                        <li><strong>WiFi:</strong> <span id="preview-wifi">-</span></li>
                        <li><strong>Tamaño:</strong> <span id="preview-size">300x300</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Instrucciones -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Instrucciones
                </h3>
            </div>
            <div class="card-body">
                <ol class="pl-3">
                    <li class="mb-2">Seleccione la empresa para la cual generará el QR</li>
                    <li class="mb-2">Elija entre generación individual o masiva</li>
                    <li class="mb-2">Configure el nombre y contraseña del WiFi</li>
                    <li class="mb-2">Ajuste las opciones avanzadas si es necesario</li>
                    <li>Haga clic en "Generar QR" para crear el código</li>
                </ol>

                <div class="alert alert-info mt-3">
                    <i class="fas fa-lightbulb"></i> <strong>Tip:</strong>
                    Use el nivel de corrección "Alto" si el QR se imprimirá en superficies
                    que puedan dañarse o ensuciarse.
                </div>
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
        // Inicializar Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Cambio de tipo de generación
        $('input[name="generation_type"]').on('change', function() {
            if ($(this).val() === 'single') {
                $('#single_fields').show();
                $('#bulk_fields').hide();
                $('#numero_bus').prop('required', true);
                $('#bulk_quantity').prop('required', false);
            } else {
                $('#single_fields').hide();
                $('#bulk_fields').show();
                $('#numero_bus').prop('required', false);
                $('#bulk_quantity').prop('required', true);
            }
        });

        // Cambio de empresa - actualizar preview
        $('#empresa_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const empresaNombre = selectedOption.data('nombre') || '-';
            $('#preview-empresa').text(empresaNombre);

            // Si es configuración automática, generar SSID
            if ($('#wifi_config_type').val() === 'automatic') {
                generateAutomaticSSID(empresaNombre);
            }
        });

        // Cambio de configuración WiFi
        $('#wifi_config_type').on('change', function() {
            const type = $(this).val();
            const empresaNombre = $('#empresa_id').find('option:selected').data('nombre') || '';

            switch (type) {
                case 'automatic':
                    generateAutomaticSSID(empresaNombre);
                    $('#wifi_password').val(generateSimplePassword());
                    $('#wifi_fields input').prop('readonly', true);
                    break;

                case 'custom':
                    $('#wifi_fields input').prop('readonly', false);
                    break;

                case 'secure':
                    generateAutomaticSSID(empresaNombre);
                    $('#wifi_password').val(generateSecurePassword());
                    $('#wifi_fields input').prop('readonly', true);
                    break;
            }
        });

        // Generar contraseña
        $('#generatePassword').on('click', function() {
            const password = generateSecurePassword();
            $('#wifi_password').val(password);
        });

        // Actualizar preview en tiempo real
        $('#numero_bus, #wifi_ssid, #qr_size').on('input change', function() {
            updatePreview();
        });

        // Envío del formulario
        $('#generateQRForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const submitBtn = $('#submitBtn');
            const originalText = submitBtn.html();

            // Validar formulario
            if (!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }

            // Preparar datos
            const formData = new FormData(form[0]);

            // Si es generación individual, agregar flag
            if ($('input[name="generation_type"]:checked').val() === 'single') {
                formData.append('generate_bulk', false);
            } else {
                formData.append('generate_bulk', true);
            }

            // Deshabilitar botón y mostrar loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generando...');

            // Enviar petición
            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡QR Generado!',
                            text: response.message,
                            showCancelButton: true,
                            confirmButtonText: 'Ver QR generados',
                            cancelButtonText: 'Generar otro'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = 'index.php';
                            } else {
                                form[0].reset();
                                $('.select2').val(null).trigger('change');
                                updatePreview();
                            }
                        });
                    } else {
                        // Mostrar errores
                        let errorMsg = 'Error al generar el QR';
                        if (response.errors) {
                            errorMsg = '<ul class="text-left">';
                            for (let field in response.errors) {
                                errorMsg += '<li>' + response.errors[field] + '</li>';
                            }
                            errorMsg += '</ul>';
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            html: errorMsg
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor'
                    });
                },
                complete: function() {
                    submitBtn.prop('disabled', false).html(originalText);
                }
            });
        });

        // Funciones auxiliares
        function generateAutomaticSSID(empresaNombre) {
            if (!empresaNombre) return;

            // Limpiar nombre de empresa para SSID
            let ssid = 'PLAYMI_' + empresaNombre
                .toUpperCase()
                .replace(/[^A-Z0-9]/g, '')
                .substring(0, 20);

            $('#wifi_ssid').val(ssid);
        }

        function generateSimplePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let password = 'PLAYMI';
            for (let i = 0; i < 6; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }

        function generateSecurePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 16; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }

        function updatePreview() {
            $('#preview-bus').text($('#numero_bus').val() || '-');
            $('#preview-wifi').text($('#wifi_ssid').val() || '-');
            $('#preview-size').text($('#qr_size').val() + 'x' + $('#qr_size').val());

            // Aquí podrías generar un QR temporal para preview
            // Por ahora solo actualizamos la información
        }

        // Actualizar preview inicial
        updatePreview();
    });
</script>