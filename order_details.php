<?php
session_start();

require_once 'header.php';
require_once 'clases/OrderManager.php';
require_once 'clases/Tracker.php'; // Incluir la clase Tracker

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
        <div class="order-header-card">
            <h3>Detalles de la Orden</h3>
            <table class="order-info">
                <tr><th>ID de Venta</th><td><?= htmlspecialchars($sale['ven_id']) ?></td></tr>
                <tr><th>Cliente</th><td><?= htmlspecialchars($sale['usu_nombre']) ?></td></tr>
                <tr><th>Fecha</th><td><?= htmlspecialchars($sale['ven_fecha']) ?></td></tr>
                <tr><th>Estado</th><td><?= htmlspecialchars($sale['ven_estado']) ?></td></tr>
                <tr><th>Total</th><td>$<?= number_format((float)$sale['ven_total'], 2) ?></td></tr>
            </table>
        </div>

        <style>
        .order-header-card {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 25px;
        }
        .order-header-card h3 {
            margin-bottom: 15px;
            color: #007bff;
        }
        .order-info {
            width: 100%;
            max-width: 600px;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .order-info th {
            background: #f8f9fa;
            text-align: left;
            padding: 10px;
            width: 35%;
            color: #333;
            border-bottom: 1px solid #dee2e6;
        }
        .order-info td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            color: #444;
        }
        </style>
        
        <?php if (!$saleIntegrityStatus || $sale['ven_modificado']): ?>
            <p class="error"><span data-translate-key="sale_integrity_warning">⚠️ ¡Advertencia: Venta modificada o integridad comprometida!</span></p>
        <?php else: ?>
            <p class="message"><span data-translate-key="sale_integrity_verified">✔ Integridad de la venta verificada.</span></p>
        <?php endif; ?>

        <h3 data-translate-key="products_in_order_title"><?= Translator::get('products_in_order_title') ?? 'Artículos de tu Orden:' ?></h3>

        <?php if (!empty($sale['details'])): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sale['details'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['pro_nombre']) ?></td>
                            <td><?= (int)$item['dev_cantidad'] ?></td>
                            <td>
                                <?php if (!empty($item['pro_precio_oferta']) && $item['pro_precio_oferta'] > 0): ?>
                                    <span style="text-decoration: line-through; color: gray;">
                                        $<?= number_format((float)$item['dev_precio_unidad_venta'], 2) ?>
                                    </span>
                                    <strong style="color: red; margin-left: 5px;">
                                        $<?= number_format((float)$item['pro_precio_oferta'], 2) ?>
                                    </strong>
                                    <?php 
                                    $descuento = round(100 * (1 - ($item['pro_precio_oferta'] / $item['dev_precio_unidad_venta'])));
                                    ?>
                                    <span style="color: green; margin-left: 5px;">
                                        (<?= $descuento ?>% OFF)
                                    </span>
                                <?php else: ?>
                                    $<?= number_format((float)$item['dev_precio_unidad_venta'], 2) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                    $precio = (!empty($item['pro_precio_oferta']) && $item['pro_precio_oferta'] > 0) 
                                        ? $item['pro_precio_oferta'] 
                                        : $item['dev_precio_unidad_venta'];
                                ?>
                                $<?= number_format(((int)$item['dev_cantidad']) * ((float)$precio), 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-order">No se encontraron productos en esta orden.</p>
        <?php endif; ?>

        <div class="order-summary">
            <h4>Resumen de la Orden</h4>
            <p><strong>Total:</strong> $<?= number_format((float)$sale['ven_total'], 2) ?></p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($sale['ven_estado']) ?></p>
            <p><strong>Fecha:</strong> <?= htmlspecialchars($sale['ven_fecha']) ?></p>
        </div>

        <a href="admin/invoice.php?saleId=<?= $sale['ven_id'] ?>" target="_blank" class="btn btn-primary">Descargar Factura (PDF)</a>

        <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .cart-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #dee2e6;
        }
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .empty-order {
            margin-top: 20px;
            color: #777;
            font-style: italic;
        }
        .order-summary {
            margin-top: 30px;
            padding: 20px;
            border-top: 1px solid #dee2e6;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .btn-primary {
            background: #007bff;
            color: #fff;
            padding: 10px 16px;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        </style>
    <?php endif; ?>
    <p><a href="index.php" class="btn btn-secondary" data-translate-key="back_to_main_page"><?= Translator::get('back_to_main_page') ?? 'Volver a la página principal' ?></a></p>
</div>

<?php require_once 'footer.php'; ?>

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
            }
        });
    })();
</script>
