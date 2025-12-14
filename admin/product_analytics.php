<?php
session_start();
require_once __DIR__ . '/../clases/MetricsManager.php';
require_once __DIR__ . '/includes/admin_header.php';

$metricsManager = new MetricsManager();

$mostViewedProducts = $metricsManager->getMostViewedProducts(10);
$leastViewedProducts = $metricsManager->getLeastViewedProducts(10);
?>
<link rel="stylesheet" href="admin_styles.css">

<div class="container mt-4">
    <h2 class="mb-4">Product Analytics</h2>

    <!-- Most Viewed Products -->
    <div class="card mb-4">
        <div class="card-header">
            <h3>10 Most Viewed Products</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($mostViewedProducts)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Image</th>
                            <th>Total Views</th>
                            <th>Avg. Permanence (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mostViewedProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['pro_id']) ?></td>
                                <td><a href="../product_detail.php?id=<?= $product['pro_id'] ?>"><?= htmlspecialchars($product['pro_nombre']) ?></a></td>
                                <td><img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" alt="Product Image" width="50"></td>
                                <td><?= htmlspecialchars($product['total_views']) ?></td>
                                <td><?= number_format(htmlspecialchars($product['average_permanence']), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No most viewed products data available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Least Viewed Products -->
    <div class="card">
        <div class="card-header">
            <h3>10 Least Viewed Products</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($leastViewedProducts)): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Image</th>
                            <th>Total Views</th>
                            <th>Avg. Permanence (s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leastViewedProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['pro_id']) ?></td>
                                <td><a href="../product_detail.php?id=<?= $product['pro_id'] ?>"><?= htmlspecialchars($product['pro_nombre']) ?></a></td>
                                <td><img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/50x50?text=No+Image') ?>" alt="Product Image" width="50"></td>
                                <td><?= htmlspecialchars($product['total_views']) ?></td>
                                <td><?= number_format(htmlspecialchars($product['average_permanence']), 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No least viewed products data available.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../footer.php'; // Assuming footer is outside admin folder ?>
