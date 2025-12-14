<?php

class Seguridad {

    /**
     * Sanitiza una cadena de entrada para prevenir ataques XSS e inyección.
     * Incluye trim, stripslashes, htmlspecialchars y strip_tags.
     * @param string $input La cadena a sanitizar.
     * @return string La cadena sanitizada.
     */
    public static function sanitizarEntrada(string $input): string {
        $input = trim($input);
        $input = stripslashes($input); // Elimina barras invertidas
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); // Convierte caracteres especiales a entidades HTML
        $input = strip_tags($input); // Elimina etiquetas HTML y PHP

        // Opcionalmente, se podría usar filter_var para filtros más específicos
        // $input = filter_var($input, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW); // Obsoleto en PHP 8.1

        return $input;
    }

    /**
     * Sanitiza un usuario y una contraseña específicos.
     * @param string $usuario El nombre de usuario.
     * @param string $contrasena La contraseña.
     * @return array Un array asociativo con 'usuario' y 'contrasena' sanitizados.
     */
    public static function sanitizarCredenciales(string $usuario, string $contrasena): array {
        return [
            'usuario' => self::sanitizarEntrada($usuario),
            'contrasena' => self::sanitizarEntrada($contrasena)
        ];
    }

    /**
     * Valida una contraseña según criterios de seguridad específicos.
     * @param string $contrasena La contraseña a validar.
     * @return bool True si la contraseña es válida, false en caso contrario.
     */
    public static function validarContrasena(string $contrasena): bool {
        // 1. No vacía
        if (empty($contrasena)) {
            return false;
        }

        // 2. Longitud mínima (8 caracteres)
        if (strlen($contrasena) < 8) {
            return false;
        }

        // 3. Complejidad: al menos una letra minúscula, una mayúscula, un número y un símbolo especial
        $hasLowercase = preg_match('/[a-z]/', $contrasena);
        $hasUppercase = preg_match('/[A-Z]/', $contrasena);
        $hasNumber = preg_match('/[0-9]/', $contrasena);
        $hasSpecialChar = preg_match('/[^a-zA-Z0-9]/', $contrasena); // Cualquier carácter que no sea alfanumérico

        if (!$hasLowercase || !$hasUppercase || !$hasNumber || !$hasSpecialChar) {
            return false;
        }

        return true; // La contraseña cumple con todos los criterios
    }
}

?>
