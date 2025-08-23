<?php
session_start();
require_once "../config/database.php";

// Verificar que sea admin
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// Procesar acciones
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['crear_usuario'])) {
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $correo = $_POST['correo'];
        $usuario = $_POST['usuario'];
        $contrasena = md5($_POST['contrasena']);
        $rol = $_POST['rol'];

        // Verificar que el usuario/correo no exista
        $check_query = "SELECT id FROM usuarios WHERE usuario = ? OR correo = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $usuario, $correo);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $mensaje = "El usuario o correo ya existe";
            $tipo_mensaje = "danger";
        } else {
            $insert_query = "INSERT INTO usuarios (nombre, telefono, correo, usuario, contrasena, rol) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssssss", $nombre, $telefono, $correo, $usuario, $contrasena, $rol);
            
            if ($insert_stmt->execute()) {
                $mensaje = "Usuario creado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al crear usuario";
                $tipo_mensaje = "danger";
            }
        }
    }

    if (isset($_POST['actualizar_usuario'])) {
        $id = $_POST['id'];
        $nombre = $_POST['nombre'];
        $telefono = $_POST['telefono'];
        $correo = $_POST['correo'];
        $usuario = $_POST['usuario'];
        $rol = $_POST['rol'];

        // Verificar que el usuario/correo no exista en otro usuario
        $check_query = "SELECT id FROM usuarios WHERE (usuario = ? OR correo = ?) AND id != ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ssi", $usuario, $correo, $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $mensaje = "El usuario o correo ya existe en otro registro";
            $tipo_mensaje = "danger";
        } else {
            $update_query = "UPDATE usuarios SET nombre = ?, telefono = ?, correo = ?, usuario = ?, rol = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("sssssi", $nombre, $telefono, $correo, $usuario, $rol, $id);
            
            if ($update_stmt->execute()) {
                $mensaje = "Usuario actualizado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al actualizar usuario";
                $tipo_mensaje = "danger";
            }
        }
    }

    if (isset($_POST['eliminar_usuario'])) {
        $id = $_POST['id'];
        
        // No permitir eliminar el admin principal
        if ($id == 1) {
            $mensaje = "No se puede eliminar el administrador principal";
            $tipo_mensaje = "danger";
        } else {
            $delete_query = "DELETE FROM usuarios WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                $mensaje = "Usuario eliminado exitosamente";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al eliminar usuario";
                $tipo_mensaje = "danger";
            }
        }
    }

    if (isset($_POST['cambiar_contrasena'])) {
        $id = $_POST['id'];
        $nueva_contrasena = md5($_POST['nueva_contrasena']);

        $update_pass_query = "UPDATE usuarios SET contrasena = ? WHERE id = ?";
        $update_pass_stmt = $conn->prepare($update_pass_query);
        $update_pass_stmt->bind_param("si", $nueva_contrasena, $id);
        
        if ($update_pass_stmt->execute()) {
            $mensaje = "Contraseña actualizada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al cambiar contraseña";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener usuarios
$usuarios_query = "SELECT * FROM usuarios ORDER BY fecha_creacion DESC";
$usuarios_result = $conn->query($usuarios_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/usuarios.css">

</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 50px;">
                        <h5 class="text-white">Admin Panel</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="personalizar.php">
                            <i class="bi bi-palette me-2"></i> Personalizar Página
                        </a>
                        <a class="nav-link active" href="usuarios.php">
                            <i class="bi bi-people me-2"></i> Gestión de Usuarios
                        </a>
                        <a class="nav-link" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Propiedades
                        </a>
                        <a class="nav-link" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Gestión de Usuarios</h2>
                        <p class="text-muted">Crear, editar y eliminar usuarios del sistema</p>
                    </div>
                    <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                        <i class="bi bi-person-plus me-2"></i> Crear Usuario
                    </button>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabla de Usuarios -->
                <div class="users-table">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Correo</th>
                                    <th>Usuario</th>
                                    <th>Rol</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($usuario = $usuarios_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $usuario['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($usuario['nombre']) ?></strong></td>
                                    <td><?= htmlspecialchars($usuario['telefono']) ?></td>
                                    <td><?= htmlspecialchars($usuario['correo']) ?></td>
                                    <td><code><?= htmlspecialchars($usuario['usuario']) ?></code></td>
                                    <td>
                                        <span class="role-badge role-<?= $usuario['rol'] ?>">
                                            <?= ucfirst($usuario['rol']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($usuario['fecha_creacion'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-custom me-1" 
                                                onclick="editarUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre']) ?>', '<?= htmlspecialchars($usuario['telefono']) ?>', '<?= htmlspecialchars($usuario['correo']) ?>', '<?= htmlspecialchars($usuario['usuario']) ?>', '<?= $usuario['rol'] ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning btn-custom me-1" 
                                                onclick="cambiarContrasena(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre']) ?>')">
                                            <i class="bi bi-key"></i>
                                        </button>
                                        <?php if ($usuario['id'] != 1): ?>
                                        <button class="btn btn-sm btn-outline-danger btn-custom" 
                                                onclick="eliminarUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['nombre']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Usuario -->
    <div class="modal fade" id="crearUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i> Crear Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario" name="usuario" required>
                        </div>
                        <div class="mb-3">
                            <label for="contrasena" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="contrasena" name="contrasena" required>
                        </div>
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select" id="rol" name="rol" required>
                                <option value="">Seleccionar rol...</option>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Agente de Ventas</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_usuario" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuario -->
    <div class="modal fade" id="editarUsuarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i> Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="edit_telefono" name="telefono" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="edit_correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_usuario" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="edit_usuario" name="usuario" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_rol" class="form-label">Rol</label>
                            <select class="form-select" id="edit_rol" name="rol" required>
                                <option value="cliente">Cliente</option>
                                <option value="vendedor">Agente de Ventas</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="actualizar_usuario" class="btn btn-primary">Actualizar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cambiar Contraseña -->
    <div class="modal fade" id="cambiarContrasenaModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-key me-2"></i> Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="pass_id" name="id">
                    <div class="modal-body">
                        <p>Cambiar contraseña para: <strong id="pass_nombre"></strong></p>
                        <div class="mb-3">
                            <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="cambiar_contrasena" class="btn btn-warning">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="eliminarUsuarioModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="delete_id" name="id">
                    <div class="modal-body">
                        <p>¿Está seguro de que desea eliminar al usuario <strong id="delete_nombre"></strong>?</p>
                        <div class="alert alert-warning">
                            <small><i class="bi bi-exclamation-triangle me-1"></i> Esta acción no se puede deshacer.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="eliminar_usuario" class="btn btn-danger">Eliminar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(id, nombre, telefono, correo, usuario, rol) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nombre').value = nombre;
            document.getElementById('edit_telefono').value = telefono;
            document.getElementById('edit_correo').value = correo;
            document.getElementById('edit_usuario').value = usuario;
            document.getElementById('edit_rol').value = rol;
            
            const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
            modal.show();
        }

        function cambiarContrasena(id, nombre) {
            document.getElementById('pass_id').value = id;
            document.getElementById('pass_nombre').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('cambiarContrasenaModal'));
            modal.show();
        }

        function eliminarUsuario(id, nombre) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nombre').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('eliminarUsuarioModal'));
            modal.show();
        }
    </script>
</body>
</html>