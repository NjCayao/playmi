<?php
/**
 * MÓDULO 2.2.5: Gestión específica de juegos HTML5
 * Propósito: Administrar juegos con validación y preview
 */

require_once '../../config/system.php';
require_once '../../controllers/ContentController.php';

$controller = new ContentController();
$controller->requireAuth();

// Obtener solo juegos
$filters = ['tipo' => 'juego'];
$result = $controller->index();
$games = array_filter($result['content'] ?? [], function($item) {
    return $item['tipo'] === 'juego';
});

// Agrupar por categoría
$gamesByCategory = [];
foreach ($games as $game) {
    $category = $game['categoria'] ?? 'Sin categoría';
    if (!isset($gamesByCategory[$category])) {
        $gamesByCategory[$category] = [];
    }
    $gamesByCategory[$category][] = $game;
}

$pageTitle = 'Gestión de Juegos';
$currentPage = 'content';

ob_start();
?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">
                    <i class="fas fa-gamepad text-danger"></i> Gestión de Juegos
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="index.php">Contenido</a></li>
                    <li class="breadcrumb-item active">Juegos</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Estadísticas -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo count($games); ?></h3>
                        <p>Total Juegos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($gamesByCategory); ?></h3>
                        <p>Categorías</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php 
                        $activeGames = array_filter($games, function($g) { 
                            return $g['estado'] === 'activo'; 
                        });
                        echo count($activeGames);
                        ?></h3>
                        <p>Juegos Activos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php 
                        $totalSize = array_sum(array_column($games, 'tamanio_archivo'));
                        echo formatFileSize($totalSize);
                        ?></h3>
                        <p>Espacio Total</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista de juegos en grid -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Biblioteca de Juegos</h3>
                <div class="card-tools">
                    <div class="btn-group btn-sm mr-2">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-filter"></i> Categoría
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="?">Todas</a>
                            <div class="dropdown-divider"></div>
                            <?php foreach (array_keys($gamesByCategory) as $cat): ?>
                                <a class="dropdown-item" href="?categoria=<?php echo urlencode($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="upload.php?type=juego" class="btn btn-danger btn-sm">
                        <i class="fas fa-plus"></i> Nuevo Juego
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($games as $game): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card game-card h-100">
                            <div class="game-thumbnail position-relative">
                                <?php if ($game['thumbnail_path']): ?>
                                    <img src="<?php echo SITE_URL . 'content/' . $game['thumbnail_path']; ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($game['titulo']); ?>">
                                <?php else: ?>
                                    <div class="game-placeholder">
                                        <i class="fas fa-gamepad fa-4x"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Estado del juego -->
                                <div class="game-status">
                                    <?php if ($game['estado'] === 'activo'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Activo
                                        </span>
                                    <?php elseif ($game['estado'] === 'procesando'): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-spinner fa-spin"></i> Procesando
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-times"></i> Inactivo
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title text-truncate">
                                    <?php echo htmlspecialchars($game['titulo']); ?>
                                </h5>
                                <p class="card-text">
                                    <span class="badge badge-info">
                                        <?php echo htmlspecialchars($game['categoria'] ?? 'Sin categoría'); ?>
                                    </span>
                                    <small class="text-muted float-right">
                                        <?php echo formatFileSize($game['tamanio_archivo'] ?? 0); ?>
                                    </small>
                                </p>
                                
                                <?php if ($game['descripcion']): ?>
                                    <p class="small text-muted text-truncate">
                                        <?php echo htmlspecialchars($game['descripcion']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer">
                                <div class="btn-group btn-group-sm w-100">
                                    <button class="btn btn-danger play-game" 
                                            data-id="<?php echo $game['id']; ?>"
                                            data-title="<?php echo htmlspecialchars($game['titulo']); ?>"
                                            data-path="<?php echo $game['archivo_path']; ?>"
                                            <?php echo $game['estado'] !== 'activo' ? 'disabled' : ''; ?>>
                                        <i class="fas fa-play"></i> Jugar
                                    </button>
                                    <button class="btn btn-warning test-game" 
                                            data-id="<?php echo $game['id']; ?>"
                                            title="Validar juego">
                                        <i class="fas fa-vial"></i>
                                    </button>
                                    <a href="edit.php?id=<?php echo $game['id']; ?>" 
                                       class="btn btn-primary"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($games)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No hay juegos disponibles. 
                            <a href="upload.php?type=juego">Sube el primer juego</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información de juegos con errores -->
        <?php 
        $errorGames = array_filter($games, function($g) { 
            return $g['estado'] === 'error'; 
        });
        if (!empty($errorGames)): 
        ?>
        <div class="card card-danger collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Juegos con Errores (<?php echo count($errorGames); ?>)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Juego</th>
                            <th>Error</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errorGames as $errorGame): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($errorGame['titulo']); ?></td>
                            <td>
                                <small class="text-danger">
                                    <?php echo htmlspecialchars($errorGame['error_message'] ?? 'Error desconocido'); ?>
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-xs btn-warning revalidate-game" 
                                        data-id="<?php echo $errorGame['id']; ?>">
                                    <i class="fas fa-redo"></i> Revalidar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal para jugar -->
<div class="modal fade" id="gameModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gameTitle">Juego</h5>
                <div class="ml-auto">
                    <button type="button" class="btn btn-sm btn-secondary" id="fullscreenGame">
                        <i class="fas fa-expand"></i> Pantalla completa
                    </button>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body p-0">
                <iframe id="gameFrame" 
                        style="width: 100%; height: 600px; border: none;"
                        sandbox="allow-scripts allow-same-origin allow-popups allow-forms">
                </iframe>
            </div>
            <div class="modal-footer">
                <div class="text-left flex-grow-1">
                    <small class="text-muted" id="gameInstructions"></small>
                </div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de validación -->
<div class="modal fade" id="validationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resultado de Validación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="validationResult">
                <!-- Resultados de validación -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once '../layouts/base.php';
?>

<!-- Estilos adicionales -->
<style>
.game-card {
    transition: transform 0.2s;
    overflow: hidden;
}
.game-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}
.game-thumbnail {
    height: 200px;
    background: #343a40;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.game-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.game-placeholder {
    text-align: center;
    color: #6c757d;
}
.game-status {
    position: absolute;
    top: 10px;
    right: 10px;
}
</style>

<!-- Scripts específicos -->
<script>
$(document).ready(function() {
    // Jugar juego
    $('.play-game').click(function() {
        const gameId = $(this).data('id');
        const gameTitle = $(this).data('title');
        const gamePath = $(this).data('path');
        
        $('#gameTitle').text(gameTitle);
        
        // Extraer la ruta del juego (asumiendo estructura games/extracted/[id]/index.html)
        const gameUrl = '<?php echo SITE_URL; ?>content/games/extracted/' + gameId + '/index.html';
        $('#gameFrame').attr('src', gameUrl);
        
        $('#gameModal').modal('show');
    });
    
    // Pantalla completa
    $('#fullscreenGame').click(function() {
        const gameFrame = document.getElementById('gameFrame');
        if (gameFrame.requestFullscreen) {
            gameFrame.requestFullscreen();
        } else if (gameFrame.webkitRequestFullscreen) {
            gameFrame.webkitRequestFullscreen();
        } else if (gameFrame.msRequestFullscreen) {
            gameFrame.msRequestFullscreen();
        }
    });
    
    // Validar juego
    $('.test-game').click(function() {
        const gameId = $(this).data('id');
        const $btn = $(this);
        
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: '../../api/content/validate-game.php',
            method: 'POST',
            data: { id: gameId },
            success: function(response) {
                let resultHtml = '<div class="validation-results">';
                
                if (response.valid) {
                    resultHtml += '<div class="alert alert-success">';
                    resultHtml += '<i class="fas fa-check-circle"></i> El juego es válido';
                    resultHtml += '</div>';
                } else {
                    resultHtml += '<div class="alert alert-danger">';
                    resultHtml += '<i class="fas fa-times-circle"></i> El juego tiene errores';
                    resultHtml += '</div>';
                }
                
                // Mostrar detalles de validación
                if (response.checks) {
                    resultHtml += '<ul class="list-group">';
                    for (let check in response.checks) {
                        const passed = response.checks[check];
                        resultHtml += '<li class="list-group-item">';
                        resultHtml += passed ? 
                            '<i class="fas fa-check text-success"></i> ' : 
                            '<i class="fas fa-times text-danger"></i> ';
                        resultHtml += check;
                        resultHtml += '</li>';
                    }
                    resultHtml += '</ul>';
                }
                
                resultHtml += '</div>';
                
                $('#validationResult').html(resultHtml);
                $('#validationModal').modal('show');
            },
            error: function() {
                toastr.error('Error al validar el juego');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-vial"></i>');
            }
        });
    });
    
    // Revalidar juegos con error
    $('.revalidate-game').click(function() {
        const gameId = $(this).data('id');
        
        $.ajax({
            url: '../../api/content/extract-game.php',
            method: 'POST',
            data: { id: gameId, revalidate: true },
            success: function(response) {
                toastr.success('Juego reenviado para validación');
                setTimeout(() => location.reload(), 1500);
            },
            error: function() {
                toastr.error('Error al revalidar el juego');
            }
        });
    });
    
    // Limpiar iframe al cerrar modal
    $('#gameModal').on('hidden.bs.modal', function() {
        $('#gameFrame').attr('src', '');
    });
});
</script>

<?php
// Helper function
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}
?>