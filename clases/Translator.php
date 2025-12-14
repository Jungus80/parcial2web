<?php

require_once __DIR__ . '/../dbconexion.php';

class Translator {
    private static $db;
    private static $langData = [];
    private static $currentLang = 'es'; // Idioma por defecto

    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        self::$db = new DB();
        self::$db->getConnection();

        self::$currentLang = self::getPreferredLanguage();
        self::loadLanguageFile(self::$currentLang);
    }

    public static function getPreferredLanguage(): string {
        $userId = $_SESSION['user_id'] ?? 0;

        if ($userId > 0) {
            $query = "SELECT i.idi_nombre FROM Usuario u JOIN Idioma i ON u.usu_idioma = i.idi_id WHERE u.usu_id = ?";
            $stmt = self::$db->query($query, [$userId]);
            $userLang = $stmt->fetchColumn();
            if ($userLang && self::isValidLanguage($userLang)) {
                return $userLang;
            }
        }

        if (isset($_COOKIE['lang']) && self::isValidLanguage($_COOKIE['lang'])) {
            return $_COOKIE['lang'];
        }

        return 'es'; // Default language if no user preference or cookie
    }

    public static function setLanguageCookie(string $langCode) {
        if (self::isValidLanguage($langCode)) {
            setcookie('lang', $langCode, [
                'expires' => time() + (86400 * 30),
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            self::$currentLang = $langCode;
            self::loadLanguageFile($langCode);
            return true;
        }
        return false;
    }

    public static function getAvailableLanguages(): array {
        $query = "SELECT idi_id, idi_nombre, idi_bandera_url FROM Idioma";
        $stmt = self::$db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function updateUserLanguage(int $userId, string $langCode): bool {
        if (!self::isValidLanguage($langCode)) {
            return false;
        }

        $query = "SELECT idi_id FROM Idioma WHERE idi_nombre = ?";
        $stmt = self::$db->query($query, [$langCode]);
        $idiomaId = $stmt->fetchColumn();

        if (!$idiomaId) {
            return false;
        }

        $updateQuery = "UPDATE Usuario SET usu_idioma = ? WHERE usu_id = ?";
        try {
            self::$db->updateSeguro($updateQuery, [$idiomaId, $userId]);
            return true;
        } catch (PDOException $e) {
            error_log("Error updating user language: " . $e->getMessage());
            return false;
        }
    }

    private static function isValidLanguage(string $langCode): bool {
        $query = "SELECT COUNT(*) FROM Idioma WHERE idi_nombre = ?";
        $stmt = self::$db->query($query, [$langCode]);
        return (bool)$stmt->fetchColumn();
    }

    private static function loadLanguageFile(string $langCode) {
        $filePath = __DIR__ . '/../lang/' . $langCode . '.json';
        if (file_exists($filePath)) {
            self::$langData = json_decode(file_get_contents($filePath), true);
        } else {
            // Cargar idioma por defecto si el archivo no existe (ej. si el archivo 'en.json' no existe)
            self::$langData = json_decode(file_get_contents(__DIR__ . '/../lang/es.json'), true);
        }
    }

    public static function get(string $key): string {
        return self::$langData[$key] ?? $key;
    }

    public static function getCurrentLanguage(): string {
        return self::$currentLang;
    }
}

// Inicializar el traductor al incluir el archivo
Translator::init();

?>
