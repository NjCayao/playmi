<?php
/**
 * API para subir logo de empresa - CON DEBUG
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// 🔍 HABILITAR DEBUG
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Incluir configuración
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/BaseController.php';

try {
    // 🔍 DEBUG: Log inicial
    error_log("🔍 DEBUG: Iniciando upload de logo");
    error_log("🔍 DEBUG: POST data: " . print_r($_POST, true));
    error_log("🔍 DEBUG: FILES data: " . print_r($_FILES, true));
    
    // Crear instancia del controlador base
    $baseController = new BaseController();
    
    // Verificar autenticación
    $baseController->requireAuth();
    
    // Verificar parámetros
    $companyId = (int)($_POST['company_id'] ?? 0);
    
    if (!$companyId) {
        echo json_encode([
            'success' => false,
            'error' => 'ID de empresa requerido',
            'debug' => ['company_id' => $companyId]
        ]);
        exit;
    }
    
    // Verificar que se subió un archivo
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['logo']['error'] ?? 'No file';
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'El archivo es más grande que upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'El archivo es más grande que MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'No se puede escribir en disco',
            UPLOAD_ERR_EXTENSION => 'Extensión de PHP detuvo la subida'
        ];
        
        echo json_encode([
            'success' => false,
            'error' => 'Error en la subida del archivo: ' . ($errorMessages[$uploadError] ?? "Error $uploadError"),
            'debug' => [
                'files_data' => $_FILES,
                'upload_error' => $uploadError,
                'php_settings' => [
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'file_uploads' => ini_get('file_uploads')
                ]
            ]
        ]);
        exit;
    }
    
    $file = $_FILES['logo'];
    
    // 🔍 DEBUG: Información del archivo
    error_log("🔍 DEBUG: Archivo recibido: " . $file['name']);
    error_log("🔍 DEBUG: Tamaño: " . $file['size']);
    error_log("🔍 DEBUG: Archivo temporal: " . $file['tmp_name']);
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    error_log("🔍 DEBUG: MIME type detectado: " . $mimeType);
    
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP',
            'debug' => [
                'detected_mime' => $mimeType,
                'allowed_types' => $allowedTypes
            ]
        ]);
        exit;
    }
    
    // Validar tamaño (máximo 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB en bytes
    if ($file['size'] > $maxSize) {
        echo json_encode([
            'success' => false,
            'error' => 'El archivo es demasiado grande. Máximo 10MB',
            'debug' => [
                'file_size' => $file['size'],
                'max_size' => $maxSize
            ]
        ]);
        exit;
    }
    
    // 🔍 DEBUG: Crear directorio
    $uploadDir = __DIR__ . '/../../../companies/data/logos/';
    $realUploadDir = realpath(dirname($uploadDir)) . '/' . basename($uploadDir);
    
    error_log("🔍 DEBUG: Directorio de upload: " . $uploadDir);
    error_log("🔍 DEBUG: Directorio real: " . $realUploadDir);
    error_log("🔍 DEBUG: ¿Directorio existe?: " . (is_dir($uploadDir) ? 'SÍ' : 'NO'));
    
    if (!is_dir($uploadDir)) {
        error_log("🔍 DEBUG: Creando directorio...");
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode([
                'success' => false,
                'error' => 'Error al crear directorio de logos',
                'debug' => [
                    'upload_dir' => $uploadDir,
                    'real_dir' => $realUploadDir,
                    'parent_exists' => is_dir(dirname($uploadDir)),
                    'parent_writable' => is_writable(dirname($uploadDir))
                ]
            ]);
            exit;
        }
        error_log("🔍 DEBUG: Directorio creado exitosamente");
    }
    
    // Verificar permisos de escritura
    if (!is_writable($uploadDir)) {
        echo json_encode([
            'success' => false,
            'error' => 'No hay permisos de escritura en directorio de logos',
            'debug' => [
                'upload_dir' => $uploadDir,
                'is_writable' => is_writable($uploadDir),
                'permissions' => substr(sprintf('%o', fileperms($uploadDir)), -4)
            ]
        ]);
        exit;
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'logo_' . $companyId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    error_log("🔍 DEBUG: Nombre del archivo: " . $fileName);
    error_log("🔍 DEBUG: Ruta completa: " . $filePath);
    error_log("🔍 DEBUG: Archivo temporal existe: " . (file_exists($file['tmp_name']) ? 'SÍ' : 'NO'));
    
    // 🔍 DEBUG: Intentar mover archivo
    error_log("🔍 DEBUG: Intentando mover de {$file['tmp_name']} a {$filePath}");
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al guardar el archivo',
            'debug' => [
                'source' => $file['tmp_name'],
                'destination' => $filePath,
                'source_exists' => file_exists($file['tmp_name']),
                'dest_dir_writable' => is_writable($uploadDir),
                'last_error' => error_get_last()
            ]
        ]);
        exit;
    }
    
    error_log("🔍 DEBUG: Archivo movido exitosamente");
    error_log("🔍 DEBUG: ¿Archivo existe en destino?: " . (file_exists($filePath) ? 'SÍ' : 'NO'));
    
    // Actualizar base de datos
    require_once __DIR__ . '/../../models/Company.php';
    $companyModel = new Company();
    
    // Obtener logo anterior para eliminarlo
    $company = $companyModel->findById($companyId);
    if ($company && $company['logo_path']) {
        $oldLogoPath = __DIR__ . '/../../../companies/data/' . $company['logo_path'];
        if (file_exists($oldLogoPath)) {
            unlink($oldLogoPath);
            error_log("🔍 DEBUG: Logo anterior eliminado: " . $oldLogoPath);
        }
    }
    
    // Actualizar ruta del logo en la base de datos
    $relativePath = 'logos/' . $fileName;
    $updateData = [
        'logo_path' => $relativePath,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    error_log("🔍 DEBUG: Actualizando BD con ruta: " . $relativePath);
    
    $result = $companyModel->update($companyId, $updateData);
    
    if ($result) {
        error_log("🔍 DEBUG: Base de datos actualizada exitosamente");
        
        echo json_encode([
            'success' => true,
            'message' => 'Logo subido exitosamente',
            'logo_path' => $relativePath,
            'logo_url' => 'http://localhost/playmi/companies/data/' . $relativePath,
            'debug' => [
                'file_saved_to' => $filePath,
                'file_exists' => file_exists($filePath),
                'file_size' => file_exists($filePath) ? filesize($filePath) : 0,
                'relative_path' => $relativePath
            ]
        ]);
    } else {
        // Si falla la actualización, eliminar el archivo subido
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar la base de datos',
            'debug' => [
                'file_was_uploaded' => file_exists($filePath),
                'db_update_failed' => true
            ]
        ]);
    }
    
} catch (Exception $e) {
    // Limpiar archivo si existe
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    error_log("🔍 DEBUG: Excepción: " . $e->getMessage());
    
    // Error general
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage(),
        'debug' => [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>