<?php

require_once 'dbconexion.php';
require_once 'Inscriptor.php';
require_once 'Pais.php'; // Assuming Pais is needed for report details
require_once 'TemaInteres.php'; // Assuming TemaInteres is needed for report details

class InscriptorDAO
{
    private $conn;

    public function __construct(DB $db)
    {
        $this->conn = $db->getConnection();
    }

    public function saveInscriptor(Inscriptor $inscriptor)
    {
        try {
            $this->conn->beginTransaction();

            // Insert into Inscriptores table
            $inscriptor_query = "INSERT INTO Inscriptores (nombre, apellido, correo, celular, edad, sexo, pais_residencia_id, nacionalidad_id, observaciones, fecha_registro) VALUES (:nombre, :apellido, :correo, :celular, :edad, :sexo, :pais_residencia_id, :nacionalidad_id, :observaciones, :fecha_registro)";
            $stmt = $this->conn->prepare($inscriptor_query);
            $stmt->bindParam(':nombre', $inscriptor->nombre);
            $stmt->bindParam(':apellido', $inscriptor->apellido);
            $stmt->bindParam(':correo', $inscriptor->correo);
            $stmt->bindParam(':celular', $inscriptor->celular);
            $stmt->bindParam(':edad', $inscriptor->edad);
            $stmt->bindParam(':sexo', $inscriptor->sexo);
            $stmt->bindParam(':pais_residencia_id', $inscriptor->pais_residencia_id);
            $stmt->bindParam(':nacionalidad_id', $inscriptor->nacionalidad_id);
            $stmt->bindParam(':observaciones', $inscriptor->observaciones);
            $stmt->bindParam(':fecha_registro', $inscriptor->fecha_registro);
            $stmt->execute();

            $inscriptor->id = $this->conn->lastInsertId();

            // Insert into Inscriptor_Temas table
            if (!empty($inscriptor->temas_interes_ids)) {
                $tema_query = "INSERT INTO Inscriptor_Temas (inscriptor_id, tema_id) VALUES (:inscriptor_id, :tema_id)";
                foreach ($inscriptor->temas_interes_ids as $tema_id) {
                    $stmt = $this->conn->prepare($tema_query);
                    $stmt->bindParam(':inscriptor_id', $inscriptor->id);
                    $stmt->bindParam(':tema_id', $tema_id);
                    $stmt->execute();
                }
            }

            $this->conn->commit();
            return true;

        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error saving inscriptor: " . $e->getMessage());
            return false;
        }
    }

    public function getAllInscriptoresWithDetails()
    {
        $query = "SELECT 
                        i.id, i.nombre, i.apellido, i.correo, i.celular, i.edad, i.sexo, 
                        i.pais_residencia_id, i.nacionalidad_id, 
                        pr.nombre as pais_residencia_nombre, 
                        n.nombre as nacionalidad_nombre, 
                        i.observaciones, i.fecha_registro 
                    FROM 
                        Inscriptores i
                    JOIN 
                        Paises pr ON i.pais_residencia_id = pr.id
                    JOIN 
                        Paises n ON i.nacionalidad_id = n.id
                    ORDER BY i.fecha_registro DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inscriptor = new Inscriptor(
                $row['id'],
                $row['nombre'],
                $row['apellido'],
                $row['correo'],
                $row['celular'],
                $row['edad'],
                $row['sexo'],
                $row['pais_residencia_id'], 
                $row['nacionalidad_id'], 
                $row['observaciones'],
                $row['fecha_registro']
            );
            // Add the names for display
            $inscriptor->pais_residencia_nombre = $row['pais_residencia_nombre'];
            $inscriptor->nacionalidad_nombre = $row['nacionalidad_nombre'];

            // Get associated themes
            $query_temas = "SELECT ti.tema FROM Inscriptor_Temas it JOIN TemasInteres ti ON it.tema_id = ti.id WHERE it.inscriptor_id = :inscriptor_id";
            $stmt_temas = $this->conn->prepare($query_temas);
            $stmt_temas->bindParam(':inscriptor_id', $inscriptor->id, PDO::PARAM_INT);
            $stmt_temas->execute();
            $temas_nombres = $stmt_temas->fetchAll(PDO::FETCH_COLUMN);
            $inscriptor->temas_interes_nombres = $temas_nombres; // Store names for display
            
            $results[] = $inscriptor;
        }
        return $results;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) FROM Inscriptores WHERE correo = :correo ";
        if ($excludeId !== null) {
            $query .= "AND id != :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':correo', $email);
        if ($excludeId !== null) {
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }

    public function celularExists(string $celular, ?int $excludeId = null): bool
    {
        $query = "SELECT COUNT(*) FROM Inscriptores WHERE celular = :celular ";
        if ($excludeId !== null) {
            $query .= "AND id != :id";
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':celular', $celular);
        if ($excludeId !== null) {
            $stmt->bindParam(':id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchColumn() > 0;
    }
}

?>
