<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar</title>
    <link rel="stylesheet" href="../proyecto-web-II/css/registrar.css" />
    <!-- Iconos de Bootstrap (para el ojito) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>

    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <header>Registrar</header>
        </div>

        <!-- Nombre -->
        <div class="input-box">
            <input type="text" class="input-field" placeholder="Nombre completo" autocomplete="off" required>
        </div>

        <!-- Correo -->
        <div class="input-box">
            <input type="email" class="input-field" placeholder="Correo electrónico" autocomplete="off" required>
        </div>

        <!-- Contraseña -->
        <div class="input-box">
            <input type="password" class="input-field" placeholder="Contraseña" id="password" autocomplete="off" required>
            <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
        </div>

        <!-- Confirmar Contraseña -->
        <div class="input-box">
            <input type="password" class="input-field" placeholder="Confirmar contraseña" id="confirmPassword" autocomplete="off" required>
            <i class="bi bi-eye-slash toggle-password" id="toggleConfirmPassword"></i>
        </div>

        <!-- Botón Registrar -->
        <div class="input-submit">
            <button class="submit-btn" id="submit"></button>
            <label for="submit">Registrar</label>
        </div>

        <!-- Link a Iniciar Sesión -->
        <div class="sign-up-link">
            <p>¿Ya tienes cuenta? <a href="index.php">Inicia Sesión</a></p>
        </div>
    </div>

    <script>
        // Función para alternar visibilidad de contraseñas
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

        // Llamar a la función para cada input
        togglePasswordVisibility("password", "togglePassword");
        togglePasswordVisibility("confirmPassword", "toggleConfirmPassword");
    </script>
</body>
</html>
