<?php
session_start();
require_once 'dbconexion.php';
require_once 'clases/Seguridad.php';

$db = new DB();
$conn = $db->getConnection();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $email = $_POST['email'] ?? '';

    // 1. Sanitizar credenciales
    $credencialesSanitizadas = Seguridad::sanitizarCredenciales($usuario, $contrasena);
    $usuarioSanitizado = $credencialesSanitizadas['usuario'];
    $contrasenaSanitizada = $credencialesSanitizadas['contrasena'];
    $emailSanitizado = Seguridad::sanitizarEntrada($email); // Asumiendo que Seguridad::sanitizarEntrada es public static

    // 2. Validar contraseña
    if (!Seguridad::validarContrasena($contrasenaSanitizada)) {
        $message = 'La contraseña no cumple con los requisitos de seguridad.';
    } else {
                // 3. Verificar si el email ya existe utilizando UserManager
        require_once 'clases/UserManager.php'; // Asegurarse de que UserManager esté incluido
        $userManager = new UserManager();
        if ($userManager->emailExists($emailSanitizado)) {
            $message = 'El correo electrónico ya está registrado.';
        } else {
            // 4. Hashear la contraseña
            $passwordHash = password_hash($contrasenaSanitizada, PASSWORD_DEFAULT);

            // 5. Insertar nuevo usuario
            $query = "INSERT INTO Usuario (usu_nombre, usu_email, usu_password_hash, usu_rol, usu_idioma) VALUES (?, ?, ?, ?, ?)";
            try {
                $stmt = $db->insertSeguro($query, [$usuarioSanitizado, $emailSanitizado, $passwordHash, 'cliente', null]); // Default usu_idioma to NULL
                if ($stmt->rowCount() > 0) {
                    $message = 'Usuario registrado exitosamente!';
                } else {
                    $message = 'Error al registrar usuario.';
                }
            } catch (PDOException $e) {
                $message = 'Error de base de datos: ' . $e->getMessage();
            }
        }
    }
}
?>
<?php require_once 'header.php'; ?>
<div class="container">
    <h2 data-translate-key="register_title"><?= Translator::get('register_title') ?? 'Registro de Usuario' ?></h2>
    <?php if ($message): 
        $messageClass = (strpos($message, 'exitosamente') !== false) ? 'message' : 'error';
    ?>
        <p class="<?= $messageClass ?>"><?= $message ?></p>
    <?php endif; ?>
    <form action="register.php" method="POST">
        <div class="form-group">
        <label for="usuario" data-translate-key="username_label"><?= Translator::get('username_label') ?? 'Usuario' ?>:</label>
        <input type="text" id="usuario" name="usuario" required minlength="3" maxlength="80">
    </div>
    
    <div class="form-group">
        <label for="email" data-translate-key="email_label"><?= Translator::get('email_label') ?? 'Email' ?>:</label>
        <input type="email" id="email" name="email" required maxlength="120">
    </div>

    <div class="form-group">
        <label for="contrasena" data-translate-key="password_label"><?= Translator::get('password_label') ?? 'Contraseña' ?>:</label>
        <input type="password" id="contrasena" name="contrasena" required minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?!.*\s).{8,}" title="Debe contener al menos un número y una letra mayúscula y minúscula, y al menos un carácter especial, y al menos 8 o más caracteres">
        <small class="form-text text-muted" data-translate-key="password_requirements"><?= Translator::get('password_requirements') ?? 'Mínimo 8 caracteres, incluyendo mayúsculas, minúsculas, números y símbolos especiales.' ?></small>
        <div class="invalid-feedback"></div>
    </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary" data-translate-key="register_button"><?= Translator::get('register_button') ?? 'Registrarse' ?></button>
        </div>
    </form>
    <p data-translate-key="already_have_account"><?= Translator::get('already_have_account') ?? '¿Ya tienes una cuenta?' ?> <a href="login.php" class="btn btn-info btn-sm" data-translate-key="login_link_text"><?= Translator::get('login_link_text') ?? 'Inicia Sesión aquí' ?></a>.</p>
</div>
<?php require_once 'footer.php'; ?>
