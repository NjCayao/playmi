<?php
require_once __DIR__ . '/../../config/system.php';
require_once __DIR__ . '/../../models/Company.php';

try {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
    
    $companyId = (int)($_GET['id'] ?? 0);
    
    if (!$companyId) {
        $_SESSION['message'] = 'ID de empresa inválido';
        $_SESSION['message_type'] = 'error';
        header('Location: ' . BASE_URL . 'views/companies/index.php');
        exit;
    }
    
    $companyModel = new Company();
    $company = $companyModel->findById($companyId);
    
    if (!$company) {
        $_SESSION['message'] = 'Empresa no encontrada';
        $_SESSION['message_type'] = 'error';
        header('Location: ' . BASE_URL . 'views/companies/index.php');
        exit;
    }
    
    $result = $companyModel->delete($companyId);
    
    if ($result) {
        $_SESSION['message'] = 'Empresa eliminada correctamente';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Error al eliminar la empresa';
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: ' . BASE_URL . 'views/companies/index.php');
    exit;
    
} catch (Exception $e) {
    $_SESSION['message'] = 'Error interno del servidor';
    $_SESSION['message_type'] = 'error';
    header('Location: ' . BASE_URL . 'views/companies/index.php');
    exit;
}
?>