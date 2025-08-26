<?php
/**
 * passenger-portal/api/track-usage.php
 * API para registrar uso y estadísticas
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../../admin/config/database.php';

// Obtener datos JSON del POST
$input = json_decode(file_get_contents('php://input'), true);

try {
    $db = Database::getInstance()->getConnection();
    
    // Datos recibidos
    $action = $input['action'] ?? '';
    $data = $input['data'] ?? [];
    $companyId = $input['company_id'] ?? 1;
    $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Validar acción - Agregar las acciones que usa Portal.js
    $validActions = [
        'content_view',
        'content_play', 
        'content_pause',
        'content_complete',
        'content_error',
        'content_click',  // Agregada
        'search_query',
        'heartbeat',
        'ad_interaction',
        'session_start',
        'session_end',
        'interaction'     // Agregada para compatibilidad general
    ];
    
    if (!in_array($action, $validActions)) {
        throw new Exception('Acción inválida: ' . $action);
    }
    
    // Preparar datos para guardar
    $logData = [
        'company_id' => $companyId,
        'action' => $action,
        'data' => json_encode($data),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'created_at' => $timestamp
    ];
    
    // Insertar log
    $sql = "INSERT INTO portal_usage_logs 
            (company_id, action, data, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        $logData['company_id'],
        $logData['action'],
        $logData['data'],
        $logData['ip_address'],
        $logData['user_agent'],
        $logData['created_at']
    ]);
    
    // Actualizar estadísticas específicas según la acción
    switch ($action) {
        case 'content_play':
        case 'content_click':
            updateContentStats($db, $data['id'] ?? 0, 'plays');
            break;
            
        case 'content_complete':
            updateContentStats($db, $data['id'] ?? 0, 'completions');
            break;
            
        case 'ad_interaction':
            updateAdStats($db, $data['ad_id'] ?? 0, $data['action'] ?? 'view');
            break;
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Interacción registrada',
        'tracking_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(200); // Cambiar a 200 para evitar errores en consola
    echo json_encode([
        'success' => false,
        'error' => 'Error al registrar interacción',
        'message' => $e->getMessage(),
        'debug' => [
            'action' => $action,
            'valid_actions' => $validActions ?? []
        ]
    ]);
}

// Funciones auxiliares
function updateContentStats($db, $contentId, $type) {
    if (!$contentId) return;
    
    try {
        // Incrementar contador en la tabla de contenido
        if ($type === 'plays') {
            $sql = "UPDATE contenido SET descargas_count = descargas_count + 1 WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$contentId]);
        }
    } catch (Exception $e) {
        error_log("Error updating content stats: " . $e->getMessage());
    }
}

function updateAdStats($db, $adId, $action) {
    if (!$adId) return;
    
    // Aquí podrías actualizar estadísticas de publicidad si tuvieras una tabla para ello
    error_log("Ad interaction: Ad ID $adId, Action: $action");
}