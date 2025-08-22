<?php
session_start();
require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST["correo"];
    $pass   = $_POST["contrasena"];

    $sql = "SELECT * FROM usuarios WHERE correo=? OR usuario=? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $correo, $correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // Comparar con md5 (tu DB usa md5 para las contraseñas)
        if ($user["contrasena"] === md5($pass)) {
            $_SESSION["usuario_id"] = $user["id"];
            $_SESSION["usuario_nombre"] = $user["nombre"];
            $_SESSION["usuario_rol"] = $user["rol"];

            header("Location: pag-princ.php");
            exit;
        } else {
            $error = "Contraseña incorrecta";
        }
    } else {
        $error = "Usuario no encontrado";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../proyecto-web-II/css/login.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <header>Inicio de Sesión</header>
        </div>

        <form method="POST" action="">
            <div class="input-box">
                <input type="text" name="correo" class="input-field" placeholder="Correo o usuario" autocomplete="off" required>
            </div>

            <div class="input-box">
                <input type="password" name="contrasena" class="input-field" placeholder="Contraseña" id="password" autocomplete="off" required>
                 <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
            </div>

            <?php if ($error): ?>
                <p style="color:red; text-align:center;"><?= $error ?></p>
            <?php endif; ?>

            <div class="input-submit">
                <button class="submit-btn" type="submit"></button>
                <label for="submit">Iniciar Sesión</label>
            </div>
        </form>

        <div class="sign-up-link">
            <p>¿No tiene una cuenta? <a href="registrar.php">Regístrese</a></p>
        </div>
    </div>

      <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", () => {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                togglePassword.classList.remove("bi-eye-slash");
                togglePassword.classList.add("bi-eye");
            } else {
                passwordInput.type = "password";
                togglePassword.classList.remove("bi-eye");
                togglePassword.classList.add("bi-eye-slash");
            }
        });
    </script>
</body>
</html>
