<?php
session_start();

require_once 'clases/PdfInvoiceGenerator.php';

$generator = new PdfInvoiceGenerator();

$saleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($saleId > 0) {
    // Opcional: Verificar permisos para descargar la factura (usuario logueado vs admin)
    $orderManager = new OrderManager(); // Re-instanciar para verificación
    $sale = $orderManager->getSaleById($saleId);

    if ($sale) {
        // Solo el usuario que hizo la compra o un administrador puede descargar la factura
        if (($_SESSION['user_id'] ?? 0) === (int)$sale['ven_usuario'] || ($_SESSION['user_rol'] ?? 'cliente') === 'admin') {
            echo $generator->generateInvoicePdf($saleId);
            exit();
        } else {
            echo "Acceso denegado: No tienes permiso para descargar esta factura.";
        }
    } else {
        echo "Error: Venta no encontrada.";
    }
} else {
    echo "Error: ID de venta no válido.";
}

?>
