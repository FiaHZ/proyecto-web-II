<?php
require_once "config/database.php";
$msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST["nombre"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $usuario = $_POST["usuario"] ?? $correo; // Usar usuario personalizado o correo como fallback
    $pass = $_POST["contrasena"];
    $confirm = $_POST["confirmar"];

    if ($pass !== $confirm) {
        $msg = "Las contraseñas no coinciden";
    } else {
        // Verificar que el correo o usuario no existan
        $check = "SELECT id FROM usuarios WHERE correo = ? OR usuario = ?";
        $stmt_check = $conn->prepare($check);
        $stmt_check->bind_param("ss", $correo, $usuario);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $msg = "El correo o nombre de usuario ya está registrado";
        } else {
            $passHash = md5($pass);

            $sql = "INSERT INTO usuarios (nombre, telefono, correo, usuario, contrasena, rol) VALUES (?,?,?,?,?, 'cliente')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $nombre, $telefono, $correo, $usuario, $passHash);

            if ($stmt->execute()) {
                $msg = "<span style='color: green;'>Usuario registrado con éxito. Ahora puede iniciar sesión.</span>";
            } else {
                $msg = "Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar</title>
    <link rel="stylesheet" href="css/registrar.css">
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
                <input type="text" name="usuario" class="input-field" placeholder="Nombre de usuario" autocomplete="off" required>
            </div>
            
            <div class="input-box">
                <input type="text" name="telefono" class="input-field" placeholder="Teléfono" autocomplete="off" required>
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
                <p style="text-align:center;"><?= $msg ?></p>
            <?php endif; ?>

            <div class="input-submit">
                <button class="submit-btn" type="submit"></button>
                <label for="submit">Registrar</label>
            </div>
        </form>

        <div class="sign-up-link">
            <p>¿Ya tienes cuenta? <a href="login.php">Inicia Sesión</a></p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            icon.addEventListener("click", () => {
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("bi-eye-slash");
                    icon.classList.add("bi-eye");
                } else {
                    input.type = "password";
                    icon.classList.remove("bi-eye");
                    icon.classList.add("bi-eye-slash");
                }
            });
        }

        togglePasswordVisibility("password", "togglePassword");
        togglePasswordVisibility("confirmPassword", "toggleConfirmPassword");
    </script>
</body>
</html>