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
<div class="product-detail-container">
    <div class="product-image-section">
        <img src="<?= htmlspecialchars($product['pro_imagen_url'] ?? 'https://via.placeholder.com/400x300?text=Producto') ?>" alt="<?= htmlspecialchars($product['pro_nombre']) ?>">
    </div>
    <div class="product-info-section">
        <h2><?= htmlspecialchars($product['pro_nombre'] ?? 'Producto sin nombre') ?></h2>
        <p class="price">$<?= number_format($product['pro_precio_unitario'], 2) ?></p>
        <p class="description"><?= nl2br(htmlspecialchars($product['pro_descripcion'])) ?></p>
        <p><strong>Categoría:</strong> <?= htmlspecialchars($product['cat_nombre'] ?? 'N/A') ?></p>
        <p><strong>Proveedor:</strong> <?= htmlspecialchars($product['prv_nombre'] ?? 'N/A') ?></p>
        <p><strong>Stock:</strong> <?= (int)$product['pro_cantidad_stock'] ?> unidades</p>

        <form action="cart.php" method="post">
            <input type="hidden" name="action" value="add_to_cart">
            <input type="hidden" name="pro_id" value="<?= $product['pro_id'] ?>">
            <label for="quantity">Cantidad:</label>
            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?= (int)$product['pro_cantidad_stock'] ?>" class="form-control">
            <button type="submit" class="btn-primary" <?= $product['pro_cantidad_stock'] > 0 ? '' : 'disabled' ?>>
                <?= $product['pro_cantidad_stock'] > 0 ? 'Añadir al Carrito' : 'Agotado' ?>
            </button>
        </form>
        <p style="margin-top:20px;">
            <a href="index.php" class="btn-secondary">← Volver al catálogo</a>
        </p>
    </div>
</div>

<style>
.product-detail-container {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  padding: 25px;
  margin: 40px auto;
  max-width: 1000px;
}
.product-image-section {
  flex: 1 1 45%;
  text-align: center;
}
.product-image-section img {
  width: 100%;
  max-width: 400px;
  border-radius: 10px;
  object-fit: cover;
}
.product-info-section {
  flex: 1 1 55%;
}
.product-info-section h2 {
  margin-bottom: 15px;
  font-size: 1.8rem;
  color: #333;
}
.product-info-section .price {
  font-size: 1.4rem;
  color: #007bff;
  font-weight: bold;
  margin-bottom: 20px;
}
.product-info-section .description {
  color: #555;
  margin-bottom: 20px;
  line-height: 1.6;
}
.product-info-section form {
  margin-top: 20px;
}
.btn-primary {
  background: #007bff;
  color: #fff;
  padding: 10px 16px;
  border-radius: 5px;
  text-decoration: none;
  border: none;
  cursor: pointer;
}
.btn-primary:hover {
  background: #0056b3;
}
.btn-secondary {
  background: #6c757d;
  color: #fff;
  padding: 8px 12px;
  border-radius: 5px;
  text-decoration: none;
}
.btn-secondary:hover {
  background: #5a6268;
}
@media(max-width: 768px) {
  .product-detail-container {
    flex-direction: column;
    align-items: center;
  }
}
</style>

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
