<?php
/**
 * Script de diagnóstico para identificar problemas
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/admin/config/system.php';
require_once __DIR__ . '/admin/config/database.php';
require_once __DIR__ . '/admin/models/Content.php';

echo "\n=== DIAGNÓSTICO DE PROBLEMAS ===\n\n";

$db = Database::getInstance()->getConnection();
$contentModel = new Content();

// 1. Verificar estructura de la tabla contenido
echo "1. Estructura de la tabla 'contenido':\n";
echo "--------------------------------------\n";
try {
    $stmt = $db->query("DESCRIBE contenido");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Columnas encontradas:\n";
    foreach ($columns as $col) {
        $nullable = $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
        $default = $col['Default'] ? " DEFAULT {$col['Default']}" : '';
        echo "  - {$col['Field']}: {$col['Type']} {$nullable}{$default}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 2. Intentar insertar un registro con información detallada
echo "\n2. Intentando insertar contenido de prueba:\n";
echo "-------------------------------------------\n";

$testData = [
    'titulo' => 'Test Diagnóstico ' . time(),
    'descripcion' => 'Descripción de prueba',
    'archivo_path' => 'test/diagnostico.mp4',
    'tamaño_archivo' => 1024000,
    'duracion' => 120,
    'tipo' => 'pelicula',
    'categoria' => 'test',
    'genero' => 'test',
    'año_lanzamiento' => 2024,
    'calificacion' => 'G',
    'estado' => 'activo',
    'descargas_count' => 0,
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

echo "Datos a insertar:\n";
foreach ($testData as $key => $value) {
    echo "  $key => $value\n";
}

// Intentar inserción directa
echo "\n3. Insertando directamente en la base de datos:\n";
echo "------------------------------------------------\n";
try {
    $fields = array_keys($testData);
    $placeholders = array_map(function($f) { return ":$f"; }, $fields);
    
    $sql = "INSERT INTO contenido (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
    echo "SQL: $sql\n\n";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute($testData);
    
    if ($result) {
        echo "✓ Inserción directa EXITOSA. ID: " . $db->lastInsertId() . "\n";
    } else {
        echo "✗ Inserción directa FALLÓ\n";
        print_r($stmt->errorInfo());
    }
} catch (Exception $e) {
    echo "✗ Error en inserción: " . $e->getMessage() . "\n";
}

// 4. Probar con el modelo
echo "\n4. Probando con el modelo Content:\n";
echo "-----------------------------------\n";
try {
    $modelData = [
        'titulo' => 'Test Modelo ' . time(),
        'archivo_path' => 'test/modelo.mp4',
        'tipo' => 'pelicula',
        'estado' => 'activo'
    ];
    
    echo "Datos mínimos para el modelo:\n";
    foreach ($modelData as $key => $value) {
        echo "  $key => $value\n";
    }
    
    $id = $contentModel->create($modelData);
    
    if ($id) {
        echo "\n✓ Inserción con modelo EXITOSA. ID: $id\n";
    } else {
        echo "\n✗ Inserción con modelo FALLÓ\n";
        // Intentar obtener el último error
        $errorInfo = $db->errorInfo();
        if ($errorInfo[0] !== '00000') {
            echo "Error SQL: " . $errorInfo[2] . "\n";
        }
    }
} catch (Exception $e) {
    echo "\n✗ Error con modelo: " . $e->getMessage() . "\n";
}

// 5. Verificar registros existentes
echo "\n5. Registros existentes en la tabla:\n";
echo "-------------------------------------\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM contenido");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total de registros: " . $result['total'] . "\n";
    
    if ($result['total'] > 0) {
        $stmt = $db->query("SELECT id, titulo, tipo, estado FROM contenido ORDER BY id DESC LIMIT 5");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\nÚltimos registros:\n";
        foreach ($records as $record) {
            echo "  - ID: {$record['id']} | {$record['titulo']} | {$record['tipo']} | {$record['estado']}\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// 6. Limpiar registros de prueba
echo "\n6. Limpiando registros de prueba:\n";
echo "----------------------------------\n";
try {
    $stmt = $db->exec("DELETE FROM contenido WHERE titulo LIKE '%Test%' OR titulo LIKE '%Diagnóstico%'");
    echo "Registros eliminados: $stmt\n";
} catch (Exception $e) {
    echo "Error limpiando: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n\n";
?>