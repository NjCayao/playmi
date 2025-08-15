<?php
// admin/api/qr/generate-wifi-qr.php
session_start();

// Verificación de sesión
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: image/png');
    $img = imagecreate(200, 50);
    $white = imagecolorallocate($img, 255, 255, 255);
    $red = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 10, 15, "No autorizado", $red);
    imagepng($img);
    exit;
}

require_once dirname(dirname(__DIR__)) . '/libs/phpqrcode/qrlib.php';

header('Content-Type: image/png');

// Parámetros
$ssid = $_GET['ssid'] ?? 'PLAYMI';
$password = $_GET['password'] ?? '12345678';
$hidden = ($_GET['hidden'] ?? 'false') === 'true' ? 'true' : 'false';

// WiFi string
$wifiString = "WIFI:T:WPA;S:{$ssid};P:{$password};H:{$hidden};;";

// Generar QR temporal con alta resolución
$tempFile = sys_get_temp_dir() . '/qr_' . uniqid() . '.png';
QRcode::png($wifiString, $tempFile, QR_ECLEVEL_H, 15, 0); // Sin borde, alta resolución

// Cargar QR original
$originalQR = imagecreatefrompng($tempFile);
$qrSize = imagesx($originalQR);

// Tamaño del módulo (cada cuadrito del QR)
$moduleSize = 15;

// Crear imagen final más grande
$padding = 50;
$finalSize = $qrSize + ($padding * 2);
$final = imagecreatetruecolor($finalSize, $finalSize + 60);

// Activar antialiasing para bordes suaves
imageantialias($final, true);

// Colores elegantes
$white = imagecolorallocate($final, 255, 255, 255);
$black = imagecolorallocate($final, 20, 20, 20); // Negro suave
$darkGray = imagecolorallocate($final, 60, 60, 60);
$lightGray = imagecolorallocate($final, 245, 245, 245);
$accent = imagecolorallocate($final, 59, 130, 246); // Azul elegante

// Fondo blanco
imagefill($final, 0, 0, $white);

// Sombra exterior suave (efecto elevación)
for ($i = 10; $i > 0; $i--) {
    $alpha = 120 - ($i * 10);
    $shadow = imagecolorallocatealpha($final, 0, 0, 0, $alpha);
    imagefilledrectangle($final, 
        $padding - $i, $padding - $i, 
        $finalSize - $padding + $i, $finalSize - $padding + $i, 
        $shadow);
}

// Marco principal con gradiente
imagefilledrectangle($final, 
    $padding - 2, $padding - 2, 
    $finalSize - $padding + 2, $finalSize - $padding + 2, 
    $lightGray);

// Fondo blanco del QR
imagefilledrectangle($final, 
    $padding, $padding, 
    $finalSize - $padding, $finalSize - $padding, 
    $white);

// Procesar cada módulo del QR para hacerlo redondeado
for ($y = 0; $y < $qrSize; $y += $moduleSize) {
    for ($x = 0; $x < $qrSize; $x += $moduleSize) {
        // Verificar si este módulo es negro
        $rgb = imagecolorat($originalQR, $x + ($moduleSize/2), $y + ($moduleSize/2));
        $isBlack = ($rgb == 0);
        
        if ($isBlack) {
            $destX = $x + $padding;
            $destY = $y + $padding;
            
            // Detectar marcadores de posición (las 3 esquinas grandes)
            $isPositionMarker = false;
            if (($x < 7 * $moduleSize && $y < 7 * $moduleSize) ||  // Superior izquierda
                ($x >= ($qrSize - 7 * $moduleSize) && $y < 7 * $moduleSize) ||  // Superior derecha
                ($x < 7 * $moduleSize && $y >= ($qrSize - 7 * $moduleSize))) {  // Inferior izquierda
                $isPositionMarker = true;
            }
            
            if ($isPositionMarker) {
                // Marcadores con esquinas redondeadas más pronunciadas
                $radius = 4;
                
                // Cuerpo principal
                imagefilledrectangle($final, 
                    $destX + $radius, $destY, 
                    $destX + $moduleSize - $radius, $destY + $moduleSize, 
                    $black);
                imagefilledrectangle($final, 
                    $destX, $destY + $radius, 
                    $destX + $moduleSize, $destY + $moduleSize - $radius, 
                    $black);
                
                // Esquinas redondeadas
                imagefilledellipse($final, $destX + $radius, $destY + $radius, $radius * 2, $radius * 2, $black);
                imagefilledellipse($final, $destX + $moduleSize - $radius, $destY + $radius, $radius * 2, $radius * 2, $black);
                imagefilledellipse($final, $destX + $radius, $destY + $moduleSize - $radius, $radius * 2, $radius * 2, $black);
                imagefilledellipse($final, $destX + $moduleSize - $radius, $destY + $moduleSize - $radius, $radius * 2, $radius * 2, $black);
            } else {
                // Módulos normales - círculos elegantes
                $dotSize = $moduleSize * 0.75;
                $centerX = $destX + ($moduleSize / 2);
                $centerY = $destY + ($moduleSize / 2);
                
                // Círculo principal
                imagefilledellipse($final, $centerX, $centerY, $dotSize, $dotSize, $black);
                
                // Pequeño highlight para profundidad
                $highlight = imagecolorallocatealpha($final, 255, 255, 255, 90);
                imagefilledellipse($final, $centerX - 2, $centerY - 2, $dotSize * 0.3, $dotSize * 0.3, $highlight);
            }
        }
    }
}

// Marco decorativo premium
$cornerLength = 30;
$cornerThickness = 3;
imagesetthickness($final, $cornerThickness);

// Esquinas con estilo
$corners = [
    [15, 15], // Superior izquierda
    [$finalSize - 15, 15], // Superior derecha
    [15, $finalSize - 15], // Inferior izquierda
    [$finalSize - 15, $finalSize - 15] // Inferior derecha
];

foreach ($corners as $i => $corner) {
    $x = $corner[0];
    $y = $corner[1];
    
    // Líneas de esquina
    if ($i == 0) { // Superior izquierda
        imageline($final, $x, $y + $cornerLength, $x, $y, $accent);
        imageline($final, $x, $y, $x + $cornerLength, $y, $accent);
    } elseif ($i == 1) { // Superior derecha
        imageline($final, $x - $cornerLength, $y, $x, $y, $accent);
        imageline($final, $x, $y, $x, $y + $cornerLength, $accent);
    } elseif ($i == 2) { // Inferior izquierda
        imageline($final, $x, $y - $cornerLength, $x, $y, $accent);
        imageline($final, $x, $y, $x + $cornerLength, $y, $accent);
    } else { // Inferior derecha
        imageline($final, $x - $cornerLength, $y, $x, $y, $accent);
        imageline($final, $x, $y - $cornerLength, $x, $y, $accent);
    }
    
    // Punto decorativo en cada esquina
    imagefilledellipse($final, $x, $y, 8, 8, $accent);
}

// Texto elegante con sombra
$text = "PLAYMI.PE";
$subtext = "WiFi Access";

// Sombra del texto principal
$shadowText = imagecolorallocatealpha($final, 0, 0, 0, 80);
$font = 5;
$textWidth = imagefontwidth($font) * strlen($text);
$textX = ($finalSize - $textWidth) / 2;
imagestring($final, $font, $textX + 1, $finalSize + 16, $text, $shadowText);

// Texto principal
imagestring($final, $font, $textX, $finalSize + 15, $text, $black);

// Subtexto
$font2 = 3;
$textWidth2 = imagefontwidth($font2) * strlen($subtext);
$textX2 = ($finalSize - $textWidth2) / 2;
imagestring($final, $font2, $textX2, $finalSize + 35, $subtext, $darkGray);

// Línea decorativa con gradiente
$lineY = $finalSize + 50;
for ($x = $finalSize/2 - 60; $x < $finalSize/2 + 60; $x++) {
    $alpha = 100 - abs($x - $finalSize/2) * 0.8;
    $lineColor = imagecolorallocatealpha($final, 59, 130, 246, $alpha);
    imagesetpixel($final, $x, $lineY, $lineColor);
    imagesetpixel($final, $x, $lineY + 1, $lineColor);
}

// Pequeños puntos decorativos adicionales
$dotPattern = imagecolorallocatealpha($final, 59, 130, 246, 100);
for ($i = 0; $i < 5; $i++) {
    $dotX = $finalSize/2 - 40 + ($i * 20);
    imagefilledellipse($final, $dotX, 10, 3, 3, $dotPattern);
}

// Limpiar
imagedestroy($originalQR);
@unlink($tempFile);

// Salida
imagepng($final);
imagedestroy($final);
?>