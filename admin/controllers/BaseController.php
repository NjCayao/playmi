<?php

/**
 * Controlador Base PLAYMI
 * Clase padre que contiene funcionalidades comunes para todos los controladores
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/system.php';
require_once __DIR__ . '/../config/constants.php';

class BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->initializeSession();
    }

    /**
     * Inicializar sesión y verificar timeout
     */
    protected function initializeSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        // Verificar timeout de sesión
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
                $this->destroySession();
                $this->redirect('login.php?timeout=1');
            }
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Verificar si el usuario está autenticado
     */
    public function isAuthenticated()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Requerir autenticación - redirige al login si no está autenticado
     */
    public function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('login.php');
        }
    }

    /**
     * Obtener datos del usuario actual
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        try {
            require_once __DIR__ . '/../models/User.php';
            $userModel = new User();
            return $userModel->findById($_SESSION['admin_id']);
        } catch (Exception $e) {
            $this->logError("Error obteniendo usuario actual: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Iniciar sesión de usuario
     */
    public function login($user)
    {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        $this->destroySession();
    }

    /**
     * Destruir sesión completamente
     */
    protected function destroySession()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }

    /**
     * Redirigir a una URL
     */
    public function redirect($url)
    {
        // Si la URL no empieza con http, asumir que es relativa al admin
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = BASE_URL . $url;
        }

        header("Location: $url");
        exit;
    }

    /**
     * Establecer mensaje flash
     */
    public function setMessage($message, $type = MSG_INFO)
    {
        $_SESSION['flash_message'] = [
            'text' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }

    /**
     * Obtener y limpiar mensaje flash
     */
    public function getMessage()
    {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);

            // Verificar que el mensaje no sea muy antiguo (más de 5 minutos)
            if (time() - $message['timestamp'] < 300) {
                return $message;
            }
        }
        return null;
    }

    /**
     * Validar entrada de datos
     */
    public function validateInput($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            // Requerido
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['label'] . ' es requerido';
                continue;
            }

            // Si no es requerido y está vacío, continuar
            if (empty($value)) {
                continue;
            }

            // Longitud mínima
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = $rule['label'] . ' debe tener al menos ' . $rule['min_length'] . ' caracteres';
            }

            // Longitud máxima
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = $rule['label'] . ' no puede tener más de ' . $rule['max_length'] . ' caracteres';
            }

            // Email
            if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = $rule['label'] . ' debe ser un email válido';
            }

            // Numérico
            if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                $errors[$field] = $rule['label'] . ' debe ser un número';
            }

            // Fecha
            if (isset($rule['date']) && $rule['date']) {
                $dateObj = DateTime::createFromFormat('Y-m-d', $value);
                if (!$dateObj || $dateObj->format('Y-m-d') !== $value) {
                    $errors[$field] = $rule['label'] . ' debe ser una fecha válida (YYYY-MM-DD)';
                }
            }

            // Patrón personalizado
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['message'] ?? $rule['label'] . ' tiene formato inválido';
            }
        }

        return $errors;
    }

    /**
     * Sanitizar datos de entrada
     */
    public function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Respuesta JSON
     */
    public function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Manejar subida de archivos
     */
    public function handleFileUpload($file, $uploadPath, $allowedTypes, $maxSize)
    {
        try {
            // Verificar errores de subida
            if (!isset($file['error']) || is_array($file['error'])) {
                throw new RuntimeException('Parámetros de archivo inválidos');
            }

            switch ($file['error']) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    throw new RuntimeException('No se seleccionó ningún archivo');
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    throw new RuntimeException('El archivo excede el tamaño máximo permitido');
                default:
                    throw new RuntimeException('Error desconocido en la subida');
            }

            // Verificar tamaño
            if ($file['size'] > $maxSize) {
                throw new RuntimeException('El archivo excede el tamaño máximo de ' . $this->formatFileSize($maxSize));
            }

            // Verificar tipo de archivo
            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension'] ?? '');

            if (!in_array($extension, $allowedTypes)) {
                throw new RuntimeException('Tipo de archivo no permitido. Tipos permitidos: ' . implode(', ', $allowedTypes));
            }

            // Crear nombre único
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $targetPath = $uploadPath . $fileName;

            // Crear directorio si no existe
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new RuntimeException('Error al guardar el archivo');
            }

            return [
                'success' => true,
                'filename' => $fileName,
                'path' => $targetPath,
                'size' => $file['size'],
                'original_name' => $file['name']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatear tamaño de archivo
     */
    public function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Registrar actividad del sistema
     */
    public function logActivity($action, $table, $recordId, $oldData = null, $newData = null)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs_sistema (usuario_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $_SESSION['admin_id'] ?? null,
                $action,
                $table,
                $recordId,
                $oldData ? json_encode($oldData) : null,
                $newData ? json_encode($newData) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (Exception $e) {
            $this->logError("Error registrando actividad: " . $e->getMessage());
        }
    }

    /**
     * Registrar errores
     */
    public function logError($message, $context = [])
    {
        $logFile = LOGS_PATH . 'controller-errors-' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date(DATETIME_FORMAT),
            'message' => $message,
            'context' => $context,
            'user_id' => $_SESSION['admin_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];

        file_put_contents($logFile, json_encode($logEntry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtener parámetro GET con valor por defecto
     */
    public function getParam($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Obtener parámetro POST con valor por defecto
     */
    public function postParam($key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Verificar si la petición es AJAX
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verificar si la petición es POST
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Verificar si la petición es GET
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
}
