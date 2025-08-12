<?php
/**
 * API para importar empresas desde CSV
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

try {
    $companyController = new CompanyController();
    $companyController->requireAuth();
    
    if (!isset($_FILES['csv_file'])) {
        echo json_encode(['success' => false, 'error' => 'No se seleccionó archivo']);
        exit;
    }
    
    $file = $_FILES['csv_file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Error al subir archivo']);
        exit;
    }
    
    // Procesar CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        echo json_encode(['success' => false, 'error' => 'No se pudo leer el archivo']);
        exit;
    }
    
    $imported = 0;
    $errors = [];
    $lineNumber = 0;
    
    // Saltar header si existe
    $header = fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== false) {
        $lineNumber++;
        
        if (count($data) < 5) {
            $errors[] = "Línea $lineNumber: Faltan columnas";
            continue;
        }
        
        $companyData = [
            'nombre' => trim($data[0]),
            'email_contacto' => trim($data[1]),
            'tipo_paquete' => trim($data[2]),
            'fecha_inicio' => trim($data[3]),
            'fecha_vencimiento' => trim($data[4]),
            'estado' => 'activo',
            'costo_mensual' => $data[5] ?? 0
        ];
        
        // Validar datos básicos
        if (empty($companyData['nombre']) || empty($companyData['email_contacto'])) {
            $errors[] = "Línea $lineNumber: Nombre y email son requeridos";
            continue;
        }
        
        // Intentar crear empresa
        try {
            $companyModel = new Company();
            $result = $companyModel->create($companyData);
            
            if ($result) {
                $imported++;
            } else {
                $errors[] = "Línea $lineNumber: Error al crear empresa";
            }
        } catch (Exception $e) {
            $errors[] = "Línea $lineNumber: " . $e->getMessage();
        }
    }
    
    fclose($handle);
    
    echo json_encode([
        'success' => true,
        'message' => "Importadas $imported empresas correctamente",
        'imported' => $imported,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>