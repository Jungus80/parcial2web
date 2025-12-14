<?php
session_start();

require_once 'clases/Tracker.php'; // Incluir la clase Tracker
$tracker = new Tracker();
$userId = $_SESSION['user_id'] ?? 0;
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$requestUri = $_SERVER['REQUEST_URI'];

// Solo trackear si no es un activo estático y la cookie esté aceptada
// No necesitamos el metId ni el JS para permanencia aquí, ya que es una redirección inmediata.
if (isset($_COOKIE['cookie_accepted'])) {
    $tracker->trackPageView($userId, $requestUri, null, $referrer);
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
// Nota: ¡Esto destruirá la sesión, y no la información de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir al login o a la página principal
header('Location: login.php');
exit();
?>
