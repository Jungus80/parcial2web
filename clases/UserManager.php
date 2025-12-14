<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/Seguridad.php';

class UserManager {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    public function getAllUsers() {
        $query = "SELECT usu_id, usu_nombre, usu_email, usu_rol, usu_activo, usu_fecha_registro, usu_ultimo_acceso, usu_idioma FROM Usuario";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserById(int $id) {
        $query = "SELECT usu_id, usu_nombre, usu_email, usu_rol, usu_activo, usu_fecha_registro, usu_ultimo_acceso, usu_idioma FROM Usuario WHERE usu_id = ?";
        $stmt = $this->db->query($query, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserByEmail(string $email) {
        $query = "SELECT usu_id, usu_nombre, usu_email, usu_rol, usu_activo, usu_fecha_registro, usu_ultimo_acceso, usu_idioma FROM Usuario WHERE usu_email = ?";
        $stmt = $this->db->query($query, [$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createUser(string $nombre, string $email, string $password, string $rol = 'cliente', bool $activo = true, ?int $idioma = null) {
        // Sanitizar y validar la contraseña antes de guardar
        $passwordSanitizado = Seguridad::sanitizarEntrada($password);
        if (!Seguridad::validarContrasena($passwordSanitizado)) {
            return false; // Contraseña no válida
        }
        $passwordHash = password_hash($passwordSanitizado, PASSWORD_DEFAULT);

        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $emailSanitizado = Seguridad::sanitizarEntrada($email);
        $rolSanitizado = Seguridad::sanitizarEntrada($rol);

        $query = "INSERT INTO Usuario (usu_nombre, usu_email, usu_password_hash, usu_rol, usu_activo, usu_idioma) VALUES (?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->insertSeguro($query, [$nombreSanitizado, $emailSanitizado, $passwordHash, $rolSanitizado, $activo, $idioma]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al crear usuario: " . $e->getMessage());
            return false;
        }
    }

    public function updateUser(int $id, string $nombre, string $email, string $rol, bool $activo, ?int $idioma = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $emailSanitizado = Seguridad::sanitizarEntrada($email);
        $rolSanitizado = Seguridad::sanitizarEntrada($rol);

        $query = "UPDATE Usuario SET usu_nombre = ?, usu_email = ?, usu_rol = ?, usu_activo = ?, usu_idioma = ? WHERE usu_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$nombreSanitizado, $emailSanitizado, $rolSanitizado, $activo, $idioma, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function deleteUser(int $id) {
        $query = "DELETE FROM Usuario WHERE usu_id = ?";
        try {
            $stmt = $this->db->insertSeguro($query, [$id]); // insertSeguro can be used for DELETE too if it prepares
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al eliminar usuario: " . $e->getMessage());
            return false;
        }
    }

    public function updatePassword(int $userId, string $newPassword): bool {
        $passwordSanitizado = Seguridad::sanitizarEntrada($newPassword);
        if (!Seguridad::validarContrasena($passwordSanitizado)) {
            return false; // Contraseña no válida
        }
        $passwordHash = password_hash($passwordSanitizado, PASSWORD_DEFAULT);

        $query = "UPDATE Usuario SET usu_password_hash = ? WHERE usu_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$passwordHash, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar contraseña: " . $e->getMessage());
            return false;
        }
    }

    // Method to check if an email already exists (useful for creation/update forms)
    public function emailExists(string $email, ?int $excludeUserId = null): bool {
        $query = "SELECT COUNT(*) FROM Usuario WHERE usu_email = ?";
        $params = [$email];
        if ($excludeUserId !== null) {
            $query .= " AND usu_id != ?";
            array_push($params, $excludeUserId);
        }
        $stmt = $this->db->query($query, $params);
        return $stmt->fetchColumn() > 0;
    }
}

?>
