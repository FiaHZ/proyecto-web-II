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
        $vendedor_id = $_SESSION["usuario_id"];

        $imagen_destacada = "";
        if (isset($_FILES['imagen_destacada']) && $_FILES['imagen_destacada']['error'] == 0) {
            $extension = pathinfo($_FILES['imagen_destacada']['name'], PATHINFO_EXTENSION);
            $imagen_destacada = "propiedad_" . time() . "." . $extension;
            move_uploaded_file($_FILES['imagen_destacada']['tmp_name'], "../img/" . $imagen_destacada);
        }

        $insert_query = "INSERT INTO propiedades (tipo, categoria, destacada, titulo, descripcion_breve, descripcion_larga, precio, ubicacion, direccion_completa, mapa, imagen_destacada, habitaciones, banos, area_m2, parqueos, vendedor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ssisssdssssiidii", $tipo, $categoria, $destacada, $titulo, $descripcion_breve, $descripcion_larga, $precio, $ubicacion, $direccion_completa, $mapa, $imagen_destacada, $habitaciones, $banos, $area_m2, $parqueos, $vendedor_id);
        
        if ($insert_stmt->execute()) {
            $mensaje = "Propiedad creada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al crear propiedad: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }

    if (isset($_POST['actualizar_propiedad'])) {
        $id = $_POST['id'];
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

        $update_query = "UPDATE propiedades SET tipo = ?, categoria = ?, destacada = ?, titulo = ?, descripcion_breve = ?, descripcion_larga = ?, precio = ?, ubicacion = ?, direccion_completa = ?, mapa = ?, habitaciones = ?, banos = ?, area_m2 = ?, parqueos = ? WHERE id = ? AND vendedor_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("ssisssdsssiiidii", $tipo, $categoria, $destacada, $titulo, $descripcion_breve, $descripcion_larga, $precio, $ubicacion, $direccion_completa, $mapa, $habitaciones, $banos, $area_m2, $parqueos, $id, $_SESSION["usuario_id"]);
        
        // Manejo de imagen si se subió una nueva
        if (isset($_FILES['imagen_destacada']) && $_FILES['imagen_destacada']['error'] == 0) {
            $extension = pathinfo($_FILES['imagen_destacada']['name'], PATHINFO_EXTENSION);
            $imagen_destacada = "propiedad_" . time() . "." . $extension;
            move_uploaded_file($_FILES['imagen_destacada']['tmp_name'], "../img/" . $imagen_destacada);
            
            $update_img_query = "UPDATE propiedades SET imagen_destacada = ? WHERE id = ? AND vendedor_id = ?";
            $update_img_stmt = $conn->prepare($update_img_query);
            $update_img_stmt->bind_param("sii", $imagen_destacada, $id, $_SESSION["usuario_id"]);
            $update_img_stmt->execute();
        }
        
        if ($update_stmt->execute()) {
            $mensaje = "Propiedad actualizada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar propiedad";
            $tipo_mensaje = "danger";
        }
    }

    if (isset($_POST['eliminar_propiedad'])) {
        $id = $_POST['id'];
        
        $delete_query = "DELETE FROM propiedades WHERE id = ? AND vendedor_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $id, $_SESSION["usuario_id"]);
        
        if ($delete_stmt->execute()) {
            $mensaje = "Propiedad eliminada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar propiedad";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener propiedades del vendedor
$propiedades_query = "SELECT * FROM propiedades WHERE vendedor_id = ? ORDER BY fecha_creacion DESC";
$propiedades_stmt = $conn->prepare($propiedades_query);
$propiedades_stmt->bind_param("i", $_SESSION["usuario_id"]);
$propiedades_stmt->execute();
$propiedades_result = $propiedades_stmt->get_result();

// Estadísticas del vendedor
$stats_query = "SELECT 
    COUNT(*) as total_propiedades,
    COUNT(CASE WHEN tipo = 'venta' THEN 1 END) as total_ventas,
    COUNT(CASE WHEN tipo = 'alquiler' THEN 1 END) as total_alquileres,
    COUNT(CASE WHEN destacada = 1 THEN 1 END) as total_destacadas
FROM propiedades WHERE vendedor_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $_SESSION["usuario_id"]);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Agente de Ventas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../proyecto-web-II/css/dashboardvendedor.css">
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Mis Propiedades
                        </a>
                        <a class="nav-link" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <hr class="my-3">
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
                        <h2>Panel de Agente de Ventas</h2>
                        <p class="text-muted">Gestiona tus propiedades y perfil</p>
                    </div>
                    <div>
                        <button class="btn btn-primary btn-custom me-2" data-bs-toggle="modal" data-bs-target="#crearPropiedadModal">
                            <i class="bi bi-plus-circle me-2"></i> Nueva Propiedad
                        </button>
                        <button class="btn btn-outline-primary btn-custom" onclick="location.href='../index.php'">
                            <i class="bi bi-house me-2"></i> Ver Sitio Web
                        </button>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card primary text-center">
                            <i class="bi bi-house-door text-primary" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Total Propiedades</h5>
                            <h3 class="text-primary"><?= $stats['total_propiedades'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card success text-center">
                            <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">En Venta</h5>
                            <h3 class="text-success"><?= $stats['total_ventas'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card secondary text-center">
                            <i class="bi bi-key text-info" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">En Alquiler</h5>
                            <h3 class="text-info"><?= $stats['total_alquileres'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stats-card warning text-center">
                            <i class="bi bi-star text-warning" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Destacadas</h5>
                            <h3 class="text-warning"><?= $stats['total_destacadas'] ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Lista de Propiedades -->
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-3">Mis Propiedades Recientes</h4>
                        <?php if ($propiedades_result->num_rows > 0): ?>
                            <?php while ($propiedad = $propiedades_result->fetch_assoc()): ?>
                            <div class="property-card">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($propiedad['imagen_destacada']): ?>
                                            <img src="../img/<?= $propiedad['imagen_destacada'] ?>" alt="Propiedad" class="property-image">
                                        <?php else: ?>
                                            <div class="property-image d-flex align-items-center justify-content-center bg-light">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="mb-1">
                                            <?= htmlspecialchars($propiedad['titulo']) ?>
                                            <?php if ($propiedad['destacada']): ?>
                                                <span class="badge bg-warning ms-2">Destacada</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-geo-alt me-1"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                                        </p>
                                        <p class="mb-1">
                                            <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                                <?= ucfirst($propiedad['tipo']) ?>
                                            </span>
                                            <small class="text-muted ms-2"><?= ucfirst($propiedad['categoria']) ?></small>
                                        </p>
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <h5 class="text-primary mb-0">
                                            $<?= number_format($propiedad['precio'], 0) ?>
                                        </h5>
                                        <small class="text-muted">
                                            <?= $propiedad['tipo'] == 'venta' ? '' : '/mes' ?>
                                        </small>
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-sm btn-outline-primary btn-custom mb-1 w-100" 
                                                onclick="editarPropiedad(<?= $propiedad['id'] ?>)">
                                            <i class="bi bi-pencil"></i> Editar
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-custom w-100" 
                                                onclick="eliminarPropiedad(<?= $propiedad['id'] ?>, '<?= htmlspecialchars($propiedad['titulo']) ?>')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-house-door text-muted" style="font-size: 4rem;"></i>
                                <h4 class="text-muted mt-3">No tienes propiedades aún</h4>
                                <p class="text-muted">Comienza agregando tu primera propiedad</p>
                                <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#crearPropiedadModal">
                                    <i class="bi bi-plus-circle me-2"></i> Crear Primera Propiedad
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Crear Propiedad -->
    <div class="modal fade" id="crearPropiedadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i> Nueva Propiedad</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo" class="form-label">Tipo</label>
                                    <select class="form-select" id="tipo" name="tipo" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="venta">Venta</option>
                                        <option value="alquiler">Alquiler</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="casa">Casa</option>
                                        <option value="apartamento">Apartamento</option>
                                        <option value="local">Local Comercial</option>
                                        <option value="terreno">Terreno</option>
                                        <option value="oficina">Oficina</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion_breve" class="form-label">Descripción Breve</label>
                            <textarea class="form-control" id="descripcion_breve" name="descripcion_breve" rows="2" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion_larga" class="form-label">Descripción Completa</label>
                            <textarea class="form-control" id="descripcion_larga" name="descripcion_larga" rows="4" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="precio" class="form-label">Precio</label>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="area_m2" class="form-label">Área (m²)</label>
                                    <input type="number" step="0.01" class="form-control" id="area_m2" name="area_m2">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="habitaciones" class="form-label">Habitaciones</label>
                                    <input type="number" class="form-control" id="habitaciones" name="habitaciones">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="banos" class="form-label">Baños</label>
                                    <input type="number" class="form-control" id="banos" name="banos">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="parqueos" class="form-label">Parqueos</label>
                                    <input type="number" class="form-control" id="parqueos" name="parqueos">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="direccion_completa" class="form-label">Dirección Completa</label>
                            <textarea class="form-control" id="direccion_completa" name="direccion_completa" rows="2"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mapa" class="form-label">Enlace de Mapa (Google Maps, etc.)</label>
                            <input type="text" class="form-control" id="mapa" name="mapa">
                        </div>
                        
                        <div class="mb-3">
                            <label for="imagen_destacada" class="form-label">Imagen Destacada</label>
                            <input type="file" class="form-control" id="imagen_destacada" name="imagen_destacada" accept="image/*">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="destacada" name="destacada">
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

    <!-- Modal Eliminar Propiedad -->
    <div class="modal fade" id="eliminarPropiedadModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" id="delete_prop_id" name="id">
                    <div class="modal-body">
                        <p>¿Está seguro de que desea eliminar la propiedad <strong id="delete_prop_titulo"></strong>?</p>
                        <div class="alert alert-warning">
                            <small><i class="bi bi-exclamation-triangle me-1"></i> Esta acción no se puede deshacer.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="eliminar_propiedad" class="btn btn-danger">Eliminar Propiedad</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function eliminarPropiedad(id, titulo) {
            document.getElementById('delete_prop_id').value = id;
            document.getElementById('delete_prop_titulo').textContent = titulo;
            
            const modal = new bootstrap.Modal(document.getElementById('eliminarPropiedadModal'));
            modal.show();
        }

        function editarPropiedad(id) {
            // Por simplicidad, redirigir a una página de edición
            window.location.href = `editar_propiedad.php?id=${id}`;
        }
    </script>
</body>
</html>