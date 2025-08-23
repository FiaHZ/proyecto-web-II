<?php
session_start();
require_once "../config/database.php";

// Verificar que sea vendedor
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "vendedor") {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";
$vendedor_id = $_SESSION["usuario_id"];

// Obtener datos actuales del vendedor
$query = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $vendedor_id);
$stmt->execute();
$result = $stmt->get_result();
$vendedor = $result->fetch_assoc();

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['actualizar_perfil'])) {
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $correo = $_POST['correo'];
        $usuario = $_POST['usuario'];

        // Verificar que el correo/usuario no esté en uso por otro usuario
        $check_query = "SELECT id FROM usuarios WHERE (correo = ? OR usuario = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ssi", $correo, $usuario, $vendedor_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $mensaje = "El correo o nombre de usuario ya está en uso por otro usuario";
            $tipo_mensaje = "danger";
        } else {
            $update_query = "UPDATE usuarios SET nombre = ?, telefono = ?, correo = ?, usuario = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ssssi", $nombre, $telefono, $correo, $usuario, $vendedor_id);
            
            if ($update_stmt->execute()) {
                $_SESSION["usuario_nombre"] = $nombre;
                $mensaje = "Perfil actualizado exitosamente";
                $tipo_mensaje = "success";
                
                // Recargar datos
                $stmt->execute();
                $result = $stmt->get_result();
                $vendedor = $result->fetch_assoc();
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
        if ($vendedor['contrasena'] !== $contrasena_actual) {
            $mensaje = "La contraseña actual es incorrecta";
            $tipo_mensaje = "danger";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $mensaje = "Las nuevas contraseñas no coinciden";
            $tipo_mensaje = "danger";
        } elseif (strlen($nueva_contrasena) < 6) {
            $mensaje = "La nueva contraseña debe tener al menos 6 caracteres";
            $tipo_mensaje = "danger";
        } else {
            $nueva_contrasena_hash = md5($nueva_contrasena);
            $update_pass_query = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
            $update_pass_stmt = $conn->prepare($update_pass_query);
            $update_pass_stmt->bind_param("si", $nueva_contrasena_hash, $vendedor_id);
            
            if ($update_pass_stmt->execute()) {
                $mensaje = "Contraseña actualizada exitosamente";
                $tipo_mensaje = "success";
                
                // Recargar datos
                $stmt->execute();
                $result = $stmt->get_result();
                $vendedor = $result->fetch_assoc();
            } else {
                $mensaje = "Error al actualizar la contraseña";
                $tipo_mensaje = "danger";
            }
        }
    }
}

// Obtener estadísticas del vendedor
$stats_query = "SELECT 
    COUNT(*) as total_propiedades,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'vendida' OR estado = 'alquilada' THEN 1 ELSE 0 END) as vendidas
    FROM propiedades WHERE vendedor_id = ?";
$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $vendedor_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                        <h5 class="text-white">Panel Vendedor</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Mis Propiedades
                        </a>
                        <a class="nav-link active" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <hr class="my-3">
                        <a class="nav-link text-light" href="../index.php">
                            <i class="bi bi-house-door me-2"></i> Ver Sitio Web
                        </a>
                        <hr class="my-3">
                        <a class="nav-link text-light" href="../index.php">
                            <i class="bi bi-house-door me-2"></i> Ver Sitio Web
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <h2><?= htmlspecialchars($vendedor['nombre']) ?></h2>
                    <p class="mb-0">Agente de Ventas</p>
                    <small>Miembro desde <?= date('d/m/Y', strtotime($vendedor['fecha_creacion'])) ?></small>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <i class="bi bi-house-door text-primary" style="font-size: 2rem;"></i>
                            <h3 class="text-primary mt-2"><?= $stats['total_propiedades'] ?></h3>
                            <p class="text-muted mb-0">Total Propiedades</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h3 class="text-success mt-2"><?= $stats['disponibles'] ?></h3>
                            <p class="text-muted mb-0">Disponibles</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-card">
                            <i class="bi bi-trophy text-warning" style="font-size: 2rem;"></i>
                            <h3 class="text-warning mt-2"><?= $stats['vendidas'] ?></h3>
                            <p class="text-muted mb-0">Vendidas/Alquiladas</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Actualizar Información Personal -->
                    <div class="col-lg-6">
                        <div class="form-section">
                            <h5 class="mb-4">
                                <i class="bi bi-person-gear me-2 text-primary"></i>
                                Información Personal
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Nombre Completo *</label>
                                    <input type="text" class="form-control" name="nombre" 
                                           value="<?= htmlspecialchars($vendedor['nombre']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nombre de Usuario *</label>
                                    <input type="text" class="form-control" name="usuario" 
                                           value="<?= htmlspecialchars($vendedor['usuario']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Teléfono *</label>
                                    <input type="text" class="form-control" name="telefono" 
                                           value="<?= htmlspecialchars($vendedor['telefono']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Correo Electrónico *</label>
                                    <input type="email" class="form-control" name="correo" 
                                           value="<?= htmlspecialchars($vendedor['correo']) ?>" required>
                                </div>
                                <button type="submit" name="actualizar_perfil" class="btn btn-primary btn-custom">
                                    <i class="bi bi-check-circle me-2"></i>Actualizar Información
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Cambiar Contraseña -->
                    <div class="col-lg-6">
                        <div class="form-section">
                            <h5 class="mb-4">
                                <i class="bi bi-shield-lock me-2 text-warning"></i>
                                Cambiar Contraseña
                            </h5>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Contraseña Actual *</label>
                                    <input type="password" class="form-control" name="contrasena_actual" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nueva Contraseña *</label>
                                    <input type="password" class="form-control" name="nueva_contrasena" 
                                           minlength="6" required>
                                    <small class="text-muted">Mínimo 6 caracteres</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirmar Nueva Contraseña *</label>
                                    <input type="password" class="form-control" name="confirmar_contrasena" 
                                           minlength="6" required>
                                </div>
                                <button type="submit" name="cambiar_contrasena" class="btn btn-warning btn-custom">
                                    <i class="bi bi-key me-2"></i>Cambiar Contraseña
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="form-section">
                    <h5 class="mb-4">
                        <i class="bi bi-info-circle me-2 text-info"></i>
                        Información de la Cuenta
                    </h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-muted">Estado de la Cuenta</h6>
                                <span class="badge bg-<?= $vendedor['estado'] == 'activo' ? 'success' : 'warning' ?> fs-6">
                                    <?= ucfirst($vendedor['estado']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-muted">Rol</h6>
                                <span class="badge bg-info fs-6">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <?= ucfirst($vendedor['rol']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-muted">Fecha de Registro</h6>
                                <span class="text-primary">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($vendedor['fecha_creacion'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 mb-3">
                                <h6 class="text-muted">Última Actualización</h6>
                                <span class="text-secondary">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('d/m/Y H:i', strtotime($vendedor['fecha_actualizacion'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="form-section">
                    <h5 class="mb-4">
                        <i class="bi bi-lightning me-2 text-warning"></i>
                        Acciones Rápidas
                    </h5>
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="propiedades.php" class="btn btn-outline-primary btn-custom w-100">
                                <i class="bi bi-house-gear me-2"></i>
                                <br>Gestionar Propiedades
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="propiedades.php?action=new" class="btn btn-outline-success btn-custom w-100">
                                <i class="bi bi-plus-circle me-2"></i>
                                <br>Agregar Propiedad
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="../index.php" class="btn btn-outline-info btn-custom w-100">
                                <i class="bi bi-eye me-2"></i>
                                <br>Ver Sitio Web
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-custom w-100">
                                <i class="bi bi-speedometer2 me-2"></i>
                                <br>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-cerrar alertas después de 5 segundos
        setTimeout(function() {
            const alert = document.querySelector('.alert-dismissible');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);

        // Validar que las contraseñas coincidan
        document.addEventListener('DOMContentLoaded', function() {
            const nuevaContrasena = document.querySelector('input[name="nueva_contrasena"]');
            const confirmarContrasena = document.querySelector('input[name="confirmar_contrasena"]');
            
            if (nuevaContrasena && confirmarContrasena) {
                confirmarContrasena.addEventListener('input', function() {
                    if (nuevaContrasena.value !== confirmarContrasena.value) {
                        confirmarContrasena.setCustomValidity('Las contraseñas no coinciden');
                    } else {
                        confirmarContrasena.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>