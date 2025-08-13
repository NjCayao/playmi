<?php
// Test de estadísticas - PARA CONFIGURACIÓN SINGLETON
require_once 'config/system.php';
require_once 'config/database.php';
require_once 'models/Company.php';

echo "<h3>🔍 Test de Estadísticas</h3>";

echo "<h4>Test de conexión:</h4>";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    exit;
}

echo "<h4>Datos directos de la base de datos:</h4>";
try {
    // Query manual para verificar datos
    $sql = "SELECT estado, COUNT(*) as total FROM companies GROUP BY estado";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $directResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Query directa por estado:</strong><br>";
    foreach ($directResults as $row) {
        echo "Estado '{$row['estado']}': {$row['total']} empresas<br>";
    }
    
    // Total general
    $sqlTotal = "SELECT COUNT(*) as total FROM companies";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute();
    $totalResult = $stmtTotal->fetch(PDO::FETCH_ASSOC);
    echo "<strong>Total general:</strong> {$totalResult['total']} empresas<br>";
    
} catch (Exception $e) {
    echo "❌ Error en query directa: " . $e->getMessage() . "<br>";
}

echo "<h4>Usando métodos del modelo:</h4>";

$companyModel = new Company();

try {
    $stats = [
        'total' => $companyModel->count(),
        'active' => $companyModel->count("estado = 'activo'"),
        'suspended' => $companyModel->count("estado = 'suspendido'"), 
        'expired' => $companyModel->count("estado = 'vencido'")
    ];

    foreach ($stats as $key => $value) {
        echo "<strong>$key:</strong> $value<br>";
    }
} catch (Exception $e) {
    echo "❌ Error en métodos del modelo: " . $e->getMessage() . "<br>";
}

echo "<h4>Usando el controlador:</h4>";
try {
    // Simular sesión para el controlador
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = 1; // Simular usuario logueado
    
    require_once 'controllers/CompanyController.php';
    $controller = new CompanyController();
    $controllerData = $controller->index();

    echo "<strong>Stats del controlador:</strong><br>";
    echo "<pre>";
    print_r($controllerData['stats']);
    echo "</pre>";
    
    echo "<strong>Total de empresas encontradas:</strong> " . count($controllerData['companies']) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error en el controlador: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h4>Verificar tabla companies:</h4>";
try {
    $sql = "DESCRIBE companies";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<strong>Estructura de la tabla:</strong><br>";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    // También verificar si hay datos
    $sql = "SELECT COUNT(*) as total FROM companies";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<br><strong>Registros en la tabla:</strong> {$result['total']}<br>";
    
} catch (Exception $e) {
    echo "❌ Error verificando tabla: " . $e->getMessage() . "<br>";
}
?>