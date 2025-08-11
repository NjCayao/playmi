<?php
/**
 * Modelo de Usuarios PLAYMI
 * Maneja autenticación y gestión de usuarios administradores
 */

require_once 'BaseModel.php';

class User extends BaseModel {
    protected $table = 'usuarios';
    
    /**
     * Autenticar usuario
     */
    public function authenticate($username, $password) {
        try {
            $sql = "SELECT * FROM usuarios WHERE username = ? AND activo = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Actualizar último acceso
                $this->updateLastAccess($user['id']);
                return $user;
            }
            
            return false;
        } catch(Exception $e) {
            $this->logError("Error en authenticate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear nuevo usuario
     */
    public function createUser($data) {
        try {
            // Verificar que el username no exista
            if ($this->usernameExists($data['username'])) {
                return ['error' => 'El nombre de usuario ya existe'];
            }
            
            // Verificar que el email no exista
            if ($this->emailExists($data['email'])) {
                return ['error' => 'El email ya está registrado'];
            }
            
            // Hash de la contraseña
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $userId = $this->create($data);
            
            if ($userId) {
                return ['success' => true, 'user_id' => $userId];
            }
            
            return ['error' => 'Error al crear el usuario'];
            
        } catch(Exception $e) {
            $this->logError("Error en createUser: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Actualizar contraseña
     */
    public function updatePassword($userId, $newPassword, $currentPassword = null) {
        try {
            // Si se proporciona contraseña actual, verificarla
            if ($currentPassword) {
                $user = $this->findById($userId);
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    return ['error' => 'Contraseña actual incorrecta'];
                }
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $result = $this->update($userId, ['password' => $hashedPassword]);
            
            if ($result) {
                return ['success' => true];
            }
            
            return ['error' => 'Error al actualizar la contraseña'];
            
        } catch(Exception $e) {
            $this->logError("Error en updatePassword: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
    
    /**
     * Actualizar último acceso
     */
    public function updateLastAccess($userId) {
        try {
            $sql = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch(Exception $e) {
            $this->logError("Error en updateLastAccess: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si username existe
     */
    public function usernameExists($username, $excludeId = null) {
        try {
            $sql = "SELECT id FROM usuarios WHERE username = ?";
            $params = [$username];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch(Exception $e) {
            $this->logError("Error en usernameExists: " . $e->getMessage());
            return true; // En caso de error, asumir que existe para evitar duplicados
        }
    }
    
    /**
     * Verificar si email existe
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT id FROM usuarios WHERE email = ?";
            $params = [$email];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch(Exception $e) {
            $this->logError("Error en emailExists: " . $e->getMessage());
            return true;
        }
    }
    
    /**
     * Obtener usuarios activos
     */
    public function getActiveUsers() {
        try {
            $sql = "SELECT * FROM usuarios WHERE activo = 1 ORDER BY nombre_completo";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            $this->logError("Error en getActiveUsers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Activar/desactivar usuario
     */
    public function toggleStatus($userId) {
        try {
            $user = $this->findById($userId);
            if (!$user) {
                return ['error' => 'Usuario no encontrado'];
            }
            
            $newStatus = $user['activo'] ? 0 : 1;
            $result = $this->update($userId, ['activo' => $newStatus]);
            
            if ($result) {
                $status = $newStatus ? 'activado' : 'desactivado';
                return ['success' => true, 'message' => "Usuario $status correctamente"];
            }
            
            return ['error' => 'Error al cambiar el estado del usuario'];
            
        } catch(Exception $e) {
            $this->logError("Error en toggleStatus: " . $e->getMessage());
            return ['error' => 'Error interno del sistema'];
        }
    }
}
?>