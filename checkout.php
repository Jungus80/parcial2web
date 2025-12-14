<?php
session_start();

require_once 'header.php';
require_once 'clases/OrderManager.php';

$orderManager = new OrderManager();

$message = '';
$error = '';
$saleId = null;

// Procesa el checkout solo si el método es POST (confirmación)
// O si no se ha procesado aún en la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_checkout') {
    $saleId = $orderManager->processCheckout();
    if ($saleId) {
        $message = '¡Gracias por tu compra! Tu pedido ha sido procesado exitosamente. ID de Venta: ' . $saleId;
        // Redirigir para evitar reenvío de formulario
        // header('Location: order_confirmation.php?sale_id=' . $saleId);
        // exit();
    } else {
        $error = 'Hubo un error al procesar tu pedido. Por favor, revisa tu carrito o inténtalo de nuevo.';
    }
} else if (!isset($_SESSION['cart_id']) || empty($orderManager->getCartItems())) {
    // Si el carrito está vacío y no se ha procesado POST, redirigir al carrito
    header('Location: cart.php');
    exit();
}

$cartItems = $orderManager->getCartItems();
$cartTotal = $orderManager->getCartTotal();

?>

<?php require_once 'header.php'; ?>
<div class="container">
    <h2 data-translate-key="checkout_title"><?= Translator::get('checkout_title') ?? 'Finalizar Compra' ?></h2>

    <?php if ($message): 
        $messageClass = (strpos($message, 'exitosamente') !== false) ? 'message' : 'error';
    ?>
        <p class="<?= $messageClass ?>"><?= $message ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error error-message"><?= $error ?></p>
    <?php endif; ?>

    <?php if ($saleId): // Si la compra fue exitosa ?>
        <p data-translate-key="purchase_success_message"><?= Translator::get('purchase_success_message') ?? 'Tu compra con ID de Venta' ?> <strong><?= $saleId ?></strong> <?= Translator::get('purchase_success_message_part2') ?? 'ha sido completada. Puedes' ?> <a href="order_details.php?id=<?= $saleId ?>" data-translate-key="view_order_details_link"><?= Translator::get('view_order_details_link') ?? 'ver los detalles de tu pedido aquí' ?></a>.</p>
        <p><a href="index.php" class="btn btn-secondary" data-translate-key="back_to_main_page"><?= Translator::get('back_to_main_page') ?? 'Volver a la página principal' ?></a></p>
    <?php else: // Muestra el resumen del carrito antes de confirmar ?>
        <h3 data-translate-key="order_summary"><?= Translator::get('order_summary') ?? 'Resumen del Pedido' ?></h3>
        <?php if (empty($cartItems)): ?>
            <p data-translate-key="cart_empty"><?= Translator::get('cart_empty') ?? 'Tu carrito está vacío.' ?> <a href="index.php" class="btn btn-info btn-sm" data-translate-key="continue_shopping"><?= Translator::get('continue_shopping') ?? 'Continuar Comprando' ?></a></p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th data-translate-key="product_header"><?= Translator::get('product_header') ?? 'Producto' ?></th>
                        <th data-translate-key="unit_price_header"><?= Translator::get('unit_price_header') ?? 'Precio Unitario' ?></th>
                        <th data-translate-key="quantity_header"><?= Translator::get('quantity_header') ?? 'Cantidad' ?></th>
                        <th data-translate-key="subtotal_header"><?= Translator::get('subtotal_header') ?? 'Subtotal' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td><?= $item['pro_nombre'] ?></td>
                        <td>$<?= $item['pro_precio_unitario'] ?></td>
                        <td><?= $item['dca_cantidad'] ?></td>
                        <td>$<?= number_format($item['dca_cantidad'] * $item['pro_precio_unitario'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong><span data-translate-key="total_to_pay">'<?= Translator::get('total_to_pay') ?? 'Total a pagar' ?>:</span></strong></td>
                        <td><strong>$<?= number_format($cartTotal, 2) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            <form action="checkout.php" method="POST">
                <input type="hidden" name="action" value="process_checkout">
                <button type="submit" class="btn btn-success" data-translate-key="confirm_order_button"><?= Translator::get('confirm_order_button') ?? 'Confirmar Pedido y Pagar' ?></button>
            </form>
            <p><a href="cart.php" class="btn btn-secondary" data-translate-key ="back_to_cart_button"><?= Translator::get('back_to_cart_button') ?? 'Volver al Carrito' ?></a></p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>

</body>
</html>
