<?php
/**
 * Subir Video Publicitario PLAYMI Admin
 */

require_once '../../config/system.php';
require_once '../../controllers/AdvertisingController.php';

$advertisingController = new AdvertisingController();
$viewData = $advertisingController->uploadVideo();

$pageTitle = 'Subir Video Publicitario - PLAYMI Admin';
$contentTitle = 'Subir Video Publicitario';
$contentSubtitle = 'Agregar nuevo video de publicidad';

$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Publicidad', 'url' => '#'],
    ['title' => 'Videos', 'url' => BASE_URL . 'views/advertising/videos.php'],
    ['title' => 'Subir Video', 'url' => '#']
];

$pageScript = "
// Preview del video seleccionado
$('#video').on('change', function() {
    const file = this.files[0];
    if (file) {
        const fileSize = (file.size / 1024 / 1024).toFixed(2);
        $('#fileInfo').html(`
            <div class='alert alert-info'>
                <strong>Archivo:</strong> \${file.name}<br>
                <strong>Tamaño:</strong> \${fileSize} MB<br>
                <strong>Tipo:</strong> \${file.type}
            </div>
        `);
        
        // Validar tamaño
        if (file.size > 100 * 1024 * 1024) {
            toastr.error('El video no debe superar los 100 MB');
            $(this).val('');
            $('#fileInfo').empty();
        }
    }
});

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
                    window.location.href = '" . BASE_URL . "views/advertising/videos.php';
                }, 1500);
            } else {
                toastr.error(response.error);
                submitBtn.html(originalText).prop('disabled', false);
            }
        },
        error: function(xhr) {
            toastr.error('Error al subir el video');
            submitBtn.html(originalText).prop('disabled', false);
        }
    });
});
";

ob_start();
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-upload"></i> Subir Video Publicitario
                </h3>
            </div>
            <form id="uploadForm" action="<?php echo API_URL; ?>advertising/upload-video.php" method="POST" enctype="multipart/form-data">
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
                        <label for="tipo_video">Tipo de Video <span class="text-danger">*</span></label>
                        <select class="form-control" id="tipo_video" name="tipo_video" required>
                            <option value="">Seleccione tipo...</option>
                            <?php foreach ($viewData['tipos'] as $value => $label): ?>
                                <option value="<?php echo $value; ?>">
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            <strong>Al inicio:</strong> Se reproduce a los 5 minutos<br>
                            <strong>A la mitad:</strong> Se reproduce a la mitad del contenido (solo si dura más de 30 min)
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="video">Video <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="video" name="video" 
                                       accept="video/mp4,video/avi,video/mov" required>
                                <label class="custom-file-label" for="video">Seleccionar video...</label>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Formatos: MP4, AVI, MOV. Máximo: 100 MB. Duración máxima: 2 minutos.
                        </small>
                    </div>

                    <div id="fileInfo"></div>

                    <div class="form-group">
                        <label for="orden_reproduccion">Orden de Reproducción</label>
                        <input type="number" class="form-control" id="orden_reproduccion" 
                               name="orden_reproduccion" value="1" min="1">
                        <small class="form-text text-muted">
                            Orden en que se reproducirán múltiples videos del mismo tipo
                        </small>
                    </div>

                    <!-- Progress bar -->
                    <div id="uploadProgress" class="progress mb-3" style="display: none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0%</div>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> Recomendaciones</h5>
                        <ul class="mb-0">
                            <li>Use videos de alta calidad pero optimizados para web</li>
                            <li>Resolución recomendada: 1080p (1920x1080)</li>
                            <li>Duración ideal: Entre 15 y 30 segundos</li>
                            <li>El audio debe estar normalizado</li>
                        </ul>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Subir Video
                    </button>
                    <a href="<?php echo BASE_URL; ?>views/advertising/videos.php" class="btn btn-secondary">
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