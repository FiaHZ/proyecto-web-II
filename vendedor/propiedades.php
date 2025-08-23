<?php
session_start();
require_once "../config/database.php";

// Verificar que sea vendedor
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "vendedor") {
    header("Location: ../index.php");
    exit;
}

$vendedor_id = $_SESSION["usuario_id"];
$mensaje = "";
$tipo_mensaje = "";

// Procesar acciones
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['crear_propiedad'])) {
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'];
        $destacada = isset($_POST['destacada']) ? 1 : 0;
        $titulo = $_POST['titulo'];
        $descripcion_breve = $_POST['descripcion_breve'];
        $descripcion_larga = $_POST['descripcion_larga'];
        $precio = $_POST['precio'];
        $ubicacion = $_POST['ubicacion'];
        $direccion_completa = $_POST['direccion_completa'];
        $mapa = $_POST['mapa'];
        $habitaciones = $_POST['habitaciones'];
        $banos = $_POST['banos'];
        $area_m2 = $_POST['area_m2'];
        $parqueos = $_POST['parqueos'];

        // Manejo de imagen
        $imagen_destacada = 'prop-dest1.png'; // Default
        if (isset($_FILES['imagen_destacada']) && $_FILES['imagen_destacada']['error'] == 0) {
            $extension = pathinfo($_FILES['imagen_destacada']['name'], PATHINFO_EXTENSION);
            $nombre_imagen = 'prop_' . time() . '.' . $extension;
            $ruta_destino = "../img/" . $nombre_imagen;

            if (move_uploaded_file($_FILES['imagen_destacada']['tmp_name'], $ruta_destino)) {
                $imagen_destacada = $nombre_imagen;
            }
        }

        $insert_query = "INSERT INTO propiedades (tipo, categoria, destacada, titulo, descripcion_breve, descripcion_larga, precio, ubicacion, direccion_completa, mapa, imagen_destacada, habitaciones, banos, area_m2, parqueos, vendedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ssisssdssssiiidi", $tipo, $categoria, $destacada, $titulo, $descripcion_breve, $descripcion_larga, $precio, $ubicacion, $direccion_completa, $mapa, $imagen_destacada, $habitaciones, $banos, $area_m2, $parqueos, $vendedor_id);

        if ($stmt->execute()) {
            $mensaje = "Propiedad creada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear la propiedad: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }

    if (isset($_POST['actualizar_estado'])) {
        $propiedad_id = $_POST['propiedad_id'];
        $nuevo_estado = $_POST['nuevo_estado'];

        $update_query = "UPDATE propiedades SET estado = ? WHERE id = ? AND vendedor_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $nuevo_estado, $propiedad_id, $vendedor_id);

        if ($stmt->execute()) {
            $mensaje = "Estado actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado";
            $tipo_mensaje = "danger";
        }
    }

    if (isset($_POST['eliminar_propiedad'])) {
        $propiedad_id = $_POST['propiedad_id'];

        $delete_query = "DELETE FROM propiedades WHERE id = ? AND vendedor_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("ii", $propiedad_id, $vendedor_id);

        if ($stmt->execute()) {
            $mensaje = "Propiedad eliminada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar la propiedad";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener propiedades del vendedor
$propiedades_query = "SELECT * FROM propiedades WHERE vendedor_id = ? ORDER BY fecha_creacion DESC";
$stmt = $conn->prepare($propiedades_query);
$stmt->bind_param("i", $vendedor_id);
$stmt->execute();
$propiedades_result = $stmt->get_result();

// Obtener estadísticas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN tipo = 'venta' THEN 1 ELSE 0 END) as ventas,
    SUM(CASE WHEN tipo = 'alquiler' THEN 1 ELSE 0 END) as alquileres,
    SUM(CASE WHEN destacada = 1 THEN 1 ELSE 0 END) as destacadas
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
    <title>Mis Propiedades - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/propiedadesvendedor.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid mb-2" style="max-height: 100px;">
                        <h5 class="text-white">Panel Vendedor</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Mis Propiedades
                        </a>
                        <a class="nav-link" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <hr class="my-3">
                        <a class="nav-link text-light" href="../index.php">
                            <i class="bi bi-house-door me-2"></i> Ver Sitio Web
                        </a>
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
                        <h2>Mis Propiedades</h2>
                        <p class="text-muted">Gestiona tus propiedades registradas</p>
                    </div>
                    <button class="btn btn-primary btn-custom" data-bs-toggle="modal"
                        data-bs-target="#crearPropiedadModal">
                        <i class="bi bi-plus-circle me-2"></i> Agregar Propiedad
                    </button>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i
                            class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 class="text-primary"><?= $stats['total'] ?></h3>
                            <p class="text-muted mb-0">Total Propiedades</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 class="text-success"><?= $stats['disponibles'] ?></h3>
                            <p class="text-muted mb-0">Disponibles</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 class="text-info"><?= $stats['ventas'] ?></h3>
                            <p class="text-muted mb-0">En Venta</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h3 class="text-warning"><?= $stats['destacadas'] ?></h3>
                            <p class="text-muted mb-0">Destacadas</p>
                        </div>
                    </div>
                </div>

                <!-- Lista de Propiedades -->
                <div class="row">
                    <?php if ($propiedades_result->num_rows > 0): ?>
                        <?php while ($propiedad = $propiedades_result->fetch_assoc()): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card property-card position-relative">
                                    <img src="../img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>"
                                        class="card-img-top property-image" alt="<?= htmlspecialchars($propiedad['titulo']) ?>">

                                    <span class="status-badge status-<?= $propiedad['estado'] ?>">
                                        <?= ucfirst($propiedad['estado']) ?>
                                    </span>

                                    <div class="price-badge">
                                        $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                                        <?= $propiedad['tipo'] == 'alquiler' ? '/mes' : '' ?>
                                    </div>

                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                                <?= ucfirst($propiedad['tipo']) ?>
                                            </span>
                                            <span class="badge bg-secondary">
                                                <?= ucfirst($propiedad['categoria']) ?>
                                            </span>
                                            <?php if ($propiedad['destacada']): ?>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-star-fill"></i> Destacada
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h5 class="card-title"><?= htmlspecialchars($propiedad['titulo']) ?></h5>

                                        <p class="card-text text-muted small">
                                            <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...
                                        </p>

                                        <div class="row text-center mb-3">
                                            <div class="col-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-house"></i><br>
                                                    <?= $propiedad['habitaciones'] ?> hab
                                                </small>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-droplet"></i><br>
                                                    <?= $propiedad['banos'] ?> baños
                                                </small>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-arrows-fullscreen"></i><br>
                                                    <?= $propiedad['area_m2'] ?? 'N/A' ?> m²
                                                </small>
                                            </div>
                                            <div class="col-3">
                                                <small class="text-muted">
                                                    <i class="bi bi-car-front"></i><br>
                                                    <?= $propiedad['parqueos'] ?> park
                                                </small>
                                            </div>
                                        </div>

                                        <div class="border-top pt-3">
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i>
                                                <?= date('d/m/Y', strtotime($propiedad['fecha_creacion'])) ?>
                                            </small>
                                        </div>

                                        <div class="d-flex gap-2 mt-3">
                                            <a href="../detalle_propiedad.php?id=<?= $propiedad['id'] ?>"
                                                class="btn btn-outline-primary btn-sm flex-fill" target="_blank">
                                                <i class="bi bi-eye"></i> Ver
                                            </a>
                                            <a href="editar_propiedad.php?id=<?= $propiedad['id'] ?>"
                                                class="btn btn-outline-warning btn-sm flex-fill">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <div class="dropdown flex-fill">
                                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100"
                                                    type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <h6 class="dropdown-header">Cambiar Estado:</h6>
                                                    </li>
                                                    <?php
                                                    $estados = ['disponible', 'reservada', 'vendida', 'alquilada', 'inactiva'];
                                                    foreach ($estados as $estado):
                                                        if ($estado !== $propiedad['estado']):
                                                            ?>
                                                            <li>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="propiedad_id"
                                                                        value="<?= $propiedad['id'] ?>">
                                                                    <input type="hidden" name="nuevo_estado" value="<?= $estado ?>">
                                                                    <button type="submit" name="actualizar_estado"
                                                                        class="dropdown-item">
                                                                        <i
                                                                            class="bi bi-circle-fill me-2 text-<?= $estado == 'disponible' ? 'success' : ($estado == 'reservada' ? 'warning' : ($estado == 'vendida' ? 'danger' : ($estado == 'alquilada' ? 'info' : 'secondary'))) ?>"></i>
                                                                        <?= ucfirst($estado) ?>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        <?php
                                                        endif;
                                                    endforeach;
                                                    ?>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li>
                                                        <button class="dropdown-item text-danger"
                                                            onclick="confirmarEliminar(<?= $propiedad['id'] ?>, '<?= htmlspecialchars($propiedad['titulo']) ?>')">
                                                            <i class="bi bi-trash me-2"></i>Eliminar
                                                        </button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card text-center">
                                <div class="card-body py-5">
                                    <i class="bi bi-house-x display-1 text-muted"></i>
                                    <h4 class="mt-3">No tienes propiedades registradas</h4>
                                    <p class="text-muted">Comienza agregando tu primera propiedad</p>
                                    <button class="btn btn-primary btn-custom" data-bs-toggle="modal"
                                        data-bs-target="#crearPropiedadModal">
                                        <i class="bi bi-plus-circle me-2"></i>Agregar Primera Propiedad
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Propiedad -->
    <div class="modal fade" id="crearPropiedadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Agregar Nueva Propiedad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Propiedad *</label>
                                    <select class="form-select" name="tipo" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="venta">Venta</option>
                                        <option value="alquiler">Alquiler</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría *</label>
                                    <select class="form-select" name="categoria" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="casa">Casa</option>
                                        <option value="apartamento">Apartamento</option>
                                        <option value="local">Local</option>
                                        <option value="terreno">Terreno</option>
                                        <option value="oficina">Oficina</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="titulo" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Breve *</label>
                            <textarea class="form-control" name="descripcion_breve" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Completa</label>
                            <textarea class="form-control" name="descripcion_larga" rows="5"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio *</label>
                                    <input type="number" class="form-control" name="precio" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ubicación *</label>
                                    <input type="text" class="form-control" name="ubicacion" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección Completa</label>
                            <textarea class="form-control" name="direccion_completa" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace de Mapa (Google Maps)</label>
                            <input type="url" class="form-control" name="mapa">
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Habitaciones</label>
                                    <input type="number" class="form-control" name="habitaciones" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Baños</label>
                                    <input type="number" class="form-control" name="banos" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Área (m²)</label>
                                    <input type="number" class="form-control" name="area_m2" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Parqueos</label>
                                    <input type="number" class="form-control" name="parqueos" value="0">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imagen Destacada</label>
                            <input type="file" class="form-control" name="imagen_destacada" accept="image/*">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="destacada" id="destacada">
                            <label class="form-check-label" for="destacada">
                                Marcar como propiedad destacada
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="crear_propiedad" class="btn btn-primary">Crear Propiedad</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="eliminarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Confirmar
                        Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la propiedad <strong id="propiedadNombre"></strong>?</p>
                    <div class="alert alert-warning">
                        <small><i class="bi bi-exclamation-triangle me-1"></i> Esta acción no se puede deshacer.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" id="eliminarId" name="propiedad_id">
                        <button type="submit" name="eliminar_propiedad" class="btn btn-danger">Eliminar
                            Propiedad</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmarEliminar(id, nombre) {
            document.getElementById('eliminarId').value = id;
            document.getElementById('propiedadNombre').textContent = nombre;

            const modal = new bootstrap.Modal(document.getElementById('eliminarModal'));
            modal.show();
        }

        // Auto-cerrar alertas después de 5 segundos
        setTimeout(function () {
            const alert = document.querySelector('.alert-dismissible');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>

</html>