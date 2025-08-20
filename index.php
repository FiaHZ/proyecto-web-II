<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../proyecto-web-II/css/index.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <header>Inicio de Sesión</header>
        </div>

        <!-- Email -->
        <div class="input-box">
            <input type="text" class="input-field" placeholder="Correo" autocomplete="off" required>
        </div>

        <!-- Password con ojito -->
        <div class="input-box">
            <input type="password" class="input-field" placeholder="Contraseña" id="password" autocomplete="off" required>
            <i class="bi bi-eye-slash toggle-password" id="togglePassword"></i>
        </div>

        <!-- Opciones -->
        <div class="forgot">
            <section>
                <input type="checkbox" id="remember">
                <label for="remember">Recuerdame</label>
            </section>
            <section>
                <a href="#">Oolvidar Contraseña</a>
            </section>
        </div>

        <!-- Botón -->
        <div class="input-submit">
            <button class="submit-btn" id="submit"></button>
            <label for="submit">Iniciar Sesión</label>
        </div>

        <!-- Sign Up -->
        <div class="sign-up-link">
            <p>¿No tiene una cuenta? <a href="registrar.php">Regístrese</a></p>
        </div>
    </div>

    <!-- Script ojito -->
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
