<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/ProductManager.php';

class CartManager {
    private $db;
    private $productManager;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
        $this->productManager = new ProductManager();

        // Si no hay un carrito en sesión, intentar recuperarlo de una cookie para invitados o crear uno nuevo
        if (!isset($_SESSION['cart_id'])) {
            $guestCartId = $_COOKIE['guest_cart_id'] ?? null;
            
            if ($guestCartId) {
                // Verificar si el carrito de invitado aún existe en la DB y no está CONVERTIDO/EXPIRADO
                $cartQuery = "SELECT car_id, car_estado FROM Carrito WHERE car_id = ? AND car_estado != 'CONVERTIDO'";
                $stmt = $this->db->query($cartQuery, [$guestCartId]);
                $cartData = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($cartData) {
                    $_SESSION['cart_id'] = $cartData['car_id'];
                } else {
                    // Carrito de invitado no válido, crear uno nuevo y limpiar cookie
                    setcookie('guest_cart_id', '', time() - 3600, '/');
                    $this->createCartForSession();
                }
            } else {
                $this->createCartForSession();
            }
        }
    }

    public function createCartForSession() {
        $userId = $_SESSION['user_id'] ?? 0; // 0 para invitado, o ID de usuario logueado
        
        // Crear carrito con estado ACTIVO y car_fecha_expiracion inicial
        $query = "INSERT INTO Carrito (car_usuario, car_fecha_creacion, car_estado, car_fecha_expiracion) VALUES (?, NOW(), 'ACTIVO', DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
        try {
            $stmt = $this->db->insertSeguro($query, [$userId]);
            if ($stmt->rowCount() > 0) {
                $newCartId = $this->db->conn->lastInsertId();
                $_SESSION['cart_id'] = $newCartId;
                // Si es un carrito para invitado (user_id = 0), guardar el ID en una cookie
                if ($userId === 0) {
                    setcookie('guest_cart_id', $newCartId, time() + (86400 * 30), '/'); // Cookie por 30 días
                }
            } else {
                error_log("Error al crear carrito para la sesión.");
            }
        } catch (PDOException $e) {
            error_log("Error de DB al crear carrito: " . $e->getMessage());
        }
    }

    /**
     * Actualiza la car_fecha_expiracion del carrito a 10 minutos desde ahora.
     * @param int $cartId ID del carrito.
     * @return bool True si la actualización fue exitosa, false en caso contrario.
     */
    private function updateCartExpiration(int $cartId): bool {
        $query = "UPDATE Carrito SET car_fecha_expiracion = DATE_ADD(NOW(), INTERVAL 10 MINUTE), car_estado = 'ACTIVO' WHERE car_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$cartId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar la expiración del carrito: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el estado actual del carrito y lo actualiza a 'EXPIRADO' si ha pasado su car_fecha_expiracion.
     * @param int $cartId ID del carrito.
     * @return array|null Los datos del carrito (car_estado, car_fecha_expiracion) o null si no se encuentra.
     */
    public function getCartStatus(int $cartId): ?array {
        // Primero, intentar actualizar el estado del carrito a 'EXPIRADO' si es necesario.
        // Usamos la lógica de DB para evitar problemas de timezone/sincronización de reloj.
        $updateQuery = "UPDATE Carrito SET car_estado = 'EXPIRADO' WHERE car_id = ? AND car_estado = 'ACTIVO' AND car_fecha_expiracion < NOW()";
        $this->db->updateSeguro($updateQuery, [$cartId]);

        // Luego, recuperamos el estado actualizado del carrito, incluyendo el timestamp Unix y la hora actual del DB.
        $query = "SELECT car_estado, car_fecha_expiracion, UNIX_TIMESTAMP(car_fecha_expiracion) as car_fecha_expiracion_ts, UNIX_TIMESTAMP(NOW()) as db_current_time_ts FROM Carrito WHERE car_id = ?";
        $stmt = $this->db->query($query, [$cartId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Reactiva un carrito expirado, restaurando su estado a ACTIVO y extendiendo su expiración.
     * @param int $cartId ID del carrito.
     * @return bool True si la reactivación fue exitosa, false en caso contrario.
     */
    public function reactivateCart(int $cartId): bool {
        // La actualización de la expiración ya establece el estado a ACTIVO
        return $this->updateCartExpiration($cartId);
    }

    public function getCartItems(): array {
        if (!isset($_SESSION['cart_id'])) {
            return [];
        }
        // Asegurarse de que solo se muestren productos disponibles y de categorías activas
        $query = "SELECT dc.*, p.pro_nombre, p.pro_precio_unitario, p.pro_imagen_url 
                  FROM Detalle_carrito dc
                  JOIN Producto p ON dc.dca_producto = p.pro_id
                  JOIN Categoria c ON p.pro_categoria = c.cat_id
                  WHERE dc.dca_carrito = ? AND p.pro_disponible = TRUE AND c.cat_activa = TRUE";
        $stmt = $this->db->query($query, [$_SESSION['cart_id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addToCart(int $productId, int $quantity): bool {
        if (!isset($_SESSION['cart_id'])) {
            $this->createCartForSession();
            if (!isset($_SESSION['cart_id'])) return false; // Falló la creación del carrito
        }

        $product = $this->productManager->getProductById($productId);
        if (!$product || $product['pro_cantidad_stock'] < $quantity) {
            error_log("Producto no encontrado o stock insuficiente.");
            return false;
        }

        // Verificar si el producto ya está en el carrito
        $existingItemQuery = "SELECT dca_id, dca_cantidad FROM Detalle_carrito WHERE dca_carrito = ? AND dca_producto = ?";
        $existingItemStmt = $this->db->query($existingItemQuery, [$_SESSION['cart_id'], $productId]);
        $existingItem = $existingItemStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingItem) {
            // Actualizar cantidad
            $newQuantity = $existingItem['dca_cantidad'] + $quantity;
            if ($product['pro_cantidad_stock'] < $newQuantity) {
                error_log("Stock insuficiente para actualizar cantidad.");
                return false;
            }
            $updateQuery = "UPDATE Detalle_carrito SET dca_cantidad = ? WHERE dca_id = ?";
            $stmt = $this->db->updateSeguro($updateQuery, [$newQuantity, $existingItem['dca_id']]);
            $success = $stmt->rowCount() > 0;
        } else {
            // Insertar nuevo producto en el carrito
            $insertQuery = "INSERT INTO Detalle_carrito (dca_carrito, dca_producto, dca_cantidad, dca_fecha_agregado) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->insertSeguro($insertQuery, [$_SESSION['cart_id'], $productId, $quantity]);
            $success = $stmt->rowCount() > 0;
        }

        // Actualizar la expiración del carrito después de cualquier modificación exitosa
        if ($success && isset($_SESSION['cart_id'])) {
            $this->updateCartExpiration($_SESSION['cart_id']);
        }
        return $success;
    }

    public function updateCartItemQuantity(int $productId, int $newQuantity): bool {
        if (!isset($_SESSION['cart_id'])) return false;

        $product = $this->productManager->getProductById($productId);
        if (!$product || $product['pro_cantidad_stock'] < $newQuantity || $newQuantity <= 0) {
            error_log("Stock insuficiente o cantidad inválida.");
            return false;
        }

        $query = "UPDATE Detalle_carrito SET dca_cantidad = ? WHERE dca_carrito = ? AND dca_producto = ?";
        $stmt = $this->db->updateSeguro($query, [$newQuantity, $_SESSION['cart_id'], $productId]);
        $success = $stmt->rowCount() > 0;
        if ($success && isset($_SESSION['cart_id'])) {
            $this->updateCartExpiration($_SESSION['cart_id']);
        }
        return $success;
    }

    public function removeFromCart(int $productId): bool {
        if (!isset($_SESSION['cart_id'])) return false;

        $query = "DELETE FROM Detalle_carrito WHERE dca_carrito = ? AND dca_producto = ?";
        $stmt = $this->db->insertSeguro($query, [$_SESSION['cart_id'], $productId]); // insertSeguro can handle DELETE
        $success = $stmt->rowCount() > 0;
        if ($success && isset($_SESSION['cart_id'])) {
            // Si se elimina el último item, el carrito podría quedar vacío, no hay necesidad de extender expiración
            $cartItems = $this->getCartItems();
            if (!empty($cartItems)) {
                $this->updateCartExpiration($_SESSION['cart_id']);
            }
        }
        return $success;
    }

    public function getCartTotal(): float {
        $items = $this->getCartItems();
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['dca_cantidad'] * $item['pro_precio_unitario'];
        }
        return $total;
    }

    public function clearCart(): bool {
        if (!isset($_SESSION['cart_id'])) return true; // Carrito ya vacío
        $query = "DELETE FROM Detalle_carrito WHERE dca_carrito = ?";
        $stmt = $this->db->insertSeguro($query, [$_SESSION['cart_id']]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene el carrito activo de un usuario por su ID.
     * @param int $userId ID del usuario.
     * @return array|null El carrito o null si no se encuentra.
     */
    public function getUserActiveCart(int $userId): ?array {
        $query = "SELECT car_id, car_estado, car_fecha_expiracion, UNIX_TIMESTAMP(car_fecha_expiracion) as car_fecha_expiracion_ts, UNIX_TIMESTAMP(NOW()) as db_current_time_ts FROM Carrito WHERE car_usuario = ? AND car_estado = 'ACTIVO'";
        $stmt = $this->db->query($query, [$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Fusiona un carrito de invitado con el carrito de un usuario logueado.
     * @param int $guestCartId ID del carrito del invitado (de la cookie).
     * @param int $userId ID del usuario logueado.
     * @return bool True si la fusión fue exitosa, false en caso contrario.
     */
    public function mergeCarts(int $guestCartId, int $userId): bool {
        try {
            $this->db->conn->beginTransaction();

            $userActiveCart = $this->getUserActiveCart($userId);
            $guestCartData = $this->getCartStatus($guestCartId); // Obtener el estado del carrito de invitado
            
            // Asegurarse de que el carrito de invitado no esté ya convertido o no sea válido
            if (!$guestCartData || $guestCartData['car_estado'] === 'CONVERTIDO') {
                // Si el carrito de invitado no es válido, solo usamos el del usuario o creamos uno nuevo.
                // Podríamos limpiar la cookie aquí.
                setcookie('guest_cart_id', '', time() - 3600, '/');
                if ($userActiveCart) {
                    $_SESSION['cart_id'] = $userActiveCart['car_id'];
                    return true;
                } else {
                    // Si no hay un carrito de usuario y el guest cart no es válido, crear uno nuevo para el usuario.
                    // Esto es un caso más complejo, por ahora simplemente retornamos false
                    // y se crearía uno nuevo en el constructor si fuera necesario.
                    return false;
                }
            }
            // En este punto, guestCartData es válido y no está CONVERTIDO.

            $guestCartItems = $this->getCartItemsById($guestCartId); 

            if (!$userActiveCart) {
                // Si el usuario no tiene carrito activo, asignamos el de invitado al usuario
                $updateQuery = "UPDATE Carrito SET car_usuario = ?, car_estado = 'ACTIVO' WHERE car_id = ?";
                $this->db->updateSeguro($updateQuery, [$userId, $guestCartId]);
                $_SESSION['cart_id'] = $guestCartId;
            } else {
                // Si el usuario ya tiene un carrito, fusionamos los ítems
                $userCartId = $userActiveCart['car_id'];

                foreach ($guestCartItems as $guestItem) {
                    $productId = $guestItem['dca_producto'];
                    $quantity = $guestItem['dca_cantidad'];

                    // Verificar si el producto ya está en el carrito del usuario
                    $existingUserItemQuery = "SELECT dca_id, dca_cantidad FROM Detalle_carrito WHERE dca_carrito = ? AND dca_producto = ?";
                    $existingUserItemStmt = $this->db->query($existingUserItemQuery, [$userCartId, $productId]);
                    $existingUserItem = $existingUserItemStmt->fetch(PDO::FETCH_ASSOC);

                    // Verificar disponibilidad del producto antes de añadir/actualizar
                    $product = $this->productManager->getProductById($productId); // Already filters by pro_disponible and cat_activa
                    if (!$product || $product['pro_cantidad_stock'] <= 0) {
                        // Producto no disponible, lo ignoramos o notificamos.
                        continue; 
                    }

                    if ($existingUserItem) {
                        // Sumar cantidades si ya existe
                        $newQuantity = $existingUserItem['dca_cantidad'] + $quantity;
                        // Asegurarse de no exceder el stock disponible
                        $finalQuantity = min($newQuantity, $product['pro_cantidad_stock']);

                        $updateItemQuery = "UPDATE Detalle_carrito SET dca_cantidad = ? WHERE dca_id = ?";
                        $this->db->updateSeguro($updateItemQuery, [$finalQuantity, $existingUserItem['dca_id']]);
                    } else {
                        // Añadir como nuevo ítem si hay stock
                        $finalQuantity = min($quantity, $product['pro_cantidad_stock']);
                        if ($finalQuantity > 0) {
                            $insertItemQuery = "INSERT INTO Detalle_carrito (dca_carrito, dca_producto, dca_cantidad, dca_fecha_agregado) VALUES (?, ?, ?, NOW())";
                            $this->db->insertSeguro($insertItemQuery, [$userCartId, $productId, $finalQuantity]);
                        }
                    }
                }

                // Marcar el carrito de invitado como CONVERTIDO después de la fusión
                $updateGuestCartQuery = "UPDATE Carrito SET car_estado = 'CONVERTIDO' WHERE car_id = ?";
                $this->db->updateSeguro($updateGuestCartQuery, [$guestCartId]);
                
                // Si el carrito de invitado se convierte en el carrito del usuario, también actualizamos su fecha de expiración
                $this->updateCartExpiration($userCartId);

                $_SESSION['cart_id'] = $userCartId;
            }
            
            // Eliminar la cookie del carrito de invitado
            setcookie('guest_cart_id', '', time() - 3600, '/'); 
            $this->db->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->conn->rollBack();
            error_log("Error al fusionar carritos: " . $e->getMessage());
            return false;
        }
    }
    
    // Necesitamos esta función para obtener ítems de cualquier carrito, no solo el de la sesión
    public function getCartItemsById(int $cartId): array {
        $query = "SELECT dc.*, p.pro_nombre, p.pro_precio_unitario, p.pro_imagen_url 
                  FROM Detalle_carrito dc
                  JOIN Producto p ON dc.dca_producto = p.pro_id
                  JOIN Categoria c ON p.pro_categoria = c.cat_id
                  WHERE dc.dca_carrito = ? AND p.pro_disponible = TRUE AND c.cat_activa = TRUE";
        $stmt = $this->db->query($query, [$cartId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Asigna un carrito de invitado existente a un usuario recién logueado.
     * @param int $guestCartId ID del carrito del invitado.
     * @param int $userId ID del usuario logueado.
     * @return bool True si la asignación fue exitosa, false en caso contrario.
     */
    public function assignGuestCartToUser(int $guestCartId, int $userId): bool {
        try {
            $this->db->conn->beginTransaction();
            $query = "UPDATE Carrito SET car_usuario = ? WHERE car_id = ?";
            $this->db->updateSeguro($query, [$userId, $guestCartId]);
            
            // Asegurarse de que el carrito asignado esté activo y con expiración actualizada
            $this->updateCartExpiration($guestCartId);
            $_SESSION['cart_id'] = $guestCartId;
            
            setcookie('guest_cart_id', '', time() - 3600, '/'); // Limpiar cookie de invitado
            $this->db->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->conn->rollBack();
            error_log("Error al asignar carrito de invitado a usuario: " . $e->getMessage());
            return false;
        }
    }

} // The proper closing brace for the class

?>

