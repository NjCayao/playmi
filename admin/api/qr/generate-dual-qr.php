<?php
// admin/api/qr/generate-dual-qr.php
require_once '../../libs/phpqrcode/qrlib.php';

// Generar 2 QRs: uno para WiFi y otro para la URL
function generateDualQR($wifiConfig, $packagePath) {
    // QR 1: Solo WiFi
    $wifiString = "WIFI:T:WPA;S:{$wifiConfig['ssid']};P:{$wifiConfig['password']};;";
    QRcode::png($wifiString, $packagePath . '/qr-wifi.png', QR_ECLEVEL_L, 10, 2);
    
    // QR 2: URL del portal
    $portalUrl = "http://192.168.4.1"; // IP del Raspberry Pi
    QRcode::png($portalUrl, $packagePath . '/qr-portal.png', QR_ECLEVEL_L, 10, 2);
    
    // QR 3: Combinado (algunos lectores lo soportan)
    $combinedString = $wifiString . "\n" . $portalUrl;
    QRcode::png($combinedString, $packagePath . '/qr-combined.png', QR_ECLEVEL_L, 10, 2);
}
?>