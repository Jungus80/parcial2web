<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $lang = $_POST['lang'];
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + (86400 * 30), '/');

    // Redirigir a la página anterior en lugar de devolver JSON
    $redirect = $_SERVER['HTTP_REFERER'] ?? '/admin/index.php';
    header("Location: $redirect");
    exit;
}

// En caso de acceso directo sin POST
header("Location: /admin/index.php");
exit;
?>
