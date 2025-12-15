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
$topSellingProducts = $metricsManager->getTopSellingProducts(5);

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

        <h2 class="dashboard-section-title">📊 Estadísticas Rápidas del Sistema</h2>
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h4>👥 Usuarios Registrados</h4>
                <p class="dashboard-metric"><?= $totalUsers ?></p>
            </div>
            <div class="dashboard-card">
                <h4>🕒 Usuarios Activos (Últimas 24h)</h4>
                <p class="dashboard-metric"><?= $activeUsers ?></p>
            </div>
            <div class="dashboard-card">
                <h4>📦 Total de Productos</h4>
                <p class="dashboard-metric"><?= $totalProducts ?></p>
            </div>
            <div class="dashboard-card">
                <h4>💰 Ventas Completadas</h4>
                <p class="dashboard-metric"><?= $totalSales ?></p>
            </div>
            <div class="dashboard-card">
                <h4>💵 Ingresos Generados</h4>
                <p class="dashboard-metric">$<?= number_format($totalRevenue, 2) ?></p>
            </div>
        </div>

        <div class="top-viewed">
            <h3>🔥 Productos Más Vistos</h3>
            <ul>
                <?php foreach ($mostViewedProducts as $product): ?>
                    <li>
                        <div class="viewed-item">
                            <span class="product-name"><?= htmlspecialchars($product['pro_nombre']) ?></span>
                            <span class="view-count"><?= (int)$product['total_views'] ?> vistas</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="top-sellers">
            <h3>🏆 Top 5 Productos Más Vendidos</h3>
            <ul>
                <?php foreach ($topSellingProducts as $product): ?>
                    <li>
                        <div class="seller-item">
                            <img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" 
                                alt="<?= htmlspecialchars($product['pro_nombre']) ?>">
                            <span class="seller-name"><?= htmlspecialchars($product['pro_nombre']) ?></span>
                            <span class="seller-count"><?= (int)$product['total_vendidos'] ?> vendidos</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

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
