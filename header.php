<?php

require_once 'clases/Translator.php';

// Obtener el idioma actual para el HTML lang attribute
$currentHtmlLang = Translator::getCurrentLanguage();

$availableLanguages = Translator::getAvailableLanguages();

$allLangData = [];
foreach ($availableLanguages as $lang) {
    $filePath = __DIR__ . '/lang/' . $lang['idi_nombre'] . '.json';
    if (file_exists($filePath)) {
        $allLangData[$lang['idi_nombre']] = json_decode(file_get_contents($filePath), true);
    }
}

?>
<!DOCTYPE html>
<html lang="<?= $currentHtmlLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Sitio Web Multilingüe</title>
    <link rel="stylesheet" href="styles.css"> <!-- Estilos generales del frontend -->
    </style>
</head>
<body>
    <?php if (!isset($_COOKIE['cookie_accepted'])): ?>
        <div class="cookie-banner" id="cookieBanner">
            <?= Translator::get('cookie_consent_message') ?? 'Este sitio web utiliza cookies para mejorar la experiencia del usuario y ofrecer publicidad dirigida. Al continuar navegando, aceptas nuestra política de cookies.' ?>
            <button class="btn btn-success" onclick="acceptCookies()">
                <?= Translator::get('accept_cookies') ?? 'Aceptar' ?>
            </button>
        </div>
        <script>
            function acceptCookies() {
                document.cookie = "cookie_accepted=true; expires=" + new Date(new Date().getTime() + 365 * 24 * 60 * 60 * 1000).toGMTString() + "; path=/; SameSite=Lax";
                document.getElementById('cookieBanner').style.display = 'none';
            }
        </script>
    <?php endif; ?>

    <header class="main-header">
        <div class="language-and-auth">
            <div class="language-selector">
                <label for="lang_select" data-translate-key="select_language">
                    <?= Translator::get('select_language'); ?>:
                </label>
                <select id="lang_select" onchange="updateLanguage(this.value)">
                    <?php foreach ($availableLanguages as $lang):
                        $optionText = $lang['idi_nombre'] == 'es' ? 'Español' : ($lang['idi_nombre'] == 'en' ? 'English' : $lang['idi_nombre']);
                        ?>
                        <option value="<?= $lang['idi_nombre'] ?>" <?= (Translator::getCurrentLanguage() === $lang['idi_nombre']) ? 'selected' : '' ?>><?= $optionText ?></option>
                    <?php endforeach; ?>
                </select>
                <img id="selected_flag" src="" alt="Flag" width="24" height="18" style="display:none;">
            </div>
            <div class="auth-links">
                <a href="cart.php" class="btn btn-secondary btn-sm">🛒 <span data-translate-key="shopping_cart"><?= Translator::get('shopping_cart') ?? 'Carrito' ?></span></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" class="btn btn-danger btn-sm" data-translate-key="logout_button"><?= Translator::get('logout_button') ?? 'Cerrar Sesión' ?> (<?= $_SESSION['username'] ?>)</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-sm" data-translate-key="login_button_header"><?= Translator::get('login_button_header') ?? 'Iniciar Sesión' ?></a>
                    <a href="register.php" class="btn btn-info btn-sm" data-translate-key="register_button_header"><?= Translator::get('register_button_header') ?? 'Registrarse' ?></a>
                <?php endif; ?>
            </div>
        </div>
        <h1 class="site-title" data-translate-key="greeting"><?= Translator::get('greeting'); ?></h1>
        <hr>
    </header>

    <main class="main-content-wrapper">
