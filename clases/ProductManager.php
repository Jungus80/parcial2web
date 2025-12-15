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

    // Obtener todos los productos activos y de categorías activas (opcionalmente incluir inactivos)
    public function getAllProducts(bool $incluirInactivos = false) {
        $query = "SELECT p.pro_id, p.pro_nombre, p.pro_descripcion, p.pro_precio_unitario, p.pro_precio_compra, p.pro_precio_oferta,
                         p.pro_cantidad_stock, p.pro_disponible, p.pro_fecha_entrada, p.pro_imagen_url,
                         c.cat_nombre, s.prv_nombre
                  FROM Producto p
                  INNER JOIN Categoria c ON p.pro_categoria = c.cat_id
                  LEFT JOIN Proveedor s ON p.pro_proveedor = s.prv_id
                  WHERE c.cat_activa = TRUE";
        if (!$incluirInactivos) {
            $query .= " AND p.pro_disponible = 1";
        }
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Restaurar un producto previamente desactivado
    public function restoreProduct(int $id) {
        $query = "UPDATE Producto SET pro_disponible = 1 WHERE pro_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$id]);
            if ($stmt->rowCount() > 0) {
                error_log("Producto reactivado (restore): ID {$id}");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al reactivar producto: " . $e->getMessage());
            return false;
        }
    }

    // Obtener un producto por ID si está activo y su categoría está activa
    public function getProductById(int $id) {
        $query = "SELECT p.pro_id, p.pro_nombre, p.pro_descripcion, p.pro_precio_unitario, p.pro_precio_compra, p.pro_precio_oferta,
                         p.pro_cantidad_stock, p.pro_disponible, p.pro_fecha_entrada, p.pro_imagen_url,
                         c.cat_nombre, s.prv_nombre
                  FROM Producto p
                  INNER JOIN Categoria c ON p.pro_categoria = c.cat_id
                  LEFT JOIN Proveedor s ON p.pro_proveedor = s.prv_id
                  WHERE p.pro_id = ? AND c.cat_activa = TRUE";
        $stmt = $this->db->query($query, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Crear un nuevo producto
    public function createProduct(string $nombre, ?string $descripcion, float $precioUnitario, ?float $precioCompra, ?float $precioOferta, int $cantidadStock, bool $disponible, ?string $fechaEntrada, ?int $proveedorId, ?int $categoriaId, ?string $imagenUrl = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');
        $imagenUrlSanitizada = Seguridad::sanitizarEntrada($imagenUrl ?? '');

        // Validaciones básicas
        if (empty($nombreSanitizado) || $precioUnitario <= 0 || $cantidadStock < 0) {
            return false; // Validaciones de negocio
        }

        // Validar formato de URL si se proporciona
        if (!empty($imagenUrlSanitizada) && !filter_var($imagenUrlSanitizada, FILTER_VALIDATE_URL)) {
            throw new Exception("La URL de la imagen no es válida.");
        }

        // Solo validar que sea una URL válida
        if (!empty($imagenUrlSanitizada) && !filter_var($imagenUrlSanitizada, FILTER_VALIDATE_URL)) {
            throw new Exception("La URL de la imagen no es válida.");
        }

        $query = "INSERT INTO Producto (pro_nombre, pro_descripcion, pro_precio_unitario, pro_precio_compra, pro_precio_oferta, pro_cantidad_stock, pro_disponible, pro_fecha_entrada, pro_proveedor, pro_categoria, pro_imagen_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $nombreSanitizado,
            $descripcionSanitizada,
            $precioUnitario,
            $precioCompra,
            $precioOferta ?? null,
            $cantidadStock,
            $disponible,
            $fechaEntrada,
            $proveedorId,
            $categoriaId,
            $imagenUrlSanitizada
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
    public function updateProduct(int $id, string $nombre, ?string $descripcion, float $precioUnitario, ?float $precioCompra, ?float $precioOferta, int $cantidadStock, bool $disponible, ?string $fechaEntrada, ?int $proveedorId, ?int $categoriaId) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        
        
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');

        // Validar cantidad recibida y asegurar consistencia
        if (!isset($cantidadStock) || !is_numeric($cantidadStock)) {
            $productoExistente = $this->getProductById($id);
            $cantidadStock = (int)($productoExistente['pro_cantidad_stock'] ?? 0);
        } else {
            $cantidadStock = (int)$cantidadStock;
        }

        if ($cantidadStock < 0) {
            $cantidadStock = 0;
        }

        // Validaciones básicas
        if (empty($nombreSanitizado) || $precioUnitario <= 0) {
            return false; // Validaciones de negocio
        }

        // Mantener proveedor y categoría si no se especifican
        if (empty($proveedorId) || empty($categoriaId)) {
            $productoExistente = $this->getProductById($id);
            if (empty($proveedorId)) {
                $proveedorId = $productoExistente['pro_proveedor'] ?? null;
            }
            if (empty($categoriaId)) {
                $categoriaId = $productoExistente['pro_categoria'] ?? null;
            }
        }

        $query = "UPDATE Producto SET pro_nombre = ?, pro_descripcion = ?, pro_precio_unitario = ?, pro_precio_compra = ?, pro_precio_oferta = ?, pro_cantidad_stock = ?, pro_disponible = ?, pro_fecha_entrada = ?, pro_proveedor = ?, pro_categoria = ? WHERE pro_id = ?";
        $params = [
            $nombreSanitizado,
            $descripcionSanitizada,
            $precioUnitario,
            $precioCompra,
            $precioOferta ?? null,
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

    // Marcar un producto como inactivo (eliminación lógica)
    public function deleteProduct(int $id) {
        $query = "UPDATE Producto SET pro_disponible = 0 WHERE pro_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$id]);
            if ($stmt->rowCount() > 0) {
                error_log("Producto marcado como inactivo (eliminación lógica): ID {$id}");
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Error al marcar producto como inactivo: " . $e->getMessage());
            return false;
        }
    }
}

?>
