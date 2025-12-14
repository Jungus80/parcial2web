<?php

require_once __DIR__ . '/../dbconexion.php';

class Tracker {
    private $db;

    public function __construct() {
        $this->db = new DB();
        $this->db->getConnection(); // Asegura que la conexión esté establecida
    }

    /**
     * Registra una métrica de navegación de usuario.
     * @param int $userId ID del usuario (0 si es invitado).
     * @param string $pageUrl URL de la página visitada.
     * @param int|null $visitTime Tiempo de visita en segundos (opcional).
     * @param string|null $referrer URL de referencia (opcional).
     * @return int El ID de la métrica de navegación registrada, o 0 si falla.
     */
    public function trackPageView(int $userId, string $pageUrl, ?int $visitTime = null, ?string $referrer = null): int {
        if (!isset($_COOKIE['cookie_accepted'])) {
            return 0; // No rastrear si no se aceptaron las cookies
        }

        $query = "INSERT INTO Metrica_navegacion (met_usuario, met_pagina_url, met_fecha_visita, met_tiempo_visita, met_referrer) VALUES (?, ?, NOW(), ?, ?)";
        $params = [$userId, $pageUrl, $visitTime, $referrer];
        try {
            $stmt = $this->db->insertSeguro($query, $params);
            if ($stmt->rowCount() > 0) {
                return (int)$this->db->conn->lastInsertId(); // Devolver el ID insertado
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Error al registrar vista de página: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Registra la observación de un producto por un usuario.
     * @param int $userId ID del usuario.
     * @param int $productId ID del producto.
     * @param int|null $permanence Tiempo de permanencia en segundos (opcional).
     * @return int El ID de la observación registrada, o 0 si falla.
     */
    public function trackProductView(int $userId, int $productId, ?int $permanence = null): int {
        if (!isset($_COOKIE['cookie_accepted'])) {
            return 0; // No rastrear si no se aceptaron las cookies
        }

        $query = "INSERT INTO Observa (obs_usuario, obs_producto, obs_fecha_visita, obs_permanencia) VALUES (?, ?, NOW(), ?)";
        $params = [$userId, $productId, $permanence];
        try {
            $stmt = $this->db->insertSeguro($query, $params);
            if ($stmt->rowCount() > 0) {
                return (int)$this->db->conn->lastInsertId();
            }
            return 0;
        } catch (PDOException $e) {
            error_log("Error al registrar observación de producto: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Actualiza el tiempo de permanencia para una observación de producto existente.
     * @param int $obsId ID de la observación.
     * @param int $permanence Tiempo de permanencia en segundos.
     * @return bool True si se actualiza correctamente, false en caso contrario.
     */
    public function updateProductViewPermanence(int $obsId, int $permanence): bool {
        $query = "UPDATE Observa SET obs_permanencia = ? WHERE obs_id = ?";
        $params = [$permanence, $obsId];
        try {
            $stmt = $this->db->updateSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar permanencia de observación de producto: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el tiempo de visita para una métrica de navegación existente.
     * @param int $metId ID de la métrica de navegación.
     * @param int $visitTime Tiempo de visita en segundos.
     * @return bool True si se actualiza correctamente, false en caso contrario.
     */
    public function updatePageVisitTime(int $metId, int $visitTime): bool {
        $query = "UPDATE Metrica_navegacion SET met_tiempo_visita = ? WHERE met_id = ?";
        $params = [$visitTime, $metId];
        try {
            $stmt = $this->db->updateSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al actualizar tiempo de visita de métrica de navegación: " . $e->getMessage());
            return false;
        }
    }
}

?>
