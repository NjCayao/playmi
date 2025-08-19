<?php
/**
 * Subir Banner Publicitario PLAYMI Admin
 */

require_once '../../config/system.php';
require_once '../../controllers/AdvertisingController.php';

$advertisingController = new AdvertisingController();
$viewData = $advertisingController->uploadBanner();

$pageTitle = 'Subir Banner Publicitario - PLAYMI Admin';
$contentTitle = 'Subir Banner Publicitario';
$contentSubtitle = 'Agregar nuevo banner de publicidad';

$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Publicidad', 'url' => '#'],
    ['title' => 'Banners', 'url' => BASE_URL . 'views/advertising/banners.php'],
    ['title' => 'Subir Banner', 'url' => '#']
];

$pageScript = "
// Preview de la imagen seleccionada
$('#banner').on('change', function() {
    const file = this.files[0];
    if (file) {
        const fileSize = (file.size / 1024).toFixed(2);
        
        // Mostrar info del archivo
        $('#fileInfo').html(`
            <div class='alert alert-info'>
                <strong>Archivo:</strong> \${file.name}<br>
                <strong>Tamaño:</strong> \${fileSize} KB<br>
                <strong>Tipo:</strong> \${file.type}
            </div>
        `);
        
        // Validar tamaño
        if (file.size > 5 * 1024 * 1024) {
            toastr.error('La imagen no debe superar los 5 MB');
            $(this).val('');
            $('#fileInfo').empty();
            $('#imagePreview').empty();
            return;
        }
        
        // Preview de la imagen
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').html(`
                <img src='\${e.target.result}' class='img-fluid rounded' style='max-height: 300px;'>
                <p class='mt-2 text-muted'>Vista previa del banner</p>
            `);
            
            // Obtener dimensiones
            const img = new Image();
            img.onload = function() {
                $('#imageDimensions').html(`
                    <strong>Dimensiones:</strong> \${this.width} x \${this.height} px
                `);
                
                // Validar dimensiones según tipo
                validateBannerDimensions($('#tipo_banner').val(), this.width, this.height);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Cambio de tipo de banner
$('#tipo_banner').on('change', function() {
    const tipo = $(this).val();
    let recomendacion = '';
    
    switch(tipo) {
        case 'header':
            recomendacion = 'Dimensiones recomendadas: 1920x200px';
            break;
        case 'footer':
            recomendacion = 'Dimensiones recomendadas: 1920x100px';
            break;
        case 'catalogo':
            recomendacion = 'Dimensiones recomendadas: 300x250px';
            break;
    }
    
    $('#dimensionHelp').text(recomendacion);
});

// Validar dimensiones del banner
function validateBannerDimensions(tipo, width, height) {
    const dimensiones = {
        'header': {w: 1920, h: 200},
        'footer': {w: 1920, h: 100},
        'catalogo': {w: 300, h: 250}
    };
    
    if (dimensiones[tipo]) {
        const expected = dimensiones[tipo];
        const tolerance = 0.1; // 10% de tolerancia
        
        const widthOk = Math.abs(width - expected.w) <= expected.w * tolerance;
        const heightOk = Math.abs(height - expected.h) <= expected.h * tolerance;
        
        if (!widthOk || !heightOk) {
            toastr.warning(`Las dimensiones recomendadas son \${expected.w}x\${expected.h}px`);
        } else {
            toastr.success('Dimensiones correctas');
        }
    }
}

// Submit del formulario
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $(this).find('button[type=\"submit\"]');
    const originalText = submitBtn.html();
    
    submitBtn.html('<i class=\"fas fa-spinner fa-spin\"></i> Subiendo...').prop('disabled', true);
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = (evt.loaded / evt.total) * 100;
                    $('#uploadProgress').show().find('.progress-bar')
                        .css('width', percentComplete + '%')
                        .text(Math.round(percentComplete) + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.href = '" . BASE_URL . "views/advertising/banners.php';
                }, 1500);
            } else {
                toastr.error(response.error);
                submitBtn.html(originalText).prop('disabled', false);
            }
        },
        error: function(xhr) {
            toastr.error('Error al subir el banner');
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
});
";

ob_start();
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-image"></i> Subir Banner Publicitario
                </h3>
            </div>
            <form id="uploadForm" action="<?php echo API_URL; ?>advertising/upload-banner.php" method="POST" enctype="multipart/form-data">
                <div class="card-body">
                    <div class="form-group">
                        <label for="empresa_id">Empresa <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="empresa_id" name="empresa_id" required>
                            <option value="">Seleccione una empresa...</option>
                            <?php foreach ($viewData['companies'] as $company): ?>
                                <option value="<?php echo $company['id']; ?>">
                                    <?php echo htmlspecialchars($company['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tipo_banner">Tipo de Banner <span class="text-danger">*</span></label>
                        <select class="form-control" id="tipo_banner" name="tipo_banner" required>
                            <option value="">Seleccione tipo...</option>
                            <?php foreach ($viewData['tipos'] as $value => $label): ?>
                                <option value="<?php echo $value; ?>">
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="dimensionHelp" class="form-text text-muted">
                            Seleccione un tipo para ver las dimensiones recomendadas
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="banner">Imagen del Banner <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="banner" name="banner" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" required>
                                <label class="custom-file-label" for="banner">Seleccionar imagen...</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Formatos: JPG, PNG, GIF, WEBP. Máximo: 5 MB.
                        </small>
                    </div>

                    <div id="fileInfo"></div>
                    <div id="imageDimensions" class="text-info mb-3"></div>

                    <!-- Preview de la imagen -->
                    <div id="imagePreview" class="text-center mb-3"></div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="posicion">Posición (opcional)</label>
                                <input type="text" class="form-control" id="posicion" name="posicion"
                                       placeholder="Ej: top, bottom, sidebar">
                                <small class="form-text text-muted">
                                    Posición específica del banner en el portal
                                </small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="orden_visualizacion">Orden de Visualización</label>
                                <input type="number" class="form-control" id="orden_visualizacion" 
                                       name="orden_visualizacion" value="1" min="1">
                                <small class="form-text text-muted">
                                    Orden cuando hay múltiples banners del mismo tipo
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div id="uploadProgress" class="progress mb-3" style="display: none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Recomendaciones</h5>
                        <ul class="mb-0">
                            <li><strong>Header:</strong> 1920x200px - Se muestra en la parte superior del portal</li>
                            <li><strong>Footer:</strong> 1920x100px - Se muestra en la parte inferior del portal</li>
                            <li><strong>Catálogo:</strong> 300x250px - Se intercala entre el contenido</li>
                            <li>Use imágenes optimizadas para web (comprimidas)</li>
                            <li>Evite texto muy pequeño que no sea legible en móviles</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Banner
                    </button>
                    <a href="<?php echo BASE_URL; ?>views/advertising/banners.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../layouts/base.php';
?>