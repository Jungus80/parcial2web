<?php
require_once '../dbconexion.php';
require_once '../clases/OrderManager.php';

header('Content-Type: application/json');

// Parámetros de filtro
$startDate = $_POST['start_date'] ?? null;
$endDate = $_POST['end_date'] ?? null;
$userId = $_POST['user_id'] ?? null;

$orderManager = new OrderManager();

// Construir consulta base con filtros
$query = "SELECT SUM(total) as total_revenue, SUM(costo_total) as cogs FROM ventas WHERE 1=1";
$params = [];

if (!empty($startDate)) {
    $query .= " AND fecha >= ?";
    $params[] = $startDate;
}
if (!empty($endDate)) {
    $query .= " AND fecha <= ?";
    $params[] = $endDate;
}
if (!empty($userId) && $userId !== 'all') {
    $query .= " AND usuario_id = ?";
    $params[] = $userId;
}

$conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
$stmt = $conn->prepare($query);
$stmt->execute($params);
$metrics = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total_revenue' => 0, 'cogs' => 0];

$totalRevenue = (float) $metrics['total_revenue'];
$cogs = (float) $metrics['cogs'];
$grossProfit = $totalRevenue - $cogs;
$margin = $totalRevenue != 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

echo json_encode([
    'total_revenue' => number_format($totalRevenue, 2),
    'cogs' => number_format($cogs, 2),
    'gross_profit' => number_format($grossProfit, 2),
    'margin' => number_format($margin, 2),
]);
?>
