<?php

/**
 * MÓDULO 2.2.2: Formulario universal para subir contenido
 * Propósito: Permitir subida de películas, música y juegos
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

// Obtener datos para el formulario
$uploadData = $controller->upload();
$contentTypes = $uploadData['content_types'] ?? [];
$genres = $uploadData['genres'] ?? [];
$categories = $uploadData['categories'] ?? [];
$ratings = $uploadData['ratings'] ?? [];

$pageTitle = 'Subir Contenido';
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Subir Nuevo Contenido</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Contenido</a></li>
                    <li class="breadcrumb-item active">Subir</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form id="uploadForm" enctype="multipart/form-data">
            <div class="row">
                <!-- Información básica -->
                <div class="col-md-6">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información Básica</h3>
                        </div>
                        <div class="card-body">
                            <!-- Tipo de contenido -->
                            <div class="form-group">
                                <label>Tipo de Contenido <span class="text-danger">*</span></label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <?php foreach ($contentTypes as $value => $label): ?>
                                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Título -->
                            <div class="form-group">
                                <label>Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="titulo" required maxlength="200"
                                    placeholder="Ingrese el título del contenido">
                            </div>

                            <!-- Descripción -->
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="3"
                                    placeholder="Ingrese una descripción (opcional)"></textarea>
                            </div>

                            <!-- Campos dinámicos según tipo -->
                            <div id="dynamicFields">
                                <!-- Se llenarán con JavaScript según el tipo seleccionado -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subida de archivos -->
                <div class="col-md-6">
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Archivos</h3>
                        </div>
                        <div class="card-body">
                            <!-- Archivo principal -->
                            <div class="form-group">
                                <label>Archivo Principal <span class="text-danger">*</span></label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivo" name="archivo" required>
                                    <label class="custom-file-label" for="archivo">Seleccionar archivo</label>
                                </div>
                                <small class="form-text text-muted" id="fileHelp">
                                    Seleccione primero el tipo de contenido
                                </small>
                            </div>

                            <!-- Progress bar -->
                            <div class="progress mb-3" style="display: none;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated"
                                    role="progressbar"
                                    style="width: 0%">0%</div>
                            </div>

                            <!-- Thumbnail -->
                            <div class="form-group">
                                <label>Imagen de Portada (Thumbnail)</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="thumbnail" name="thumbnail" accept="image/*">
                                    <label class="custom-file-label" for="thumbnail">Seleccionar imagen</label>
                                </div>
                                <small class="form-text text-muted">
                                    JPG, PNG o WebP. Máximo 10MB. Se generará automáticamente si no se proporciona.
                                </small>
                            </div>

                            <!-- Preview de thumbnail -->
                            <div id="thumbnailPreview" class="text-center" style="display: none;">
                                <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                <i class="fas fa-upload"></i> Subir Contenido
                            </button>
                            <a href="index.php" class="btn btn-default btn-block">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- Templates para campos dinámicos -->
<script type="text/template" id="peliculaFields">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Género</label>
                <select class="form-control" name="genero">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($genres['pelicula'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Clasificación</label>
                <select class="form-control" name="calificacion">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($ratings as $rating): ?>
                        <option value="<?php echo $rating; ?>"><?php echo $rating; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="row">        
        <div class="col-md-12">
            <div class="form-group">
                <label>Año</label>
                <input type="number" class="form-control" name="anio_lanzamiento" 
                       min="1900" max="<?php echo date('Y'); ?>" placeholder="Año de lanzamiento">
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="musicaFields">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Artista</label>
                <input type="text" class="form-control" name="artista" placeholder="Nombre del artista">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Álbum</label>
                <input type="text" class="form-control" name="album" placeholder="Nombre del álbum">
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Género</label>
                <select class="form-control" name="genero">  <!-- Cambiar de "categoria" a "genero" -->
                    <option value="">Seleccionar...</option>
                    <?php foreach ($genres['musica'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Año</label>
                <input type="number" class="form-control" name="anio_lanzamiento" 
                       min="1900" max="<?php echo date('Y'); ?>" placeholder="Año de lanzamiento">
            </div>
        </div>
    </div>
</script>

<script type="text/template" id="juegoFields">
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <label>Categoría</label>
                <select class="form-control" name="categoria">
                    <option value="">Seleccionar...</option>
                    <?php foreach ($categories['juego'] as $cat): ?>
                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label>Instrucciones</label>
        <textarea class="form-control" name="instrucciones" rows="3"
                  placeholder="Cómo jugar..."></textarea>
    </div>
    <div class="form-group">
        <label>Controles</label>
        <input type="text" class="form-control" name="controles" 
               placeholder="Ej: Flechas para mover, Espacio para saltar">
    </div>
</script>

<?php
$content = ob_get_clean();
require_once '../layouts/base.php';
?>

<!-- Scripts específicos -->
<script>
    $(document).ready(function() {
        const fileTypes = {
            pelicula: {
                accept: '.mp4,.avi,.mkv,.mov',
                maxSize: <?php echo MAX_VIDEO_SIZE; ?>,
                help: 'SOLO EN MP4. Máximo 15GB.'
            },
            musica: {
                accept: '.mp3,.m4a,.wav,.flac,.mp4,.avi',
                maxSize: <?php echo MAX_AUDIO_SIZE; ?>,
                help: 'MP3, MP4. Máximo 2GB.'
            },
            juego: {
                accept: '.zip',
                maxSize: <?php echo MAX_GAME_SIZE; ?>,
                help: 'Archivo ZIP que contenga index.html. Máximo 10GB.'
            }
        };

        // Cambiar campos según tipo
        $('#tipo').on('change', function() {
            const tipo = $(this).val();

            if (tipo) {
                // Cargar campos dinámicos
                const template = $('#' + tipo + 'Fields').html();
                $('#dynamicFields').html(template);

                // Actualizar restricciones de archivo
                const config = fileTypes[tipo];
                $('#archivo').attr('accept', config.accept);
                $('#fileHelp').text(config.help);
            } else {
                $('#dynamicFields').empty();
                $('#archivo').removeAttr('accept');
                $('#fileHelp').text('Seleccione primero el tipo de contenido');
            }
        });

        // Preview de thumbnail
        $('#thumbnail').on('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#thumbnailPreview img').attr('src', e.target.result);
                    $('#thumbnailPreview').show();
                };
                reader.readAsDataURL(file);
            }
        });

        // Actualizar label de archivo
        $('.custom-file-input').on('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Seleccionar archivo';
            $(this).next('.custom-file-label').text(fileName);
        });

        // Enviar formulario
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();

            const tipo = $('#tipo').val();
            if (!tipo) {
                toastr.error('Debe seleccionar un tipo de contenido');
                return;
            }

            const formData = new FormData(this);
            const $submitBtn = $('#submitBtn');
            const $progress = $('.progress');
            const $progressBar = $('.progress-bar');

            // Deshabilitar botón y mostrar progress
            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
            $progress.show();

            // Mapear tipos a nombres de archivo
            const apiMap = {
                'pelicula': 'movie',
                'musica': 'music',
                'juego': 'game'
            };

            $.ajax({
                url: '../../api/content/upload-' + apiMap[tipo] + '.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $progressBar.css('width', percentComplete + '%').text(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    toastr.success('Contenido subido exitosamente');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.error || 'Error al subir el contenido';
                    toastr.error(error);
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Contenido');
                    $progress.hide();
                    $progressBar.css('width', '0%').text('0%');
                }
            });
        });
    });
</script>