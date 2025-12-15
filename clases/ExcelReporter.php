<?php

require_once __DIR__ . '/../dbconexion.php';

class ExcelReporter {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    public function generateSalesExcel(array $filters = []): string {
        // Requiere la librería PhpSpreadsheet. Asegúrate de que esté instalada (ej. vía Composer: composer require phpoffice/phpspreadsheet)
        require_once __DIR__ . '/../vendor/autoload.php';

        $query = "SELECT 
                    v.ven_id, 
                    v.ven_fecha, 
                    v.ven_total, 
                    v.ven_estado, 
                    u.usu_nombre,
                    u.usu_email,
                    dv.dev_cantidad,
                    dv.dev_precio_unidad_venta,
                    p.pro_nombre,
                    p.pro_precio_compra,
                    (dv.dev_cantidad * p.pro_precio_compra) AS costo_total_producto,
                    (dv.dev_cantidad * (dv.dev_precio_unidad_venta - p.pro_precio_compra)) AS ganancia,
                    CASE 
                        WHEN (dv.dev_cantidad * dv.dev_precio_unidad_venta) > 0 
                        THEN ((dv.dev_cantidad * (dv.dev_precio_unidad_venta - p.pro_precio_compra)) / (dv.dev_cantidad * dv.dev_precio_unidad_venta)) * 100 
                        ELSE 0 
                    END AS margen_ganancia
                  FROM Venta v
                  JOIN Usuario u ON v.ven_usuario = u.usu_id
                  JOIN Detalle_venta dv ON v.ven_id = dv.dev_venta
                  JOIN Producto p ON dv.dev_producto = p.pro_id
                  WHERE 1=1";
        $params = [];

        if (isset($filters['start_date']) && !empty($filters['start_date'])) {
            $query .= " AND v.ven_fecha >= ?";
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        if (isset($filters['end_date']) && !empty($filters['end_date'])) {
            $query .= " AND v.ven_fecha <= ?";
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        if (isset($filters['user_id']) && $filters['user_id'] > 0) {
            $query .= " AND v.ven_usuario = ?";
            $params[] = $filters['user_id'];
        }

        $query .= " ORDER BY v.ven_fecha DESC, v.ven_id, p.pro_nombre";

        $stmt = $this->db->query($query, $params);
        $salesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Crear un nuevo objeto Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeceras de la tabla
        $sheet->setCellValue('A1', 'ID Venta');
        $sheet->setCellValue('B1', 'Fecha');
        $sheet->setCellValue('C1', 'Usuario');
        $sheet->setCellValue('D1', 'Correo');
        $sheet->setCellValue('E1', 'Producto');
        $sheet->setCellValue('F1', 'Cantidad');
        $sheet->setCellValue('G1', 'Precio Unidad Venta');
        $sheet->setCellValue('H1', 'Costo Unitario');
        $sheet->setCellValue('I1', 'Costo Total Producto');
        $sheet->setCellValue('J1', 'Ganancia por Producto');
        $sheet->setCellValue('K1', 'Margen Ganancia (%)');
        $sheet->setCellValue('L1', 'Total Venta');
        $sheet->setCellValue('M1', 'Estado Venta');
        $sheet->setCellValue('N1', 'Oferta Aplicada');

        // Llenar datos
        $row = 2;
        foreach ($salesData as $sale) {
            // Calcular si hubo oferta
            $ofertaAplicada = 'No';
            if (isset($sale['pro_precio_compra']) && isset($sale['dev_precio_unidad_venta'])) {
                $productoQuery = "SELECT pro_precio_unitario, pro_precio_oferta FROM Producto WHERE pro_nombre = ? LIMIT 1";
                $productoData = $this->db->query($productoQuery, [$sale['pro_nombre']])->fetch(PDO::FETCH_ASSOC);
                if (!empty($productoData['pro_precio_oferta']) && $productoData['pro_precio_oferta'] > 0 && $productoData['pro_precio_oferta'] < $productoData['pro_precio_unitario']) {
                    $descuento = round(100 * (1 - ($productoData['pro_precio_oferta'] / $productoData['pro_precio_unitario'])));
                    $ofertaAplicada = "Sí ({$descuento}% OFF)";
                }
            }

            $sheet->setCellValue('A' . $row, $sale['ven_id']);
            $sheet->setCellValue('B' . $row, $sale['ven_fecha']);
            $sheet->setCellValue('C' . $row, $sale['usu_nombre']);
            $sheet->setCellValue('D' . $row, $sale['usu_email']);
            $sheet->setCellValue('E' . $row, $sale['pro_nombre']);
            $sheet->setCellValue('F' . $row, $sale['dev_cantidad']);
            $sheet->setCellValue('G' . $row, $sale['dev_precio_unidad_venta']);
            $sheet->setCellValue('H' . $row, $sale['pro_precio_compra']);
            $sheet->setCellValue('I' . $row, $sale['costo_total_producto']);
            $sheet->setCellValue('J' . $row, $sale['ganancia']);
            $sheet->setCellValue('K' . $row, number_format($sale['margen_ganancia'], 2));
            $sheet->setCellValue('L' . $row, $sale['ven_total']);
            $sheet->setCellValue('M' . $row, $sale['ven_estado']);
            $sheet->setCellValue('N' . $row, $ofertaAplicada);
            $row++;
        }

        // Configurar el escritor y las cabeceras para la descarga
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'informe_ventas_' . date('Ymd_His') . '.xlsx';

        // Ob_start/ob_get_clean para capturar la salida del Writer
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        return $excelOutput; // Devolver el contenido del Excel
    }
}

?>
