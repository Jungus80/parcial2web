<?php
session_start();

require_once '../dbconexion.php';
require_once '../clases/Seguridad.php';
require_once '../clases/UserManager.php';
require_once __DIR__ . '/../clases/Translator.php';

$db = new DB();
$conn = $db->getConnection();
$userManager = new UserManager();

// Verificar autenticación y rol de administrador
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? 'cliente') !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$editUser = null;

// --- Manejo de acciones RUD (Leer, Actualizar, Borrar) ---

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'edit':
            if (isset($_GET['id'])) {
                $editUser = $userManager->getUserById((int)$_GET['id']);
                if (!$editUser) {
                    $error = 'Usuario no encontrado.';
                }
            }
            break;
        case 'delete':
            if (isset($_GET['id'])) {
                if ($userManager->deleteUser((int)$_GET['id'])) {
                    $message = 'Usuario eliminado exitosamente.';
                } else {
                    $error = 'Error al eliminar usuario.';
                }
            }
            break;
    }
}

// --- Manejo de acciones CUD (Crear, Actualizar) por POST ---

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['usu_id'] ?? 0);
    $nombre = $_POST['usu_nombre'] ?? '';
    $email = $_POST['usu_email'] ?? '';
    $rol = $_POST['usu_rol'] ?? 'cliente';
    $password = $_POST['usu_password'] ?? '';
    $activo = isset($_POST['usu_activo']) ? true : false;

    // Sanitizar entradas generales
    $nombre = Seguridad::sanitizarEntrada($nombre);
    $email = Seguridad::sanitizarEntrada($email);
    $rol = Seguridad::sanitizarEntrada($rol);

    if ($action === 'create') {
        if ($userManager->emailExists($email)) {
            $error = 'El correo electrónico ya está registrado.';
        } elseif ($userManager->createUser($nombre, $email, $password, $rol, $activo)) {
            $message = 'Usuario creado exitosamente.';
        } else {
            $error = 'Error al crear usuario o contraseña inválida.';
        }
    } elseif ($action === 'update') {
        if ($userId === 0) {
            $error = 'ID de usuario inválido para actualización.';
        } elseif ($userManager->emailExists($email, $userId)) {
            $error = 'El correo electrónico ya está registrado por otro usuario.';
        } elseif ($userManager->updateUser($userId, $nombre, $email, $rol, $activo)) {
            $message = 'Usuario actualizado exitosamente.';
            // Si se proporciona una nueva contraseña, actualizarla
            if (!empty($password)) {
                if ($userManager->updatePassword($userId, $password)) {
                    $message .= ' Contraseña actualizada.';
                } else {
                    $error .= ' Error al actualizar contraseña o contraseña inválida.';
                }
            }
        } else {
            $error = 'Error al actualizar usuario.';
        }
    }
}

$users = $userManager->getAllUsers();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de Usuarios</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <?php include 'includes/admin_header.php'; // Header del panel de administración ?>
    <div class="admin-container">
        <h2 data-translate-key="user_management_title"><?= Translator::get('user_management_title') ?? 'Administración de Usuarios' ?></h2>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <h3 data-translate-key="<?= $editUser ? 'edit_user_title' : 'create_new_user_title' ?>"><?= $editUser ? (Translator::get('edit_user_title') ?? 'Editar Usuario') : (Translator::get('create_new_user_title') ?? 'Crear Nuevo Usuario') ?></h3>
        <form action="users.php" method="POST">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <input type="hidden" name="usu_id" value="<?= $editUser['usu_id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="usu_nombre" data-translate-key="username_label"><?= Translator::get('username_label') ?? 'Nombre de Usuario' ?>:</label>
                <input type="text" id="usu_nombre" name="usu_nombre" value="<?= $editUser['usu_nombre'] ?? '' ?>" required minlength="3" maxlength="80" class="form-control">
            </div>
            <div class="form-group">
                <label for="usu_email" data-translate-key="email_label"><?= Translator::get('email_label') ?? 'Email' ?>:</label>
                <input type="email" id="usu_email" name="usu_email" value="<?= $editUser['usu_email'] ?? '' ?>" required maxlength="120" class="form-control">
            </div>
            <div class="form-group">
                <label for="usu_password" data-translate-key="password_label"><?= Translator::get('password_label') ?? 'Contraseña' ?> (<?= Translator::get('leave_blank_to_not_change') ?? 'dejar en blanco para no cambiar' ?>):</label>
                <input type="password" id="usu_password" name="usu_password" class="form-control" <?= $editUser ? '' : 'required' ?> minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?![.\s]).{8,}" title="Debe contener al menos un número y una letra mayúscula y minúscula, y al menos un carácter especial, y al menos 8 o más caracteres">
                <?php if (!$editUser): ?><small class="form-text" data-translate-key="password_requirements"><?= Translator::get('password_requirements') ?? 'Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos especiales.' ?></small><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="usu_rol" data-translate-key="role_label"><?= Translator::get('role_label') ?? 'Rol' ?>:</label>
                <select id="usu_rol" name="usu_rol" required class="form-control">
                    <option value="cliente" <?= (($editUser['usu_rol'] ?? '') === 'cliente') ? 'selected' : '' ?> data-translate-key="client_role"><?= Translator::get('client_role') ?? 'Cliente' ?></option>
                    <option value="admin" <?= (($editUser['usu_rol'] ?? '') === 'admin') ? 'selected' : '' ?> data-translate-key="admin_role"><?= Translator::get('admin_role') ?? 'Administrador' ?></option>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="usu_activo" name="usu_activo" <?= (($editUser['usu_activo'] ?? true) ? 'checked' : '') ?>>
                <label class="form-check-label" for="usu_activo" data-translate-key="active_label"><?= Translator::get('active_label') ?? 'Activo' ?></label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary" data-translate-key="<?= $editUser ? 'update_user_button' : 'create_user_button' ?>"><?= $editUser ? (Translator::get('update_user_button') ?? 'Actualizar Usuario') : (Translator::get('create_user_button') ?? 'Crear Usuario') ?></button>
                <?php if ($editUser): ?><a href="users.php" class="btn btn-secondary" data-translate-key="cancel_edit_button"><?= Translator::get('cancel_edit_button') ?? 'Cancelar Edición' ?></a><?php endif; ?>
            </div>
        </form>

        <h3 data-translate-key="user_list_title"><?= Translator::get('user_list_title') ?? 'Lista de Usuarios' ?></h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th data-translate-key="username_header"><?= Translator::get('username_header') ?? 'Nombre' ?></th>
                    <th data-translate-key="email_header"><?= Translator::get('email_header') ?? 'Email' ?></th>
                    <th data-translate-key="role_header"><?= Translator::get('role_header') ?? 'Rol' ?></th>
                    <th data-translate-key="active_header"><?= Translator::get('active_header') ?? 'Activo' ?></th>
                    <th data-translate-key="registration_date_header"><?= Translator::get('registration_date_header') ?? 'Registro' ?></th>
                    <th data-translate-key="actions_header"><?= Translator::get('actions_header') ?? 'Acciones' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['usu_id'] ?></td>
                    <td><?= $user['usu_nombre'] ?></td>
                    <td><?= $user['usu_email'] ?></td>
                    <td><?= $user['usu_rol'] ?></td>
                    <td><?= $user['usu_activo'] ? (Translator::get('yes') ?? 'Sí') : (Translator::get('no') ?? 'No') ?></td>
                    <td><?= $user['usu_fecha_registro'] ?></td>
                    <td class="actions">
                        <a href="users.php?action=edit&id=<?= $user['usu_id'] ?>" class="btn btn-sm btn-info" data-translate-key="edit_button"><?= Translator::get('edit_button') ?? 'Editar' ?></a> 
                        <a href="users.php?action=delete&id=<?= $user['usu_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= Translator::get('confirm_delete_user') ?? '¿Estás seguro de eliminar este usuario?' ?>');" data-translate-key="delete_button"><?= Translator::get('delete_button') ?? 'Eliminar' ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
