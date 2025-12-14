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

    public function getMostViewedProducts(int $limit = 10): array {
        $query = "SELECT MIN(p.pro_id) as pro_id, p.pro_nombre, COALESCE(MIN(p.pro_imagen_url),'https://via.placeholder.com/50x50?text=No+Image') as pro_imagen_url,
                          COUNT(o.obs_producto) as total_views,
                          COALESCE(AVG(o.obs_permanencia), 0) as average_permanence
                  FROM Observa o
                  JOIN Producto p ON o.obs_producto = p.pro_id
                  GROUP BY p.pro_nombre
                  ORDER BY total_views DESC
                  LIMIT " . (int)$limit;
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLeastViewedProducts(int $limit = 10): array {
      $query = "SELECT MIN(p.pro_id) as pro_id, p.pro_nombre, COALESCE(MIN(p.pro_imagen_url),'https://via.placeholder.com/50x50?text=No+Image') as pro_imagen_url,
                        COUNT(o.obs_producto) as total_views,
                        COALESCE(AVG(o.obs_permanencia), 0) as average_permanence
                  FROM Producto p
                  LEFT JOIN Observa o ON o.obs_producto = p.pro_id
                  GROUP BY p.pro_nombre
                  ORDER BY total_views ASC
                LIMIT " . (int)$limit;
      $stmt = $this->db->query($query);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductAnalytics(int $productId): array {
        $query = "SELECT p.pro_id, p.pro_nombre, p.pro_imagen_url,
                          COUNT(o.obs_producto) as total_views,
                          COALESCE(AVG(o.obs_permanencia), 0) as average_permanence
                  FROM Producto p
                  LEFT JOIN Observa o ON o.obs_producto = p.pro_id
                  WHERE p.pro_id = ?
                  GROUP BY p.pro_id, p.pro_nombre, p.pro_imagen_url";
        $stmt = $this->db->query($query, [$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
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

    public function getTotalCostOfGoodsSold(): float {
        $query = "SELECT SUM(dv.dev_cantidad * p.pro_precio_compra)
                  FROM Detalle_venta dv
                  JOIN Venta v ON dv.dev_venta = v.ven_id
                  JOIN Producto p ON dv.dev_producto = p.pro_id
                  WHERE v.ven_estado IN ('ACEPTADA', 'PAGADA')";
        $stmt = $this->db->query($query);
        return (float)$stmt->fetchColumn();
    }

    public function getGrossProfit(): float {
        return $this->getTotalRevenue() - $this->getTotalCostOfGoodsSold();
    }

    public function getGrossProfitMargin(): float {
        $totalRevenue = $this->getTotalRevenue();
        if ($totalRevenue == 0) {
            return 0.0;
        }
        return ($this->getGrossProfit() / $totalRevenue) * 100;
    }
}

?>
