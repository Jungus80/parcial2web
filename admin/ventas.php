<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/OrderManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$orderManager = new OrderManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$sales = $orderManager->getAllSales();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="admin-container">
        <h2 data-translate-key="sales_management_title"><?= Translator::get('sales_management_title') ?? 'Gestión de Ventas' ?></h2>

        <?php if (empty($sales)): ?>
            <p data-translate-key="no_sales_found"><?= Translator::get('no_sales_found') ?? 'No se encontraron ventas.' ?></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th data-translate-key="sale_id_header"><?= Translator::get('sale_id_header') ?? 'ID Venta' ?></th>
                        <th data-translate-key="user_name_header"><?= Translator::get('user_name_header') ?? 'Usuario' ?></th>
                        <th data-translate-key="sale_date_header"><?= Translator::get('sale_date_header') ?? 'Fecha' ?></th>
                        <th data-translate-key="total_header"><?= Translator::get('total_header') ?? 'Total' ?></th>
                        <th data-translate-key="status_header"><?= Translator::get('status_header') ?? 'Estado' ?></th>
                        <th data-translate-key="integrity_header"><?= Translator::get('integrity_header') ?? 'Integridad de Firma' ?></th>
                        <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $sale): ?>
                    <?php 
                        // Verificación de la integridad de la venta
                        $isValid = $orderManager->verifySaleIntegrity($sale['ven_id']);
                    ?>
                    <tr>
                        <td><?= $sale['ven_id'] ?></td>
                        <td><?= $sale['usu_nombre'] ?></td>
                        <td><?= $sale['ven_fecha'] ?></td>
                        <td>$<?= number_format($sale['ven_total'], 2) ?></td>
                        <td><?= $sale['ven_estado'] ?></td>
                        <td>
                            <?php if ($isValid): ?>
                                <span style="color: green; font-weight: bold;">OK</span>
                            <?php else: ?>
                                <span style="color: red; font-weight: bold;">ADULTERADA</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="invoice.php?saleId=<?= $sale['ven_id'] ?>" target="_blank" class="btn btn-info btn-sm" data-translate-key="view_invoice_button"><?= Translator::get('view_invoice_button') ?? 'Ver Factura' ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
