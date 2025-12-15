<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/ProductManager.php';
require_once '../clases/CategoryManager.php';
require_once '../clases/SupplierManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$productManager = new ProductManager();
$categoryManager = new CategoryManager();
$supplierManager = new SupplierManager();

$categories = $categoryManager->getAllCategories();
$suppliers = $supplierManager->getAllSuppliers();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$editProduct = null;

// --- Manejo de acciones RUD (Leer, Actualizar, Borrar) ---

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'edit':
            if (isset($_GET['id'])) {
                $editProduct = $productManager->getProductById((int)$_GET['id']);
                if (!$editProduct) {
                    $error = 'Producto no encontrado.';
                }
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                if ($productManager->deleteProduct((int)$_GET['id'])) {
                    $message = 'Producto eliminado exitosamente.';
                } else {
                    $error = 'Error al eliminar producto.';
                }
            }
            break;
    }
}

// --- Manejo de acciones CUD (Crear, Actualizar) por POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = (int)($_POST['pro_id'] ?? 0);
    $nombre = $_POST['pro_nombre'] ?? '';
    $descripcion = $_POST['pro_descripcion'] ?? '';
    $precioUnitario = (float)($_POST['pro_precio_unitario'] ?? 0.0);
    $precioCompra = isset($_POST['pro_precio_compra']) && $_POST['pro_precio_compra'] !== '' ? (float)$_POST['pro_precio_compra'] : null;
    $cantidadStock = (int)($_POST['pro_cantidad_stock'] ?? 0);
    $disponible = isset($_POST['pro_disponible']) ? 1 : 0;
    $fechaEntrada = $_POST['pro_fecha_entrada'] ?? null;
    $proveedorId = (int)($_POST['pro_proveedor'] ?? 0);
    $categoriaId = (int)($_POST['pro_categoria'] ?? 0);
    $imagenUrl = $_POST['pro_imagen_url'] ?? null;

    $proveedorId = ($proveedorId === 0) ? null : $proveedorId;
    $categoriaId = ($categoriaId === 0) ? null : $categoriaId;

    if ($action === 'create') {
        if (empty($nombre) || $precioUnitario <= 0 || $cantidadStock < 0) {
            $error = 'Datos de producto incompletos o inválidos.';
        } elseif ($productManager->createProduct($nombre, $descripcion, $precioUnitario, $precioCompra, $cantidadStock, $disponible, $fechaEntrada, $proveedorId, $categoriaId, $imagenUrl)) {
            $message = 'Producto creado exitosamente.';
        } else {
            $error = 'Error al crear producto.';
        }
    } elseif ($action === 'update') {
        if ($productId === 0 || empty($nombre) || $precioUnitario <= 0 || $cantidadStock < 0) {
            $error = 'Datos de producto incompletos o inválidos para actualización.';
        } elseif ($productManager->updateProduct($productId, $nombre, $descripcion, $precioUnitario, $precioCompra, $cantidadStock, $disponible, $fechaEntrada, $proveedorId, $categoriaId)) {
            $message = 'Producto actualizado exitosamente.';
        } else {
            $error = 'Error al actualizar producto.';
        }
    }
}

$products = $productManager->getAllProducts();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Productos</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="admin-container">
        <h2 data-translate-key="product_management_title"><?= Translator::get('product_management_title') ?? 'Administración de Productos' ?></h2>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <h3 data-translate-key="<?= $editProduct ? 'edit_product_title' : 'create_new_product_title' ?>"><?= $editProduct ? (Translator::get('edit_product_title') ?? 'Editar Producto') : (Translator::get('create_new_product_title') ?? 'Crear Nuevo Producto') ?></h3>
        <form action="products.php" method="POST">
            <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
            <input type="hidden" name="pro_id" value="<?= $editProduct['pro_id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="pro_nombre" data-translate-key="product_name_label"><?= Translator::get('product_name_label') ?? 'Nombre del Producto' ?>:</label>
                <input type="text" id="pro_nombre" name="pro_nombre" value="<?= $editProduct['pro_nombre'] ?? 'Nuevo Producto' ?>" required minlength="3" maxlength="120" class="form-control" placeholder="Ingrese el nombre del producto">
            </div>
            <div class="form-group">
                <label for="pro_descripcion" data-translate-key="description_label"><?= Translator::get('description_label') ?? 'Descripción' ?>:</label>
                <textarea id="pro_descripcion" name="pro_descripcion" maxlength="1000" class="form-control"><?= $editProduct['pro_descripcion'] ?? '' ?></textarea>
            </div>
            <div class="form-group">
                <label for="pro_precio_unitario" data-translate-key="unit_price_label"><?= Translator::get('unit_price_label') ?? 'Precio Unitario' ?>:</label>
                <input type="number" step="0.01" min="0.01" id="pro_precio_unitario" name="pro_precio_unitario" value="<?= $editProduct['pro_precio_unitario'] ?? '' ?>" required class="form-control">
            </div>
            <div class="form-group">
                <label for="pro_precio_compra" data-translate-key="purchase_price_label"><?= Translator::get('purchase_price_label') ?? 'Precio de Compra' ?>:</label>
                <input type="number" step="0.01" min="0" id="pro_precio_compra" name="pro_precio_compra" value="<?= $editProduct['pro_precio_compra'] ?? '' ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="pro_cantidad_stock" data-translate-key="quantity_in_stock_label"><?= Translator::get('quantity_in_stock_label') ?? 'Cantidad en Stock' ?>:</label>
                <input type="number" min="0" id="pro_cantidad_stock" name="pro_cantidad_stock" value="<?= $editProduct['pro_cantidad_stock'] ?? '' ?>" required class="form-control">
            </div>
            <div class="form-group">
                <label for="pro_imagen_url" data-translate-key="product_image_url_label"><?= Translator::get('product_image_url_label') ?? 'URL de Imagen del Producto' ?>:</label>
                <input type="url" id="pro_imagen_url" name="pro_imagen_url" value="<?= $editProduct['pro_imagen_url'] ?? '' ?>" placeholder="https://example.com/imagen.jpg" class="form-control">
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="pro_disponible" name="pro_disponible" <?= (($editProduct['pro_disponible'] ?? true) ? 'checked' : '') ?>>
                <label class="form-check-label" for="pro_disponible" data-translate-key="available_label"><?= Translator::get('available_label') ?? 'Disponible' ?></label>
            </div>
            <div class="form-group">
                <label for="pro_fecha_entrada" data-translate-key="entry_date_label"><?= Translator::get('entry_date_label') ?? 'Fecha de Entrada' ?>:</label>
                <input type="date" id="pro_fecha_entrada" name="pro_fecha_entrada" value="<?= (isset($editProduct['pro_fecha_entrada']) && $editProduct['pro_fecha_entrada']) ? date('Y-m-d', strtotime($editProduct['pro_fecha_entrada'])) : '' ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="pro_proveedor" data-translate-key="supplier_label"><?= Translator::get('supplier_label') ?? 'Proveedor' ?>:</label>
                <select id="pro_proveedor" name="pro_proveedor" class="form-control">
                    <option value="0" data-translate-key="select_supplier_option"><?= Translator::get('select_supplier_option') ?? '-- Seleccione Proveedor --' ?></option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= $supplier['prv_id'] ?>" <?= ((int)($editProduct['pro_proveedor'] ?? 0) === $supplier['prv_id']) ? 'selected' : '' ?>>
                            <?= $supplier['prv_nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="pro_categoria" data-translate-key="category_label"><?= Translator::get('category_label') ?? 'Categoría' ?>:</label>
                <select id="pro_categoria" name="pro_categoria" class="form-control">
                    <option value="0" data-translate-key="select_category_option"><?= Translator::get('select_category_option') ?? '-- Seleccione Categoría --' ?></option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['cat_id'] ?>" <?= ((int)($editProduct['pro_categoria'] ?? 0) === $category['cat_id']) ? 'selected' : '' ?>>
                            <?= $category['cat_nombre'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" data-translate-key="<?= $editProduct ? 'update_product_button' : 'create_product_button' ?>"><?= $editProduct ? (Translator::get('update_product_button') ?? 'Actualizar Producto') : (Translator::get('create_product_button') ?? 'Crear Producto') ?></button>
                <?php if ($editProduct): ?><a href="products.php" class="btn btn-secondary" data-translate-key="cancel_edit_button"><?= Translator::get('cancel_edit_button') ?? 'Cancelar Edición' ?></a><?php endif; ?>
            </div>
        </form>

        <h3 data-translate-key="product_list_title"><?= Translator::get('product_list_title') ?? 'Lista de Productos' ?></h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th data-translate-key="product_name_header"><?= Translator::get('product_name_header') ?? 'Nombre' ?></th>
                    <th data-translate-key="description_header"><?= Translator::get('description_header') ?? 'Descripción' ?></th>
                    <th data-translate-key="unit_price_header"><?= Translator::get('unit_price_header') ?? 'Precio Unitario' ?></th>
                    <th data-translate-key="stock_header"><?= Translator::get('stock_header') ?? 'Stock' ?></th>
                    <th data-translate-key="available_header"><?= Translator::get('available_header') ?? 'Disponible' ?></th>
                    <th data-translate-key="supplier_header"><?= Translator::get('supplier_header') ?? 'Proveedor' ?></th>
                    <th data-translate-key="category_header"><?= Translator::get('category_header') ?? 'Categoría' ?></th>
                    <th data-translate-key="image_header"><?= Translator::get('image_header') ?? 'Imagen' ?></th>
                    <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['pro_id'] ?></td>
                    <td><?= $product['pro_nombre'] ?></td>
                    <td><?= substr($product['pro_descripcion'], 0, 50) ?>...</td>
                    <td><?= $product['pro_precio_unitario'] ?></td>
                    <td><?= $product['pro_cantidad_stock'] ?></td>
                    <td><?= $product['pro_disponible'] ? (Translator::get('yes') ?? 'Sí') : (Translator::get('no') ?? 'No') ?></td>
                    <td><?= $product['prv_nombre'] ?></td>
                    <td><?= $product['cat_nombre'] ?></td>
                    <td>
                        <?php if (!empty($product['pro_imagen_url'])): ?>
                            <img src="<?= htmlspecialchars($product['pro_imagen_url']) ?>" alt="<?= htmlspecialchars($product['pro_nombre']) ?>" width="60" height="60" style="object-fit: cover;">
                        <?php else: ?>
                            <span>No Imagen</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="products.php?action=edit&id=<?= $product['pro_id'] ?>" class="btn btn-sm btn-info" data-translate-key="edit_button"><?= Translator::get('edit_button') ?? 'Editar' ?></a> 
                        <a href="products.php?action=delete&id=<?= $product['pro_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= Translator::get('confirm_delete_product') ?? '¿Estás seguro de eliminar este producto?' ?>');" data-translate-key="delete_button"><?= Translator::get('delete_button') ?? 'Eliminar' ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
