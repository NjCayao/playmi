<?php
/**
 * Modelo para logs de actividad del sistema
 */

require_once 'BaseModel.php';

class ActivityLog extends BaseModel {
    protected $table = 'logs_sistema';  // ← Usar tabla existente
    
    /**
     * Crear log de actividad
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO logs_sistema (
                        usuario_id, accion, tabla_afectada, registro_id, 
                        descripcion, ip_address, user_agent, created_at
                    ) VALUES (
                        :usuario_id, :accion, :tabla_afectada, :registro_id,
                        :descripcion, :ip_address, :user_agent, NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':usuario_id' => $data['user_id'],
                ':accion' => $data['action'],
                ':tabla_afectada' => $data['table_name'],
                ':registro_id' => $data['record_id'],
                ':descripcion' => $data['description'],
                ':ip_address' => $data['ip_address'],
                ':user_agent' => $data['user_agent']
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            error_log("Error creating log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener logs por tabla y registro
     */
    public function getByRecord($tableName, $recordId, $limit = 50) {
        try {
            $sql = "SELECT ls.*, u.username 
                    FROM logs_sistema ls
                    LEFT JOIN users u ON ls.usuario_id = u.id
                    WHERE ls.tabla_afectada = :tabla_afectada AND ls.registro_id = :registro_id
                    ORDER BY ls.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':tabla_afectada', $tableName);
            $stmt->bindParam(':registro_id', $recordId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener actividad reciente
     */
    public function getRecentActivity($limit = 20) {
        try {
            $sql = "SELECT al.*, u.username 
                    FROM activity_logs al
                    LEFT JOIN users u ON al.user_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formatear para timeline
            $formatted = [];
            foreach ($logs as $log) {
                $formatted[] = [
                    'action' => $this->formatAction($log['action'], $log['table_name']),
                    'user' => $log['username'] ?? 'Sistema',
                    'timestamp' => $log['created_at'],
                    'time_ago' => $this->timeAgo($log['created_at']),
                    'icon' => $this->getActionIcon($log['action']),
                    'color' => $this->getActionColor($log['action']),
                    'record_name' => $this->getRecordName($log['table_name'], $log['record_id'])
                ];
            }
            
            return $formatted;
        } catch (PDOException $e) {
            error_log("Error getting recent activity: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Formatear acción para mostrar
     */
    private function formatAction($action, $table) {
        $actions = [
            'create' => 'Creó',
            'update' => 'Actualizó',
            'delete' => 'Eliminó',
            'login' => 'Inició sesión',
            'logout' => 'Cerró sesión'
        ];
        
        $tables = [
            'companies' => 'empresa',
            'content' => 'contenido',
            'users' => 'usuario'
        ];
        
        $actionText = $actions[$action] ?? $action;
        $tableText = $tables[$table] ?? $table;
        
        return $actionText . ' ' . $tableText;
    }
    
    /**
     * Obtener nombre del registro
     */
    private function getRecordName($table, $recordId) {
        try {
            switch ($table) {
                case 'companies':
                    $sql = "SELECT nombre FROM companies WHERE id = :id";
                    break;
                case 'users':
                    $sql = "SELECT username FROM users WHERE id = :id";
                    break;
                default:
                    return null;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $recordId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? array_values($result)[0] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Calcular tiempo transcurrido
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'hace ' . $time . ' segundos';
        if ($time < 3600) return 'hace ' . floor($time/60) . ' minutos';
        if ($time < 86400) return 'hace ' . floor($time/3600) . ' horas';
        if ($time < 2592000) return 'hace ' . floor($time/86400) . ' días';
        
        return date('d/m/Y', strtotime($datetime));
    }
    
    /**
     * Obtener icono para acción
     */
    private function getActionIcon($action) {
        $icons = [
            'create' => 'fas fa-plus',
            'update' => 'fas fa-edit',
            'delete' => 'fas fa-trash',
            'login' => 'fas fa-sign-in-alt',
            'logout' => 'fas fa-sign-out-alt'
        ];
        
        return $icons[$action] ?? 'fas fa-info';
    }
    
    /**
     * Obtener color para acción
     */
    private function getActionColor($action) {
        $colors = [
            'create' => 'success',
            'update' => 'info',
            'delete' => 'danger',
            'login' => 'primary',
            'logout' => 'secondary'
        ];
        
        return $colors[$action] ?? 'info';
    }
}
?>