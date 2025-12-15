<?php
session_start();

require_once 'header.php'; // SOLO UNA VEZ
require_once 'clases/CartManager.php';
require_once 'clases/ProductManager.php';
require_once 'clases/Tracker.php';

$tracker = new Tracker();
$userId = $_SESSION['user_id'] ?? 0;
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$requestUri = $_SERVER['REQUEST_URI'];

// Lista de extensiones a ignorar
$ignoredExtensions = ['.ico', '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg'];
$isStaticAsset = false;
foreach ($ignoredExtensions as $ext) {
    if (str_ends_with($requestUri, $ext)) {
        $isStaticAsset = true;
        break;
    }
}

$metId = 0;
if (!$isStaticAsset && isset($_COOKIE['cookie_accepted'])) {
    $metId = $tracker->trackPageView($userId, $requestUri, null, $referrer);
}

$cartManager = new CartManager();
$productManager = new ProductManager();

$message = '';
$error = '';

// =========================
// ESTADO DEL CARRITO
// =========================
$cartStatus = null;
$cartId = $_SESSION['cart_id'] ?? 0;

if ($cartId > 0) {
    $cartStatus = $cartManager->getCartStatus($cartId);
}

$isCartExpired = ($cartStatus && ($cartStatus['car_estado'] ?? '') === 'EXPIRADO');

// Estos deben venir en UNIX timestamp (segundos)
$expirationTimestamp = (int)($cartStatus['car_fecha_expiracion_ts'] ?? 0);
$dbCurrentTime       = (int)($cartStatus['db_current_time_ts'] ?? time()); // fallback si no viene

// Segundos restantes (basado en la DB)
$remainingSeconds = max(0, $expirationTimestamp - $dbCurrentTime);

// =========================
// ACCIONES POST
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['pro_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    // Si el carrito está expirado y NO es reactivar, no dejes hacer nada más.
    if ($isCartExpired && $action !== 'reactivate_cart') {
        $error = 'Tu carrito ha expirado. Por favor, actualiza los precios.';
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
                        header('Location: cart.php');
                        exit();
                    } else {
                        $error = 'Error al reactivar el carrito.';
                    }
                }
                break;
        }

        // Re-leer estado del carrito después de acciones
        if ($cartId > 0) {
            $cartStatus = $cartManager->getCartStatus($cartId);
        }

        $isCartExpired = ($cartStatus && ($cartStatus['car_estado'] ?? '') === 'EXPIRADO');
        $expirationTimestamp = (int)($cartStatus['car_fecha_expiracion_ts'] ?? 0);
        $dbCurrentTime       = (int)($cartStatus['db_current_time_ts'] ?? time());
        $remainingSeconds    = max(0, $expirationTimestamp - $dbCurrentTime);
    }
}

// =========================
// DATA PARA RENDER
// =========================
$cartItems = $cartManager->getCartItems();
$cartTotal = $cartManager->getCartTotal();
?>

<div class="container">
    <h2 data-translate-key="shopping_cart_title">
        <?= Translator::get('shopping_cart_title') ?? 'Carrito de Compras' ?>
    </h2>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

  

    <?php if (empty($cartItems) && !$isCartExpired): ?>
        <p data-translate-key="cart_empty">
            <?= Translator::get('cart_empty') ?? 'Tu carrito está vacío.' ?>
        </p>

    <?php elseif ($isCartExpired): ?>
        <div class="cart-expired-message">
            <p>
                <i class="fas fa-exclamation-triangle"></i>
                <span data-translate-key="cart_expired_message">
                    <?= Translator::get('cart_expired_message') ?? 'Tu carrito ha expirado.' ?>
                </span>
            </p>
            <p data-translate-key="cart_expired_details">
                <?= Translator::get('cart_expired_details') ?? 'Los precios y la disponibilidad de los productos pueden haber cambiado.' ?>
            </p>

            <form action="cart.php" method="post">
                <input type="hidden" name="action" value="reactivate_cart">
                <button type="submit" class="btn btn-warning" data-translate-key="update_cart_button">
                    <?= Translator::get('update_cart_button') ?? 'Actualizar Carrito' ?>
                </button>
            </form>
        </div>

    <?php else: ?>
        <?php if ($remainingSeconds > 0): ?>
                <p data-translate-key="cart_expires_in">
                    <?= Translator::get('cart_expires_in') ?? 'Tu carrito expirará en:' ?>
                    <span id="countdown-timer"></span>
                    <?php if (!$isCartExpired && $remainingSeconds > 0): ?>
<div class="cart-timer">
  <p>
    <span data-translate-key="cart_expires_in">
      <?= Translator::get('cart_expires_in') ?? 'Tu carrito vencerá en:' ?>
    </span>
    <strong>
      <span id="countdown-timer" style="display:inline;visibility:visible;opacity:1;color:#d9534f;font-weight:bold;"></span>
    </strong>
  </p>
</div>

<script>
(function () {
  // Esperar DOM listo
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startTimer);
  } else {
    startTimer();
  }

  function startTimer() {
    let remaining = <?= (int)$remainingSeconds ?>; // segundos calculados en PHP (DB)

    const pad = (n) => String(n).padStart(2, "0");

    function tick() {
      const el = document.getElementById("countdown-timer");

      // Si el traductor re-renderizó el DOM, esperamos al siguiente tick
      if (!el) return;

      // Forzar visibilidad (por si CSS lo oculta)
      el.style.display = "inline";
      el.style.visibility = "visible";
      el.style.opacity = "1";

      if (remaining <= 0) {
        el.textContent = "00:00";
        location.reload(); // fuerza estado EXPIRADO en backend
        return;
      }

      const minutes = Math.floor(remaining / 60);
      const seconds = remaining % 60;

      el.textContent = `${pad(minutes)}:${pad(seconds)}`;
      remaining--;
    }

    // Pintar de una vez
    tick();

    // Tick cada segundo
    setInterval(tick, 1000);
  }
})();
</script>
<?php endif; ?>

                </p>
            </div >
        <?php endif; ?>
        <div class="table-responsive"> 
        <table class="cart-table">
            <thead>
                <tr>
                    <th></th>
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
                                <img src="<?= htmlspecialchars($item['pro_imagen_url']) ?>"
                                     alt="<?= htmlspecialchars($item['pro_nombre']) ?>"
                                     width="50">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/50?text=No+Img"
                                     alt="<?= htmlspecialchars($item['pro_nombre']) ?>"
                                     width="50">
                            <?php endif; ?>
                        </td>

                        <td><?= htmlspecialchars($item['pro_nombre']) ?></td>

                        <td>$<?= number_format((float)$item['pro_precio_unitario'], 2) ?></td>

                        <td>
                            <form action="cart.php" method="post" class="form-inline">
                                <input type="hidden" name="action" value="update_quantity">
                                <input type="hidden" name="pro_id" value="<?= (int)$item['dca_producto'] ?>">
                                <input type="number"
                                       name="quantity"
                                       value="<?= (int)$item['dca_cantidad'] ?>"
                                       min="1"
                                       class="form-control form-control-sm"
                                       onchange="this.form.submit()"
                                       <?= $isCartExpired ? 'disabled' : '' ?>>
                            </form>
                        </td>

                        <td>
                            $<?= number_format(((int)$item['dca_cantidad']) * ((float)$item['pro_precio_unitario']), 2) ?>
                        </td>

                        <td>
                            <form action="cart.php" method="post" class="form-inline">
                                <input type="hidden" name="action" value="remove_item">
                                <input type="hidden" name="pro_id" value="<?= (int)$item['dca_producto'] ?>">
                                <button type="submit"
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('<?= htmlspecialchars(Translator::get('confirm_remove_item') ?? '¿Estás seguro de eliminar este producto del carrito?') ?>');"
                                        <?= $isCartExpired ? 'disabled' : '' ?>
                                        data-translate-key="remove_item_button">
                                    <?= Translator::get('remove_item_button') ?? 'Eliminar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"> <!-- Adjusted colspan to align with appropriate column -->
                        <strong><span data-translate-key="total_label"><?= Translator::get('total_label') ?? 'Total' ?>:</span></strong>
                    </td>
                    <td><strong>$<?= number_format((float)$cartTotal, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
        </div>

        <div class="cart-summary">
            <h3>Resumen del Carrito</h3>
            <p class="cart-total"><strong>Total:</strong> $<?= number_format((float)$cartTotal, 2) ?></p>
            <div class="cart-actions">
                <a href="checkout.php" 
                   class="btn-primary" 
                   <?= $isCartExpired || empty($cartItems) ? 'disabled' : '' ?>>
                    Finalizar Compra
                </a>
                <a href="index.php" class="btn-secondary">Seguir Comprando</a>
            </div>
        </div>

        <style>
        .cart-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .cart-summary {
            margin-top: 30px;
            padding: 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        .cart-actions {
            margin-top: 15px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn-primary, .btn-secondary {
            border: none;
            padding: 10px 16px;
            border-radius: 5px;
            text-decoration: none;
            color: #fff;
            transition: background 0.3s;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .cart-table th {
            background: #f8f9fa;
            text-align: left;
            padding: 12px;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .cart-table td {
            padding: 12px;
            vertical-align: middle;
            border-bottom: 1px solid #eee;
        }
        .cart-table img {
            width: 70px;
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
        }
        .cart-table input[type="number"] {
            width: 60px;
            text-align: center;
        }
        .btn-danger {
            background: #dc3545;
            color: #fff;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-danger:hover {
            background: #b52a37;
        }
        @media(max-width: 768px) {
            .cart-table, .cart-table thead { display: none; }
            .cart-table tr, .cart-table td { display: block; width: 100%; }
            .cart-table tr { margin-bottom: 15px; border: 1px solid #eee; padding: 10px; border-radius: 8px; }
            .cart-summary { text-align: center; }
            .cart-actions { justify-content: center; }
        }
        </style>
    <?php endif; ?>

    <p>
        <a href="index.php" class="btn btn-secondary btn-sm" data-translate-key="continue_shopping">
            <?= Translator::get('continue_shopping') ?? 'Continuar Comprando' ?>
        </a>
    </p>
</div>

<?php require_once 'footer.php'; ?>

<?php if (!$isCartExpired && $remainingSeconds > 0): ?>
<script>
(function () {
  const el = document.getElementById("countdown-timer");
  if (!el) return;

  // Tiempo restante calculado con hora de DB (servidor)
  let remaining = <?= (int)$remainingSeconds ?>; // segundos

  const pad = (n) => String(n).padStart(2, "0");

  function render() {
    if (remaining <= 0) {
      el.textContent = "00:00";
      // Cuando llega a 0, recarga para que PHP muestre estado EXPIRADO
      window.location.reload();
      return;
    }

    // mm:ss
    const minutes = Math.floor(remaining / 60);
    const seconds = remaining % 60;

    el.textContent = `${pad(minutes)}:${pad(seconds)}`;
    remaining--;
  }

  render();
  setInterval(render, 1000);
})();
</script>
<?php endif; ?>

</body>

<script>
    (function() {
        let startTime = Date.now();
        let metId = parseInt(<?= $metId ?>);
        console.log('Page loaded. Initial metId:', metId);

        function sendPagePermanence() {
            let endTime = Date.now();
            let permanence = endTime - startTime; // in milliseconds
            console.log('Attempting to send page permanence. Current metId:', metId, 'Permanence:', permanence, 'ms');

            let cookieAccepted = document.cookie.includes('cookie_accepted');
            console.log('Cookie Accepted:', cookieAccepted);

            if (cookieAccepted && !isNaN(metId) && metId > 0 && permanence > 0) {
                console.log('Sending page permanence AJAX for met_id=' + metId + ', permanence=' + permanence + 'ms');
                fetch('track_page_duration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        met_id: metId,
                        permanence: permanence
                    }),
                }).then(response => {
                    // console.log('Page permanence tracking sent:', response);
                }).catch(error => {
                    console.error('Error sending page permanence:', error);
                });
            }
        }

        window.addEventListener('beforeunload', sendPagePermanence);
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendPagePermanence();
            } else {
                startTime = Date.now();
            }
        });
    })();
</script>
</html>
