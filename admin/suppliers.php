<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/SupplierManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$supplierManager = new SupplierManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$editSupplier = null;

// --- Manejo de acciones RUD (Leer, Actualizar, Borrar) ---

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'edit':
            if (isset($_GET['id'])) {
                $editSupplier = $supplierManager->getSupplierById((int)$_GET['id']);
                if (!$editSupplier) {
                    $error = 'Proveedor no encontrado.';
                }
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                if ($supplierManager->deleteSupplier((int)$_GET['id'])) {
                    $message = 'Proveedor eliminado exitosamente.';
                } else {
                    $error = 'Error al eliminar proveedor (puede tener productos asociados).';
                }
            }
            break;
    }
}

// --- Manejo de acciones CUD (Crear, Actualizar) por POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $supplierId = (int)($_POST['prv_id'] ?? 0);
    $nombre = $_POST['prv_nombre'] ?? '';
    $telefono = $_POST['prv_telefono'] ?? '';
    $celular = $_POST['prv_celular'] ?? '';
    $direccion = $_POST['prv_direccion'] ?? '';
    $urlWeb = $_POST['prv_url_web'] ?? '';
    $calificacion = isset($_POST['prv_calificacion_estrellas']) ? (float)$_POST['prv_calificacion_estrellas'] : null;

    if ($action === 'create') {
        if (empty($nombre)) {
            $error = 'El nombre del proveedor no puede estar vacío.';
        } elseif ($supplierManager->createSupplier($nombre, $telefono, $celular, $direccion, $urlWeb, $calificacion)) {
            $message = 'Proveedor creado exitosamente.';
        } else {
            $error = 'Error al crear proveedor.';
        }
    } elseif ($action === 'update') {
        if ($supplierId === 0) {
            $error = 'ID de proveedor inválido para actualización.';
        } elseif (empty($nombre)) {
            $error = 'El nombre del proveedor no puede estar vacío.';
        } elseif ($supplierManager->updateSupplier($supplierId, $nombre, $telefono, $celular, $direccion, $urlWeb, $calificacion)) {
            $message = 'Proveedor actualizado exitosamente.';
        } else {
            $error = 'Error al actualizar proveedor.';
        }
    }
}

$suppliers = $supplierManager->getAllSuppliers();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Proveedores</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="admin-container">
        <h2 data-translate-key="supplier_management_title"><?= Translator::get('supplier_management_title') ?? 'Administración de Proveedores' ?></h2>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <h3 data-translate-key="<?= $editSupplier ? 'edit_supplier_title' : 'create_new_supplier_title' ?>"><?= $editSupplier ? (Translator::get('edit_supplier_title') ?? 'Editar Proveedor') : (Translator::get('create_new_supplier_title') ?? 'Crear Nuevo Proveedor') ?></h3>
        <form action="suppliers.php" method="POST">
            <input type="hidden" name="action" value="<?= $editSupplier ? 'update' : 'create' ?>">
            <input type="hidden" name="prv_id" value="<?= $editSupplier['prv_id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="prv_nombre" data-translate-key="supplier_name_label"><?= Translator::get('supplier_name_label') ?? 'Nombre del Proveedor' ?>:</label>
                <input type="text" id="prv_nombre" name="prv_nombre" value="<?= $editSupplier['prv_nombre'] ?? '' ?>" required minlength="3" maxlength="120" class="form-control">
            </div>
            <div class="form-group">
                <label for="prv_telefono" data-translate-key="phone_label"><?= Translator::get('phone_label') ?? 'Teléfono' ?>:</label>
                <input type="tel" id="prv_telefono" name="prv_telefono" value="<?= $editSupplier['prv_telefono'] ?? '' ?>" maxlength="30" class="form-control">
            </div>
            <div class="form-group">
                <label for="prv_celular" data-translate-key="mobile_label"><?= Translator::get('mobile_label') ?? 'Celular' ?>:</label>
                <input type="tel" id="prv_celular" name="prv_celular" value="<?= $editSupplier['prv_celular'] ?? '' ?>" maxlength="30" class="form-control">
            </div>
            <div class="form-group">
                <label for="prv_direccion" data-translate-key="address_label"><?= Translator::get('address_label') ?? 'Dirección' ?>:</label>
                <input type="text" id="prv_direccion" name="prv_direccion" value="<?= $editSupplier['prv_direccion'] ?? '' ?>" maxlength="255" class="form-control">
            </div>
            <div class="form-group">
                <label for="prv_url_web" data-translate-key="web_url_label"><?= Translator::get('web_url_label') ?? 'URL Web' ?>:</label>
                <input type="url" id="prv_url_web" name="prv_url_web" value="<?= $editSupplier['prv_url_web'] ?? '' ?>" maxlength="255" class="form-control">
            </div>
            <div class="form-group">
                <label for="prv_calificacion_estrellas" data-translate-key="rating_label"><?= Translator::get('rating_label') ?? 'Calificación Estrellas (ej. 4.5)' ?>:</label>
                <input type="number" step="0.1" min="0" max="5" id="prv_calificacion_estrellas" name="prv_calificacion_estrellas" value="<?= $editSupplier['prv_calificacion_estrellas'] ?? '' ?>" class="form-control">
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" data-translate-key="<?= $editSupplier ? 'update_supplier_button' : 'create_supplier_button' ?>"><?= $editSupplier ? (Translator::get('update_supplier_button') ?? 'Actualizar Proveedor') : (Translator::get('create_supplier_button') ?? 'Crear Proveedor') ?></button>
                <?php if ($editSupplier): ?><a href="suppliers.php" class="btn btn-secondary" data-translate-key="cancel_edit_button"><?= Translator::get('cancel_edit_button') ?? 'Cancelar Edición' ?></a><?php endif; ?>
            </div>
        </form>

        <h3 data-translate-key="supplier_list_title"><?= Translator::get('supplier_list_title') ?? 'Lista de Proveedores' ?></h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th data-translate-key="supplier_name_header"><?= Translator::get('supplier_name_header') ?? 'Nombre' ?></th>
                    <th data-translate-key="phone_header"><?= Translator::get('phone_header') ?? 'Teléfono' ?></th>
                    <th data-translate-key="mobile_header"><?= Translator::get('mobile_header') ?? 'Celular' ?></th>
                    <th data-translate-key="address_header"><?= Translator::get('address_header') ?? 'Dirección' ?></th>
                    <th data-translate-key="web_url_header"><?= Translator::get('web_url_header') ?? 'URL Web' ?></th>
                    <th data-translate-key="rating_header"><?= Translator::get('rating_header') ?? 'Calificación' ?></th>
                    <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><?= $supplier['prv_id'] ?></td>
                    <td><?= $supplier['prv_nombre'] ?></td>
                    <td><?= $supplier['prv_telefono'] ?></td>
                    <td><?= $supplier['prv_celular'] ?></td>
                    <td><?= $supplier['prv_direccion'] ?></td>
                    <td><?= $supplier['prv_url_web'] ?></td>
                    <td><?= $supplier['prv_calificacion_estrellas'] ?></td>
                    <td class="actions">
                        <a href="suppliers.php?action=edit&id=<?= $supplier['prv_id'] ?>" class="btn btn-sm btn-info" data-translate-key="edit_button"><?= Translator::get('edit_button') ?? 'Editar' ?></a> 
                        <a href="suppliers.php?action=delete&id=<?= $supplier['prv_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= Translator::get('confirm_delete_supplier') ?? '¿Estás seguro de eliminar este proveedor?' ?>');" data-translate-key="delete_button"><?= Translator::get('delete_button') ?? 'Eliminar' ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
