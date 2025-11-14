<?php

require_once 'dbconexion.php';
require_once 'TemaInteres.php';

class TemaInteresDAO
{
    private $conn;

    public function __construct(DB $db)
    {
        $this->conn = $db->getConnection();
    }

    public function getAllTemas()
    {
        $query = "SELECT id, tema FROM TemasInteres ORDER BY tema";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $temas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $temas[] = new TemaInteres($row['id'], $row['tema']);
        }
        return $temas;
    }

    public function getTemaById($id)
    {
        $query = "SELECT id, tema FROM TemasInteres WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            return new TemaInteres($row['id'], $row['tema']);
        }
        return null;
    }
}

?>
