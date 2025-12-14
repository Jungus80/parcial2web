<?php
session_start();

require_once 'header.php';
require_once 'clases/CartManager.php';
require_once 'clases/ProductManager.php';

$cartManager = new CartManager();
$productManager = new ProductManager();

$message = '';
$error = '';

// Obtener el estado actual del carrito y manejar la expiración
$cartStatus = null;
$cartId = $_SESSION['cart_id'] ?? 0;
if ($cartId > 0) {
    $cartStatus = $cartManager->getCartStatus($cartId);
}

$isCartExpired = ($cartStatus && $cartStatus['car_estado'] === 'EXPIRADO');
$expirationTimestamp = $cartStatus['car_fecha_expiracion_ts'] ?? 0;
$dbCurrentTime = $cartStatus['db_current_time_ts'] ?? time(); // Usar time() como fallback si no proviene de DB

// Manejo de acciones del carrito
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['pro_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    // Si el carrito está expirado y la acción no es reactivarlo, forzar la reactivación o error.
    if ($isCartExpired && $action !== 'reactivate_cart') {
        $error = 'Tu carrito ha expirado. Por favor, actualiza los precios.';
        // No permitir otras acciones si el carrito está expirado, a menos que sea reactivar
    } else {
        switch ($action) {
            case 'add_to_cart':
                if ($productId > 0) {
                    if ($cartManager->addToCart($productId, $quantity)) {
                        $message = 'Producto añadido al carrito.';
                    } else {
                        $error = 'Error al añadir producto al carrito o stock insuficiente.';
                    }
                }
                break;
            case 'update_quantity':
                if ($productId > 0 && $quantity > 0) {
                    if ($cartManager->updateCartItemQuantity($productId, $quantity)) {
                        $message = 'Cantidad actualizada.';
                    } else {
                        $error = 'Error al actualizar cantidad o stock insuficiente.';
                    }
                } 
                break;
            case 'remove_item':
                if ($productId > 0) {
                    if ($cartManager->removeFromCart($productId)) {
                        $message = 'Producto eliminado del carrito.';
                    } else {
                        $error = 'Error al eliminar producto del carrito.';
                    }
                }
                break;
            case 'reactivate_cart':
                if ($cartId > 0) {
                    if ($cartManager->reactivateCart($cartId)) {
                        $message = 'Carrito reactivado y precios actualizados.';
                        // Forzar una recarga para que el front-end actualice el estado del carrito
                        header('Location: cart.php');
                        exit();
                    } else {
                        $error = 'Error al reactivar el carrito.';
                    }
                }
                break;
        }

        // Después de cualquier modificación, re-obtener el estado del carrito
        if ($cartId > 0) {
            $cartStatus = $cartManager->getCartStatus($cartId);
        }
        $isCartExpired = ($cartStatus && $cartStatus['car_estado'] === 'EXPIRADO');
        $expirationTimestamp = $cartStatus['car_fecha_expiracion_ts'] ?? 0;
        $dbCurrentTime = $cartStatus['db_current_time_ts'] ?? time();
    }
}

$cartItems = $cartManager->getCartItems();
$cartTotal = $cartManager->getCartTotal();

?>

<?php require_once 'header.php'; ?>
<div class="container">
    <h2 data-translate-key="shopping_cart_title"><?= Translator::get('shopping_cart_title') ?? 'Carrito de Compras' ?></h2>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>


    <?php if (empty($cartItems) && !$isCartExpired): ?>
        <p data-translate-key="cart_empty"><?= Translator::get('cart_empty') ?? 'Tu carrito está vacío.' ?></p>
    <?php elseif ($isCartExpired): ?>
        <div class="cart-expired-message">
            <p><i class="fas fa-exclamation-triangle"></i> <span data-translate-key="cart_expired_message"><?= Translator::get('cart_expired_message') ?? 'Tu carrito ha expirado.' ?></span></p>
            <p data-translate-key="cart_expired_details"><?= Translator::get('cart_expired_details') ?? 'Los precios y la disponibilidad de los productos pueden haber cambiado.' ?></p>
            <form action="cart.php" method="post">
                <input type="hidden" name="action" value="reactivate_cart">
                <button type="submit" class="btn btn-warning" data-translate-key="update_cart_button"><?= Translator::get('update_cart_button') ?? 'Actualizar Carrito' ?></button>
            </form>
        </div>
    <?php else: // Carrito no vacío y no expirado ?>
        <?php if (!$isCartExpired && $expirationTimestamp > $dbCurrentTime): ?>
            <div class="cart-timer">
                <p data-translate-key="cart_expires_in"><?= Translator::get('cart_expires_in') ?? 'Tu carrito expirará en:' ?> <span id="countdown-timer"></span></p>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th></th> <!-- Para la imagen -->
                    <th data-translate-key="product_header"><?= Translator::get('product_header') ?? 'Producto' ?></th>
                    <th data-translate-key="unit_price_header"><?= Translator::get('unit_price_header') ?? 'Precio Unitario' ?></th>
                    <th data-translate-key="quantity_header"><?= Translator::get('quantity_header') ?? 'Cantidad' ?></th>
                    <th data-translate-key="subtotal_header"><?= Translator::get('subtotal_header') ?? 'Subtotal' ?></th>
                    <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['pro_imagen_url'])): ?>
                            <img src="<?= htmlspecialchars($item['pro_imagen_url']) ?>" alt="<?= htmlspecialchars($item['pro_nombre']) ?>" width="50">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/50?text=No+Img" alt="<?= htmlspecialchars($item['pro_nombre']) ?>" width="50">
                        <?php endif; ?>
                    </td>
                    <td><?= $item['pro_nombre'] ?></td>
                    <td>$<?= number_format($item['pro_precio_unitario'], 2) ?></td>
                    <td>
                        <form action="cart.php" method="post" class="form-inline">
                            <input type="hidden" name="action" value="update_quantity">
                            <input type="hidden" name="pro_id" value="<?= $item['dca_producto'] ?>">
                            <input type="number" name="quantity" value="<?= $item['dca_cantidad'] ?>" min="1" class="form-control form-control-sm" onchange="this.form.submit()" <?= $isCartExpired ? 'disabled' : '' ?>>
                        </form>
                    </td>
                    <td>$<?= number_format($item['dca_cantidad'] * $item['pro_precio_unitario'], 2) ?></td>
                    <td>
                        <form action="cart.php" method="post" class="form-inline">
                            <input type="hidden" name="action" value="remove_item">
                            <input type="hidden" name="pro_id" value="<?= $item['dca_producto'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('<?= Translator::get('confirm_remove_item') ?? '¿Estás seguro de eliminar este producto del carrito?' ?>');" <?= $isCartExpired ? 'disabled' : '' ?> data-translate-key="remove_item_button"><?= Translator::get('remove_item_button') ?? 'Eliminar' ?></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong><span data-translate-key="total_label"><?= Translator::get('total_label') ?? 'Total' ?>:</span></strong></td>
                    <td><strong>$<?= number_format($cartTotal, 2) ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <p class="text-right"><a href="checkout.php" class="btn btn-success" <?= $isCartExpired || empty($cartItems) ? 'disabled' : '' ?> data-translate-key="checkout_button"><?= Translator::get('checkout_button') ?? 'Finalizar Compra' ?></a></p>
    <?php endif; ?>
    <p><a href="index.php" class="btn btn-secondary btn-sm" data-translate-key="continue_shopping"><?= Translator::get('continue_shopping') ?? 'Continuar Comprando' ?></a></p>
</div>

<?php require_once 'footer.php'; ?>

<script>
    function startCountdown(expirationTimestamp) {
        const countdownElement = document.getElementById('countdown-timer');
        if (!countdownElement) return;

        function updateCountdown() {
            const now = Math.floor(Date.now() / 1000);
            const timeLeft = expirationTimestamp - now;

            if (timeLeft <= 0) {
                countdownElement.textContent = '<?= Translator::get('expired_message') ?? 'Expirado' ?>';
                location.reload(); 
                return;
            }

            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;

            const formattedMinutes = String(minutes).padStart(2, '0');
            const formattedSeconds = String(seconds).padStart(2, '0');

            countdownElement.textContent = `${formattedMinutes}:${formattedSeconds}`;
        }

        updateCountdown();
        setInterval(updateCountdown, 1000);
    }

    <?php if (!$isCartExpired && $expirationTimestamp > time()): ?>
        startCountdown(<?= $expirationTimestamp ?>);
    <?php endif; ?>
</script>

</body>
</html>
