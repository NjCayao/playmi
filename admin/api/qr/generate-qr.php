
<?php
/**
 * MÓDULO 3.4: API para generar códigos QR con configuración WiFi
 * Genera QR codes con información WiFi y URL del portal
 */
require_once '../../config/system.php';
require_once '../../controllers/QRController.php';
// Verificar autenticación
session_start();
if (!isset(SESSION[′adminloggedin′])∣∣!_SESSION['admin_logged_in']) || !
S​ESSION[′adminl​oggedi​n′])∣∣!_SESSION['admin_logged_in']) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Configurar respuesta JSON
header('Content-Type: application/json');
// El controlador manejará toda la lógica
$qrController = new QRController();
$result = $qrController->generateQR();
// La respuesta ya viene en formato JSON del controlador
echo json_encode($result);
?>