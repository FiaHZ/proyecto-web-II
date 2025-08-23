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
    if (isset($_POST['cambiar_estado'])) {
        $id = $_POST['propiedad_id'];
        $nuevo_estado = $_POST['nuevo_estado'];

        $update_query = "UPDATE propiedades SET estado = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $nuevo_estado, $id);
        
        if ($stmt->execute()) {
            $mensaje = "Estado de la propiedad actualizado exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al actualizar el estado";
            $tipo_mensaje = "danger";
        }
    }

    if (isset($_POST['eliminar_propiedad'])) {
        $id = $_POST['propiedad_id'];
        
        $delete_query = "DELETE FROM propiedades WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $mensaje = "Propiedad eliminada exitosamente";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al eliminar la propiedad";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener filtros
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$buscar = $_GET['buscar'] ?? '';

// Construir query con filtros
$where_conditions = [];
$params = [];
$types = '';

if (!empty($filtro_tipo)) {
    $where_conditions[] = "p.tipo = ?";
    $params[] = $filtro_tipo;
    $types .= 's';
}

if (!empty($filtro_categoria)) {
    $where_conditions[] = "p.categoria = ?";
    $params[] = $filtro_categoria;
    $types .= 's';
}

if (!empty($filtro_estado)) {
    $where_conditions[] = "p.estado = ?";
    $params[] = $filtro_estado;
    $types .= 's';
}

if (!empty($buscar)) {
    $where_conditions[] = "(p.titulo LIKE ? OR p.descripcion_breve LIKE ? OR p.ubicacion LIKE ?)";
    $buscar_param = "%$buscar%";
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $params[] = $buscar_param;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$propiedades_query = "
    SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono, u.correo as vendedor_correo 
    FROM propiedades p 
    LEFT JOIN usuarios u ON p.vendedor_id = u.id 
    $where_clause 
    ORDER BY p.fecha_creacion DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($propiedades_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $propiedades_result = $stmt->get_result();
} else {
    $propiedades_result = $conn->query($propiedades_query);
}

// Obtener vendedores para el select
$vendedores_query = "SELECT id, nombre FROM usuarios WHERE rol = 'vendedor' AND estado = 'activo'";
$vendedores_result = $conn->query($vendedores_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Propiedades - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/propiedades.css">
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
                        <a class="nav-link" href="usuarios.php">
                            <i class="bi bi-people me-2"></i> Gestión de Usuarios
                        </a>
                        <a class="nav-link active" href="propiedades.php">
                            <i class="bi bi-house me-2"></i> Propiedades
                        </a>
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
                        <h2>Gestión de Propiedades</h2>
                        <p class="text-muted">Administra todas las propiedades del sistema</p>
                    </div>
                    <a href="../index.php" class="btn btn-outline-primary btn-custom">
                        <i class="bi bi-eye me-2"></i> Ver Sitio Web
                    </a>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="filter-card">
                    <h5 class="mb-3"><i class="bi bi-funnel me-2"></i> Filtros</h5>
                    <form method="GET" class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select">
                                <option value="">Todos</option>
                                <option value="venta" <?= $filtro_tipo == 'venta' ? 'selected' : '' ?>>Venta</option>
                                <option value="alquiler" <?= $filtro_tipo == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select">
                                <option value="">Todas</option>
                                <option value="casa" <?= $filtro_categoria == 'casa' ? 'selected' : '' ?>>Casa</option>
                                <option value="apartamento" <?= $filtro_categoria == 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                                <option value="local" <?= $filtro_categoria == 'local' ? 'selected' : '' ?>>Local</option>
                                <option value="terreno" <?= $filtro_categoria == 'terreno' ? 'selected' : '' ?>>Terreno</option>
                                <option value="oficina" <?= $filtro_categoria == 'oficina' ? 'selected' : '' ?>>Oficina</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="">Todos</option>
                                <option value="disponible" <?= $filtro_estado == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="reservada" <?= $filtro_estado == 'reservada' ? 'selected' : '' ?>>Reservada</option>
                                <option value="vendida" <?= $filtro_estado == 'vendida' ? 'selected' : '' ?>>Vendida</option>
                                <option value="alquilada" <?= $filtro_estado == 'alquilada' ? 'selected' : '' ?>>Alquilada</option>
                                <option value="inactiva" <?= $filtro_estado == 'inactiva' ? 'selected' : '' ?>>Inactiva</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="text" name="buscar" class="form-control" placeholder="Título, descripción, ubicación..." value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-custom">
                                    <i class="bi bi-search"></i> Buscar
                                </button>
                                <a href="propiedades.php" class="btn btn-outline-secondary btn-custom">
                                    <i class="bi bi-x"></i> Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <?php
                    $stats = [];
                    $stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
                        SUM(CASE WHEN tipo = 'venta' THEN 1 ELSE 0 END) as ventas,
                        SUM(CASE WHEN tipo = 'alquiler' THEN 1 ELSE 0 END) as alquileres
                        FROM propiedades";
                    $stats_result = $conn->query($stats_query);
                    $stats = $stats_result->fetch_assoc();
                    ?>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <h3 class="text-primary"><?= $stats['total'] ?></h3>
                                <p class="text-muted mb-0">Total Propiedades</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <h3 class="text-success"><?= $stats['disponibles'] ?></h3>
                                <p class="text-muted mb-0">Disponibles</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <h3 class="text-info"><?= $stats['ventas'] ?></h3>
                                <p class="text-muted mb-0">En Venta</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center border-0 shadow-sm">
                            <div class="card-body">
                                <h3 class="text-warning"><?= $stats['alquileres'] ?></h3>
                                <p class="text-muted mb-0">En Alquiler</p>
                            </div>
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
                                         class="card-img-top property-image" 
                                         alt="<?= htmlspecialchars($propiedad['titulo']) ?>">
                                    
                                    <span class="status-badge status-<?= $propiedad['estado'] ?>">
                                        <?= ucfirst($propiedad['estado']) ?>
                                    </span>
                                    
                                    <div class="price-badge">
                                        $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                                        <?= $propiedad['tipo'] == 'alquiler' ? '/mes' : '' ?>
                                    </div>

                                    <div class="property-actions">
                                        <div class="dropdown">
                                            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="../detalle_propiedad.php?id=<?= $propiedad['id'] ?>" target="_blank">
                                                    <i class="bi bi-eye me-2"></i>Ver Detalles
                                                </a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><h6 class="dropdown-header">Cambiar Estado:</h6></li>
                                                <?php
                                                $estados = ['disponible', 'reservada', 'vendida', 'alquilada', 'inactiva'];
                                                foreach ($estados as $estado):
                                                    if ($estado !== $propiedad['estado']):
                                                ?>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="propiedad_id" value="<?= $propiedad['id'] ?>">
                                                        <input type="hidden" name="nuevo_estado" value="<?= $estado ?>">
                                                        <button type="submit" name="cambiar_estado" class="dropdown-item">
                                                            <i class="bi bi-circle-fill me-2 text-<?= $estado == 'disponible' ? 'success' : ($estado == 'reservada' ? 'warning' : ($estado == 'vendida' ? 'danger' : ($estado == 'alquilada' ? 'info' : 'secondary'))) ?>"></i>
                                                            <?= ucfirst($estado) ?>
                                                        </button>
                                                    </form>
                                                </li>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-danger" onclick="confirmarEliminar(<?= $propiedad['id'] ?>, '<?= htmlspecialchars($propiedad['titulo']) ?>')">
                                                        <i class="bi bi-trash me-2"></i>Eliminar
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
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
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($propiedad['vendedor_nombre']) ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($propiedad['fecha_creacion'])) ?>
                                            </small>
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
                                    <h4 class="mt-3">No se encontraron propiedades</h4>
                                    <p class="text-muted">No hay propiedades que coincidan con los filtros aplicados.</p>
                                    <a href="propiedades.php" class="btn btn-primary btn-custom">Ver todas las propiedades</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div class="modal fade" id="eliminarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro de que desea eliminar la propiedad <strong id="propiedadNombre"></strong>?</p>
                    <div class="alert alert-warning">
                        <small><i class="bi bi-exclamation-triangle me-1"></i> Esta acción no se puede deshacer y también eliminará todas las imágenes y reservas asociadas.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" id="eliminarId" name="propiedad_id">
                        <button type="submit" name="eliminar_propiedad" class="btn btn-danger">Eliminar Propiedad</button>
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
        setTimeout(function() {
            const alert = document.querySelector('.alert-dismissible');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    </script>
</body>
</html>