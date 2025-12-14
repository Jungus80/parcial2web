<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/CategoryManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$categoryManager = new CategoryManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$editCategory = null;

// --- Manejo de acciones RUD (Leer, Actualizar, Borrar) ---

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'edit':
            if (isset($_GET['id'])) {
                $editCategory = $categoryManager->getCategoryById((int)$_GET['id']);
                if (!$editCategory) {
                    $error = 'Categoría no encontrada.';
                }
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                if ($categoryManager->deleteCategory((int)$_GET['id'])) {
                    $message = 'Categoría eliminada exitosamente.';
                } else {
                    $error = 'Error al eliminar categoría (puede tener productos asociados).';
                }
            }
            break;
    }
}

// --- Manejo de acciones CUD (Crear, Actualizar) por POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $categoryId = (int)($_POST['cat_id'] ?? 0);
    $nombre = $_POST['cat_nombre'] ?? '';
    $descripcion = $_POST['cat_descripcion'] ?? '';

    // Sanitizar entradas generales
    $nombre = Seguridad::sanitizarEntrada($nombre);
    $descripcion = Seguridad::sanitizarEntrada($descripcion);

    if ($action === 'create') {
        if (empty($nombre)) {
            $error = 'El nombre de la categoría no puede estar vacío.';
        } elseif ($categoryManager->createCategory($nombre, $descripcion)) {
            $message = 'Categoría creada exitosamente.';
        } else {
            $error = 'Error al crear categoría o el nombre ya existe.';
        }
    } elseif ($action === 'update') {
        if ($categoryId === 0) {
            $error = 'ID de categoría inválido para actualización.';
        } elseif (empty($nombre)) {
            $error = 'El nombre de la categoría no puede estar vacío.';
        } elseif ($categoryManager->updateCategory($categoryId, $nombre, $descripcion)) {
            $message = 'Categoría actualizada exitosamente.';
        } else {
            $error = 'Error al actualizar categoría o el nombre ya existe.';
        }
    }
}

$categories = $categoryManager->getAllCategories();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Categorías</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    <div class="admin-container">
        <h2 data-translate-key="category_management_title"><?= Translator::get('category_management_title') ?? 'Administración de Categorías' ?></h2>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <h3 data-translate-key="<?= $editCategory ? 'edit_category_title' : 'create_new_category_title' ?>"><?= $editCategory ? (Translator::get('edit_category_title') ?? 'Editar Categoría') : (Translator::get('create_new_category_title') ?? 'Crear Nueva Categoría') ?></h3>
        <form action="categories.php" method="POST">
            <input type="hidden" name="action" value="<?= $editCategory ? 'update' : 'create' ?>">
            <input type="hidden" name="cat_id" value="<?= $editCategory['cat_id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="cat_nombre" data-translate-key="category_name_label"><?= Translator::get('category_name_label') ?? 'Nombre de Categoría' ?>:</label>
                <input type="text" id="cat_nombre" name="cat_nombre" value="<?= $editCategory['cat_nombre'] ?? '' ?>" required minlength="2" maxlength="80" class="form-control">
            </div>
            <div class="form-group">
                <label for="cat_descripcion" data-translate-key="description_label"><?= Translator::get('description_label') ?? 'Descripción' ?>:</label>
                <textarea id="cat_descripcion" name="cat_descripcion" maxlength="255" class="form-control"><?= $editCategory['cat_descripcion'] ?? '' ?></textarea>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" data-translate-key="<?= $editCategory ? 'update_category_button' : 'create_category_button' ?>"><?= $editCategory ? (Translator::get('update_category_button') ?? 'Actualizar Categoría') : (Translator::get('create_category_button') ?? 'Crear Categoría') ?></button>
                <?php if ($editCategory): ?><a href="categories.php" class="btn btn-secondary" data-translate-key="cancel_edit_button"><?= Translator::get('cancel_edit_button') ?? 'Cancelar Edición' ?></a><?php endif; ?>
            </div>
        </form>

        <h3 data-translate-key="category_list_title"><?= Translator::get('category_list_title') ?? 'Lista de Categorías' ?></h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th data-translate-key="category_name_header"><?= Translator::get('category_name_header') ?? 'Nombre' ?></th>
                    <th data-translate-key="description_header"><?= Translator::get('description_header') ?? 'Descripción' ?></th>
                    <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= $category['cat_id'] ?></td>
                    <td><?= $category['cat_nombre'] ?></td>
                    <td><?= $category['cat_descripcion'] ?></td>
                    <td class="actions">
                        <a href="categories.php?action=edit&id=<?= $category['cat_id'] ?>" class="btn btn-sm btn-info" data-translate-key="edit_button"><?= Translator::get('edit_button') ?? 'Editar' ?></a> 
                        <a href="categories.php?action=delete&id=<?= $category['cat_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= Translator::get('confirm_delete_category') ?? '¿Estás seguro de eliminar esta categoría?' ?>');" data-translate-key="delete_button"><?= Translator::get('delete_button') ?? 'Eliminar' ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
