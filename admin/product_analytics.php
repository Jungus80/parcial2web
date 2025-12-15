<?php
session_start();
require_once __DIR__ . '/../clases/MetricsManager.php';
require_once __DIR__ . '/includes/admin_header.php';

$metricsManager = new MetricsManager();

$mostViewedProducts = $metricsManager->getMostViewedProducts(10);
$leastViewedProducts = $metricsManager->getLeastViewedProducts(10);
?>
<link rel="stylesheet" href="admin_styles.css">

<div class="admin-container">
    <h2 class="dashboard-section-title">📈 Análisis de Productos</h2>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h4>🔥 Productos más vistos</h4>
            <p class="dashboard-metric"><?= count($mostViewedProducts) ?></p>
            <small>Top 10 productos con más vistas registradas.</small>
        </div>
        <div class="dashboard-card">
            <h4>🧊 Menos vistos</h4>
            <p class="dashboard-metric"><?= count($leastViewedProducts) ?></p>
            <small>Top 10 menos visualizados.</small>
        </div>
    </div>

    <div class="top-viewed">
        <h3>🔥 Top 10 Productos Más Vistos</h3>
        <?php if (!empty($mostViewedProducts)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Imagen</th>
                    <th>Vistas Totales</th>
                    <th>Permanencia Promedio (s)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mostViewedProducts as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['pro_id']) ?></td>
                        <td><a href="../product_detail.php?id=<?= $product['pro_id'] ?>"><?= htmlspecialchars($product['pro_nombre']) ?></a></td>
                        <td><img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" alt="Img" width="50" height="50" style="border-radius:5px;object-fit:cover;"></td>
                        <td><?= htmlspecialchars($product['total_views']) ?></td>
                        <td><?= number_format(htmlspecialchars($product['average_permanence']), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No hay datos disponibles para los productos más vistos.</p>
        <?php endif; ?>
    </div>

    <div class="top-sellers">
        <h3>❄️ Top 10 Productos Menos Vistos</h3>
        <?php if (!empty($leastViewedProducts)): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Imagen</th>
                    <th>Vistas Totales</th>
                    <th>Permanencia Promedio (s)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leastViewedProducts as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['pro_id']) ?></td>
                        <td><a href="../product_detail.php?id=<?= $product['pro_id'] ?>"><?= htmlspecialchars($product['pro_nombre']) ?></a></td>
                        <td><img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" alt="Img" width="50" height="50" style="border-radius:5px;object-fit:cover;"></td>
                        <td><?= htmlspecialchars($product['total_views']) ?></td>
                        <td><?= number_format(htmlspecialchars($product['average_permanence']), 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p>No hay datos disponibles para los productos menos vistos.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../footer.php'; // Assuming footer is outside admin folder ?>
