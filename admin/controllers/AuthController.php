<?php
/**
 * Controlador de Autenticación PLAYMI
 * Maneja login, logout y verificación de permisos
 */

require_once 'BaseController.php';
require_once __DIR__ . '/../models/User.php';

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    /**
     * Procesar login
     */
    public function processLogin() {
        try {
            // Verificar que sea POST
            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }
            
            // Obtener datos del formulario
            $username = $this->sanitizeInput($this->postParam('username'));
            $password = $this->postParam('password');
            $remember = $this->postParam('remember') === '1';
            
            // Validar entrada
            $errors = $this->validateInput($_POST, [
                'username' => [
                    'required' => true,
                    'label' => 'Usuario',
                    'min_length' => 3,
                    'max_length' => 50
                ],
                'password' => [
                    'required' => true,
                    'label' => 'Contraseña',
                    'min_length' => 3
                ]
            ]);
            
            if (!empty($errors)) {
                if ($this->isAjax()) {
                    $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
                } else {
                    $this->setMessage('Por favor corrige los errores en el formulario', MSG_ERROR);
                    $this->redirect('login.php');
                }
            }
            
            // Intentar autenticación
            $user = $this->userModel->authenticate($username, $password);
            
            if ($user) {
                // Login exitoso
                $this->login($user);
                
                // Configurar cookie "recordar" si se solicitó
                if ($remember) {
                    $this->setRememberCookie($user['id']);
                }
                
                // Registrar actividad
                $this->logActivity('login_success', 'usuarios', $user['id'], null, [
                    'username' => $username,
                    'remember' => $remember
                ]);
                
                // Respuesta
                if ($this->isAjax()) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Login exitoso',
                        'redirect' => BASE_URL . 'index.php'
                    ]);
                } else {
                    $this->setMessage('Bienvenido, ' . $user['nombre_completo'], MSG_SUCCESS);
                    $this->redirect('index.php');
                }
                
            } else {
                // Login fallido
                $this->logActivity('login_failed', 'usuarios', null, null, [
                    'username' => $username,
                    'ip' => $_SERVER['REMOTE_ADDR']
                ]);
                
                if ($this->isAjax()) {
                    $this->jsonResponse(['error' => 'Usuario o contraseña incorrectos'], 401);
                } else {
                    $this->setMessage('Usuario o contraseña incorrectos', MSG_ERROR);
                    $this->redirect('login.php');
                }
            }
            
        } catch (Exception $e) {
            $this->logError("Error en processLogin: " . $e->getMessage());
            
            if ($this->isAjax()) {
                $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
            } else {
                $this->setMessage('Error interno del sistema', MSG_ERROR);
                $this->redirect('login.php');
            }
        }
    }
    
    /**
     * Procesar logout
     */
    public function processLogout() {
        try {
            $userId = $_SESSION['admin_id'] ?? null;
            
            // Registrar actividad antes de cerrar sesión
            if ($userId) {
                $this->logActivity('logout', 'usuarios', $userId);
            }
            
            // Eliminar cookie "recordar" si existe
            $this->clearRememberCookie();
            
            // Cerrar sesión
            $this->logout();
            
            // Redirigir al login
            $this->setMessage('Sesión cerrada correctamente', MSG_INFO);
            $this->redirect('login.php');
            
        } catch (Exception $e) {
            $this->logError("Error en processLogout: " . $e->getMessage());
            $this->redirect('login.php');
        }
    }
    
    /**
     * Verificar autenticación automática por cookie
     */
    public function checkRememberMe() {
        try {
            if ($this->isAuthenticated()) {
                return true; // Ya está autenticado
            }
            
            // Verificar cookie "recordar"
            if (!isset($_COOKIE['playmi_remember'])) {
                return false;
            }
            
            $rememberToken = $_COOKIE['playmi_remember'];
            
            // Buscar usuario por token (simplificado - en producción usar tokens más seguros)
            $userId = $this->decodeRememberToken($rememberToken);
            if (!$userId) {
                $this->clearRememberCookie();
                return false;
            }
            
            $user = $this->userModel->findById($userId);
            if (!$user || !$user['activo']) {
                $this->clearRememberCookie();
                return false;
            }
            
            // Auto-login
            $this->login($user);
            
            // Registrar actividad
            $this->logActivity('auto_login', 'usuarios', $user['id'], null, [
                'method' => 'remember_cookie'
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logError("Error en checkRememberMe: " . $e->getMessage());
            $this->clearRememberCookie();
            return false;
        }
    }
    
    /**
     * Cambiar contraseña
     */
    public function changePassword() {
        try {
            $this->requireAuth();
            
            if (!$this->isPost()) {
                $this->jsonResponse(['error' => 'Método no permitido'], 405);
            }
            
            $currentPassword = $this->postParam('current_password');
            $newPassword = $this->postParam('new_password');
            $confirmPassword = $this->postParam('confirm_password');
            
            // Validaciones
            $errors = [];
            
            if (empty($currentPassword)) {
                $errors['current_password'] = 'Contraseña actual es requerida';
            }
            
            if (empty($newPassword)) {
                $errors['new_password'] = 'Nueva contraseña es requerida';
            } elseif (strlen($newPassword) < 6) {
                $errors['new_password'] = 'La nueva contraseña debe tener al menos 6 caracteres';
            }
            
            if ($newPassword !== $confirmPassword) {
                $errors['confirm_password'] = 'Las contraseñas no coinciden';
            }
            
            if (!empty($errors)) {
                $this->jsonResponse(['error' => 'Datos inválidos', 'errors' => $errors], 400);
            }
            
            // Cambiar contraseña
            $userId = $_SESSION['admin_id'];
            $result = $this->userModel->updatePassword($userId, $newPassword, $currentPassword);
            
            if ($result['success'] ?? false) {
                $this->logActivity('password_changed', 'usuarios', $userId);
                $this->jsonResponse(['success' => true, 'message' => 'Contraseña cambiada correctamente']);
            } else {
                $this->jsonResponse(['error' => $result['error'] ?? 'Error al cambiar la contraseña'], 400);
            }
            
        } catch (Exception $e) {
            $this->logError("Error en changePassword: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Verificar estado de sesión (para AJAX)
     */
    public function checkSession() {
        try {
            $isAuthenticated = $this->isAuthenticated();
            $user = $isAuthenticated ? $this->getCurrentUser() : null;
            
            $this->jsonResponse([
                'authenticated' => $isAuthenticated,
                'user' => $user ? [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'nombre_completo' => $user['nombre_completo']
                ] : null,
                'session_time_left' => $isAuthenticated ? (SESSION_TIMEOUT - (time() - $_SESSION['last_activity'])) : 0
            ]);
            
        } catch (Exception $e) {
            $this->logError("Error en checkSession: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Error interno del sistema'], 500);
        }
    }
    
    /**
     * Establecer cookie "recordar"
     */
    private function setRememberCookie($userId) {
        $token = $this->generateRememberToken($userId);
        $expiry = time() + (30 * 24 * 60 * 60); // 30 días
        
        setcookie('playmi_remember', $token, $expiry, '/', '', false, true); // HttpOnly
    }
    
    /**
     * Eliminar cookie "recordar"
     */
    private function clearRememberCookie() {
        if (isset($_COOKIE['playmi_remember'])) {
            setcookie('playmi_remember', '', time() - 3600, '/', '', false, true);
            unset($_COOKIE['playmi_remember']);
        }
    }
    
    /**
     * Generar token para "recordar" (simplificado)
     */
    private function generateRememberToken($userId) {
        // En producción usar método más seguro con base de datos
        return base64_encode($userId . '|' . hash('sha256', $userId . SECRET_KEY . time()));
    }
    
    /**
     * Decodificar token "recordar" (simplificado)
     */
    private function decodeRememberToken($token) {
        try {
            $decoded = base64_decode($token);
            $parts = explode('|', $decoded);
            return isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

// Definir clave secreta si no existe
if (!defined('SECRET_KEY')) {
    define('SECRET_KEY', 'playmi_secret_key_change_in_production');
}
?>