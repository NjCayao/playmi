<?php
/**
 * Página de Logout PLAYMI Admin
 * Cerrar sesión y limpiar cookies
 */

// Incluir configuración del sistema
require_once 'config/system.php';
require_once 'controllers/AuthController.php';

// Crear instancia del controlador de autenticación
$authController = new AuthController();

// Procesar logout
$authController->processLogout();

// Este código no debería ejecutarse nunca porque processLogout hace redirect
// Pero por seguridad, redirigir manualmente
header('Location: ' . BASE_URL . 'login.php');
exit;
?>