<?php
require_once '../clases/Tracker.php';
require_once '../clases/Translator.php';
include 'includes/admin_header.php';

$tracker = new Tracker();
$db = new DB();
$conn = $db->getConnection();

// Obtener métricas de navegación solo de usuarios con cookies aceptadas
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

// Resumen general
$totalUsuarios = 0;
$totalVisitas = 0;
$tiempoPromedio = 0;
if ($pagesData) {
    $totalUsuarios = array_sum(array_column($pagesData, 'total_usuarios'));
    $totalVisitas = array_sum(array_column($pagesData, 'total_visitas'));
    $tiempoPromedio = round(array_sum(array_column($pagesData, 'promedio_tiempo')) / count($pagesData), 2);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Cookies</title>
<link rel="stylesheet" href="../admin_styles.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="admin-container">
    <h2><i class="fas fa-cookie"></i> Dashboard de Métricas por Cookies Aceptadas</h2>

    <div class="dashboard-cards">
        <div class="card-admin blue">
            <h3><i class="fas fa-users"></i> Usuarios con Cookies Aceptadas</h3>
            <p><?= $totalUsuarios ?></p>
        </div>
        <div class="card-admin green">
            <h3><i class="fas fa-eye"></i> Total de Visitas</h3>
            <p><?= $totalVisitas ?></p>
        </div>
        <div class="card-admin orange">
            <h3><i class="fas fa-clock"></i> Tiempo Promedio (segundos)</h3>
            <p><?= $tiempoPromedio ?></p>
        </div>
    </div>

    <div class="chart-section">
        <h3>Páginas Más Visitadas</h3>
        <canvas id="pagesChart"></canvas>
    </div>
</div>

<script>
const ctx = document.getElementById('pagesChart').getContext('2d');
const pages = <?= json_encode(array_column($pagesData, 'pagina')) ?>;
const visitas = <?= json_encode(array_column($pagesData, 'total_visitas')) ?>;
const tiempos = <?= json_encode(array_column($pagesData, 'tiempo_total')) ?>;

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: pages,
        datasets: [
            {
                label: 'Visitas',
                data: visitas,
                backgroundColor: 'rgba(0, 123, 255, 0.6)',
                borderColor: '#007bff',
                borderWidth: 1
            },
            {
                label: 'Tiempo Total (s)',
                data: tiempos,
                backgroundColor: 'rgba(40, 167, 69, 0.6)',
                borderColor: '#28a745',
                borderWidth: 1
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
