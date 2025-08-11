<?php
/**
 * Footer PLAYMI Admin
 */

// Información del sistema
$systemVersion = SYSTEM_VERSION ?? '1.0.0';
$currentYear = date('Y');
?>

<!-- Main Footer -->
<footer class="main-footer">
    <strong>
        Copyright &copy; <?php echo $currentYear; ?> 
        <a href="https://playmi.com" target="_blank">PLAYMI Entertainment</a>.
    </strong>
    Todos los derechos reservados.
    
    <div class="float-right d-none d-sm-inline-block">
        <b>Versión</b> <?php echo $systemVersion; ?>
        | 
        <small class="text-muted">
            Última conexión: <?php echo date('d/m/Y H:i'); ?>
        </small>
    </div>
</footer>

<!-- Control Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <div class="p-3">
        <h5>Configuración Rápida</h5>
        
        <hr class="mb-2">
        
        <div class="mb-4">
            <input type="checkbox" value="1" checked="checked" class="mr-1">
            <span>Notificaciones Push</span>
        </div>
        
        <div class="mb-4">
            <input type="checkbox" value="1" class="mr-1">
            <span>Modo Oscuro</span>
        </div>
        
        <div class="mb-4">
            <input type="checkbox" value="1" checked="checked" class="mr-1">
            <span>Auto-refresh Dashboard</span>
        </div>
        
        <hr class="mb-2">
        
        <h6>Estadísticas Rápidas</h6>
        <div class="mb-2">
            <span class="text-sm">
                <i class="fas fa-building mr-1"></i>
                Empresas Activas: 
                <span class="float-right font-weight-bold">
                    <?php 
                    try {
                        require_once __DIR__ . '/../../models/Company.php';
                        $companyModel = new Company();
                        echo $companyModel->count("estado = 'activo'");
                    } catch (Exception $e) {
                        echo '-';
                    }
                    ?>
                </span>
            </span>
        </div>
        
        <div class="mb-2">
            <span class="text-sm">
                <i class="fas fa-film mr-1"></i>
                Contenido Total: 
                <span class="float-right font-weight-bold">
                    <?php 
                    try {
                        require_once __DIR__ . '/../../models/Content.php';
                        $contentModel = new Content();
                        echo $contentModel->count("estado = 'activo'");
                    } catch (Exception $e) {
                        echo '-';
                    }
                    ?>
                </span>
            </span>
        </div>
        
        <div class="mb-2">
            <span class="text-sm">
                <i class="fas fa-server mr-1"></i>
                Espacio Usado: 
                <span class="float-right font-weight-bold">
                    <?php 
                    $totalSpace = disk_total_space(ROOT_PATH);
                    $freeSpace = disk_free_space(ROOT_PATH);
                    if ($totalSpace && $freeSpace) {
                        $usedPercentage = (($totalSpace - $freeSpace) / $totalSpace) * 100;
                        echo number_format($usedPercentage, 1) . '%';
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </span>
        </div>
        
        <hr class="mb-2">
        
        <div class="text-center">
            <button class="btn btn-sm btn-info btn-block" onclick="refreshStats()">
                <i class="fas fa-sync-alt"></i> Actualizar Stats
            </button>
        </div>
    </div>
</aside>

<script>
function refreshStats() {
    // Mostrar loading
    const btn = event.target;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
    btn.disabled = true;
    
    // Simular actualización (en implementación real haríamos AJAX)
    setTimeout(() => {
        location.reload();
    }, 1000);
}

// Auto-actualizar estadísticas cada 5 minutos
setInterval(() => {
    $.ajax({
        url: PLAYMI.baseUrl + 'api/quick-stats.php',
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Actualizar estadísticas sin recargar página
                // Implementar según necesidades
            }
        }
    });
}, 300000); // 5 minutos
</script>