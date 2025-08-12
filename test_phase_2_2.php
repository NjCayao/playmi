<?php
/**
 * Suite de Pruebas Simplificada - Fase 2.2
 * Sistema de Gestión de Contenido
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivos necesarios
require_once __DIR__ . '/admin/config/system.php';
require_once __DIR__ . '/admin/config/database.php';
require_once __DIR__ . '/admin/models/Content.php';

echo "\n=== PLAYMI - Pruebas Fase 2.2 ===\n";
echo "Sistema de Gestión de Contenido\n";
echo "==================================\n\n";

$db = Database::getInstance()->getConnection();
$contentModel = new Content();
$totalTests = 0;
$passedTests = 0;

// Función para ejecutar pruebas
function test($name, $callback) {
    global $totalTests, $passedTests;
    $totalTests++;
    
    try {
        $result = $callback();
        if ($result) {
            echo "  ✓ $name\n";
            $passedTests++;
        } else {
            echo "  ✗ $name\n";
        }
    } catch (Exception $e) {
        echo "  ✗ $name - Error: " . $e->getMessage() . "\n";
    }
}

// Limpiar datos de prueba
function cleanupTestData() {
    global $db;
    try {
        $db->exec("DELETE FROM contenido WHERE titulo LIKE '%Test%' OR titulo LIKE '%Prueba%'");
        echo "Datos de prueba limpiados.\n\n";
    } catch (Exception $e) {
        echo "Error limpiando datos: " . $e->getMessage() . "\n\n";
    }
}

cleanupTestData();

// 1. Pruebas básicas del modelo
echo "1. Probando modelo de contenido...\n";

test("Crear película", function() use ($contentModel) {
    $data = [
        'titulo' => 'Película Test ' . time(),
        'archivo_path' => 'movies/test.mp4',
        'tamaño_archivo' => 1024000,
        'tipo' => 'pelicula',
        'estado' => 'activo'
    ];
    $id = $contentModel->create($data);
    return $id > 0;
});

test("Crear música", function() use ($contentModel) {
    $data = [
        'titulo' => 'Canción Test ' . time(),
        'archivo_path' => 'music/test.mp3',
        'tamaño_archivo' => 5000000,
        'tipo' => 'musica',
        'estado' => 'activo'
    ];
    $id = $contentModel->create($data);
    return $id > 0;
});

test("Crear juego", function() use ($contentModel) {
    $data = [
        'titulo' => 'Juego Test ' . time(),
        'archivo_path' => 'games/test.html',
        'tamaño_archivo' => 2000000,
        'tipo' => 'juego',
        'estado' => 'activo'
    ];
    $id = $contentModel->create($data);
    return $id > 0;
});

test("Obtener contenido", function() use ($contentModel) {
    $all = $contentModel->findAll();
    return is_array($all) && count($all) > 0;
});

test("Buscar contenido", function() use ($contentModel) {
    $result = $contentModel->searchContent(['search' => 'Test'], 1, 10);
    return isset($result['data']) && is_array($result['data']);
});

test("Filtrar por tipo", function() use ($contentModel) {
    $result = $contentModel->searchContent(['tipo' => 'pelicula'], 1, 10);
    return isset($result['data']);
});

// 2. Verificar directorios
echo "\n2. Verificando directorios...\n";

$directories = [
    UPLOADS_PATH . 'movies/',
    UPLOADS_PATH . 'music/',
    UPLOADS_PATH . 'games/',
    UPLOADS_PATH . 'advertising/',
    UPLOADS_PATH . 'banners/',
    UPLOADS_PATH . 'thumbnails/'
];

$allDirsOk = true;
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "  ✓ Directorio existe: $dir\n";
    } else {
        echo "  ✗ Directorio NO existe: $dir\n";
        $allDirsOk = false;
    }
}

// 3. Verificar tablas
echo "\n3. Verificando tablas de base de datos...\n";

$tables = ['contenido', 'publicidad_empresa', 'banners_empresa'];
$allTablesOk = true;

foreach ($tables as $table) {
    try {
        $stmt = $db->query("DESCRIBE $table");
        echo "  ✓ Tabla '$table' existe\n";
    } catch (Exception $e) {
        echo "  ✗ Tabla '$table' NO existe\n";
        $allTablesOk = false;
    }
}

// 4. Verificar archivos de controladores
echo "\n4. Verificando archivos necesarios...\n";

$files = [
    __DIR__ . '/admin/models/Content.php',
    __DIR__ . '/admin/models/Advertising.php',
    __DIR__ . '/admin/controllers/ContentController.php',
    __DIR__ . '/admin/controllers/AdvertisingController.php'
];

$allFilesOk = true;
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "  ✓ Archivo existe: " . basename($file) . "\n";
    } else {
        echo "  ✗ Archivo NO existe: " . basename($file) . "\n";
        $allFilesOk = false;
    }
}

// Limpiar datos de prueba
cleanupTestData();

// Resultados finales
echo "\n==================================\n";
echo "RESULTADOS FINALES\n";
echo "==================================\n";
echo "Pruebas ejecutadas: $totalTests\n";
echo "Pruebas exitosas: $passedTests\n";
echo "Pruebas fallidas: " . ($totalTests - $passedTests) . "\n";

$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
echo "Porcentaje de éxito: {$percentage}%\n\n";

// Verificación final
if ($percentage >= 80 && $allDirsOk && $allTablesOk && $allFilesOk) {
    echo "✓ FASE 2.2 LISTA PARA USAR\n";
    echo "  - El modelo de contenido funciona\n";
    echo "  - Los directorios están creados\n";
    echo "  - Las tablas existen\n";
    echo "  - Los archivos necesarios están presentes\n";
} else {
    echo "✗ FASE 2.2 REQUIERE ATENCIÓN\n";
    
    if (!$allFilesOk) {
        echo "\nNECESITA CREAR LOS ARCHIVOS FALTANTES:\n";
        echo "- Advertising.php en admin/models/\n";
        echo "- ContentController.php en admin/controllers/\n";
        echo "- AdvertisingController.php en admin/controllers/\n";
    }
    
    if (!$allDirsOk) {
        echo "\nCREAR DIRECTORIOS FALTANTES EN content/\n";
    }
    
    if (!$allTablesOk) {
        echo "\nVERIFICAR TABLAS EN BASE DE DATOS\n";
    }
}

echo "\n==================================\n\n";
?>