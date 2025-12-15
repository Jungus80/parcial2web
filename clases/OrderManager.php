<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/CartManager.php';
require_once __DIR__ . '/ProductManager.php';

class OrderManager {
    private $db;
    private $cartManager;
    private $productManager;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
        $this->cartManager = new CartManager();
        $this->productManager = new ProductManager();
    }

    public function processCheckout(): ?int {
        $cartItems = $this->cartManager->getCartItems();
        if (empty($cartItems)) {
            error_log("Intento de checkout con carrito vacío.");
            return null;
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $cartId = $_SESSION['cart_id'] ?? null;

        if (!$cartId) {
            error_log("No hay un ID de carrito en la sesión.");
            return null;
        }

        // 1. Verificar stock y precio actual del producto
        $totalVenta = 0.0;
        $detailsForHash = [];

        $this->db->conn->beginTransaction();
        try {
            foreach ($cartItems as $item) {
                $product = $this->productManager->getProductById($item['dca_producto']);
                if (!$product || $product['pro_cantidad_stock'] < $item['dca_cantidad']) {
                    $this->db->conn->rollBack();
                    error_log("Stock insuficiente para producto: " . $item['pro_nombre']);
                    return null; // Stock insuficiente o producto no encontrado
                }
                // Determinar precio final tomando en cuenta oferta activa
                $precioFinal = (!empty($product['pro_precio_oferta']) && $product['pro_precio_oferta'] > 0)
                    ? $product['pro_precio_oferta']
                    : $product['pro_precio_unitario'];

                $subtotal = $item['dca_cantidad'] * $precioFinal;
                $totalVenta += $subtotal;
                $detailsForHash[] = [
                    'pro_id' => $product['pro_id'],
                    'cantidad' => $item['dca_cantidad'],
                    'precio_unidad_venta' => $precioFinal,
                    'subtotal' => $subtotal
                ];
            }
            
            // 2. Crear la Venta
            // 2. Crear la Venta
            // Formatear totalVenta para asegurar precisión consistente al insertar en DB y para el hash.
            $totalVentaFormatted = number_format($totalVenta, 2, '.', '');
            $queryVenta = "INSERT INTO Venta (ven_carrito, ven_usuario, ven_fecha, ven_total, ven_estado) VALUES (?, ?, NOW(), ?, 'ACEPTADA')";
            $stmtVenta = $this->db->insertSeguro($queryVenta, [$cartId, $userId, $totalVentaFormatted]);
            if ($stmtVenta->rowCount() === 0) {
                $this->db->conn->rollBack();
                error_log("Error al crear la venta.");
                return null;
            }
            $ventaId = $this->db->conn->lastInsertId();

            // Recuperar la fecha exacta de la DB para usarla en el hash
            $queryGetVentaFecha = "SELECT ven_fecha FROM Venta WHERE ven_id = ?";
            $stmtGetVentaFecha = $this->db->query($queryGetVentaFecha, [$ventaId]);
            $ventaData = $stmtGetVentaFecha->fetch(PDO::FETCH_ASSOC);
            $venFechaDb = $ventaData['ven_fecha'];

            // 3. Crear Detalle_venta y actualizar stock de productos
            foreach ($detailsForHash as $item) {
                $queryDetalle = "INSERT INTO Detalle_venta (dev_venta, dev_producto, dev_cantidad, dev_precio_unidad_venta, dev_subtotal) VALUES (?, ?, ?, ?, ?)";
                $stmtDetalle = $this->db->insertSeguro($queryDetalle, [
                    $ventaId,
                    $item['pro_id'],
                    $item['cantidad'],
                    number_format($item['precio_unidad_venta'], 2, '.', ''), // Asegurar formato consistente
                    number_format($item['subtotal'], 2, '.', '') // Asegurar formato consistente
                ]);
                if ($stmtDetalle->rowCount() === 0) {
                    $this->db->conn->rollBack();
                    error_log("Error al crear el detalle de venta para producto: " . $item['pro_id']);
                    return null;
                }

                // Actualizar stock
                $updateStockQuery = "UPDATE Producto SET pro_cantidad_stock = pro_cantidad_stock - ? WHERE pro_id = ?";
                $stmtStock = $this->db->updateSeguro($updateStockQuery, [$item['cantidad'], $item['pro_id']]);
                if ($stmtStock->rowCount() === 0) {
                    $this->db->conn->rollBack();
                    error_log("Error al actualizar stock para producto: " . $item['pro_id']);
                    return null;
                }
            }

            // 4. Generar y guardar hash de integridad de la venta
            $saleHash = $this->generateSaleHash($ventaId, $userId, $totalVenta, 'ACEPTADA', $venFechaDb, $detailsForHash);
            $updateHashQuery = "UPDATE Venta SET ven_hash = ? WHERE ven_id = ?";
            $this->db->updateSeguro($updateHashQuery, [$saleHash, $ventaId]);

            // 5. Marcar carrito como CONVERTIDO
            $updateCartStatusQuery = "UPDATE Carrito SET car_estado = 'CONVERTIDO' WHERE car_id = ?";
            $this->db->updateSeguro($updateCartStatusQuery, [$cartId]);

            // 6. Limpiar carrito de la sesión
            $this->cartManager->clearCart();
            unset($_SESSION['cart_id']);

            $this->db->conn->commit();
            return $ventaId;

        } catch (PDOException $e) {
            $this->db->conn->rollBack();
            error_log("Error general en checkout: " . $e->getMessage());
            return null;
        }
    }

    private function generateSaleHash(int $venId, int $venUsuario, float $venTotal, string $venEstado, string $venFechaDb, array $details): string {
        // Ordenar detalles por pro_id para un hash consistente
        usort($details, function($a, $b) { return $a['pro_id'] <=> $b['pro_id']; });

        $detailsString = '';
        foreach ($details as $detail) {
            $detailsString .= $detail['pro_id'] . '|' .
                              $detail['cantidad'] . '|' .
                              number_format($detail['precio_unidad_venta'], 2, '.', '') . '|' .
                              number_format($detail['subtotal'], 2, '.', '') . '|';
        }

        // Usar la fecha recuperada de la base de datos para asegurar consistencia
        $canonicalString = "{$venId}|{$venUsuario}|{$venFechaDb}|{" . number_format($venTotal, 2, '.', '') . "}|{$venEstado}|{$detailsString}";
        return hash('sha256', $canonicalString);
    }

    private function formatDateForHash(DateTime $date): string {
        // Usar un formato consistente para la fecha al generar el hash
        return $date->format('Y-m-d H:i:s'); // Ajustar si el formato TIMESTAMP de MySQL difiere
    }

    public function verifySaleIntegrity(int $ventaId): bool {
        $query = "SELECT ven_id, ven_usuario, ven_fecha, ven_total, ven_estado, ven_hash FROM Venta WHERE ven_id = ?";
        $stmt = $this->db->query($query, [$ventaId]);
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) return false;

        $detailsQuery = "SELECT dev_producto as pro_id, dev_cantidad as cantidad, dev_precio_unidad_venta as precio_unidad_venta, dev_subtotal as subtotal FROM Detalle_venta WHERE dev_venta = ?";
        $detailsStmt = $this->db->query($detailsQuery, [$ventaId]);
        $details = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Recalcular el hash usando la ven_fecha de la DB, NO NOW()

        // Recuperar la fecha en el mismo formato que se guardó
        $venFechaInHashFormat = $this->formatDateForHash(new DateTime($venta['ven_fecha']));
        
        $canonicalString = "{$venta['ven_id']}|{$venta['ven_usuario']}|{$venFechaInHashFormat}|{" . number_format($venta['ven_total'], 2, '.', '') . "}|{$venta['ven_estado']}|";

        usort($details, function($a, $b) { return $a['pro_id'] <=> $b['pro_id']; });
        foreach ($details as $detail) {
            $canonicalString .= $detail['pro_id'] . '|' .
                                $detail['cantidad'] . '|' .
                                number_format($detail['precio_unidad_venta'], 2, '.', '') . '|' .
                                number_format($detail['subtotal'], 2, '.', '') . '|';
        }

        $recalculatedHash = hash('sha256', $canonicalString);

        if ($recalculatedHash !== $venta['ven_hash']) {
            // Marcar como modificado si los hashes no coinciden
            $updateModifiedQuery = "UPDATE Venta SET ven_modificado = TRUE WHERE ven_id = ?";
            $this->db->updateSeguro($updateModifiedQuery, [$ventaId]);
            return false;
        }

        return true;
    }

    public function getSaleById(int $saleId): ?array{
        $query = "SELECT v.*, u.usu_nombre 
                  FROM Venta v 
                  JOIN Usuario u ON v.ven_usuario = u.usu_id 
                  WHERE v.ven_id = ?";
        $stmt = $this->db->query($query, [$saleId]);
        $sale = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale) {
            $detailsQuery = "SELECT dv.*, p.pro_nombre
                             FROM Detalle_venta dv
                             JOIN Producto p ON dv.dev_producto = p.pro_id
                             WHERE dv.dev_venta = ?";
            $detailsStmt = $this->db->query($detailsQuery, [$saleId]);
            $sale['details'] = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $sale;
    }

    public function getAllSales(): array {
        $query = "SELECT v.ven_id, v.ven_fecha, v.ven_total, v.ven_estado, u.usu_nombre 
                  FROM Venta v 
                  JOIN Usuario u ON v.ven_usuario = u.usu_id
                  ORDER BY v.ven_fecha DESC";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Métodos públicos para acceder a información del carrito a través del OrderManager
    public function getCartItems(): array {
        return $this->cartManager->getCartItems();
    }

    public function getCartTotal(): float {
        return $this->cartManager->getCartTotal();
    }
}

?>
