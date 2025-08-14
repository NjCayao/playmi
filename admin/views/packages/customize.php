<?php
/**
 * MÓDULO 2.3.3: Personalizar contenido específico por empresa
 * Interfaz de personalización avanzada con drag & drop y preview en tiempo real
 * 
 * Funcionalidades:
 * - Orden de contenido en portal
 * - Contenido destacado en hero banner
 * - Colores del portal según empresa
 * - Logo de empresa en portal
 * - Nombre personalizado del servicio
 * - Configuración de categorías
 */

// Incluir configuración y controlador
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/PackageController.php';

// Crear instancia del controlador
$packageController = new PackageController();

// Obtener ID de empresa
$companyId = $_GET['company_id'] ?? null;
if (!$companyId) {
    header('Location: ' . BASE_URL . 'views/packages/index.php');
    exit;
}

// Obtener datos del controlador
$data = $packageController->customize($companyId);

// Extraer variables
$company = $data['company'] ?? [];
$content = $data['content'] ?? [];
$customization = $data['customization'] ?? [];

// Configuración de la página
$pageTitle = 'Personalizar Portal - ' . $company['nombre'] . ' - PLAYMI Admin';
$contentTitle = 'Personalizar Portal';
$contentSubtitle = 'Configuración específica para ' . $company['nombre'];

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => BASE_URL . 'index.php'],
    ['title' => 'Paquetes', 'url' => BASE_URL . 'views/packages/index.php'],
    ['title' => 'Personalizar', 'url' => '#']
];

// CSS adicional
$additionalCSS = [
    ASSETS_URL . 'plugins/spectrum-colorpicker/spectrum.min.css'
];

// JS adicional
$additionalJS = [
    ASSETS_URL . 'plugins/sortablejs/Sortable.min.js',
    ASSETS_URL . 'plugins/spectrum-colorpicker/spectrum.min.js'
];

// Iniciar buffer de contenido
ob_start();
?>

<div class="row">
    <!-- Panel de configuración (izquierda) -->
    <div class="col-lg-7">
        <!-- Información de la empresa -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($company['nombre']); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <?php if ($company['logo_path']): ?>
                            <img src="<?php echo BASE_URL; ?>companies/data/<?php echo $company['logo_path']; ?>" 
                                 alt="Logo" class="img-fluid" id="companyLogo">
                        <?php else: ?>
                            <div class="text-muted">Sin logo</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <dl class="row">
                            <dt class="col-sm-4">Tipo de paquete:</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-info"><?php echo ucfirst($company['tipo_paquete']); ?></span>
                            </dd>
                            <dt class="col-sm-4">Total de buses:</dt>
                            <dd class="col-sm-8"><?php echo $company['total_buses']; ?></dd>
                            <dt class="col-sm-4">Estado:</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-<?php echo $company['estado'] == 'activo' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($company['estado']); ?>
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Branding -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-palette"></i> Configuración de Branding
                </h3>
            </div>
            <div class="card-body">
                <form id="brandingForm">
                    <input type="hidden" name="company_id" value="<?php echo $companyId; ?>">
                    
                    <div class="form-group">
                        <label for="service_name">Nombre del Servicio</label>
                        <input type="text" class="form-control" id="service_name" name="service_name" 
                               value="<?php echo htmlspecialchars($customization['service_name'] ?? $company['nombre_servicio'] ?? 'PLAYMI Entertainment'); ?>"
                               placeholder="PLAYMI Entertainment">
                        <small class="form-text text-muted">
                            Nombre que aparecerá en el portal de pasajeros
                        </small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="primary_color">Color Primario</label>
                                <input type="text" class="form-control colorpicker" id="primary_color" 
                                       name="primary_color" 
                                       value="<?php echo $customization['primary_color'] ?? $company['color_primario'] ?? '#2563eb'; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="secondary_color">Color Secundario</label>
                                <input type="text" class="form-control colorpicker" id="secondary_color" 
                                       name="secondary_color" 
                                       value="<?php echo $customization['secondary_color'] ?? $company['color_secundario'] ?? '#64748b'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="welcome_message">Mensaje de Bienvenida</label>
                        <textarea class="form-control" id="welcome_message" name="welcome_message" rows="3"><?php echo htmlspecialchars($customization['welcome_message'] ?? 'Bienvenido a bordo! Disfruta del mejor entretenimiento durante tu viaje.'); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="use_company_logo" 
                                   name="use_company_logo" 
                                   <?php echo ($customization['use_company_logo'] ?? true) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="use_company_logo">
                                Usar logo de la empresa en el portal
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="dark_mode" 
                                   name="dark_mode" 
                                   <?php echo ($customization['dark_mode'] ?? true) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="dark_mode">
                                Tema oscuro por defecto
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contenido Destacado -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-star"></i> Contenido Destacado
                </h3>
                <div class="card-tools">
                    <small class="text-muted">Arrastra para reordenar</small>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Selecciona y ordena el contenido que aparecerá en el banner principal del portal
                </p>
                
                <div id="featuredContent" class="featured-sortable">
                    <?php 
                    $featured = $customization['featured_content'] ?? [];
                    if (empty($featured)): 
                    ?>
                        <div class="empty-state text-center text-muted py-4">
                            <i class="fas fa-hand-pointer fa-3x mb-3"></i>
                            <p>Arrastra contenido desde abajo para agregarlo aquí</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($featured as $item): ?>
                            <div class="featured-item" data-id="<?php echo $item['id']; ?>" data-type="<?php echo $item['type']; ?>">
                                <i class="fas fa-grip-vertical handle"></i>
                                <img src="<?php echo BASE_URL . 'content/' . $item['thumbnail_path']; ?>" alt="Thumbnail">
                                <div class="content">
                                    <strong><?php echo htmlspecialchars($item['titulo']); ?></strong>
                                    <span class="badge badge-<?php echo $item['type'] == 'movie' ? 'danger' : ($item['type'] == 'music' ? 'success' : 'warning'); ?>">
                                        <?php echo $item['type'] == 'movie' ? 'Película' : ($item['type'] == 'music' ? 'Música' : 'Juego'); ?>
                                    </span>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger remove-featured">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Orden de Categorías -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-layer-group"></i> Orden de Categorías
                </h3>
                <div class="card-tools">
                    <small class="text-muted">Arrastra para reordenar</small>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Define el orden en que aparecerán las categorías en el portal
                </p>
                
                <div id="categoryOrder" class="category-sortable">
                    <div class="category-item" data-category="movies">
                        <i class="fas fa-grip-vertical handle"></i>
                        <i class="fas fa-film text-danger"></i>
                        <span>Películas</span>
                        <div class="custom-control custom-switch float-right">
                            <input type="checkbox" class="custom-control-input category-toggle" 
                                   id="enable_movies" checked>
                            <label class="custom-control-label" for="enable_movies"></label>
                        </div>
                    </div>
                    
                    <div class="category-item" data-category="music">
                        <i class="fas fa-grip-vertical handle"></i>
                        <i class="fas fa-music text-success"></i>
                        <span>Música</span>
                        <div class="custom-control custom-switch float-right">
                            <input type="checkbox" class="custom-control-input category-toggle" 
                                   id="enable_music" checked>
                            <label class="custom-control-label" for="enable_music"></label>
                        </div>
                    </div>
                    
                    <div class="category-item" data-category="games">
                        <i class="fas fa-grip-vertical handle"></i>
                        <i class="fas fa-gamepad text-warning"></i>
                        <span>Juegos</span>
                        <div class="custom-control custom-switch float-right">
                            <input type="checkbox" class="custom-control-input category-toggle" 
                                   id="enable_games" checked>
                            <label class="custom-control-label" for="enable_games"></label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido Disponible -->
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-database"></i> Contenido Disponible
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Arrastra contenido al área de destacados
                </p>
                
                <!-- Tabs de contenido -->
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#availableMovies">
                            <i class="fas fa-film"></i> Películas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#availableMusic">
                            <i class="fas fa-music"></i> Música
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#availableGames">
                            <i class="fas fa-gamepad"></i> Juegos
                        </a>
                    </li>
                </ul>
                
                <div class="tab-content mt-3">
                    <!-- Películas disponibles -->
                    <div class="tab-pane fade show active" id="availableMovies">
                        <div class="available-content-grid">
                            <?php foreach ($content['movies'] ?? [] as $movie): ?>
                                <div class="available-item" data-id="<?php echo $movie['id']; ?>" 
                                     data-type="movie" 
                                     data-title="<?php echo htmlspecialchars($movie['titulo']); ?>"
                                     data-thumbnail="<?php echo $movie['thumbnail_path']; ?>">
                                    <img src="<?php echo BASE_URL . 'content/' . $movie['thumbnail_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($movie['titulo']); ?>">
                                    <div class="overlay">
                                        <span><?php echo htmlspecialchars($movie['titulo']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Música disponible -->
                    <div class="tab-pane fade" id="availableMusic">
                        <div class="available-content-grid">
                            <?php foreach ($content['music'] ?? [] as $music): ?>
                                <div class="available-item" data-id="<?php echo $music['id']; ?>" 
                                     data-type="music" 
                                     data-title="<?php echo htmlspecialchars($music['titulo']); ?>"
                                     data-thumbnail="<?php echo $music['thumbnail_path']; ?>">
                                    <img src="<?php echo BASE_URL . 'content/' . $music['thumbnail_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($music['titulo']); ?>">
                                    <div class="overlay">
                                        <span><?php echo htmlspecialchars($music['titulo']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Juegos disponibles -->
                    <div class="tab-pane fade" id="availableGames">
                        <div class="available-content-grid">
                            <?php foreach ($content['games'] ?? [] as $game): ?>
                                <div class="available-item" data-id="<?php echo $game['id']; ?>" 
                                     data-type="game" 
                                     data-title="<?php echo htmlspecialchars($game['titulo']); ?>"
                                     data-thumbnail="<?php echo $game['thumbnail_path']; ?>">
                                    <img src="<?php echo BASE_URL . 'content/' . $game['thumbnail_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($game['titulo']); ?>">
                                    <div class="overlay">
                                        <span><?php echo htmlspecialchars($game['titulo']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview del portal (derecha) -->
    <div class="col-lg-5">
        <div class="card card-primary card-outline sticky-top" style="top: 20px;">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-eye"></i> Vista Previa del Portal
                </h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-tool" id="previewMobile" data-device="mobile">
                            <i class="fas fa-mobile-alt"></i>
                        </button>
                        <button type="button" class="btn btn-tool active" id="previewTablet" data-device="tablet">
                            <i class="fas fa-tablet-alt"></i>
                        </button>
                        <button type="button" class="btn btn-tool" id="previewDesktop" data-device="desktop">
                            <i class="fas fa-desktop"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body bg-dark p-3">
                <div id="portalPreview" class="portal-preview tablet">
                    <!-- Header del portal -->
                    <div class="preview-header">
                        <img id="previewLogo" src="" alt="Logo" style="display: none;">
                        <h1 id="previewServiceName">PLAYMI Entertainment</h1>
                        <p id="previewWelcome">Bienvenido a bordo!</p>
                    </div>
                    
                    <!-- Hero banner -->
                    <div class="preview-hero" id="previewHero">
                        <div class="hero-placeholder">
                            <i class="fas fa-image fa-3x"></i>
                            <p>Contenido destacado aparecerá aquí</p>
                        </div>
                    </div>
                    
                    <!-- Categorías -->
                    <div class="preview-categories" id="previewCategories">
                        <!-- Se llenarán dinámicamente -->
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-success btn-block" id="saveCustomization">
                    <i class="fas fa-save"></i> Guardar Personalización
                </button>
                <button type="button" class="btn btn-secondary btn-block mt-2" id="resetDefaults">
                    <i class="fas fa-undo"></i> Restaurar Valores por Defecto
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para personalización */
.featured-sortable {
    min-height: 100px;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 10px;
}

.featured-sortable.drag-over {
    background-color: #e3f2fd;
    border-color: #2196f3;
}

.featured-item {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    cursor: move;
}

.featured-item.sortable-ghost {
    opacity: 0.4;
}

.featured-item .handle {
    color: #6c757d;
    margin-right: 10px;
}

.featured-item img {
    width: 60px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
    margin-right: 15px;
}

.featured-item .content {
    flex: 1;
}

.featured-item .badge {
    margin-left: 10px;
}

.featured-item .remove-featured {
    margin-left: 10px;
}

.category-sortable {
    list-style: none;
    padding: 0;
}

.category-item {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: move;
}

.category-item .handle {
    color: #6c757d;
    margin-right: 15px;
}

.category-item i:not(.handle) {
    font-size: 1.2rem;
    margin-right: 10px;
}

.category-item span {
    flex: 1;
    font-weight: 500;
}

.available-content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
}

.available-item {
    position: relative;
    cursor: grab;
    transition: transform 0.2s;
}

.available-item:hover {
    transform: scale(1.05);
}

.available-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
}

.available-item .overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
    padding: 10px;
    border-radius: 0 0 8px 8px;
    font-size: 12px;
}

/* Preview del portal */
.portal-preview {
    background: #1a1a1a;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
    max-height: 600px;
    overflow-y: auto;
}

.portal-preview.mobile {
    max-width: 375px;
    margin: 0 auto;
}

.portal-preview.tablet {
    max-width: 768px;
    margin: 0 auto;
}

.portal-preview.desktop {
    max-width: 100%;
}

.preview-header {
    text-align: center;
    padding: 20px;
    color: white;
}

.preview-header img {
    max-height: 50px;
    margin-bottom: 10px;
}

.preview-header h1 {
    font-size: 24px;
    margin: 10px 0;
}

.preview-header p {
    font-size: 14px;
    opacity: 0.8;
}

.preview-hero {
    height: 200px;
    background: #333;
    position: relative;
    overflow: hidden;
}

.hero-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
}

.preview-categories {
    padding: 20px;
}

.preview-category {
    margin-bottom: 20px;
}

.preview-category h3 {
    color: white;
    font-size: 18px;
    margin-bottom: 10px;
}

.preview-content-row {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 10px;
}

.preview-content-item {
    flex: 0 0 120px;
    height: 180px;
    background: #444;
    border-radius: 4px;
}
</style>

<script>
$(document).ready(function() {
    let featuredSortable, categorySortable;
    let currentCustomization = {};
    
    // Inicializar color pickers
    $('.colorpicker').spectrum({
        type: "component",
        showInput: true,
        showInitial: true,
        showAlpha: false,
        preferredFormat: "hex",
        change: function(color) {
            updatePreview();
        }
    });
    
    // Inicializar Sortable para contenido destacado
    featuredSortable = new Sortable(document.getElementById('featuredContent'), {
        animation: 150,
        handle: '.handle',
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            updatePreview();
        }
    });
    
    // Inicializar Sortable para categorías
    categorySortable = new Sortable(document.getElementById('categoryOrder'), {
        animation: 150,
        handle: '.handle',
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            updatePreview();
        }
    });
    
    // Drag and drop desde contenido disponible
    $('.available-item').draggable({
        helper: 'clone',
        revert: 'invalid',
        appendTo: 'body',
        zIndex: 1000,
        start: function(event, ui) {
            $(ui.helper).addClass('dragging');
        }
    });
    
    $('#featuredContent').droppable({
        accept: '.available-item',
        activeClass: 'drag-over',
        drop: function(event, ui) {
            const $item = $(ui.draggable);
            const itemData = {
                id: $item.data('id'),
                type: $item.data('type'),
                title: $item.data('title'),
                thumbnail: $item.data('thumbnail')
            };
            
            // Verificar si ya existe
            if ($(`#featuredContent .featured-item[data-id="${itemData.id}"]`).length > 0) {
                toastr.warning('Este contenido ya está en destacados');
                return;
            }
            
            // Agregar a destacados
            addToFeatured(itemData);
            updatePreview();
        }
    });
    
    // Cambios en formulario
    $('#brandingForm input, #brandingForm textarea').on('input change', function() {
        updatePreview();
    });
    
    // Toggle categorías
    $('.category-toggle').on('change', function() {
        updatePreview();
    });
    
    // Cambiar vista previa dispositivo
    $('.btn-group .btn-tool').on('click', function() {
        $('.btn-group .btn-tool').removeClass('active');
        $(this).addClass('active');
        
        const device = $(this).data('device');
        $('#portalPreview').removeClass('mobile tablet desktop').addClass(device);
    });
    
    // Remover de destacados
    $(document).on('click', '.remove-featured', function() {
        $(this).closest('.featured-item').fadeOut(300, function() {
            $(this).remove();
            updatePreview();
        });
    });
    
    // Guardar personalización
    $('#saveCustomization').on('click', function() {
        const customization = collectCustomization();
        
        $.ajax({
            url: '<?php echo API_URL; ?>packages/save-customization.php',
            method: 'POST',
            data: customization,
            success: function(response) {
                if (response.success) {
                    toastr.success('Personalización guardada exitosamente');
                } else {
                    toastr.error(response.error || 'Error al guardar');
                }
            },
            error: function() {
                toastr.error('Error de conexión');
            }
        });
    });
    
    // Restaurar valores por defecto
    $('#resetDefaults').on('click', function() {
        Swal.fire({
            title: '¿Restaurar valores por defecto?',
            text: 'Se perderán todas las personalizaciones actuales',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, restaurar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                resetToDefaults();
                updatePreview();
                toastr.success('Valores restaurados');
            }
        });
    });
    
    // Inicializar preview
    updatePreview();
});

// Agregar contenido a destacados
function addToFeatured(itemData) {
    // Remover mensaje vacío si existe
    $('#featuredContent .empty-state').remove();
    
    const typeLabel = itemData.type === 'movie' ? 'Película' : (itemData.type === 'music' ? 'Música' : 'Juego');
    const typeClass = itemData.type === 'movie' ? 'danger' : (itemData.type === 'music' ? 'success' : 'warning');
    
    const featuredHtml = `
        <div class="featured-item" data-id="${itemData.id}" data-type="${itemData.type}">
            <i class="fas fa-grip-vertical handle"></i>
            <img src="<?php echo BASE_URL; ?>content/${itemData.thumbnail}" alt="Thumbnail">
            <div class="content">
                <strong>${itemData.title}</strong>
                <span class="badge badge-${typeClass}">${typeLabel}</span>
            </div>
            <button type="button" class="btn btn-sm btn-danger remove-featured">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    $('#featuredContent').append(featuredHtml);
}

// Actualizar preview del portal
function updatePreview() {
    // Actualizar header
    const serviceName = $('#service_name').val() || 'PLAYMI Entertainment';
    const welcomeMessage = $('#welcome_message').val() || 'Bienvenido a bordo!';
    const primaryColor = $('#primary_color').val();
    const secondaryColor = $('#secondary_color').val();
    const useLogo = $('#use_company_logo').is(':checked');
    
    $('#previewServiceName').text(serviceName).css('color', primaryColor);
    $('#previewWelcome').text(welcomeMessage).css('color', secondaryColor);
    
    if (useLogo && $('#companyLogo').length) {
        $('#previewLogo').attr('src', $('#companyLogo').attr('src')).show();
    } else {
        $('#previewLogo').hide();
    }
    
    // Actualizar hero con contenido destacado
    updateHeroPreview();
    
    // Actualizar categorías
    updateCategoriesPreview();
}

// Actualizar hero preview
function updateHeroPreview() {
    const $hero = $('#previewHero');
    const $featured = $('#featuredContent .featured-item');
    
    if ($featured.length === 0) {
        $hero.html(`
            <div class="hero-placeholder">
                <i class="fas fa-image fa-3x"></i>
                <p>Contenido destacado aparecerá aquí</p>
            </div>
        `);
    } else {
        // Simular carousel con primer elemento destacado
        const first = $featured.first();
        const thumbnail = first.find('img').attr('src');
        const title = first.find('strong').text();
        
        $hero.html(`
            <div style="background-image: url('${thumbnail}'); background-size: cover; 
                        background-position: center; height: 100%; position: relative;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; 
                           background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
                           padding: 20px; color: white;">
                    <h2 style="margin: 0;">${title}</h2>
                    <button class="btn btn-primary btn-sm mt-2">
                        <i class="fas fa-play"></i> Reproducir
                    </button>
                </div>
            </div>
        `);
    }
}

// Actualizar preview de categorías
function updateCategoriesPreview() {
    const $container = $('#previewCategories');
    $container.empty();
    
    $('#categoryOrder .category-item').each(function() {
        const category = $(this).data('category');
        const isEnabled = $(this).find('.category-toggle').is(':checked');
        
        if (!isEnabled) return;
        
        const categoryName = category === 'movies' ? 'Películas' : 
                           (category === 'music' ? 'Música' : 'Juegos');
        const categoryIcon = category === 'movies' ? 'film' : 
                           (category === 'music' ? 'music' : 'gamepad');
        
        const categoryHtml = `
            <div class="preview-category">
                <h3><i class="fas fa-${categoryIcon}"></i> ${categoryName}</h3>
                <div class="preview-content-row">
                    <div class="preview-content-item"></div>
                    <div class="preview-content-item"></div>
                    <div class="preview-content-item"></div>
                    <div class="preview-content-item"></div>
                </div>
            </div>
        `;
        
        $container.append(categoryHtml);
    });
}

// Recolectar datos de personalización
function collectCustomization() {
    const featured = [];
    $('#featuredContent .featured-item').each(function() {
        featured.push({
            id: $(this).data('id'),
            type: $(this).data('type'),
            order: featured.length
        });
    });
    
    const categories = [];
    $('#categoryOrder .category-item').each(function() {
        categories.push({
            category: $(this).data('category'),
            enabled: $(this).find('.category-toggle').is(':checked'),
            order: categories.length
        });
    });
    
    return {
        company_id: <?php echo $companyId; ?>,
        service_name: $('#service_name').val(),
        primary_color: $('#primary_color').val(),
        secondary_color: $('#secondary_color').val(),
        welcome_message: $('#welcome_message').val(),
        use_company_logo: $('#use_company_logo').is(':checked'),
        dark_mode: $('#dark_mode').is(':checked'),
        featured_content: featured,
        category_order: categories
    };
}

// Restaurar valores por defecto
function resetToDefaults() {
    $('#service_name').val('<?php echo htmlspecialchars($company['nombre_servicio'] ?? 'PLAYMI Entertainment'); ?>');
    $('#primary_color').spectrum('set', '<?php echo $company['color_primario'] ?? '#2563eb'; ?>');
    $('#secondary_color').spectrum('set', '<?php echo $company['color_secundario'] ?? '#64748b'; ?>');
    $('#welcome_message').val('Bienvenido a bordo! Disfruta del mejor entretenimiento durante tu viaje.');
    $('#use_company_logo').prop('checked', true);
    $('#dark_mode').prop('checked', true);
    
    // Limpiar destacados
    $('#featuredContent').html(`
        <div class="empty-state text-center text-muted py-4">
            <i class="fas fa-hand-pointer fa-3x mb-3"></i>
            <p>Arrastra contenido desde abajo para agregarlo aquí</p>
        </div>
    `);
    
    // Resetear categorías
    $('.category-toggle').prop('checked', true);
}
</script>

<?php
// Capturar contenido
$content = ob_get_clean();

// Incluir layout base
require_once __DIR__ . '/../layouts/base.php';
?>