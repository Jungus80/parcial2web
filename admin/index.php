<?php
session_start();
require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/MetricsManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$metricsManager = new MetricsManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php'); // Redirigir al login si no es admin
    exit();
}

$message = "Bienvenido al Panel de Administración, " . $_SESSION['username'] . "!";

$totalUsers = $metricsManager->getTotalUsers();
$activeUsers = $metricsManager->getActiveUsers();
$totalProducts = $metricsManager->getTotalProducts();
$totalSales = $metricsManager->getTotalSales();
$totalRevenue = $metricsManager->getTotalRevenue();
$mostViewedProducts = $metricsManager->getMostViewedProducts();
$salesByDay = $metricsManager->getSalesByDay();

// Preparar datos para Chart.js 
$salesDates = json_encode(array_column($salesByDay, 'sale_date'));
$salesRevenue = json_encode(array_column($salesByDay, 'daily_revenue'));

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Dashboard</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="admin-container">
        <?php include 'includes/admin_header.php'; ?>
        <p class="message"><?= $message ?></p>

        <h2 data-translate-key="quick_stats"><?= Translator::get('quick_stats') ?? 'Estadísticas Rápidas' ?></h2>
        <div class="metric-card-grid">
            <div class="metric-card">
                <h3 data-translate-key="registered_users"><?= Translator::get('registered_users') ?? 'Usuarios Registrados' ?></h3>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="metric-card">
                <h3 data-translate-key="active_users_24h"><?= Translator::get('active_users_24h') ?? 'Usuarios Activos (24h)' ?></h3>
                <p><?= $activeUsers ?></p>
            </div>
            <div class="metric-card">
                <h3 data-translate-key="total_products"><?= Translator::get('total_products') ?? 'Productos Totales' ?></h3>
                <p><?= $totalProducts ?></p>
            </div>
            <div class="metric-card">
                <h3 data-translate-key="processed_sales"><?= Translator::get('processed_sales') ?? 'Ventas Procesadas' ?></h3>
                <p><?= $totalSales ?></p>
            </div>
            <div class="metric-card">
                <h3 data-translate-key="total_revenue"><?= Translator::get('total_revenue') ?? 'Ingresos Totales' ?></h3>
                <p class="revenue">$<?= number_format($totalRevenue, 2) ?></p>
            </div>
        </div>

        <h3 data-translate-key="most_viewed_products"><?= Translator::get('most_viewed_products') ?? 'Productos Más Vistos' ?></h3>
        <ul>
            <?php foreach ($mostViewedProducts as $product): ?>
                <li><?= $product['pro_nombre'] ?> (<span data-translate-key="views"><?= Translator::get('views') ?? 'vistas' ?></span>: <?= $product['views'] ?>)</li>
            <?php endforeach; ?>
        </ul>

        <h3 data-translate-key="sales_by_day"><?= Translator::get('sales_by_day') ?? 'Ventas por Día (Últimos 7 días)' ?></h3>
        <canvas id="salesChart" width="600" height="300"></canvas>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const salesChartCtx = document.getElementById('salesChart').getContext('2d');
            const salesDates = <?= $salesDates ?>; 
            const salesRevenue = <?= $salesRevenue ?>;

            // Debugging: Log data to console
            console.log('Sales Dates:', salesDates);
            console.log('Sales Revenue:', salesRevenue);

            // Ensure data is numeric for Chart.js
            const parsedSalesRevenue = salesRevenue.map(Number);

            new Chart(salesChartCtx, {
                type: 'line',
                data: {
                    labels: salesDates,
                    datasets: [{
                        label: '<?= Translator::get('daily_revenue_chart_label') ?? 'Ingresos Diarios' ?>',
                        data: parsedSalesRevenue, // Use parsed data
                        borderColor: 'var(--primary-color)',
                        tension: 0.1,
                        fill: false 
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>

    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
