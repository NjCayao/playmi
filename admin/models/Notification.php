<?php
/**
 * Modelo de Notificaciones PLAYMI
 * Maneja el sistema de notificaciones por email
 */

require_once 'BaseModel.php';

class Notification extends BaseModel {
    // Este modelo manejará múltiples tablas relacionadas con notificaciones
    
    /**
     * Agregar notificación a la cola
     */
    public function addToQueue($companyId, $type, $data = []) {
        try {
            // Por ahora usamos la tabla logs_sistema para las notificaciones
            // En fases posteriores crearemos tablas específicas para notificaciones
            
            $logData = [
                'usuario_id' => $_SESSION['admin_id'] ?? null,
                'accion' => 'notification_queued',
                'tabla_afectada' => 'notification_queue',
                'registro_id' => $companyId,
                'valores_nuevos' => json_encode([
                    'type' => $type,
                    'data' => $data,
                    'status' => 'pending'
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $sql = "INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, valores_nuevos, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $logData['usuario_id'],
                $logData['accion'],
                $logData['tabla_afectada'],
                $logData['registro_id'],
                $logData['valores_nuevos'],
                $logData['ip_address'],
                $logData['user_agent']
            ]);
            
            if ($result) {
                return ['success' => true, 'notification_id' => $this->db->lastInsertId()];
            }
            
            return ['error' => 'Error al agregar notificación a la cola'];
            
        } catch(Exception $e) {
            $this->logError("Error en addToQueue: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Obtener notificaciones pendientes
     */
    public function getPendingNotifications($limit = 10) {
        try {
            $sql = "SELECT * FROM logs_sistema 
                    WHERE accion = 'notification_queued' 
                    AND JSON_EXTRACT(valores_nuevos, '$.status') = 'pending' 
                    ORDER BY created_at ASC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getPendingNotifications: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Marcar notificación como enviada
     */
    public function markAsSent($notificationId, $status = 'sent', $errorMessage = null) {
        try {
            // Obtener la notificación actual
            $sql = "SELECT * FROM logs_sistema WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$notificationId]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                return ['error' => 'Notificación no encontrada'];
            }
            
            // Actualizar el estado en el JSON
            $data = json_decode($notification['valores_nuevos'], true);
            $data['status'] = $status;
            $data['sent_at'] = date(DB_DATETIME_FORMAT);
            
            if ($errorMessage) {
                $data['error_message'] = $errorMessage;
            }
            
            $updateSql = "UPDATE logs_sistema SET valores_nuevos = ? WHERE id = ?";
            $stmt = $this->db->prepare($updateSql);
            $result = $stmt->execute([json_encode($data), $notificationId]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['error' => 'Error al actualizar estado de notificación'];
            
        } catch(Exception $e) {
            $this->logError("Error en markAsSent: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Obtener historial de notificaciones por empresa
     */
    public function getCompanyNotificationHistory($companyId, $limit = 50) {
        try {
            $sql = "SELECT * FROM logs_sistema 
                    WHERE accion = 'notification_queued' 
                    AND registro_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getCompanyNotificationHistory: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de notificaciones
     */
    public function getNotificationStats($days = 30) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_notifications,
                        COUNT(CASE WHEN JSON_EXTRACT(valores_nuevos, '$.status') = 'sent' THEN 1 END) as sent,
                        COUNT(CASE WHEN JSON_EXTRACT(valores_nuevos, '$.status') = 'failed' THEN 1 END) as failed,
                        COUNT(CASE WHEN JSON_EXTRACT(valores_nuevos, '$.status') = 'pending' THEN 1 END) as pending
                    FROM logs_sistema 
                    WHERE accion = 'notification_queued' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getNotificationStats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpiar notificaciones antiguas
     */
    public function cleanOldNotifications($days = 90) {
        try {
            $sql = "DELETE FROM logs_sistema 
                    WHERE accion = 'notification_queued' 
                    AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch(Exception $e) {
            $this->logError("Error en cleanOldNotifications: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verificar si una empresa necesita notificación de vencimiento
     */
    public function shouldSendExpiryNotification($companyId) {
        try {
            // Verificar si ya se envió notificación en los últimos 7 días
            $sql = "SELECT COUNT(*) as count FROM logs_sistema 
                    WHERE accion = 'notification_queued' 
                    AND registro_id = ? 
                    AND JSON_EXTRACT(valores_nuevos, '$.type') = 'license_expiry' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] == 0; // Solo enviar si no se ha enviado en los últimos 7 días
            
        } catch(Exception $e) {
            $this->logError("Error en shouldSendExpiryNotification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registrar error en logs
     */
    protected function logError($message) {
        $logFile = LOGS_PATH . 'notification-errors-' . date('Y-m-d') . '.log';
        $logEntry = date(DATETIME_FORMAT) . " - Notification: $message" . PHP_EOL;
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>