<?php
session_start();
require_once "../config/database.php";

// Verificar que esté logueado
if (!isset($_SESSION["usuario_id"])) {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// Obtener datos del usuario actual
$user_query = "SELECT * FROM usuarios WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $_SESSION["usuario_id"]);
$user_stmt->execute();
$usuario = $user_stmt->get_result()->fetch_assoc();

// Procesar formulario de actualización
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['actualizar_perfil'])) {
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $correo = $_POST['correo'];
        $usuario_name = $_POST['usuario'];
        
        // Verificar que el correo/usuario no estén en uso por otro usuario
        $check_query = "SELECT id FROM usuarios WHERE (correo = ? OR usuario = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ssi", $correo, $usuario_name, $_SESSION["usuario_id"]);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $mensaje = "El correo o usuario ya está en uso por otro usuario";
            $tipo_mensaje = "danger";
        } else {
            $update_query = "UPDATE usuarios SET nombre = ?, telefono = ?, correo = ?, usuario = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssssi", $nombre, $telefono, $correo, $usuario_name, $_SESSION["usuario_id"]);
            
            if ($update_stmt->execute()) {
                $_SESSION["usuario_nombre"] = $nombre; // Actualizar la sesión
                $mensaje = "Perfil actualizado exitosamente";
                $tipo_mensaje = "success";
                
                // Recargar datos del usuario
                $user_stmt->execute();
                $usuario = $user_stmt->get_result()->fetch_assoc();
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "danger";
            }
        }
    }
    
    if (isset($_POST['cambiar_contrasena'])) {
        $contrasena_actual = md5($_POST['contrasena_actual']);
        $nueva_contrasena = $_POST['nueva_contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];
        
        // Verificar contraseña actual
        if ($usuario['contrasena'] !== $contrasena_actual) {
            $mensaje = "La contraseña actual no es correcta";
            $tipo_mensaje = "danger";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $mensaje = "Las contraseñas nuevas no coinciden";
            $tipo_mensaje = "danger";
        } elseif (strlen($nueva_contrasena) < 6) {
            $mensaje = "La nueva contraseña debe tener al menos 6 caracteres";
            $tipo_mensaje = "danger";
        } else {
            $nueva_contrasena_hash = md5($nueva_contrasena);
            $update_pass_query = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
            $update_pass_stmt = $conn->prepare($update_pass_query);
            $update_pass_stmt->bind_param("si", $nueva_contrasena_hash, $_SESSION["usuario_id"]);
            
            if ($update_pass_stmt->execute()) {
                $mensaje = "Contraseña cambiada exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al cambiar la contraseña";
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Determinar el layout según el rol
$is_admin = ($_SESSION["usuario_rol"] === "admin");
$is_vendedor = ($_SESSION["usuario_rol"] === "vendedor");
$sidebar_path = $is_admin ? "admin" : ($is_vendedor ? "vendedor" : "");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - <?= $is_admin ? 'Admin' : ($is_vendedor ? 'Vendedor' : 'Cliente') ?> Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../proyecto-web-II/css/perfil.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php if ($is_admin || $is_vendedor): ?>
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                        <h5 class="text-white"><?= $is_admin ? 'Admin' : 'Vendedor' ?> Panel</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <?php if ($is_admin): ?>
                        <a class="nav-link" href="personalizar.php">
                            <i class="bi bi-palette me-2"></i> Personalizar Página
                        </a>
                        <a class="nav-link" href="usuarios.php">
                            <i class="bi bi-people me-2"></i> Gestión de Usuarios
                        </a>
                        <a class="nav-link" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Propiedades
                        </a>
                        <a class="nav-link" href="mensajes.php">
                            <i class="bi bi-envelope me-2"></i> Mensajes
                        </a>
                        <?php else: ?>
                        <a class="nav-link" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Mis Propiedades
                        </a>
                        <?php endif; ?>
                        <a class="nav-link active" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <hr class="my-3">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            <?php endif; ?>

            <!-- Main Content -->
            <div class="<?= ($is_admin || $is_vendedor) ? 'col-md-9 col-lg-10' : 'col-12' ?> main-content">
                <?php if (!$is_admin && !$is_vendedor): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Mi Perfil</h2>
                        <p class="text-muted">Actualiza tu información personal</p>
                    </div>
                    <button class="btn btn-outline-primary btn-custom" onclick="location.href='index.php'">
                        <i class="bi bi-house me-2"></i> Volver al Inicio
                    </button>
                </div>
                <?php else: ?>
                <div class="mb-4">
                    <h2>Mi Perfil</h2>
                    <p class="text-muted">Actualiza tu información personal y configuraciones de cuenta</p>
                </div>
                <?php endif; ?>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Información Personal -->
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="bi bi-person me-2 text-primary"></i> Información Personal</h4>
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre Completo</label>
                                            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($usuario['telefono']) ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="correo" class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control" id="correo" name="correo" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="usuario" class="form-label">Usuario</label>
                                            <input type="text" class="form-control" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="actualizar_perfil" class="btn btn-primary btn-custom">
                                        <i class="bi bi-check-circle me-2"></i> Actualizar Información
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Cambiar Contraseña -->
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="bi bi-shield-lock me-2 text-warning"></i> Cambiar Contraseña</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="contrasena_actual" class="form-label">Contraseña Actual</label>
                                    <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                                            <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" minlength="6" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                                            <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" minlength="6" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="cambiar_contrasena" class="btn btn-warning btn-custom">
                                        <i class="bi bi-key me-2"></i> Cambiar Contraseña
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Información de la Cuenta -->
                        <div class="profile-section text-center">
                            <div class="profile-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <h5><?= htmlspecialchars($usuario['nombre']) ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($usuario['correo']) ?></p>
                            <span class="badge bg-<?= $usuario['rol'] == 'admin' ? 'danger' : ($usuario['rol'] == 'vendedor' ? 'warning' : 'success') ?> mb-3">
                                <?= ucfirst($usuario['rol']) ?>
                            </span>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Miembro desde:</small>
                                <small><?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?></small>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small class="text-muted">Última actualización:</small>
                                <small><?= date('d/m/Y', strtotime($usuario['fecha_actualizacion'])) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que las contraseñas coincidan
        document.getElementById('confirmar_contrasena').addEventListener('input', function() {
            const nuevaContrasena = document.getElementById('nueva_contrasena').value;
            const confirmarContrasena = this.value;
            
            if (nuevaContrasena !== confirmarContrasena) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>