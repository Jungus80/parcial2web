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
     * @return bool True si se registra correctamente, false en caso contrario.
     */
    public function trackPageView(int $userId, string $pageUrl, ?int $visitTime = null, ?string $referrer = null): bool {
        if (!isset($_COOKIE['cookie_accepted'])) {
            return false; // No rastrear si no se aceptaron las cookies
        }

        $query = "INSERT INTO Metrica_navegacion (met_usuario, met_pagina_url, met_fecha_visita, met_tiempo_visita, met_referrer) VALUES (?, ?, NOW(), ?, ?)";
        $params = [$userId, $pageUrl, $visitTime, $referrer];
        try {
            $stmt = $this->db->insertSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al registrar vista de página: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra la observación de un producto por un usuario.
     * @param int $userId ID del usuario.
     * @param int $productId ID del producto.
     * @param int|null $permanence Tiempo de permanencia en segundos (opcional).
     * @return bool True si se registra correctamente, false en caso contrario.
     */
    public function trackProductView(int $userId, int $productId, ?int $permanence = null): bool {
        if (!isset($_COOKIE['cookie_accepted'])) {
            return false; // No rastrear si no se aceptaron las cookies
        }

        $query = "INSERT INTO Observa (obs_usuario, obs_producto, obs_fecha_visita, obs_permanencia) VALUES (?, ?, NOW(), ?)";
        $params = [$userId, $productId, $permanence];
        try {
            $stmt = $this->db->insertSeguro($query, $params);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Error al registrar observación de producto: " . $e->getMessage());
            return false;
        }
    }
}

?>
