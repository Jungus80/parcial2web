<?php

require_once __DIR__ . '/../dbconexion.php';

class MetricsManager {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    public function getTotalUsers(): int {
        $query = "SELECT COUNT(*) FROM Usuario";
        $stmt = $this->db->query($query);
        return (int)$stmt->fetchColumn();
    }

    public function getActiveUsers(int $hours = 24): int {
        $query = "SELECT COUNT(DISTINCT met_usuario) FROM Metrica_navegacion WHERE met_fecha_visita >= DATE_SUB(NOW(), INTERVAL ? HOUR)";
        $stmt = $this->db->query($query, [$hours]);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalProducts(): int {
        $query = "SELECT COUNT(*) FROM Producto";
        $stmt = $this->db->query($query);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalSales(): int {
        // Incluir ventas en estado 'ACEPTADA' y 'PAGADA' para métricas
        $query = "SELECT COUNT(*) FROM Venta WHERE ven_estado IN ('ACEPTADA', 'PAGADA')";
        $stmt = $this->db->query($query);
        return (int)$stmt->fetchColumn();
    }

    public function getTotalRevenue(): float {
        $query = "SELECT SUM(ven_total) FROM Venta WHERE ven_estado IN ('ACEPTADA', 'PAGADA')";
        $stmt = $this->db->query($query);
        return (float)$stmt->fetchColumn();
    }

    public function getMostViewedProducts(int $limit = 5): array {
        // Para LIMIT, los parámetros no pueden ser placeholders; deben concatenarse directamente.
        // Dado que $limit es un int, es seguro concatenar.
        $query = "SELECT p.pro_nombre, COUNT(o.obs_producto) as views
                  FROM Observa o
                  JOIN Producto p ON o.obs_producto = p.pro_id
                  GROUP BY p.pro_nombre
                  ORDER BY views DESC
                  LIMIT " . (int)$limit;
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSalesByDay(int $days = 7): array {
        $query = "SELECT DATE(ven_fecha) as sale_date, SUM(ven_total) as daily_revenue
                  FROM Venta
                  WHERE ven_fecha >= DATE_SUB(NOW(), INTERVAL ? DAY) AND ven_estado IN ('ACEPTADA', 'PAGADA')
                  GROUP BY DATE(ven_fecha)
                  ORDER BY sale_date ASC";
        $stmt = $this->db->query($query, [$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
