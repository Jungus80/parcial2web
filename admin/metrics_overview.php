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
require_once '../dbconexion.php';
require_once '../clases/Translator.php';
include 'includes/admin_header.php';

// =========================
// MÉTRICAS POR COOKIES ACEPTADAS
// =========================
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

// Totales para tarjetas
$totalUsuarios = 0;
$totalVisitas = 0;
$tiempoPromedio = 0;

if (!empty($pagesData)) {
    $totalUsuarios = array_sum(array_column($pagesData, 'total_usuarios'));
    $totalVisitas = array_sum(array_column($pagesData, 'total_visitas'));
    $tiempoPromedio = round(array_sum(array_column($pagesData, 'promedio_tiempo')) / count($pagesData), 2);
}
?>

<div class="admin-container">

  <h2 class="section-title">
    <i class="fas fa-cookie"></i>
    <?= Translator::get('cookies_metrics_title') ?? 'Métricas por Cookies Aceptadas' ?>
  </h2>

  <div class="dashboard-cards">
    <div class="card-admin blue">
      <div class="card-body">
        <h3><i class="fas fa-users"></i> <?= Translator::get('cookies_users_label') ?? 'Usuarios con Cookies Aceptadas' ?></h3>
        <p><?= (int)$totalUsuarios ?></p>
      </div>
    </div>

    <div class="card-admin green">
      <div class="card-body">
        <h3><i class="fas fa-eye"></i> <?= Translator::get('cookies_total_visits_label') ?? 'Total de Visitas' ?></h3>
        <p><?= (int)$totalVisitas ?></p>
      </div>
    </div>

    <div class="card-admin orange">
      <div class="card-body">
        <h3><i class="fas fa-clock"></i> <?= Translator::get('cookies_avg_time_label') ?? 'Tiempo Promedio (s)' ?></h3>
        <p><?= htmlspecialchars((string)$tiempoPromedio) ?></p>
      </div>
    </div>
  </div>

  <div class="chart-section">
    <h3><?= Translator::get('cookies_pages_chart_title') ?? 'Páginas Más Visitadas (Usuarios con Cookies)' ?></h3>
    <canvas id="cookiesChart"></canvas>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
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
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
</script>

<?php include 'includes/admin_footer.php'; ?>

</body>
</html>
