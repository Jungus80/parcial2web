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
        <h2>Gestión de Usuarios</h2>

        <?php if ($message): ?>
            <p class="message success-message"><?= $message ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error error-message"><?= $error ?></p>
        <?php endif; ?>

        <h3><?= $editUser ? 'Editar Usuario' : 'Registrar Nuevo Usuario' ?></h3>
        <form action="users.php" method="POST">
            <input type="hidden" name="action" value="<?= $editUser ? 'update' : 'create' ?>">
            <input type="hidden" name="usu_id" value="<?= $editUser['usu_id'] ?? '' ?>">
            
            <div class="form-group">
                <label for="usu_nombre">Nombre de Usuario:</label>
                <input type="text" id="usu_nombre" name="usu_nombre" value="<?= $editUser['usu_nombre'] ?? '' ?>" required minlength="3" maxlength="80" class="form-control">
            </div>
            <div class="form-group">
                <label for="usu_email">Correo Electrónico:</label>
                <input type="email" id="usu_email" name="usu_email" value="<?= $editUser['usu_email'] ?? '' ?>" required maxlength="120" class="form-control">
            </div>
            <div class="form-group">
                <label for="usu_password">Contraseña (dejar en blanco para no cambiarla):</label>
                <input type="password" id="usu_password" name="usu_password" class="form-control" <?= $editUser ? '' : 'required' ?> minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?![.\s]).{8,}" title="Debe contener al menos un número y una letra mayúscula y minúscula, y al menos un carácter especial, y al menos 8 o más caracteres">
                <?php if (!$editUser): ?><small class="form-text">Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos especiales.</small><?php endif; ?>
            </div>
            <div class="form-group">
                <label for="usu_rol">Rol:</label>
                <select id="usu_rol" name="usu_rol" required class="form-control">
                    <option value="cliente" <?= (($editUser['usu_rol'] ?? '') === 'cliente') ? 'selected' : '' ?>>Cliente</option>
                    <option value="admin" <?= (($editUser['usu_rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
                </select>
            </div>
            <div class="form-group form-check">
                <input type="checkbox" id="usu_activo" name="usu_activo" <?= (($editUser['usu_activo'] ?? true) ? 'checked' : '') ?>>
                <label class="form-check-label" for="usu_activo">Estado Activo</label>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary"><?= $editUser ? 'Actualizar Usuario' : 'Registrar Usuario' ?></button>
                <?php if ($editUser): ?><a href="users.php" class="btn btn-secondary">Cancelar Edición</a><?php endif; ?>
            </div>
        </form>

        <h3>Lista de Usuarios</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Alias</th>
                    <th>Correo Electrónico</th>
                    <th>Permisos</th>
                    <th>Activo</th>
                    <th>Fecha de Registro</th>
                    <th>Acciones</th>
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
                        <a href="users.php?action=edit&id=<?= $user['usu_id'] ?>" class="btn btn-sm btn-info">Editar</a> 
                        <a href="users.php?action=delete&id=<?= $user['usu_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
<?php require_once '../footer.php'; ?>
</html>
