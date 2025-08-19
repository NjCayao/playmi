<?php
// Habilitar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Banners.php</h2>";

// Verificar sesión
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true; // Simular login para prueba
    $_SESSION['admin_id'] = 1;
}

try {
    echo "<h3>1. Incluyendo system.php...</h3>";
    require_once '../../config/system.php';
    echo "✅ system.php cargado<br>";
    
    echo "<h3>2. Incluyendo AdvertisingController.php...</h3>";
    require_once '../../controllers/AdvertisingController.php';
    echo "✅ AdvertisingController.php cargado<br>";
    
    echo "<h3>3. Creando instancia del controlador...</h3>";
    $advertisingController = new AdvertisingController();
    echo "✅ Controlador creado<br>";
    
    echo "<h3>4. Obteniendo datos...</h3>";
    $viewData = $advertisingController->banners();
    echo "✅ Datos obtenidos<br>";
    echo "Banners encontrados: " . count($viewData['banners'] ?? []) . "<br>";
    
    echo "<h3>5. Verificando base.php...</h3>";
    $basePath = '../layouts/base.php';
    if (file_exists($basePath)) {
        echo "✅ base.php existe en: " . realpath($basePath) . "<br>";
        
        // Intentar incluir base.php
        echo "<h3>6. Intentando incluir base.php...</h3>";
        
        // Simular las variables que espera base.php
        $pageTitle = 'Test Banners';
        $content = '<div>Test content</div>';
        
        // Capturar cualquier error
        ob_start();
        include $basePath;
        $output = ob_get_clean();
        
        echo "✅ base.php incluido sin errores fatales<br>";
        echo "Longitud del output: " . strlen($output) . " caracteres<br>";
        
    } else {
        echo "❌ base.php NO existe<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "En archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}

echo "<h3>7. Variables de sesión:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>8. Constantes definidas:</h3>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NO DEFINIDO') . "<br>";
echo "ASSETS_URL: " . (defined('ASSETS_URL') ? ASSETS_URL : 'NO DEFINIDO') . "<br>";
?>