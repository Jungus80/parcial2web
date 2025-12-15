<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/OrderManager.php';

class PdfInvoiceGenerator
{
    private DB $db;
    private OrderManager $orderManager;

    public function __construct()
    {
        $this->db = new DB();
        $this->db->getConnection();
        $this->orderManager = new OrderManager();
    }

    /**
     * Genera el PDF y devuelve el BINARIO como string.
     */
    public function generateInvoicePdf(int $saleId): string
    {
        // Composer autoload (si instalaste FPDF por composer)
        require_once __DIR__ . '/../vendor/autoload.php';

        // Si NO es por composer, comenta la línea de arriba y usa:
        // require_once __DIR__ . '/../fpdf/fpdf.php';

        $sale = $this->orderManager->getSaleById($saleId);

        if (!$sale) {
            // OJO: no hagas echo aquí, solo devuelve error en string o lanza excepción
            return '';
        }

        $pdf = new FPDF();
        $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, mb_convert_encoding('FACTURA', 'ISO-8859-1', 'UTF-8'), 0, 1);
        $pdf->Ln(2);

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, mb_convert_encoding('ID Venta: ', 'ISO-8859-1', 'UTF-8') . $sale['ven_id'], 0, 1);
        $pdf->Cell(0, 8, mb_convert_encoding('Fecha: ', 'ISO-8859-1', 'UTF-8') . $sale['ven_fecha'], 0, 1);
        $pdf->Cell(0, 8, mb_convert_encoding('Cliente: ', 'ISO-8859-1', 'UTF-8') . $sale['usu_nombre'], 0, 1);
        $pdf->Cell(0, 8, mb_convert_encoding('Estado: ', 'ISO-8859-1', 'UTF-8') . $sale['ven_estado'], 0, 1);
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, mb_convert_encoding('Productos:', 'ISO-8859-1', 'UTF-8'), 0, 1);

        // Cabecera tabla
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(80, 7, mb_convert_encoding('Producto', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(20, 7, mb_convert_encoding('Cant.', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(40, 7, mb_convert_encoding('Precio Unit.', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Cell(40, 7, mb_convert_encoding('Subtotal', 'ISO-8859-1', 'UTF-8'), 1);
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 10);

        $details = $sale['details'] ?? [];
        foreach ($details as $item) {
            $nombre = (string)($item['pro_nombre'] ?? '');
            $cant   = (int)($item['dev_cantidad'] ?? 0);
            $precio = (float)($item['dev_precio_unidad_venta'] ?? 0);
            $subt   = (float)($item['dev_subtotal'] ?? ($cant * $precio));

            // Verificar oferta si el producto tiene precio original distinto del precio de venta
            $productoInfo = $this->db->query("SELECT pro_precio_unitario, pro_precio_oferta FROM Producto WHERE pro_id = ?", [$item['dev_producto']])->fetch(PDO::FETCH_ASSOC);
            $precioOriginal = (float)($productoInfo['pro_precio_unitario'] ?? $precio);
            $precioOferta   = (float)($productoInfo['pro_precio_oferta'] ?? 0);

            $precioTexto = '';
            if ($precioOferta > 0 && $precioOferta < $precioOriginal) {
                $descuento = round(100 * (1 - ($precioOferta / $precioOriginal)));
                $precioTexto = "Oferta $" . number_format($precioOferta, 2) . " (" . $descuento . "% OFF)";
            } else {
                $precioTexto = "$" . number_format($precio, 2);
            }

            $pdf->Cell(80, 7, mb_convert_encoding($nombre, 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Cell(20, 7, (string)$cant, 1);
            $pdf->Cell(40, 7, mb_convert_encoding($precioTexto, 'ISO-8859-1', 'UTF-8'), 1);
            $pdf->Cell(40, 7, '$' . number_format($subt, 2), 1);
            $pdf->Ln();
        }

        $pdf->Ln(4);
        $pdf->SetFont('Arial', 'I', 9);
        $pdf->MultiCell(0, 6, mb_convert_encoding('Los precios bajo "Oferta" reflejan descuentos aplicados durante la compra.', 'ISO-8859-1', 'UTF-8'), 0, 'L');

        $total = (float)($sale['ven_total'] ?? 0);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(140, 10, mb_convert_encoding('Total:', 'ISO-8859-1', 'UTF-8'), 1, 0, 'R');
        $pdf->Cell(40, 10, '$' . number_format($total, 2), 1, 1, 'R');

        $pdf->Ln(8);
        $pdf->Cell(0, 10, mb_convert_encoding('¡Gracias por su compra!', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

        // DEVUELVE BINARIO PDF
        return $pdf->Output('S');
    }
}
