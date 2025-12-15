<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Administración</title>
<link rel="stylesheet" href="admin_styles.css">
</head>
<body>

<?php
require_once '../clases/MetricsManager.php';
require_once '../clases/Tracker.php';
require_once '../clases/Translator.php';
include 'includes/admin_header.php';

$metricsManager = new MetricsManager();
$dailyViews = $metricsManager->getDailyViews() ?? [];
$averageDuration = $metricsManager->getAverageDuration() ?? 0;
$topProducts = $metricsManager->getTopProducts(5) ?? [];
?>

<div class="admin-container">
  <div class="admin-content">
    <h2 class="section-title"><i class="fas fa-chart-line"></i> <?= Translator::get('metrics_overview_title') ?? 'Panel de Métricas del Sistema' ?></h2>

    <div class="dashboard-cards">
      <div class="card-admin blue">
        <div class="card-body">
          <h3><i class="fas fa-clock"></i> <?= Translator::get('average_duration_label') ?? 'Duración Promedio (min)' ?></h3>
          <p><?= round($averageDuration * 60, 2) ?> <span>segundos</span></p>
        </div>
      </div>

      <div class="card-admin orange">
        <div class="card-body">
          <h3><i class="fas fa-user"></i> <?= Translator::get('unique_users_label') ?? 'Usuarios Únicos' ?></h3>
          <p><?= $metricsManager->getUniqueUsersCount() ?? 0 ?></p>
        </div>
      </div>

      <div class="card-admin green">
        <div class="card-body">
          <h3><i class="fas fa-sync-alt"></i> <?= Translator::get('returning_users_label') ?? 'Usuarios Recurrentes' ?></h3>
          <p><?= $metricsManager->getReturningUsersCount() ?? 0 ?></p>
        </div>
      </div>

      <div class="card-admin purple">
        <div class="card-body">
          <h3><i class="fas fa-eye"></i> <?= Translator::get('total_views_label') ?? 'Total de Visitas' ?></h3>
          <p><?= $metricsManager->getTotalPageViews() ?? 0 ?></p>
        </div>
      </div>
    </div>

    <div class="chart-section">
      <h3 data-translate-key="visits_chart_label"><?= Translator::get('visits_chart_label') ?? 'Visitas Diarias (Últimos 7 días)' ?></h3>
      <canvas id="visitsChart"></canvas>
    </div>

    <div class="chart-section">
      <h3 data-translate-key="most_viewed_pages"><?= Translator::get('most_viewed_pages') ?? 'Páginas Más Visitadas' ?></h3>
      <ul>
        <?php foreach ($topProducts as $product): ?>
          <li><?= htmlspecialchars($product['pro_nombre'] ?? 'Producto desconocido') ?> — <?= (int)($product['visitas'] ?? 0) ?> visitas</li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="chart-section">
        <h3 data-translate-key="most_visited_pages"><?= Translator::get('most_visited_pages') ?? 'URLs Más Visitadas' ?></h3>
        <canvas id="pagesChart"></canvas>
    </div>

    <!-- Nueva sección de métricas basadas en cookies aceptadas -->
    <?php
    require_once '../clases/Tracker.php';
    $tracker = new Tracker();
    $db = new DB();
    $conn = $db->getConnection();

    $query = "SELECT 
        COUNT(DISTINCT met_usuario) AS total_usuarios,
        COUNT(*) AS total_visitas,
        AVG(met_tiempo_visita) AS promedio_tiempo,
        met_pagina_url AS pagina,
        SUM(met_tiempo_visita) AS tiempo_total
    FROM Metrica_navegacion
    WHERE met_usuario IS NOT NULL
    GROUP BY met_pagina_url
    ORDER BY tiempo_total DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $pagesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalUsuarios = 0;
    $totalVisitas = 0;
    $tiempoPromedio = 0;
    if ($pagesData) {
        $totalUsuarios = array_sum(array_column($pagesData, 'total_usuarios'));
        $totalVisitas = array_sum(array_column($pagesData, 'total_visitas'));
        $tiempoPromedio = round(array_sum(array_column($pagesData, 'promedio_tiempo')) / count($pagesData), 2);
    }
    ?>

    <h2 class="section-title"><i class="fas fa-cookie"></i> <?= Translator::get('cookies_metrics_title') ?? 'Métricas por Cookies Aceptadas' ?></h2>
    <div class="dashboard-cards">
        <div class="card-admin blue">
            <div class="card-body">
                <h3><i class="fas fa-users"></i> <?= Translator::get('cookies_users_label') ?? 'Usuarios con Cookies Aceptadas' ?></h3>
                <p><?= $totalUsuarios ?></p>
            </div>
        </div>
        <div class="card-admin green">
            <div class="card-body">
                <h3><i class="fas fa-eye"></i> <?= Translator::get('cookies_total_visits_label') ?? 'Total de Visitas' ?></h3>
                <p><?= $totalVisitas ?></p>
            </div>
        </div>
        <div class="card-admin orange">
            <div class="card-body">
                <h3><i class="fas fa-clock"></i> <?= Translator::get('cookies_avg_time_label') ?? 'Tiempo Promedio (s)' ?></h3>
                <p><?= $tiempoPromedio ?></p>
            </div>
        </div>
    </div>

    <div class="chart-section">
        <h3><?= Translator::get('cookies_most_visited_label') ?? 'Páginas Más Visitadas (Usuarios con Cookies)' ?></h3>
        <canvas id="cookiesChart"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('visitsChart').getContext('2d');
const dailyViews = <?= json_encode($dailyViews) ?>;
const pagesData = <?= json_encode((new MetricsManager())->getMostVisitedPages(10, 7)) ?>;

new Chart(ctx, {
  type: 'line',
  data: {
    labels: dailyViews.map(d => d.date),
    datasets: [{
      label: 'Visitas',
      data: dailyViews.map(d => d.views),
      backgroundColor: 'rgba(0, 123, 255, 0.3)',
      borderColor: '#007bff',
      borderWidth: 2,
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true } }
  }
});

const ctx2 = document.getElementById('pagesChart').getContext('2d');
new Chart(ctx2, {
  type: 'bar',
  data: {
    labels: pagesData.map(p => p.url),
    datasets: [{
      label: 'Visitas',
      data: pagesData.map(p => p.visitas),
      backgroundColor: 'rgba(54, 162, 235, 0.7)',
      borderColor: '#36A2EB',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true } }
  }
});
const ctxCookies = document.getElementById('cookiesChart').getContext('2d');
const cookiesPages = <?= json_encode(array_column($pagesData ?? [], 'pagina')) ?>;
const cookiesVisits = <?= json_encode(array_column($pagesData ?? [], 'total_visitas')) ?>;
const cookiesTimes = <?= json_encode(array_column($pagesData ?? [], 'tiempo_total')) ?>;

new Chart(ctxCookies, {
    type: 'bar',
    data: {
        labels: cookiesPages,
        datasets: [
            {
                label: 'Visitas',
                data: cookiesVisits,
                backgroundColor: 'rgba(0, 123, 255, 0.6)'
            },
            {
                label: 'Tiempo Total (s)',
                data: cookiesTimes,
                backgroundColor: 'rgba(40, 167, 69, 0.6)'
            }
        ]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>

</body>
</html>
