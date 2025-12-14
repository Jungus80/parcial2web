<?php

require_once 'dbconexion.php';
require_once 'clases/Seguridad.php';

$db = new DB();
$db->getConnection();

$adminNombre = 'admin';
$adminEmail = 'admin@example.com';
$adminPassword = 'Admin@123'; // Una contraseña segura para el admin

// Sanitizar y hashear la contraseña
$passwordSanitizado = Seguridad::sanitizarEntrada($adminPassword);
$adminPasswordHash = password_hash($passwordSanitizado, PASSWORD_DEFAULT);

// Verificar si el correo ya existe
$checkQuery = "SELECT usu_id FROM Usuario WHERE usu_email = ?";
$stmtCheck = $db->query($checkQuery, [$adminEmail]);
$existingUser = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
    // Actualizar usuario existente a admin
    $query = "UPDATE Usuario SET usu_nombre = ?, usu_password_hash = ?, usu_rol = 'admin', usu_activo = TRUE WHERE usu_id = ?";
    $stmt = $db->updateSeguro($query, [$adminNombre, $adminPasswordHash, $existingUser['usu_id']]);
    if ($stmt->rowCount() > 0) {
        echo "Usuario existente actualizado a admin: " . $adminEmail . "<br>";
    } else {
        echo "Error al actualizar usuario existente a admin.<br>";
    }
} else {
    // Insertar nuevo usuario admin
    $query = "INSERT INTO Usuario (usu_nombre, usu_email, usu_password_hash, usu_rol, usu_activo) VALUES (?, ?, ?, 'admin', TRUE)";
    $stmt = $db->insertSeguro($query, [$adminNombre, $adminEmail, $adminPasswordHash]);
    if ($stmt->rowCount() > 0) {
        echo "Usuario admin creado exitosamente: " . $adminEmail . "<br>";
    } else {
        echo "Error al crear usuario admin.<br>";
    }
}

echo "Accede con Email: " . $adminEmail . " y Contraseña: " . $adminPassword . "<br>";
echo "\n<b>¡IMPORTANTE: Elimine este archivo (create_admin_temp.php) después de usarlo por seguridad!</b>";

?>
