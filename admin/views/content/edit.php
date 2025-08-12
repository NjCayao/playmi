<?php
/**
 * Editar Contenido PLAYMI Admin
 * Formulario para modificar contenido existente
 */

// Incluir configuración y controladores
require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

// Crear instancia del controlador
$contentController = new ContentController();

// Obtener ID del contenido
$contentId = (int)($_GET['id'] ?? 0);
if (!$contentId) {
    header('Location: ' . BASE_URL . 'views/content/index.php');
    exit;
}

// Obtener datos
$editData = $contentController->edit($contentId);
$content = $editData['content'] ?? [];
$categories = $editData['categories'] ?? [];
$genres = $editData['genres'] ?? [];

// Variables para la vista
$pageTitle = 'Editar Contenido - PLAYMI Admin';
$contentTitle = 'Editar Contenido';
$contentSubtitle = htmlspecialchars($content['titulo']);
$showContentHeader = true;

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Inicio', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Contenido', 'url' => BASE_URL . 'views/content/index.php'],
    ['title' => 'Editar', 'url' => '']
];

// JavaScript específico
$pageScript = "
// Configurar formulario de edición
$('#editForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('content_id', {$contentId});
    
    $.ajax({
        url: PLAYMI.baseUrl + 'api/content/update.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        beforeSend: function() {
            $('#submitBtn').prop('disabled', true).html('<i class=\"fas fa-spinner fa-spin\"></i> Guardando...');
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                setTimeout(() => {
                    window.location.href = PLAYMI.baseUrl + 'views/content/view.php?id={$contentId}';
                }, 1500);
            } else {
                toastr.error(response.error);
            }
        },
        error: function() {
            toastr.error('Error al actualizar el contenido');
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html('<i class=\"fas fa-save\"></i> Guardar Cambios');
        }
    });
});

// Preview de nuevo archivo
$('#nuevo_archivo').on('change', function() {
    const file = this.files[0];
    if (file) {
        $('#nuevoArchivoInfo').removeClass('d-none');
        $('#nuevoArchivoNombre').text(file.name);
        $('#nuevoArchivoTamano').text((file.size / (1024 * 1024)).toFixed(2) + ' MB');
    }
});

// Preview de nueva miniatura
$('#nueva_miniatura').on('change', function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#thumbnailPreview').html('<img src=\"' + e.target.result + '\" class=\"img-thumbnail\" style=\"max-width: 200px;\">');
        };
        reader.readAsDataURL(file);
    }
});
";

// Generar contenido
ob_start();
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-1"></i>
                    Editar: <?php echo htmlspecialchars($content['titulo']); ?>
                </h3>
                <div class="card-tools">
                    <a href="<?php echo BASE_URL; ?>views/content/view.php?id=<?php echo $contentId; ?>" 
                       class="btn btn-info btn-sm">
                        <i class="fas fa-eye"></i> Ver Detalles
                    </a>
                </div>
            </div>
            
            <form id="editForm" enctype="multipart/form-data">
                <div class="card-body">
                    <!-- Información básica -->
                    <div class="form-group">
                        <label for="titulo">Título <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control" 
                               id="titulo" 
                               name="titulo" 
                               required 
                               maxlength="200"
                               value="<?php echo htmlspecialchars($content['titulo']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" 
                                  id="descripcion" 
                                  name="descripcion" 
                                  rows="3"><?php echo htmlspecialchars($content['descripcion'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="categoria">Categoría</label>
                                <select class="form-control" id="categoria" name="categoria">
                                    <option value="">Sin categoría</option>
                                    <?php 
                                    $defaultCategories = [
                                        'pelicula' => ['Acción', 'Comedia', 'Drama', 'Terror', 'Ciencia Ficción'],
                                        'musica' => ['Pop', 'Rock', 'Salsa', 'Cumbia', 'Reggaeton'],
                                        'juego' => ['Puzzle', 'Arcade', 'Aventura', 'Estrategia']
                                    ];
                                    
                                    $catList = $defaultCategories[$content['tipo']] ?? [];
                                    foreach ($catList as $cat): 
                                        $catValue = strtolower($cat);
                                    ?>
                                        <option value="<?php echo $catValue; ?>" 
                                                <?php echo $content['categoria'] === $catValue ? 'selected' : ''; ?>>
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="genero">Género</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="genero" 
                                       name="genero" 
                                       maxlength="50"
                                       value="<?php echo htmlspecialchars($content['genero'] ?? ''); ?>">
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
                                       value="<?php echo htmlspecialchars($content['año_lanzamiento'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($content['tipo'] !== 'juego'): ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="calificacion">Calificación</label>
                                <select class="form-control" id="calificacion" name="calificacion">
                                    <option value="">Sin calificación</option>
                                    <?php 
                                    $ratings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                                    foreach ($ratings as $rating): 
                                    ?>
                                        <option value="<?php echo $rating; ?>" 
                                                <?php echo $content['calificacion'] === $rating ? 'selected' : ''; ?>>
                                            <?php echo $rating; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="activo" <?php echo $content['estado'] === 'activo' ? 'selected' : ''; ?>>
                                        Activo
                                    </option>
                                    <option value="inactivo" <?php echo $content['estado'] === 'inactivo' ? 'selected' : ''; ?>>
                                        Inactivo
                                    </option>
                                    <option value="procesando" <?php echo $content['estado'] === 'procesando' ? 'selected' : ''; ?>>
                                        Procesando
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Archivos -->
                    <hr>
                    <h5>Actualizar Archivos</h5>
                    
                    <div class="form-group">
                        <label>Archivo Actual</label>
                        <div class="bg-light p-3 rounded">
                            <i class="fas fa-file mr-2"></i>
                            <?php echo basename($content['archivo_path']); ?>
                            <br>
                            <small class="text-muted">
                                Tamaño: <?php echo number_format($content['tamaño_archivo'] / (1024 * 1024), 1); ?> MB
                                <?php if ($content['duracion']): ?>
                                    | Duración: <?php 
                                        $minutes = floor($content['duracion'] / 60);
                                        $seconds = $content['duracion'] % 60;
                                        echo sprintf('%d:%02d', $minutes, $seconds);
                                    ?>
                                <?php endif; ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="nuevo_archivo">Reemplazar Archivo (Opcional)</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="nuevo_archivo" 
                                   name="archivo">
                            <label class="custom-file-label" for="nuevo_archivo">Seleccionar nuevo archivo...</label>
                        </div>
                        <small class="text-muted">
                            Deje vacío para mantener el archivo actual
                        </small>
                        <div id="nuevoArchivoInfo" class="alert alert-info mt-2 d-none">
                            <strong>Nuevo archivo:</strong> 
                            <span id="nuevoArchivoNombre"></span> 
                            (<span id="nuevoArchivoTamano"></span>)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Miniatura Actual</label>
                        <div class="mb-2">
                            <?php if ($content['thumbnail_path']): ?>
                                <img src="<?php echo BASE_URL; ?>../content/<?php echo htmlspecialchars($content['thumbnail_path']); ?>" 
                                     alt="Miniatura actual" 
                                     class="img-thumbnail" 
                                     style="max-width: 200px;">
                            <?php else: ?>
                                <div class="bg-light p-3 rounded text-center" style="width: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                    <br>
                                    <small class="text-muted">Sin miniatura</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <label for="nueva_miniatura">Cambiar Miniatura (Opcional)</label>
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="nueva_miniatura" 
                                   name="thumbnail" 
                                   accept="image/*">
                            <label class="custom-file-label" for="nueva_miniatura">Seleccionar imagen...</label>
                        </div>
                        <div id="thumbnailPreview" class="mt-2"></div>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <a href="<?php echo BASE_URL; ?>views/content/view.php?id=<?php echo $contentId; ?>" 
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
    
    <div class="col-md-4">
        <!-- Información adicional -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-1"></i>
                    Información del Archivo
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Tipo:</strong></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $content['tipo'] === 'pelicula' ? 'primary' : 
                                    ($content['tipo'] === 'musica' ? 'success' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($content['tipo']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Estado:</strong></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $content['estado'] === 'activo' ? 'success' : 
                                    ($content['estado'] === 'procesando' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst($content['estado']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Subido:</strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($content['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Modificado:</strong></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($content['updated_at'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Descargas:</strong></td>
                        <td><?php echo $content['descargas_count'] ?? 0; ?></td>
                    </tr>
                    <?php if ($content['archivo_hash']): ?>
                    <tr>
                        <td><strong>Hash:</strong></td>
                        <td>
                            <small class="text-monospace">
                                <?php echo substr($content['archivo_hash'], 0, 16); ?>...
                            </small>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <!-- Acciones rápidas -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Acciones Rápidas
                </h3>
            </div>
            <div class="card-body">
                <button class="btn btn-block btn-outline-primary" 
                        onclick="window.open('<?php echo BASE_URL; ?>../content/<?php echo $content['archivo_path']; ?>', '_blank')">
                    <i class="fas fa-download"></i> Descargar Archivo
                </button>
                
                <?php if ($content['tipo'] === 'pelicula'): ?>
                    <button class="btn btn-block btn-outline-info mt-2">
                        <i class="fas fa-play"></i> Preview
                    </button>
                <?php endif; ?>
                
                <button class="btn btn-block btn-outline-danger mt-2" 
                        onclick="if(confirm('¿Eliminar este contenido?')) { deleteContent(<?php echo $contentId; ?>); }">
                    <i class="fas fa-trash"></i> Eliminar Contenido
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function deleteContent(id) {
    window.location.href = '<?php echo BASE_URL; ?>api/content/delete.php?id=' + id;
}
</script>

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