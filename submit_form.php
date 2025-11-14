<?php

require_once 'dbconexion.php';
require_once 'FormValidator.php';
require_once 'Inscriptor.php';
require_once 'InscriptorDAO.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new DB();
    $inscriptorDAO = new InscriptorDAO($database);

    $validator = new FormValidator($_POST, $inscriptorDAO);

    if ($validator->validate()) {
        $sanitized_data = $validator->getSanitizedData();

        $inscriptor = new Inscriptor(
            null, // ID will be set by the database
            $sanitized_data['nombre'],
            $sanitized_data['apellido'],
            $sanitized_data['correo'],
            $sanitized_data['celular'],
            $sanitized_data['edad'],
            $sanitized_data['sexo'],
            $sanitized_data['pais_residencia_id'],
            $sanitized_data['nacionalidad_id'],
            $sanitized_data['observaciones'],
            $sanitized_data['fecha_inscripcion'],
            $sanitized_data['temas']
        );

        
        if ($inscriptorDAO->saveInscriptor($inscriptor)) {
            header("Location: report.php?message=success");
            exit();
        } else {
            // Handle database save error
            echo "<p style='color:red;'>Error al guardar la inscripción. Por favor, inténtelo de nuevo.</p>";
            echo "<p><a href='index.php'>Volver al formulario</a></p>";
        }
    } else {
        // Validation failed, display errors
        $errors = $validator->getErrors();
        foreach ($errors as $field => $error_message) {
            echo "<p style='color:red;'>" . htmlspecialchars($error_message) . "</p>";
        }
        echo "<p><a href='index.php'>Volver al formulario</a></p>";
    }
} else {
    echo "Acceso denegado.";
}

?>
