<?php
echo "<h2>🧪 TEST RÁPIDO DEL DASHBOARD</h2>";

// Test 1: Verificar Chart.js
echo "<h3>📊 TEST Chart.js</h3>";
echo "<script src='https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js'></script>";
echo "<canvas id='testChart' width='200' height='100'></canvas>";
echo "<script>
const ctx = document.getElementById('testChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Test'],
        datasets: [{
            data: [10],
            backgroundColor: ['#007bff']
        }]
    }
});
console.log('Chart.js funcionando');
</script>";

echo "<p>Si ves un gráfico azul arriba, Chart.js funciona ✅</p>";

// Test 2: Verificar DashboardController
echo "<h3>🎮 TEST DashboardController</h3>";
try {
    require_once '../admin/controllers/DashboardController.php';
    $controller = new DashboardController();
    $data = $controller->getMainStats();
    echo "✅ DashboardController funciona<br>";
    echo "📊 Empresas activas: " . ($data['companies']['active'] ?? 0) . "<br>";
    echo "💰 Ingresos mensuales: S/ " . number_format($data['revenue']['monthly_total'] ?? 0, 2) . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>