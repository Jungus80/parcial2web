<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Inscripción</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="logo_utp_small.png" alt="Logo UTP" class="logo left">
            <img src="logo_fic_small.png" alt="Logo FIC" class="logo right">
            <h1>UNIVERSIDAD TECNOLÓGICA DE PANAMÁ</h1>
            <h2>FACULTAD DE INGENIERÍA DE SISTEMAS</h2>
            <h3>DEPARTAMENTO DE INGENIERÍA DE SOFTWARE</h3>
            <h4>CARRERA INGENIERÍA DE SOFTWARE</h4>
        </div>

        <form action="submit_form.php" method="POST">
            <h2>II Parte</h2>
            <div class="form-group">
                <label for="nombre">1. Nombre:</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="form-group">
                <label for="apellido">2. Apellido:</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            <div class="form-group">
                <label for="correo">Correo Electrónico:</label>
                <input type="email" id="correo" name="correo" required>
            </div>
            <div class="form-group">
                <label for="celular">Número de Celular:</label>
                <input type="tel" id="celular" name="celular">
            </div>
            <div class="form-group">
                <label for="edad">3. Edad:</label>
                <input type="number" id="edad" name="edad" min="18" max="99" required>
            </div>
            <div class="form-group">
                <label>4. Sexo:</label>
                <div class="radio-group">
                    <input type="radio" id="masculino" name="sexo" value="Masculino" required>
                    <label for="masculino">Masculino</label>
                    <input type="radio" id="femenino" name="sexo" value="Femenino">
                    <label for="femenino">Femenino</label>
                    <input type="radio" id="otro" name="sexo" value="Otro">
                    <label for="otro">Otro</label>
                </div>
            </div>
            <div class="form-group">
                <label for="pais_residencia">5. País de Residencia:</label>
                <select id="pais_residencia" name="pais_residencia" required>
                    <option value="">Seleccione un país</option>
                    <?php
                    require_once 'dbconexion.php';
                    require_once 'PaisDAO.php';

                    $database = new DB();
                    $paisDAO = new PaisDAO($database);
                    $paises = $paisDAO->getAllPaises();

                    foreach ($paises as $pais) {
                        echo "<option value='{$pais->id}'>{$pais->nombre}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nacionalidad">6. Nacionalidad:</label>
                <select id="nacionalidad" name="nacionalidad" required>
                    <option value="">Seleccione una nacionalidad</option>
                    <?php
                    foreach ($paises as $pais) {
                        echo "<option value='{$pais->id}'>{$pais->nombre}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>7. Tema Tecnológico que le gustaría aprender (checkbox):</label>
                <div class="checkbox-group">
                    <?php
                    require_once 'TemaInteresDAO.php';
                    $temaInteresDAO = new TemaInteresDAO($database);
                    $temas = $temaInteresDAO->getAllTemas();

                    foreach ($temas as $tema) {
                        echo "<input type='checkbox' id='tema_{$tema->id}' name='temas[]' value='{$tema->id}'>";
                        echo "<label for='tema_{$tema->id}'>{$tema->tema}</label><br>";
                    }
                    ?>
                </div>
            </div>
            <div class="form-group">
                <label for="observaciones">8. Observaciones o Consulta sobre el evento:</label>
                <textarea id="observaciones" name="observaciones" rows="5"></textarea>
            </div>
            <div class="form-group">
                <label for="fecha_inscripcion">Fecha de Inscripción:</label>
                <input type="date" id="fecha_inscripcion" name="fecha_inscripcion" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <button type="submit">Enviar Inscripción</button>
        </form>

        <div class="footer">
            <p>&copy; <?php echo date("Y"); ?> iTECH. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
