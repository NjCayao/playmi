<?php

/**
 * MÓDULO 2.2.6: Editar metadatos de contenido existente
 * Propósito: Formulario para actualizar información del contenido
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

// Obtener ID del contenido
$contentId = (int)($_GET['id'] ?? 0);
if (!$contentId) {
    $controller->setMessage('ID de contenido no válido', MSG_ERROR);
    $controller->redirect('views/content/index.php');
}

// Obtener ID del contenido
$contentId = (int)($_GET['id'] ?? 0);

// Obtener datos del contenido
$editData = $controller->edit($contentId);
$content = $editData['content'] ?? null;

// Obtener datos para los selects
$uploadData = $controller->upload();
$genres = $uploadData['genres'] ?? [];
$categories = $uploadData['categories'] ?? [];
$ratings = $uploadData['ratings'] ?? [];

if (!$content) {
    $controller->setMessage('Contenido no encontrado', MSG_ERROR);
    $controller->redirect('views/content/index.php');
}

$pageTitle = 'Editar ' . ucfirst($content['tipo']);
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Editar <?php echo ucfirst($content['tipo']); ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Contenido</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <form id="editForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $content['id']; ?>">

            <div class="row">
                <!-- Información principal -->
                <div class="col-md-8">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Información del Contenido</h3>
                        </div>
                        <div class="card-body">
                            <!-- Tipo (solo lectura) -->
                            <div class="form-group">
                                <label>Tipo de Contenido</label>
                                <input type="text" class="form-control" value="<?php echo ucfirst($content['tipo']); ?>" readonly>
                            </div>

                            <!-- Título -->
                            <div class="form-group">
                                <label>Título <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="titulo"
                                    value="<?php echo htmlspecialchars($content['titulo']); ?>"
                                    required maxlength="200">
                            </div>

                            <!-- Descripción -->
                            <div class="form-group">
                                <label>Descripción</label>
                                <textarea class="form-control" name="descripcion" rows="4"><?php echo htmlspecialchars($content['descripcion'] ?? ''); ?></textarea>
                            </div>

                            <!-- Campos específicos según tipo -->
                            <?php if ($content['tipo'] === 'pelicula'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Género</label>
                                            <select class="form-control" name="genero">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $movieGenres = $uploadData['genres']['pelicula'] ?? [];
                                                foreach ($movieGenres as $value => $label):
                                                ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $content['genero'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">                                       
                                        <div class="form-group">
                                            <label>Clasificación</label>
                                            <select class="form-control" name="calificacion">
                                                <option value="">Seleccionar...</option>
                                                <?php foreach ($ratings as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $content['calificacion'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <!-- Año -->
                                <div class="form-group">
                                    <label>Año</label>
                                    <input type="number" class="form-control" name="anio_lanzamiento"
                                        value="<?php echo $content['anio_lanzamiento'] ?? ''; ?>"
                                        min="1900" max="<?php echo date('Y'); ?>">
                                </div>

                            <?php elseif ($content['tipo'] === 'musica'): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Artista</label>
                                            <input type="text" class="form-control" name="artista"
                                                value="<?php echo htmlspecialchars($content['artista'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Álbum</label>
                                            <input type="text" class="form-control" name="album"
                                                value="<?php echo htmlspecialchars($content['album'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Género</label>
                                            <select class="form-control" name="genero">
                                                <option value="">Seleccionar...</option>
                                                <?php
                                                $musicGenres = $uploadData['genres']['musica'] ?? [];
                                                foreach ($musicGenres as $value => $label):
                                                ?>
                                                    <option value="<?php echo $value; ?>" <?php echo $content['genero'] === $value ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Año</label>
                                            <input type="number" class="form-control" name="anio_lanzamiento"
                                                value="<?php echo $content['anio_lanzamiento'] ?? ''; ?>"
                                                min="1900" max="<?php echo date('Y'); ?>">
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($content['tipo'] === 'juego'): ?>
                                <div class="form-group">
                                    <label>Categoría</label>
                                    <select class="form-control" name="categoria">
                                        <option value="">Seleccionar...</option>
                                        <?php
                                        $gameCategories = $uploadData['categories']['juego'] ?? [];
                                        foreach ($gameCategories as $cat):
                                        ?>
                                            <option value="<?php echo $cat; ?>" <?php echo $content['categoria'] === $cat ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Instrucciones</label>
                                    <textarea class="form-control" name="instrucciones" rows="3"><?php echo htmlspecialchars($content['instrucciones'] ?? ''); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Controles</label>
                                    <input type="text" class="form-control" name="controles"
                                        value="<?php echo htmlspecialchars($content['controles'] ?? ''); ?>"
                                        placeholder="Ej: Flechas para mover, Espacio para saltar">
                                </div>
                            <?php endif; ?>

                            <!-- Estado -->
                            <div class="form-group">
                                <label>Estado</label>
                                <select class="form-control" name="estado">
                                    <option value="activo" <?php echo $content['estado'] === 'activo' ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo $content['estado'] === 'inactivo' ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="procesando" <?php echo $content['estado'] === 'procesando' ? 'selected' : ''; ?>>Procesando</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de archivo y media -->
                <div class="col-md-4">
                    <!-- Información del archivo -->
                    <div class="card card-info">
                        <div class="card-header">
                            <h3 class="card-title">Información de la película</h3>
                        </div>
                        <div class="card-body">
                            <dl>
                                <dt>Archivo actual:</dt>
                                <dd class="text-truncate"><?php echo basename($content['archivo_path']); ?></dd>

                                <dt>Tamaño:</dt>
                                <dd><?php echo formatFileSize($content['tamanio_archivo'] ?? 0); ?></dd>

                                <?php if ($content['duracion'] > 0): ?>
                                    <dt>Duración:</dt>
                                    <dd><?php echo gmdate("H:i:s", $content['duracion']); ?></dd>
                                <?php endif; ?>

                                <dt>Subido:</dt>
                                <dd><?php echo date('d/m/Y H:i', strtotime($content['created_at'])); ?></dd>

                                <dt>Última actualización:</dt>
                                <dd><?php echo date('d/m/Y H:i', strtotime($content['updated_at'])); ?></dd>
                            </dl>

                            <!-- Cambiar archivo -->
                            <div class="form-group">
                                <label>Subir archivo de película</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivo" name="archivo">
                                    <label class="custom-file-label" for="archivo">Seleccionar archivo</label>
                                </div>
                                <small class="form-text text-muted">
                                    Deja vacío para mantener el archivo de la película actual
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Thumbnail -->
                    <div class="card card-warning">
                        <div class="card-header">
                            <h3 class="card-title">Imagen de Portada</h3>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($content['thumbnail_path']): ?>
                                <img src="<?php echo SITE_URL . 'content/' . $content['thumbnail_path']; ?>"
                                    alt="Thumbnail actual"
                                    class="img-fluid mb-3"
                                    style="max-height: 200px;">
                            <?php else: ?>
                                <div class="bg-gray p-5 mb-3">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <p class="mt-2">Sin imagen</p>
                                </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label>Cambiar imagen</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="thumbnail" name="thumbnail" accept="image/*">
                                    <label class="custom-file-label" for="thumbnail">Seleccionar imagen</label>
                                </div>
                            </div>

                            <!-- Preview -->
                            <div id="thumbnailPreview" style="display: none;">
                                <hr>
                                <p class="text-muted">Nueva imagen:</p>
                                <img src="" alt="Preview" class="img-fluid" style="max-height: 150px;">
                            </div>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <a href="<?php echo BASE_URL; ?>views/content/index.php" class="btn btn-default btn-block">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Historial de cambios -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">Historial de Cambios</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    // Aquí se cargaría el historial real desde logs_sistema
                    $changes = []; // Simulado
                    ?>
                    <?php if (empty($changes)): ?>
                        <p class="text-muted">No hay cambios registrados</p>
                    <?php else: ?>
                        <!-- Timeline items -->
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
require_once '../layouts/base.php';
?>

<!-- Scripts específicos -->
<script>
    $(document).ready(function() {
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

        // Actualizar label de archivos
        $('.custom-file-input').on('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Seleccionar archivo';
            $(this).next('.custom-file-label').text(fileName);
        });

        // Enviar formulario
        $('#editForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const $submitBtn = $(this).find('button[type="submit"]');


            $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            $.ajax({
                url: '../../api/content/update-content.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success('Contenido actualizado exitosamente');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.error || 'Error al actualizar';
                    toastr.error(error);
                    $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar Cambios');
                }
            });
        });
    });
</script>

<?php
// Helper function
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>