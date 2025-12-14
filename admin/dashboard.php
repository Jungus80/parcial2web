<?php
require_once __DIR__ . '/../clases/MetricsManager.php';
require_once __DIR__ . '/includes/admin_header.php';

$metricsManager = new MetricsManager();

$totalUsers = $metricsManager->getTotalUsers();
$activeUsers = $metricsManager->getActiveUsers(24);
$totalProducts = $metricsManager->getTotalProducts();
$totalSales = $metricsManager->getTotalSales();
$totalRevenue = $metricsManager->getTotalRevenue();
$mostViewed = $metricsManager->getMostViewedProducts(3);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo</title>
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .dashboard-container {
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        .metric h3 {
            font-size: 1rem;
            color: #333;
            margin-bottom: 5px;
        }
        .metric p {
            font-size: 1.3rem;
            font-weight: bold;
            color: #007bff;
        }
        .product-list {
            list-style: none;
            margin-top: 20px;
            padding: 0;
        }
        .product-list li {
            background: #fff;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <h2>Estadísticas Rápidas del Sistema</h2>
    <div class="dashboard-grid">
        <div class="card">
            <div class="icon">👥</div>
            <div class="metric">
                <h3>Usuarios Registrados</h3>
                <p><?= $totalUsers ?></p>
            </div>
        </div>
        <div class="card">
            <div class="icon">🟢</div>
            <div class="metric">
                <h3>Usuarios Activos (Últimas 24h)</h3>
                <p><?= $activeUsers ?></p>
            </div>
        </div>
        <div class="card">
            <div class="icon">📦</div>
            <div class="metric">
                <h3>Total de Productos</h3>
                <p><?= $totalProducts ?></p>
            </div>
        </div>
        <div class="card">
            <div class="icon">🧾</div>
            <div class="metric">
                <h3>Ventas Completadas</h3>
                <p><?= $totalSales ?></p>
            </div>
        </div>
        <div class="card">
            <div class="icon">💰</div>
            <div class="metric">
                <h3>Ingresos Generados</h3>
                <p>$<?= number_format($totalRevenue, 2) ?></p>
            </div>
        </div>
    </div>

    <h3 style="margin-top: 30px;">Productos Más Vistos</h3>
    <ul class="product-list">
        <?php foreach ($mostViewed as $product): ?>
            <li><?= htmlspecialchars($product['pro_nombre']) ?> (Visitas: <?= htmlspecialchars($product['total_views']) ?>)</li>
        <?php endforeach; ?>
    </ul>
</div>
</body>
<?php require_once __DIR__ . '/../footer.php'; ?>
</html>
