<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/Seguridad.php';
require_once __DIR__ . '/CategoryManager.php'; // Para obtener la lista de categorías
require_once __DIR__ . '/SupplierManager.php'; // Para obtener la lista de proveedores

class ProductManager {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    // Obtener todos los productos activos y de categorías activas
    public function getAllProducts() {
        $query = "SELECT p.*, c.cat_nombre, s.prv_nombre 
                  FROM Producto p
                  INNER JOIN Categoria c ON p.pro_categoria = c.cat_id
                  LEFT JOIN Proveedor s ON p.pro_proveedor = s.prv_id
                  WHERE c.cat_activa = TRUE AND p.pro_disponible = TRUE";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un producto por ID si está activo y su categoría está activa
    public function getProductById(int $id) {
        $query = "SELECT p.*, c.cat_nombre, s.prv_nombre 
                  FROM Producto p
                  INNER JOIN Categoria c ON p.pro_categoria = c.cat_id
                  LEFT JOIN Proveedor s ON p.pro_proveedor = s.prv_id
                  WHERE p.pro_id = ? AND c.cat_activa = TRUE AND p.pro_disponible = TRUE";
        $stmt = $this->db->query($query, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear un nuevo producto
    public function createProduct(string $nombre, ?string $descripcion, float $precioUnitario, ?float $precioCompra, int $cantidadStock, bool $disponible, ?string $fechaEntrada, ?int $proveedorId, ?int $categoriaId) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');
        
        // Validaciones básicas
        if (empty($nombreSanitizado) || $precioUnitario <= 0 || $cantidadStock < 0) {
            return false; // Validaciones de negocio
        }

        $query = "INSERT INTO Producto (pro_nombre, pro_descripcion, pro_precio_unitario, pro_precio_compra, pro_cantidad_stock, pro_disponible, pro_fecha_entrada, pro_proveedor, pro_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $nombreSanitizado,
            $descripcionSanitizada,
            $precioUnitario,
            $precioCompra,
            $cantidadStock,
            $disponible,
            $fechaEntrada,
            $proveedorId,
            $categoriaId
        ];
        try {
            $stmt = $this->db->insertSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al crear producto: " . $e->getMessage());
            return false;
        }
    }

    // Actualizar un producto existente
    public function updateProduct(int $id, string $nombre, ?string $descripcion, float $precioUnitario, ?float $precioCompra, int $cantidadStock, bool $disponible, ?string $fechaEntrada, ?int $proveedorId, ?int $categoriaId) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');

        // Validaciones básicas
        if (empty($nombreSanitizado) || $precioUnitario <= 0 || $cantidadStock < 0) {
            return false; // Validaciones de negocio
        }
        
        $query = "UPDATE Producto SET pro_nombre = ?, pro_descripcion = ?, pro_precio_unitario = ?, pro_precio_compra = ?, pro_cantidad_stock = ?, pro_disponible = ?, pro_fecha_entrada = ?, pro_proveedor = ?, pro_categoria = ? WHERE pro_id = ?";
        $params = [
            $nombreSanitizado,
            $descripcionSanitizada,
            $precioUnitario,
            $precioCompra,
            $cantidadStock,
            $disponible,
            $fechaEntrada,
            $proveedorId,
            $categoriaId,
            $id
        ];
        try {
            $stmt = $this->db->updateSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar producto: " . $e->getMessage());
            return false;
        }
    }

    // Eliminar un producto
    public function deleteProduct(int $id) {
        $query = "DELETE FROM Producto WHERE pro_id = ?";
        try {
            $stmt = $this->db->insertSeguro($query, [$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al eliminar producto: " . $e->getMessage());
            return false;
        }
    }
}

?>
