<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/ExcelReporter.php';
require_once '../clases/UserManager.php'; // Para listar usuarios en el filtro
require_once __DIR__ . '/../clases/Translator.php';
require_once __DIR__ . '/../clases/MetricsManager.php';

$db = new DB();
$conn = $db->getConnection();
$excelReporter = new ExcelReporter();
$userManager = new UserManager();
$metricsManager = new MetricsManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Obtener métricas generales
$totalRevenue = $metricsManager->getTotalRevenue();
$totalCostOfGoodsSold = $metricsManager->getTotalCostOfGoodsSold();
$grossProfit = $metricsManager->getGrossProfit();
$grossProfitMargin = $metricsManager->getGrossProfitMargin();

$message = '';
$error = '';
$reportContent = '';
$users = $userManager->getAllUsers();

$filters = [
    'start_date' => $_GET['start_date'] ?? '',
    'end_date' => $_GET['end_date'] ?? '',
    'user_id' => (int)($_GET['user_id'] ?? 0)
];

if (isset($_GET['action']) && $_GET['action'] === 'generate_report') {
    $reportContent = $excelReporter->generateSalesExcel($filters);
    if (!empty($reportContent)) {
        echo $reportContent;
        exit();
    } else {
        $error = 'No se encontraron ventas con los filtros aplicados.';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="admin-container">
        <h2 data-translate-key="sales_report_title"><?= Translator::get('sales_report_title') ?? 'Reporte de Ventas' ?></h2>

        <div class="metrics-summary mb-4">
            <h4>Métricas de Ganancias:</h4>
            <p><strong>Total de Ingresos:</strong> $<?= number_format($totalRevenue, 2) ?></p>
            <p><strong>Costo Total de Bienes Vendidos (COGS):</strong> $<?= number_format($totalCostOfGoodsSold, 2) ?></p>
            <p><strong>Ganancia Bruta:</strong> $<?= number_format($grossProfit, 2) ?></p>
            <p><strong>Margen de Ganancia Bruta:</strong> <?= number_format($grossProfitMargin, 2) ?>%</p>
        </div>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <form action="sales_report.php" method="GET">
            <input type="hidden" name="action" value="generate_report">
            <div class="form-group">
                <label for="start_date" data-translate-key="start_date_label"><?= Translator::get('start_date_label') ?? 'Fecha Inicio:' ?></label>
                <input type="date" id="start_date" name="start_date" value="<?= $filters['start_date'] ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="end_date" data-translate-key="end_date_label"><?= Translator::get('end_date_label') ?? 'Fecha Fin:' ?></label>
                <input type="date" id="end_date" name="end_date" value="<?= $filters['end_date'] ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="user_id" data-translate-key="user_label"><?= Translator::get('user_label') ?? 'Usuario:' ?></label>
                <select id="user_id" name="user_id" class="form-control">
                    <option value="0" data-translate-key="all_users_option"><?= Translator::get('all_users_option') ?? '-- Todos los Usuarios --' ?></option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['usu_id'] ?>" <?= ((int)$filters['user_id'] === $user['usu_id']) ? 'selected' : '' ?>>
                            <?= $user['usu_nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" data-translate-key="generate_report_button"><?= Translator::get('generate_report_button') ?? 'Generar Reporte' ?></button>
            </div>
        </form>

        <?php if ($reportContent): ?>
            <h3 data-translate-key="report_content_title"><?= Translator::get('report_content_title') ?? 'Contenido del Reporte (CSV simulado)' ?></h3>
            <pre class="report-content"><?= htmlspecialchars($reportContent) ?></pre>
            <p data-translate-key="real_implementation_note"><?= Translator::get('real_implementation_note') ?? 'En una implementación real, aquí se ofrecería un botón para descargar el archivo .xlsx.' ?></p>
        <?php endif; ?>

    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
