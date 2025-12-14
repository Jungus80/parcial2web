<?php
session_start();

require_once 'dbconexion.php';
require_once 'clases/Seguridad.php';
require_once 'clases/CartManager.php'; // Incluir CartManager
require_once 'clases/UserManager.php'; // Incluir UserManager
require_once 'clases/Tracker.php'; // Incluir la clase Tracker

$tracker = new Tracker();
$userId = $_SESSION['user_id'] ?? 0;
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$requestUri = $_SERVER['REQUEST_URI'];

// Lista de extensiones a ignorar
$ignoredExtensions = ['.ico', '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg'];
$isStaticAsset = false;
foreach ($ignoredExtensions as $ext) {
    if (str_ends_with($requestUri, $ext)) {
        $isStaticAsset = true;
        break;
    }
}

$metId = 0;
if (!$isStaticAsset && isset($_COOKIE['cookie_accepted'])) {
    $metId = $tracker->trackPageView($userId, $requestUri, null, $referrer);
}

$db = new DB();
$conn = $db->getConnection();

$message = '';
$cartManager = new CartManager(); // Instanciar CartManager
$userManager = new UserManager(); // Instanciar UserManager

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';

    // 1. Sanitizar
    $emailSanitizado = Seguridad::sanitizarEntrada($email);
    $contrasenaSanitizada = Seguridad::sanitizarEntrada($contrasena);

    // 2. Obtener datos del usuario por email desde la DB
    $query = "SELECT usu_id, usu_nombre, usu_password_hash, usu_rol FROM Usuario WHERE usu_email = ?";
    $stmt = $db->query($query, [$emailSanitizado]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // 3. Verificar contraseña
        if (password_verify($contrasenaSanitizada, $user['usu_password_hash'])) {
            $_SESSION['user_id'] = $user['usu_id'];
            $_SESSION['username'] = $user['usu_nombre'];
            $_SESSION['user_rol'] = $user['usu_rol']; // Set user role in session

            // Actualizar usu_ultimo_acceso
            $db->updateSeguro("UPDATE Usuario SET usu_ultimo_acceso = NOW() WHERE usu_id = ?", [$user['usu_id']]);

            $message = 'Inicio de sesión exitoso. ¡Bienvenido, ' . $user['usu_nombre'] . '!';

            // --- Lógica de fusión de carrito ---
            $guestCartId = $_COOKIE['guest_cart_id'] ?? null;
            if ($guestCartId && isset($_SESSION['cart_id'])) {
                // Si había un carrito de invitado y ya hay un carrito en sesión (el del user o uno nuevo)
                $currentUserCartId = $_SESSION['cart_id'];
                // Intentar fusionar. Si falla, el guest cart se marcará como CONVERTIDO y se usará el user cart.
                $cartManager->mergeCarts($guestCartId, $_SESSION['user_id']);

                // Después de la fusión, el $_SESSION['cart_id'] ya estará actualizado por mergeCarts/assignGuestCartToUser.           
            } elseif ($guestCartId && !isset($_SESSION['cart_id'])) {
                // Si había un carrito de invitado pero no hay uno en sesión (ej. sesión expirada)
                $cartManager->assignGuestCartToUser($guestCartId, $_SESSION['user_id']);
            } else {
                // No hay carrito de invitado, o no se encontró, buscar/crear el carrito del usuario
                $userActiveCart = $cartManager->getUserActiveCart($_SESSION['user_id']);
                if ($userActiveCart) {
                    $_SESSION['cart_id'] = $userActiveCart['car_id'];
                } else {
                    // Si no tiene un carrito activo, se crea uno nuevo para él
                    // Esto es manejado por el constructor de CartManager si $_SESSION['cart_id'] no está set
                    // Forzamos la creación/actualización para este usuario
                    $cartManager->createCartForSession(); // crea un carrito_id nuevo para este usuario
                }
            }
            // --- Fin Lógica de fusión de carrito --- 

            // Redirigir a la página de administración si es admin, o a la página principal si es cliente
            // Asegurarse de que la sesión se guarde antes de la redirección
            session_write_close();

            if ($_SESSION['user_rol'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $message = 'Contraseña incorrecta.';
        }
    } else {
        $message = 'Usuario no encontrado.';
    }
}
?>
<?php require_once 'header.php'; ?>
<script>
    // JavaScript para pasar metId a la parte de script del footer
    window.currentMetId = <?= $metId ?>;
</script>
<style>
    .auth-links a[href="cart.php"] {
        display: none;
    }
</style>
<div class="container">
    <h2 data-translate-key="login_title"><?= Translator::get('login_title') ?? 'Iniciar Sesión' ?></h2>
    <?php if ($message): 
        $messageClass = (strpos($message, 'incorrecta') !== false || strpos($message, 'no encontrado') !== false) ? 'error' : 'message';
    ?>
        <p class="<?= $messageClass ?>"><?= $message ?></p>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email" data-translate-key="email_label"><?= Translator::get('email_label') ?? 'Email' ?>:</label>
            <input type="email" id="email" name="email" required maxlength="80">
        </div>
        
        <div class="form-group">
            <label for="contrasena" data-translate-key="password_label"><?= Translator::get('password_label') ?? 'Contraseña' ?>:</label>
            <input type="password" id="contrasena" name="contrasena" required minlength="8">
            <small class="form-text text-muted" data-translate-key="password_min_length"><?= Translator::get('password_min_length') ?? 'Mínimo 8 caracteres.' ?></small>
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn btn-primary" data-translate-key="login_button"><?= Translator::get('login_button') ?? 'Iniciar Sesión' ?></button>
        </div>
    </form>
    <p data-translate-key="no_account_yet"><?= Translator::get('no_account_yet') ?? '¿No tienes una cuenta?' ?></p>
    <p><a href="register.php" class="btn btn-info" data-translate-key="register_link_text"><?= Translator::get('register_link_text') ?? 'Regístrate aquí' ?></a>.</p>
</div>
<?php require_once 'footer.php'; ?>

<script>
    (function() {
        let startTime = Date.now();
        let metId = window.currentMetId; // Usar la variable global o pasarla directamente
        console.log('Page loaded. Initial metId:', metId);

        function sendPagePermanence() {
            let endTime = Date.now();
            let permanence = endTime - startTime; // in milliseconds
            console.log('Attempting to send page permanence. Current metId:', metId, 'Permanence:', permanence, 'ms');

            let cookieAccepted = document.cookie.includes('cookie_accepted');
            console.log('Cookie Accepted:', cookieAccepted);

            if (cookieAccepted && !isNaN(metId) && metId > 0 && permanence > 0) {
                console.log('Sending page permanence AJAX for met_id=' + metId + ', permanence=' + permanence + 'ms');
                fetch('track_page_duration.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        met_id: metId,
                        permanence: permanence
                    }),
                }).then(response => {
                    // console.log('Page permanence tracking sent:', response);
                }).catch(error => {
                    console.error('Error sending page permanence:', error);
                });
            }
        }

        window.addEventListener('beforeunload', sendPagePermanence);
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                sendPagePermanence();
            } else {
                startTime = Date.now();
            }
        });
    })();
</script>
