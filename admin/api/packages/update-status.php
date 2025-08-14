<?php
/**
 * MÓDULO 2.3.7: API para actualizar estado de paquetes
 * Endpoint para cambiar el estado de un paquete y registrar el cambio
 * 
 * Estados posibles:
 * - generando: Paquete en proceso de creación
 * - listo: Paquete completado y disponible
 * - descargado: Paquete descargado por empresa
 * - instalado: Paquete instalado en Raspberry Pi
 * - activo: Paquete funcionando en producción
 * - error: Error en generación o instalación
 * - vencido: Licencia expirada
 */

// Incluir configuración
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../models/Package.php';
require_once __DIR__ . '/../../controllers/BaseController.php';

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Método no permitido. Use POST']);
    exit;
}

// Configurar respuesta JSON
header('Content-Type: application/json');

try {
    // Obtener y validar datos
    $packageId = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    $piId = isset($_POST['pi_id']) ? trim($_POST['pi_id']) : null;
    $installationData = isset($_POST['installation_data']) ? $_POST['installation_data'] : null;
    
    // Validar ID del paquete
    if (!$packageId) {
        throw new Exception('ID de paquete requerido');
    }
    
    // Estados válidos
    $validStatuses = [
        'generando',
        'listo',
        'descargado',
        'instalado',
        'activo',
        'error',
        'vencido'
    ];
    
    // Validar estado
    if (!in_array($newStatus, $validStatuses)) {
        throw new Exception('Estado inválido. Estados permitidos: ' . implode(', ', $validStatuses));
    }
    
    // Inicializar modelo
    $packageModel = new Package();
    
    // Obtener paquete actual
    $currentPackage = $packageModel->findById($packageId);
    if (!$currentPackage) {
        throw new Exception('Paquete no encontrado');
    }
    
    // Validar transiciones de estado
    $allowedTransitions = [
        'generando' => ['listo', 'error'],
        'listo' => ['descargado', 'vencido', 'error'],
        'descargado' => ['instalado', 'listo', 'vencido', 'error'],
        'instalado' => ['activo', 'descargado', 'vencido', 'error'],
        'activo' => ['instalado', 'vencido', 'error'],
        'error' => ['generando', 'listo'],
        'vencido' => ['listo'] // Permitir regenerar desde vencido
    ];
    
    $currentStatus = $currentPackage['estado'];
    
    // Verificar si la transición es válida
    if (!isset($allowedTransitions[$currentStatus]) || 
        !in_array($newStatus, $allowedTransitions[$currentStatus])) {
        throw new Exception("Transición de estado no permitida: {$currentStatus} → {$newStatus}");
    }
    
    // Preparar datos adicionales según el nuevo estado
    $additionalData = [];
    
    switch ($newStatus) {
        case 'instalado':
            // Si se está instalando, registrar información del Pi
            if ($piId) {
                $additionalData['pi_id'] = $piId;
                $additionalData['fecha_instalacion'] = date('Y-m-d H:i:s');
            }
            
            // Si hay datos de instalación adicionales
            if ($installationData) {
                $additionalData['datos_instalacion'] = is_array($installationData) 
                    ? json_encode($installationData) 
                    : $installationData;
            }
            break;
            
        case 'activo':
            $additionalData['fecha_activacion'] = date('Y-m-d H:i:s');
            break;
            
        case 'error':
            // Registrar mensaje de error si se proporciona
            if ($notes) {
                $additionalData['mensaje_error'] = $notes;
            }
            break;
            
        case 'vencido':
            $additionalData['fecha_vencimiento_real'] = date('Y-m-d H:i:s');
            break;
    }
    
    // Si hay notas adicionales, agregarlas
    if ($notes) {
        $currentNotes = $currentPackage['notas'] ?? '';
        $separator = $currentNotes ? "\n---\n" : "";
        $timestamp = date('d/m/Y H:i');
        $user = $_SESSION['admin_username'] ?? 'Sistema';
        
        $additionalData['notas'] = $currentNotes . $separator . 
            "[{$timestamp}] {$user}: {$notes}";
    }
    
    // Actualizar estado
    $result = $packageModel->updateStatus($packageId, $newStatus, $additionalData);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Error al actualizar estado');
    }
    
    // Registrar en log de actividad
    logStatusChange($packageId, $currentStatus, $newStatus, $notes);
    
    // Acciones adicionales según el nuevo estado
    performStatusActions($packageId, $newStatus, $currentPackage);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'package_id' => $packageId,
        'old_status' => $currentStatus,
        'new_status' => $newStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Registrar cambio de estado en logs
 */
function logStatusChange($packageId, $oldStatus, $newStatus, $notes = null) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $logData = [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $_SESSION['admin_username'] ?? 'Sistema',
            'notes' => $notes
        ];
        
        $sql = "INSERT INTO logs_sistema (
                    usuario_id, accion, tabla_afectada, registro_id,
                    valores_anteriores, valores_nuevos, ip_address, user_agent
                ) VALUES (
                    :usuario_id, :accion, :tabla_afectada, :registro_id,
                    :valores_anteriores, :valores_nuevos, :ip_address, :user_agent
                )";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $_SESSION['admin_id'] ?? null,
            ':accion' => 'package_status_change',
            ':tabla_afectada' => 'paquetes_generados',
            ':registro_id' => $packageId,
            ':valores_anteriores' => json_encode(['estado' => $oldStatus]),
            ':valores_nuevos' => json_encode($logData),
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
    } catch (Exception $e) {
        error_log("Error registrando cambio de estado: " . $e->getMessage());
    }
}

/**
 * Realizar acciones adicionales según el nuevo estado
 */
function performStatusActions($packageId, $newStatus, $packageData) {
    try {
        switch ($newStatus) {
            case 'listo':
                // Notificar que el paquete está listo
                sendPackageReadyNotification($packageData);
                break;
                
            case 'instalado':
                // Registrar instalación exitosa
                registerSuccessfulInstallation($packageData);
                break;
                
            case 'error':
                // Notificar error a administradores
                sendErrorNotification($packageData);
                break;
                
            case 'vencido':
                // Marcar contenido como no disponible
                markPackageContentAsExpired($packageId);
                break;
        }
    } catch (Exception $e) {
        error_log("Error en acciones de estado: " . $e->getMessage());
    }
}

/**
 * Enviar notificación de paquete listo (placeholder)
 */
function sendPackageReadyNotification($packageData) {
    // En producción, aquí se enviaría un email o notificación
    error_log("Paquete {$packageData['id']} está listo para descarga");
}

/**
 * Registrar instalación exitosa (placeholder)
 */
function registerSuccessfulInstallation($packageData) {
    // En producción, aquí se registraría la instalación
    error_log("Paquete {$packageData['id']} instalado exitosamente");
}

/**
 * Enviar notificación de error (placeholder)
 */
function sendErrorNotification($packageData) {
    // En producción, aquí se enviaría una alerta
    error_log("Error en paquete {$packageData['id']}");
}

/**
 * Marcar contenido del paquete como expirado (placeholder)
 */
function markPackageContentAsExpired($packageId) {
    // En producción, aquí se actualizaría el estado del contenido
    error_log("Contenido del paquete {$packageId} marcado como expirado");
}
?>