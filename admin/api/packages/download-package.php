<?php
/**
 * MÓDULO 2.3.6: API para descargar paquetes generados
 * Endpoint de descarga con validación de permisos y registro de actividad
 * 
 * Proceso de descarga:
 * 1. Validar permisos de descarga
 * 2. Verificar que archivo existe
 * 3. Registrar descarga en base de datos
 * 4. Configurar headers para descarga
 * 5. Stream del archivo al cliente
 * 6. Log de actividad
 */

// Incluir configuración
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../models/Package.php';
require_once __DIR__ . '/../../models/Company.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar que se proporcionó ID del paquete
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'ID de paquete inválido']);
    exit;
}

$packageId = (int)$_GET['id'];

try {
    // Inicializar modelos
    $packageModel = new Package();
    $companyModel = new Company();
    
    // Obtener información del paquete
    $package = $packageModel->findById($packageId);
    
    if (!$package) {
        throw new Exception('Paquete no encontrado');
    }
    
    // Verificar que el paquete está listo para descarga
    if ($package['estado'] !== 'listo' && $package['estado'] !== 'descargado' && $package['estado'] !== 'instalado') {
        throw new Exception('El paquete no está disponible para descarga. Estado actual: ' . $package['estado']);
    }
    
    // Verificar permisos - en este caso simplificado, cualquier admin puede descargar
    // En producción, podrías verificar permisos más específicos por empresa
    
    // Verificar que el archivo existe
    if (empty($package['ruta_paquete']) || !file_exists($package['ruta_paquete'])) {
        throw new Exception('Archivo del paquete no encontrado en el servidor');
    }
    
    // Obtener información de la empresa
    $company = $companyModel->findById($package['empresa_id']);
    if (!$company) {
        throw new Exception('Empresa asociada no encontrada');
    }
    
    // Verificar integridad del archivo si hay checksum
    if (!empty($package['checksum'])) {
        $currentChecksum = hash_file('sha256', $package['ruta_paquete']);
        if ($currentChecksum !== $package['checksum']) {
            throw new Exception('Error de integridad: el archivo ha sido modificado o está corrupto');
        }
    }
    
    // Preparar nombre del archivo para descarga
    $downloadFilename = sprintf(
        'PLAYMI_%s_%s_v%s.zip',
        preg_replace('/[^a-zA-Z0-9_-]/', '_', $company['nombre']),
        date('Y-m-d', strtotime($package['fecha_generacion'])),
        $package['version_paquete']
    );
    
    // Registrar la descarga
    $updateData = [
        'estado' => 'descargado',
        'ultima_descarga' => date('Y-m-d H:i:s')
    ];
    
    // Si es la primera vez que se descarga, mantener el estado 'listo'
    if ($package['estado'] === 'listo') {
        $updateData['estado'] = 'descargado';
    }
    
    $packageModel->update($packageId, $updateData);
    
    // Registrar en log de actividad
    logActivity([
        'usuario_id' => $_SESSION['admin_id'],
        'accion' => 'download_package',
        'tabla_afectada' => 'paquetes_generados',
        'registro_id' => $packageId,
        'descripcion' => "Descarga de paquete: {$package['nombre_paquete']} para {$company['nombre']}",
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    // Configurar headers para descarga
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadFilename . '"');
    header('Content-Length: ' . filesize($package['ruta_paquete']));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Headers adicionales de seguridad
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Información adicional en headers personalizados
    header('X-Package-ID: ' . $packageId);
    header('X-Package-Version: ' . $package['version_paquete']);
    header('X-Package-Checksum: ' . ($package['checksum'] ?? 'none'));
    header('X-Company-ID: ' . $company['id']);
    
    // Limpiar cualquier buffer de salida previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Stream del archivo
    $handle = fopen($package['ruta_paquete'], 'rb');
    if ($handle === false) {
        throw new Exception('Error al abrir el archivo para lectura');
    }
    
    // Configurar para no tener límite de tiempo en la descarga
    set_time_limit(0);
    
    // Enviar archivo en chunks para mejor performance
    $chunkSize = 8192; // 8KB por chunk
    while (!feof($handle)) {
        $buffer = fread($handle, $chunkSize);
        echo $buffer;
        
        // Flush para enviar inmediatamente al cliente
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        
        // Verificar si la conexión sigue activa
        if (connection_aborted()) {
            break;
        }
    }
    
    fclose($handle);
    
    // Si llegamos aquí, la descarga fue exitosa
    exit;
    
} catch (Exception $e) {
    // En caso de error, responder con JSON
    http_response_code(500);
    header('Content-Type: application/json');
    
    // Registrar error en log
    error_log("Error en download-package.php: " . $e->getMessage());
    
    // Registrar en log de actividad
    logActivity([
        'usuario_id' => $_SESSION['admin_id'] ?? null,
        'accion' => 'download_package_error',
        'tabla_afectada' => 'paquetes_generados',
        'registro_id' => $packageId ?? null,
        'descripcion' => "Error al descargar paquete: " . $e->getMessage(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
    
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    exit;
}

/**
 * Función para registrar actividad
 */
function logActivity($data) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO logs_sistema (
                    usuario_id, accion, tabla_afectada, registro_id, 
                    valores_nuevos, ip_address, user_agent, created_at
                ) VALUES (
                    :usuario_id, :accion, :tabla_afectada, :registro_id,
                    :valores_nuevos, :ip_address, :user_agent, NOW()
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $data['usuario_id'],
            ':accion' => $data['accion'],
            ':tabla_afectada' => $data['tabla_afectada'],
            ':registro_id' => $data['registro_id'],
            ':valores_nuevos' => json_encode(['descripcion' => $data['descripcion']]),
            ':ip_address' => $data['ip_address'],
            ':user_agent' => $data['user_agent']
        ]);
        
    } catch (Exception $e) {
        error_log("Error registrando actividad: " . $e->getMessage());
    }
}
?>