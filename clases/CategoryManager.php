<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/Seguridad.php';

class CategoryManager {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    public function getAllCategories(bool $includeInactive = false) {
        $query = "SELECT cat_id, cat_nombre, cat_descripcion, cat_activa FROM Categoria";
        if (!$includeInactive) {
            $query .= " WHERE cat_activa = TRUE";
        }
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoryById(int $id) {
        $query = "SELECT cat_id, cat_nombre, cat_descripcion, cat_activa FROM Categoria WHERE cat_id = ?";
        $stmt = $this->db->query($query, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCategory(string $nombre, ?string $descripcion = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');

        if (empty($nombreSanitizado)) {
            return false; // El nombre no puede estar vacío
        }

        // Verificar si la categoría ya existe (UNIQUE constraint)
        $checkQuery = "SELECT COUNT(*) FROM Categoria WHERE cat_nombre = ?";
        $checkStmt = $this->db->query($checkQuery, [$nombreSanitizado]);
        if ($checkStmt->fetchColumn() > 0) {
            return false; // Categoría ya existe
        }

        $query = "INSERT INTO Categoria (cat_nombre, cat_descripcion) VALUES (?, ?)";
        try {
            $stmt = $this->db->insertSeguro($query, [$nombreSanitizado, $descripcionSanitizada]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al crear categoría: " . $e->getMessage());
            return false;
        }
    }

    public function updateCategory(int $id, string $nombre, ?string $descripcion = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $descripcionSanitizada = Seguridad::sanitizarEntrada($descripcion ?? '');

        if (empty($nombreSanitizado)) {
            return false; // El nombre no puede estar vacío
        }

        // Verificar si la categoría ya existe (UNIQUE constraint) excluyendo la actual
        $checkQuery = "SELECT COUNT(*) FROM Categoria WHERE cat_nombre = ? AND cat_id != ?";
        $checkStmt = $this->db->query($checkQuery, [$nombreSanitizado, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            return false; // Categoría ya existe
        }

        $query = "UPDATE Categoria SET cat_nombre = ?, cat_descripcion = ? WHERE cat_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$nombreSanitizado, $descripcionSanitizada, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar categoría: " . $e->getMessage());
            return false;
        }
    }

    public function activateCategory(int $id) {
        $query = "UPDATE Categoria SET cat_activa = TRUE WHERE cat_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al activar categoría: " . $e->getMessage());
            return false;
        }
    }

    public function deactivateCategory(int $id) {
        $query = "UPDATE Categoria SET cat_activa = FALSE WHERE cat_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al desactivar categoría: " . $e->getMessage());
            return false;
        }
    }

    public function deleteCategory(int $id) {
        // En lugar de eliminar, desactivamos la categoría para preservar la integridad histórica.
        return $this->deactivateCategory($id);
    }
}

?>
