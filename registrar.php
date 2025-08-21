<?php
require_once "config/database.php";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $correo = $_POST["correo"];
    $pass   = $_POST["contrasena"];
    $confirm= $_POST["confirmar"];

    if ($pass !== $confirm) {
        $msg = "Las contraseñas no coinciden";
    } else {
        $passHash = md5($pass); // igual que en la BD

        $sql = "INSERT INTO usuarios (nombre, correo, usuario, contrasena, rol) VALUES (?,?,?,?, 'cliente')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nombre, $correo, $correo, $passHash);

        if ($stmt->execute()) {
            $msg = "Usuario registrado con éxito. Ahora puede iniciar sesión.";
        } else {
            $msg = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar</title>
    <link rel="stylesheet" href="../proyecto-web-II/css/registrar.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <header>Registrar</header>
        </div>

        <form method="POST" action="">
            <div class="input-box">
                <input type="text" name="nombre" class="input-field" placeholder="Nombre completo" autocomplete="off" required>
            </div>

            <div class="input-box">
                <input type="email" name="correo" class="input-field" placeholder="Correo electrónico" autocomplete="off" required>
            </div>

            <div class="input-box">
                <input type="password" name="contrasena" class="input-field" placeholder="Contraseña" id="password" required>
                <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
            </div>

            <div class="input-box">
                <input type="password" name="confirmar" class="input-field" placeholder="Confirmar contraseña" id="confirmPassword" required>
                <i class="bi bi-eye-slash toggle-password" id="toggleConfirmPassword"></i>
            </div>

            <?php if ($msg): ?>
                <p style="color:red; text-align:center;"><?= $msg ?></p>
            <?php endif; ?>

            <div class="input-submit">
                <button class="submit-btn" type="submit"></button>
                <label for="submit">Registrar</label>
            </div>
        </form>

        <div class="sign-up-link">
            <p>¿Ya tienes cuenta? <a href="index.php">Inicia Sesión</a></p>
        </div>
    </div>
</body>
</html>
