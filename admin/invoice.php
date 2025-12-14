<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../clases/PdfInvoiceGenerator.php';
require_once __DIR__ . '/../clases/OrderManager.php'; // Added to use OrderManager
require_once __DIR__ . '/../dbconexion.php'; // Needed for DB access in OrderManager

// Check if any user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] <= 0) {
    http_response_code(403);
    echo "Acceso denegado. Debes iniciar sesión para ver tus facturas.";
    exit;
}

$userId = (int)$_SESSION['user_id'];
$saleId = isset($_GET['saleId']) ? (int)$_GET['saleId'] : 0;

if ($saleId <= 0) {
    http_response_code(400);
    echo "saleId inválido";
    exit;
}

// Instantiate OrderManager to verify ownership
$orderManager = new OrderManager();
$sale = $orderManager->getSaleById($saleId);

// Verify if the sale exists and belongs to the logged-in user, or if the user is an admin
if (!$sale || (($_SESSION['user_rol'] ?? 'cliente') !== 'admin' && $sale['ven_usuario'] !== $userId)) {
    http_response_code(404);
    echo "Factura no encontrada o no tienes permiso para verla.";
    exit;
}

$generator = new PdfInvoiceGenerator();
$pdfBinary = $generator->generateInvoicePdf($saleId);

if ($pdfBinary === '') {
    http_response_code(500); // Internal server error if PDF generation fails unexpectedly
    echo "Error al generar la factura.";
    exit;
}

// Limpia cualquier buffer (evita PDFs corruptos)
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="factura_' . $saleId . '.pdf"');
header('Content-Length: ' . strlen($pdfBinary));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $pdfBinary;
exit;
