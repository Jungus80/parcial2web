<?php
require_once 'dbconexion.php';
require_once 'InscriptorDAO.php';
require_once 'Inscriptor.php'; // Ensure Inscriptor class is available for type hinting and properties

$database = new DB();
$inscriptorDAO = new InscriptorDAO($database);

$inscriptores = $inscriptorDAO->getAllInscriptoresWithDetails();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscripciones</title>
    <link rel="stylesheet" href="style.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reporte de Inscripciones</h1>
        </div>

        <?php if (isset($_GET['message']) && $_GET['message'] == 'success'): ?>
            <p style="color: green; font-weight: bold;">¡Inscripción registrada exitosamente!</p>
        <?php endif; ?>

        <?php if (empty($inscriptores)): ?>
            <p>No hay inscripciones registradas aún.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Correo</th>
                        <th>Celular</th>
                        <th>Edad</th>
                        <th>Sexo</th>
                        <th>País de Residencia</th>
                        <th>Nacionalidad</th>
                        <th>Temas de Interés</th>
                        <th>Observaciones</th>
                        <th>Fecha de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscriptores as $inscriptor): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inscriptor->nombre); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->apellido); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->correo); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->celular); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->edad); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->sexo); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->pais_residencia_nombre); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->nacionalidad_nombre); ?></td>
                            <td>
                                <?php
                                echo htmlspecialchars(implode(", ", $inscriptor->temas_interes_nombres));
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($inscriptor->observaciones); ?></td>
                            <td><?php echo htmlspecialchars($inscriptor->fecha_registro); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <p><a href="index.php">Volver al Formulario</a></p>
    </div>
</body>
</html>
