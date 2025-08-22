<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../config/database.php';

// Verificar autenticación
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar parámetro
$packageId = $_GET['package_id'] ?? 0;
if (!$packageId) {
    echo json_encode(['error' => 'ID de paquete requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener información del paquete
    $stmt = $db->prepare("
        SELECT 
            pg.*,
            (SELECT COUNT(*) FROM paquetes_contenido WHERE paquete_id = pg.id) as total_files,
            e.nombre as empresa_nombre
        FROM paquetes_generados pg
        LEFT JOIN companies e ON pg.empresa_id = e.id
        WHERE pg.id = ?
    ");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        echo json_encode(['error' => 'Paquete no encontrado']);
        exit;
    }
    
    // Simular progreso basado en el estado y tiempo transcurrido
    $progress = 0;
    $message = 'Iniciando...';
    $filesProcessed = 0;
    
    // Calcular progreso basado en el tiempo transcurrido
    $startTime = strtotime($package['fecha_generacion']);
    $currentTime = time();
    $elapsed = $currentTime - $startTime;
    
    // Estados del proceso
    if ($package['estado'] === 'listo') {
        $progress = 100;
        $message = '¡Paquete generado exitosamente!';
        $filesProcessed = $package['total_files'];
    } elseif ($package['estado'] === 'error') {
        $progress = 0;
        $message = 'Error al generar el paquete';
    } else {
        // Simular progreso basado en tiempo (máximo 60 segundos para completar)
        $estimatedTime = 30; // segundos estimados
        $progress = min(95, ($elapsed / $estimatedTime) * 100);
        
        // Mensajes según progreso
        if ($progress < 10) {
            $message = 'Preparando archivos...';
            $filesProcessed = 0;
        } elseif ($progress < 30) {
            $message = 'Copiando contenido multimedia...';
            $filesProcessed = intval($package['total_files'] * 0.2);
        } elseif ($progress < 50) {
            $message = 'Procesando archivos de video...';
            $filesProcessed = intval($package['total_files'] * 0.4);
        } elseif ($progress < 70) {
            $message = 'Generando configuraciones...';
            $filesProcessed = intval($package['total_files'] * 0.6);
        } elseif ($progress < 90) {
            $message = 'Comprimiendo paquete...';
            $filesProcessed = intval($package['total_files'] * 0.8);
        } else {
            $message = 'Finalizando...';
            $filesProcessed = $package['total_files'];
        }
        
        // Si ha pasado mucho tiempo, verificar si realmente terminó
        if ($elapsed > 120) {
            // Verificar si el archivo existe
            if (!empty($package['ruta_paquete']) && file_exists($package['ruta_paquete'])) {
                $progress = 100;
                $message = '¡Paquete generado exitosamente!';
                $filesProcessed = $package['total_files'];
                
                // Actualizar estado en BD
                $updateStmt = $db->prepare("UPDATE paquetes_generados SET estado = 'listo' WHERE id = ?");
                $updateStmt->execute([$packageId]);
            }
        }
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'package_id' => $packageId,
        'status' => $package['estado'],
        'progress' => round($progress, 2),
        'message' => $message,
        'files_processed' => $filesProcessed,
        'total_files' => $package['total_files'] ?? 0,
        'package_size' => $package['tamanio_paquete'] ? formatBytes($package['tamanio_paquete']) : null,
        'empresa' => $package['empresa_nombre']
    ];
    
    // Si está listo, incluir información adicional
    if ($package['estado'] === 'listo' && !empty($package['clave_instalacion'])) {
        $response['installation_key'] = $package['clave_instalacion'];
        $response['download_url'] = API_URL . 'packages/download-package.php?id=' . $packageId;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error al obtener progreso',
        'message' => $e->getMessage()
    ]);
}

// Función helper para formatear bytes
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>