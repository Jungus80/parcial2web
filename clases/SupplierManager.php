<?php

require_once __DIR__ . '/../dbconexion.php';
require_once __DIR__ . '/Seguridad.php';

class SupplierManager {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection();
    }

    public function getAllSuppliers() {
        $query = "SELECT prv_id, prv_nombre, prv_telefono, prv_celular, prv_direccion, prv_url_web, prv_calificacion_estrellas FROM Proveedor";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSupplierById(int $id) {
        $query = "SELECT prv_id, prv_nombre, prv_telefono, prv_celular, prv_direccion, prv_url_web, prv_calificacion_estrellas FROM Proveedor WHERE prv_id = ?";
        $stmt = $this->db->query($query, [$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createSupplier(string $nombre, ?string $telefono = null, ?string $celular = null, ?string $direccion = null, ?string $urlWeb = null, ?float $calificacion = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $telefonoSanitizado = Seguridad::sanitizarEntrada($telefono ?? '');
        $celularSanitizado = Seguridad::sanitizarEntrada($celular ?? '');
        $direccionSanitizada = Seguridad::sanitizarEntrada($direccion ?? '');
        $urlWebSanitizada = Seguridad::sanitizarEntrada($urlWeb ?? '');

        if (empty($nombreSanitizado)) {
            return false; // El nombre no puede estar vacío
        }

        $query = "INSERT INTO Proveedor (prv_nombre, prv_telefono, prv_celular, prv_direccion, prv_url_web, prv_calificacion_estrellas) VALUES (?, ?, ?, ?, ?, ?)";
        try {
            $stmt = $this->db->insertSeguro($query, [$nombreSanitizado, $telefonoSanitizado, $celularSanitizado, $direccionSanitizada, $urlWebSanitizada, $calificacion]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al crear proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function updateSupplier(int $id, string $nombre, ?string $telefono = null, ?string $celular = null, ?string $direccion = null, ?string $urlWeb = null, ?float $calificacion = null) {
        $nombreSanitizado = Seguridad::sanitizarEntrada($nombre);
        $telefonoSanitizado = Seguridad::sanitizarEntrada($telefono ?? '');
        $celularSanitizado = Seguridad::sanitizarEntrada($celular ?? '');
        $direccionSanitizada = Seguridad::sanitizarEntrada($direccion ?? '');
        $urlWebSanitizada = Seguridad::sanitizarEntrada($urlWeb ?? '');

        if (empty($nombreSanitizado)) {
            return false; // El nombre no puede estar vacío
        }

        $query = "UPDATE Proveedor SET prv_nombre = ?, prv_telefono = ?, prv_celular = ?, prv_direccion = ?, prv_url_web = ?, prv_calificacion_estrellas = ? WHERE prv_id = ?";
        try {
            $stmt = $this->db->updateSeguro($query, [$nombreSanitizado, $telefonoSanitizado, $celularSanitizado, $direccionSanitizada, $urlWebSanitizada, $calificacion, $id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar proveedor: " . $e->getMessage());
            return false;
        }
    }

    public function deleteSupplier(int $id) {
        $query = "DELETE FROM Proveedor WHERE prv_id = ?";
        try {
            $stmt = $this->db->insertSeguro($query, [$id]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al eliminar proveedor: " . $e->getMessage());
            return false;
        }
    }
}

?>
