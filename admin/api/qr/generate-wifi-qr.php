<?php
// admin/api/qr/generate-wifi-qr.php
require_once '../../libs/phpqrcode/qrlib.php';

header('Content-Type: image/png');

// Obtener parámetros
$ssid = $_GET['ssid'] ?? 'PLAYMI-WIFI';
$password = $_GET['password'] ?? '';
$hidden = $_GET['hidden'] === 'true' ? 'true' : 'false';
$security = $_GET['security'] ?? 'WPA';

// Formato estándar WiFi QR
$wifiString = "WIFI:T:{$security};S:{$ssid};P:{$password};H:{$hidden};;";

// Generar QR en memoria y enviarlo como imagen
QRcode::png($wifiString, false, QR_ECLEVEL_L, 8, 2);
?>