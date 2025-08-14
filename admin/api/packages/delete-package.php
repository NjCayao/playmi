<?php
/**
 * API para eliminar paquetes
 * Elimina registro de BD y archivos físicos
 */

session_start();

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Obtener ID del paquete
$packageId = $_POST['package_id'] ?? null;

if (!$packageId) {
    echo json_encode(['success' => false, 'error' => 'ID de paquete requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // 1. Obtener información del paquete antes de eliminar
    $stmt = $db->prepare("
        SELECT pg.*, c.nombre as empresa_nombre 
        FROM paquetes_generados pg
        LEFT JOIN companies c ON pg.empresa_id = c.id
        WHERE pg.id = :id
    ");
    $stmt->execute(['id' => $packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception('Paquete no encontrado');
    }
    
    // Verificar que no esté instalado o en generación
    if (in_array($package['estado'], ['instalado', 'generando'])) {
        throw new Exception('No se puede eliminar un paquete ' . $package['estado']);
    }
    
    // 2. Eliminar archivos físicos
    $deletedFiles = [];
    
    // Definir ruta base de paquetes
    $packagesBaseDir = dirname(dirname(dirname(__DIR__))) . '/packages/';
    
    // Ruta del archivo principal del paquete
    if ($package['ruta_paquete'] && file_exists($package['ruta_paquete'])) {
        if (unlink($package['ruta_paquete'])) {
            $deletedFiles[] = basename($package['ruta_paquete']);
        }
    }
    
    // Buscar en la carpeta de la empresa
    $companyPackageDir = $packagesBaseDir . $package['empresa_id'] . '/';
    if (is_dir($companyPackageDir)) {
        // Buscar archivos del paquete
        $pattern = 'package_' . $packageId . '_*.zip';
        $files = glob($companyPackageDir . $pattern);
        foreach ($files as $file) {
            if (file_exists($file) && unlink($file)) {
                $deletedFiles[] = basename($file);
            }
        }
    }
    
    // 3. Eliminar registros relacionados de la BD
    
    // Eliminar contenido del paquete
    try {
        $stmt = $db->prepare("DELETE FROM paquetes_contenido WHERE paquete_id = :id");
        $stmt->execute(['id' => $packageId]);
        $contentDeleted = $stmt->rowCount();
    } catch (PDOException $e) {
        $contentDeleted = 0;
    }
    
    // 4. Eliminar el paquete principal
    $stmt = $db->prepare("DELETE FROM paquetes_generados WHERE id = :id");
    $stmt->execute(['id' => $packageId]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception('No se pudo eliminar el paquete de la base de datos');
    }
    
    // 5. Registrar la eliminación en logs
    if (isset($_SESSION['admin_id'])) {
        try {
            $stmt = $db->prepare("
                INSERT INTO logs_sistema (
                    usuario_id, accion, tabla_afectada, registro_id, 
                    valores_nuevos, created_at
                ) VALUES (
                    :usuario_id, :accion, :tabla_afectada, :registro_id, 
                    :valores_nuevos, NOW()
                )
            ");
            $stmt->execute([
                'usuario_id' => $_SESSION['admin_id'],
                'accion' => 'delete_package',
                'tabla_afectada' => 'paquetes_generados',
                'registro_id' => $packageId,
                'valores_nuevos' => json_encode([
                    'package_name' => $package['nombre_paquete'],
                    'company' => $package['empresa_nombre'],
                    'files_deleted' => count($deletedFiles),
                    'content_deleted' => $contentDeleted
                ])
            ]);
        } catch (PDOException $e) {
            // Si falla el log, continuar
        }
    }
    
    // Confirmar transacción
    $db->commit();
    
    // Calcular espacio liberado
    $spaceFreed = $package['tamanio_paquete'] ?? 0;
    $spaceFreedMB = round($spaceFreed / 1024 / 1024, 2);
    
    echo json_encode([
        'success' => true,
        'message' => 'Paquete eliminado exitosamente',
        'details' => [
            'files_deleted' => count($deletedFiles),
            'space_freed' => $spaceFreedMB . ' MB',
            'content_deleted' => $contentDeleted
        ]
    ]);
    
} catch (Exception $e) {
    // Revertir cambios si hay error
    if (isset($db)) {
        $db->rollBack();
    }
    
    error_log('Error al eliminar paquete: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>