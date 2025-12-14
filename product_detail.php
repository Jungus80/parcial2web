<?php
session_start();

require_once 'header.php'; // Incluye el encabezado con selector de idioma, carrito y logout
require_once 'clases/ProductManager.php';
require_once 'clases/Tracker.php';

$productManager = new ProductManager();
$tracker = new Tracker();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($productId > 0) {
    $product = $productManager->getProductById($productId);

    // Registrar la visita al producto (si el usuario ha aceptado las cookies)
    $userId = $_SESSION['user_id'] ?? 0;
    $obsId = $tracker->trackProductView($userId, $productId); // Usar $productId para el ID del producto y obtener obsId

} else {
    // Redirigir si no se proporciona un ID de producto válido
    header('Location: index.php');
    exit();
}

if (!$product) {
    // Producto no encontrado
    require_once 'header.php'; // Asegurarse de tener el header
    echo '<div class="container"><h2>Producto no encontrado.</h2><p>Lo sentimos, el producto que buscas no existe o fue eliminado.</p></div>';
    require_once 'footer.php'; // Asegurarse de tener el footer
    exit();
}

?>

<?php require_once 'header.php'; ?>
<div class="container">
    <h2 data-translate-key="product_name"><?= $product['pro_nombre'] ?></h2>
    <div class="product-details">
        <img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/300x200?text=Producto') ?>" alt="<?= htmlspecialchars($product['pro_nombre']) ?>" class="product-detail-image">
        <p class="description"><strong data-translate-key="description_label"><?= Translator::get('description_label') ?? 'Descripción' ?>:</strong> <?= nl2br($product['pro_descripcion']) ?></p>
        <p class="price"><strong data-translate-key="price_label"><?= Translator::get('price_label') ?? 'Precio' ?>:</strong> $<?= number_format($product['pro_precio_unitario'], 2) ?></p>
        <p><strong data-translate-key="stock_label"><?= Translator::get('stock_label') ?? 'Stock' ?>:</strong> <?= $product['pro_cantidad_stock'] ?> unidades</p>
        <p><strong data-translate-key="available_label"><?= Translator::get('available_label') ?? 'Disponible' ?>:</strong> <?= $product['pro_disponible'] ? (Translator::get('yes') ?? 'Sí') : (Translator::get('no') ?? 'No') ?></p>
        <?php if ($product['prv_nombre']): ?>
            <p><strong data-translate-key="supplier_label"><?= Translator::get('supplier_label') ?? 'Proveedor' ?>:</strong> <?= $product['prv_nombre'] ?></p>
        <?php endif; ?>
        <?php if ($product['cat_nombre']): ?>
            <p><strong data-translate-key="category_label"><?= Translator::get('category_label') ?? 'Categoría' ?>:</strong> <?= $product['cat_nombre'] ?></p>
        <?php endif; ?>

        <form action="cart.php" method="post" class="add-to-cart-form">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="pro_id" value="<?= $product['pro_id'] ?>">
            <div class="form-group form-inline">
                <label for="quantity" class="sr-only" data-translate-key="quantity_label"><?= Translator::get('quantity_label') ?? 'Cantidad' ?>:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= $product['pro_cantidad_stock'] ?>" class="form-control form-control-sm">
                <button type="submit" class="btn btn-primary add-to-cart-btn" <?= $product['pro_cantidad_stock'] > 0 ? '' : 'disabled' ?> data-translate-key="add_to_cart_button">
                    <?= $product['pro_cantidad_stock'] > 0 ? (Translator::get('add_to_cart_button') ?? 'Añadir al Carrito') : (Translator::get('sold_out_button') ?? 'Agotado') ?>
                </button>
            </div>
        </form>
    </div>
    <p><a href="index.php" class="btn btn-secondary" data-translate-key="back_to_product_list"><?= Translator::get('back_to_product_list') ?? 'Volver a la lista de productos' ?></a></p>
</div>

<?php require_once 'footer.php'; ?>

<script>
    (function() {
        let startTime = Date.now();
        let obsId = parseInt(<?= $obsId ?>);
        console.log('Product Detail Page loaded. Initial obsId:', obsId);

        function sendPermanence() {
            let endTime = Date.now();
            let permanence = endTime - startTime; // in milliseconds
            console.log('Attempting to send permanence. Current obsId:', obsId, 'Permanence:', permanence, 'ms');

            // Check for cookie acceptance here, as the PHP side also checks it.
            // This is a simplified check for client-side debugging, actual check is server-side.
            // If you have a client-side way to check $_COOKIE['cookie_accepted'] you can add it.
            let cookieAccepted = document.cookie.includes('cookie_accepted');
            console.log('Cookie Accepted:', cookieAccepted);

            if (cookieAccepted && !isNaN(obsId) && obsId > 0 && permanence > 0) {
                console.log('Sending permanence AJAX for obs_id=' + obsId + ', permanence=' + permanence + 'ms');
                fetch('track_duration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        obs_id: obsId,
                        permanence: permanence
                    }),
                }).then(response => {
                    // console.log('Permanence tracking sent:', response);
                }).catch(error => {
                    console.error('Error sending permanence:', error);
                });
            }
        }

        // Track when the user leaves the page or closes the tab
        window.addEventListener('beforeunload', sendPermanence);
        // Track when the user switches tabs or minimizes the window
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendPermanence();
            } else {
                startTime = Date.now(); // Reset start time when coming back
            }
        });
    })();
</script>
