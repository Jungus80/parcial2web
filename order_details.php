<?php
session_start();

require_once 'header.php';
require_once 'clases/OrderManager.php';

$orderManager = new OrderManager();

$errorMessage = '';
$sale = null;
$saleIntegrityStatus = true; // Asumir que la venta es íntegra por defecto

$saleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($saleId > 0) {
    $sale = $orderManager->getSaleById($saleId);

    if ($sale) {
        // Verificar si el usuario actual tiene permiso para ver esta venta
        // (Solo el usuario que hizo la compra o un administrador)
        if (($_SESSION['user_id'] ?? 0) !== (int)$sale['ven_usuario'] && ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
            $errorMessage = Translator::get('permission_denied') ?? 'No tienes permiso para ver esta venta.';
            $sale = null; // Ocultar detalles de la venta
        } else {
            $saleIntegrityStatus = $orderManager->verifySaleIntegrity($saleId);
        }
    } else {
        $errorMessage = Translator::get('sale_not_found') ?? 'Venta no encontrada.';
    }
} else {
    $errorMessage = Translator::get('invalid_sale_id') ?? 'ID de venta no válido.';
}

?>

<div class="container">
    <h2 data-translate-key="order_details_title"><?= Translator::get('order_details_title') ?? 'Detalles del Pedido' ?></h2>

    <?php if ($errorMessage): ?>
        <p class="error"><?= $errorMessage ?></p>
    <?php elseif ($sale): ?>
        <p data-translate-key="sale_id">ID de Venta: <strong><?= $sale['ven_id'] ?></strong></p>
        <p data-translate-key="user_label">Usuario: <?= $sale['usu_nombre'] ?></p>
        <p data-translate-key="date_label">Fecha: <?= $sale['ven_fecha'] ?></p>
        <p data-translate-key="status_label">Estado: <?= $sale['ven_estado'] ?></p>
        <p data-translate-key="total_label">Total: $<?= number_format($sale['ven_total'], 2) ?></p>
        
        <?php if (!$saleIntegrityStatus || $sale['ven_modificado']): ?>
            <p class="error"><span data-translate-key="sale_integrity_warning">⚠️ ¡Advertencia: Venta modificada o integridad comprometida!</span></p>
        <?php else: ?>
            <p class="message"><span data-translate-key="sale_integrity_verified">✔ Integridad de la venta verificada.</span></p>
        <?php endif; ?>

        <h3 data-translate-key="products_in_order_title"><?= Translator::get('products_in_order_title') ?? 'Productos en el Pedido:' ?></h3>
        <table>
            <thead>
                <tr>
                    <th data-translate-key="product_header"><?= Translator::get('product_header') ?? 'Producto' ?></th>
                    <th data-translate-key="quantity_header"><?= Translator::get('quantity_header') ?? 'Cantidad' ?></th>
                    <th data-translate-key="sale_unit_price_header"><?= Translator::get('sale_unit_price_header') ?? 'Precio Unidad Venta' ?></th>
                    <th data-translate-key="subtotal_header"><?= Translator::get('subtotal_header') ?? 'Subtotal' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sale['details'] as $item): ?>
                <tr>
                    <td><?= $item['pro_nombre'] ?></td>
                    <td><?= $item['dev_cantidad'] ?></td>
                    <td>$<?= number_format($item['dev_precio_unidad_venta'], 2) ?></td>
                    <td>$<?= number_format($item['dev_subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><a href="admin/invoice.php?saleId=<?= $sale['ven_id'] ?>" target="_blank" class="btn btn-primary" data-translate-key="download_invoice_button"><?= Translator::get('download_invoice_button') ?? 'Descargar Factura (PDF)' ?></a></p>

    <?php endif; ?>
    <p><a href="index.php" class="btn btn-secondary" data-translate-key="back_to_main_page"><?= Translator::get('back_to_main_page') ?? 'Volver a la página principal' ?></a></p>
</div>

<?php require_once 'footer.php'; ?>
