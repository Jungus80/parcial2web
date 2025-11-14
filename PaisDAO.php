<?php

require_once 'dbconexion.php';
require_once 'Pais.php';

class PaisDAO
{
    private $conn;

    public function __construct(DB $db)
    {
        $this->conn = $db->getConnection();
    }

    public function getAllPaises()
    {
        $query = "SELECT id, nombre FROM Paises ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $paises = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $paises[] = new Pais($row['id'], $row['nombre']);
        }
        return $paises;
    }

    public function getPaisById($id)
    {
        $query = "SELECT id, nombre FROM Paises WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new Pais($row['id'], $row['nombre']);
        }
        return null;
    }
}

?>
