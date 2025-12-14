<?php
session_start();

require_once 'clases/Translator.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lang'])) {
    $langCode = $_POST['lang'];
    $userId = $_SESSION['user_id'] ?? null;

    // Set language cookie
    if (Translator::setLanguageCookie($langCode)) {
        $response['success'] = true;
        $response['message'] = 'Language cookie set.';

        // If user is logged in, update their preference in the DB
        if ($userId && Translator::updateUserLanguage($userId, $langCode)) {
            $response['message'] .= ' User language preference updated.';
        } elseif ($userId) {
            $response['success'] = false;
            $response['message'] = 'Failed to update user language preference.';
        }
    } else {
        $response['message'] = 'Invalid language code.';
    }
}

echo json_encode($response);
exit();
?>
