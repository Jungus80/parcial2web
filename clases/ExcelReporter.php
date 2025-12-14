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

        $query = "SELECT v.ven_id, v.ven_fecha, v.ven_total, v.ven_estado, u.usu_nombre 
                  FROM Venta v
                  JOIN Usuario u ON v.ven_usuario = u.usu_id
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

        $query .= " ORDER BY v.ven_fecha DESC";

        $stmt = $this->db->query($query, $params);
        $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Crear un nuevo objeto Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Cabeceras de la tabla
        $sheet->setCellValue('A1', 'ID Venta');
        $sheet->setCellValue('B1', 'Fecha');
        $sheet->setCellValue('C1', 'Total');
        $sheet->setCellValue('D1', 'Estado');
        $sheet->setCellValue('E1', 'Usuario');

        // Llenar datos
        $row = 2;
        foreach ($sales as $sale) {
            $sheet->setCellValue('A' . $row, $sale['ven_id']);
            $sheet->setCellValue('B' . $row, $sale['ven_fecha']);
            $sheet->setCellValue('C' . $row, $sale['ven_total']);
            $sheet->setCellValue('D' . $row, $sale['ven_estado']);
            $sheet->setCellValue('E' . $row, $sale['usu_nombre']);
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
