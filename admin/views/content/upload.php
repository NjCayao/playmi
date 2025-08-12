<?php
/**
 * Subir Contenido PLAYMI Admin
 * Formulario para agregar películas, música y juegos
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

// Crear instancia del controlador
$contentController = new ContentController();

// Obtener datos para el formulario
$formData = $contentController->upload();

// Variables para la vista
$pageTitle = 'Subir Contenido - PLAYMI Admin';
$contentTitle = 'Subir Contenido';
$contentSubtitle = 'Agregar nuevo contenido multimedia';
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Contenido', 'url' => BASE_URL . 'views/content/index.php'],
    ['title' => 'Subir Contenido', 'url' => '']
];

// JavaScript específico
$pageScript = "
// Configurar el tipo de contenido
$('#tipo').on('change', function() {
    const tipo = $(this).val();
    
    // Actualizar categorías según tipo
    updateCategories(tipo);
    
    // Mostrar/ocultar campos específicos
    if (tipo === 'juego') {
        $('#duracionGroup').hide();
        $('#calificacionGroup').hide();
    } else {
        $('#duracionGroup').show();
        $('#calificacionGroup').show();
    }
    
    // Actualizar texto de ayuda
    updateHelpText(tipo);
});

// Actualizar categorías dinámicamente
function updateCategories(tipo) {
    const categories = {
        'pelicula': ['Acción', 'Comedia', 'Drama', 'Terror', 'Ciencia Ficción', 'Documental', 'Animación', 'Romance'],
        'musica': ['Pop', 'Rock', 'Salsa', 'Cumbia', 'Reggaeton', 'Folclore', 'Electrónica', 'Clásica'],
        'juego': ['Puzzle', 'Arcade', 'Aventura', 'Estrategia', 'Educativo', 'Deportes']
    };
    
    const select = $('#categoria');
    select.empty();
    select.append('<option value=\"\">Seleccionar categoría...</option>');
    
    if (categories[tipo]) {
        categories[tipo].forEach(cat => {
            select.append('<option value=\"' + cat.toLowerCase() + '\">' + cat + '</option>');
        });
    }
}

// Actualizar texto de ayuda
function updateHelpText(tipo) {
    const helpTexts = {
        'pelicula': 'Formatos permitidos: MP4, AVI, MKV, MOV. Máximo 5GB',
        'musica': 'Formatos permitidos: MP3, WAV, FLAC, M4A. Máximo 500MB',
        'juego': 'Formatos permitidos: HTML, ZIP. Máximo 5GB'
    };
    
    $('#archivoHelp').text(helpTexts[tipo] || '');
}

// Validación del formulario
$('#uploadForm').on('submit', function(e) {
    e.preventDefault();
    
    // Validar campos
    let isValid = true;
    $(this).find('[required]').each(function() {
        if (!$(this).val()) {
            isValid = false;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    if (!isValid) {
        toastr.error('Por favor complete todos los campos requeridos');
        return;
    }
    
    // Validar tamaño de archivo
    const file = $('#archivo')[0].files[0];
    if (file) {
        const tipo = $('#tipo').val();
        const maxSizes = {
            'pelicula': 5 * 1024 * 1024 * 1024, // 5GB
            'musica': 500 * 1024 * 1024, // 500MB
            'juego': 5 * 1024 * 1024 * 1024 // 5GB
        };
        
        if (file.size > maxSizes[tipo]) {
            toastr.error('El archivo excede el tamaño máximo permitido');
            return;
        }
    }
    
    // Crear FormData
    const formData = new FormData(this);
    
    // Mostrar progreso
    $('#uploadProgress').removeClass('d-none');
    $('#submitBtn').prop('disabled', true);
    
    // Enviar por AJAX con progreso
    $.ajax({
        url: PLAYMI.baseUrl + 'api/content/upload.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener('progress', function(evt) {
                if (evt.lengthComputable) {
                    const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                    $('#progressBar').css('width', percentComplete + '%');
                    $('#progressBar').text(percentComplete + '%');
                }
            }, false);
            return xhr;
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.href = PLAYMI.baseUrl + 'views/content/index.php';
                }, 1500);
            } else {
                toastr.error(response.error);
                if (response.errors) {
                    showFormErrors(response.errors);
                }
                resetUploadForm();
            }
        },
        error: function(xhr) {
            toastr.error('Error al subir el contenido');
            resetUploadForm();
        }
    });
});

// Resetear formulario de carga
function resetUploadForm() {
    $('#uploadProgress').addClass('d-none');
    $('#progressBar').css('width', '0%').text('0%');
    $('#submitBtn').prop('disabled', false);
}

// Mostrar errores en campos
function showFormErrors(errors) {
    Object.keys(errors).forEach(field => {
        const input = $('[name=\"' + field + '\"]');
        input.addClass('is-invalid');
        input.siblings('.invalid-feedback').remove();
        input.after('<div class=\"invalid-feedback\">' + errors[field] + '</div>');
    });
}

// Vista previa de thumbnail
$('#thumbnail').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#thumbnailPreview').html('<img src=\"' + e.target.result + '\" class=\"img-thumbnail\" style=\"max-width: 200px;\">');
        };
        reader.readAsDataURL(file);
    }
});

// Inicializar
$(document).ready(function() {
    $('#tipo').trigger('change');
});
";

// Generar contenido
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-upload mr-1"></i>
                    Subir Nuevo Contenido
                </h3>
            </div>
            
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="card-body">
                    <!-- Tipo de contenido -->
                    <div class="form-group">
                        <label for="tipo">Tipo de Contenido <span class="text-danger">*</span></label>
                        <select class="form-control" id="tipo" name="tipo" required>
                            <option value="">Seleccionar tipo...</option>
                            <?php foreach ($formData['content_types'] as $key => $value): ?>
                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Información básica -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="titulo">Título <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control" 
                                       id="titulo" 
                                       name="titulo" 
                                       required 
                                       maxlength="200"
                                       placeholder="Título del contenido">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="año_lanzamiento">Año</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="año_lanzamiento" 
                                       name="año_lanzamiento" 
                                       min="1900" 
                                       max="<?php echo date('Y'); ?>"
                                       placeholder="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"
                                  placeholder="Descripción del contenido..."></textarea>
                    </div>
                    
                    <!-- Categorización -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoria">Categoría</label>
                                <select class="form-control" id="categoria" name="categoria">
                                    <option value="">Seleccionar categoría...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="genero" 
                                       name="genero" 
                                       maxlength="50"
                                       placeholder="Género específico">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Calificación (solo para películas y música) -->
                    <div class="form-group" id="calificacionGroup">
                        <label for="calificacion">Calificación</label>
                        <select class="form-control" id="calificacion" name="calificacion">
                            <option value="">Sin calificación</option>
                            <?php foreach ($formData['ratings'] as $rating): ?>
                                <option value="<?php echo $rating; ?>"><?php echo $rating; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Archivos -->
                    <hr>
                    <h5>Archivos</h5>
                    
                    <div class="form-group">
                        <label for="archivo">Archivo Principal <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="archivo" 
                                   name="archivo" 
                                   required>
                            <label class="custom-file-label" for="archivo">Seleccionar archivo...</label>
                        </div>
                        <small class="text-muted" id="archivoHelp">
                            Seleccione primero el tipo de contenido
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="thumbnail">Miniatura (Thumbnail)</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="thumbnail" 
                                   name="thumbnail" 
                                   accept="image/*">
                            <label class="custom-file-label" for="thumbnail">Seleccionar imagen...</label>
                        </div>
                        <small class="text-muted">
                            Imagen para mostrar en el catálogo. Formatos: JPG, PNG, WEBP
                        </small>
                        <div id="thumbnailPreview" class="mt-2"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="trailer">Trailer/Preview (Opcional)</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="trailer" 
                                   name="trailer" 
                                   accept="video/*">
                            <label class="custom-file-label" for="trailer">Seleccionar video...</label>
                        </div>
                        <small class="text-muted">
                            Video corto de preview (máximo 100MB)
                        </small>
                    </div>
                    
                    <!-- Progreso de carga -->
                    <div id="uploadProgress" class="d-none">
                        <hr>
                        <h6>Progreso de Carga</h6>
                        <div class="progress">
                            <div id="progressBar" 
                                 class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 style="width: 0%">0%</div>
                        </div>
                        <small class="text-muted mt-2">
                            Por favor no cierre esta página mientras se sube el contenido
                        </small>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?php echo BASE_URL; ?>views/content/index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-upload"></i> Subir Contenido
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Información adicional -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Información Importante
                </h3>
            </div>
            <div class="card-body">
                <ul>
                    <li><strong>Películas:</strong> MP4, AVI, MKV, MOV - Máximo 5GB</li>
                    <li><strong>Música:</strong> MP3, WAV, FLAC, M4A - Máximo 500MB</li>
                    <li><strong>Juegos:</strong> HTML5 o ZIP - Máximo 5GB</li>
                    <li>El contenido será procesado automáticamente después de subirlo</li>
                    <li>La duración se calculará automáticamente para videos y música</li>
                    <li>Se recomienda subir miniaturas de 16:9 para películas y 1:1 para música</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript adicional
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