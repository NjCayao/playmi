<?php
/**
 * API para subir logo de empresa
 */

// Headers para API JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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
    // Crear instancia del controlador base
    $baseController = new BaseController();
    
    // Verificar autenticación
    $baseController->requireAuth();
    
    // Verificar parámetros
    $companyId = (int)($_POST['company_id'] ?? 0);
    
    if (!$companyId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'ID de empresa requerido'
        ]);
        exit;
    }
    
    // Verificar que se subió un archivo
    if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No se seleccionó ningún archivo o hubo un error en la subida'
        ]);
        exit;
    }
    
    $file = $_FILES['logo'];
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP'
        ]);
        exit;
    }
    
    // Validar tamaño (máximo 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB en bytes
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'El archivo es demasiado grande. Máximo 10MB'
        ]);
        exit;
    }
    
    // Crear directorio si no existe
    $uploadDir = ROOT_PATH . '/companies/data/logos/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al crear directorio de logos'
            ]);
            exit;
        }
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'logo_' . $companyId . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al guardar el archivo'
        ]);
        exit;
    }
    
    // Actualizar base de datos
    require_once __DIR__ . '/../../models/Company.php';
    $companyModel = new Company();
    
    // Obtener logo anterior para eliminarlo
    $company = $companyModel->findById($companyId);
    if ($company && $company['logo_path']) {
        $oldLogoPath = ROOT_PATH . '/companies/data/' . $company['logo_path'];
        if (file_exists($oldLogoPath)) {
            unlink($oldLogoPath);
        }
    }
    
    // Actualizar ruta del logo en la base de datos
    $relativePath = 'logos/' . $fileName;
    $updateData = [
        'logo_path' => $relativePath,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $companyModel->update($companyId, $updateData);
    
    if ($result) {
        // Registrar en log
        require_once __DIR__ . '/../../models/ActivityLog.php';
        $currentUser = $baseController->getCurrentUser();
        
        $activityLog = new ActivityLog();
        $activityLog->create([
            'user_id' => $currentUser['id'],
            'action' => 'update_logo',
            'table_name' => 'companies',
            'record_id' => $companyId,
            'description' => 'Logo actualizado para empresa: ' . ($company['nombre'] ?? ''),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Logo subido exitosamente',
            'logo_path' => $relativePath,
            'logo_url' => BASE_URL . '../companies/data/' . $relativePath
        ]);
    } else {
        // Si falla la actualización, eliminar el archivo subido
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Error al actualizar la base de datos'
        ]);
    }
    
} catch (Exception $e) {
    // Limpiar archivo si existe
    if (isset($filePath) && file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Error general
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor'
    ]);
}
?>