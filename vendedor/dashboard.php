<?php
session_start();
require_once "../config/database.php";

// Verificar que sea vendedor
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "vendedor") {
    header("Location: ../index.php");
    exit;
}

$vendedor_id = $_SESSION["usuario_id"];

// Obtener estadísticas del vendedor
$stats_query = "SELECT 
    COUNT(*) as total_propiedades,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'vendida' OR estado = 'alquilada' THEN 1 ELSE 0 END) as vendidas,
    SUM(CASE WHEN destacada = 1 THEN 1 ELSE 0 END) as destacadas
    FROM propiedades WHERE vendedor_id = ?";
$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->bind_param("i", $vendedor_id);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result();
$stats = $stats_result->fetch_assoc();

// Obtener reservas pendientes
$reservas_query = "SELECT COUNT(*) as reservas_pendientes 
    FROM reservas r 
    INNER JOIN propiedades p ON r.propiedad_id = p.id 
    WHERE p.vendedor_id = ? AND r.estado = 'pendiente'";
$stmt_reservas = $conn->prepare($reservas_query);
$stmt_reservas->bind_param("i", $vendedor_id);
$stmt_reservas->execute();
$reservas_result = $stmt_reservas->get_result();
$reservas_stats = $reservas_result->fetch_assoc();

// Obtener propiedades recientes del vendedor
$propiedades_query = "SELECT * FROM propiedades WHERE vendedor_id = ? ORDER BY fecha_creacion DESC LIMIT 5";
$stmt_prop = $conn->prepare($propiedades_query);
$stmt_prop->bind_param("i", $vendedor_id);
$stmt_prop->execute();
$propiedades_result = $stmt_prop->get_result();

// Obtener reservas recientes
$reservas_recientes_query = "SELECT r.*, p.titulo as propiedad_titulo, p.precio, p.tipo 
    FROM reservas r 
    INNER JOIN propiedades p ON r.propiedad_id = p.id 
    WHERE p.vendedor_id = ? 
    ORDER BY r.fecha_reserva DESC LIMIT 5";
$stmt_res = $conn->prepare($reservas_recientes_query);
$stmt_res->bind_param("i", $vendedor_id);
$stmt_res->execute();
$reservas_recientes_result = $stmt_res->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Vendedor - Dashboard</title>
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
                        <a class="nav-link text-light" href="../index.php">
                            <i class="bi bi-house-door me-2"></i> Ver Sitio Web
                        </a>
                        <a class="nav-link text-warning" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Welcome Card -->
                <div class="welcome-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">¡Bienvenido, <?= $_SESSION["usuario_nombre"] ?>!</h2>
                            <p class="mb-0">Gestiona tus propiedades y reservas desde tu panel personalizado</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="bi bi-house-heart" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: linear-gradient(135deg, #007bff, #0056b3);">
                                <i class="bi bi-house"></i>
                            </div>
                            <h3 class="text-primary"><?= $stats['total_propiedades'] ?></h3>
                            <p class="text-muted mb-0">Total Propiedades</p>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3 class="text-success"><?= $stats['disponibles'] ?></h3>
                            <p class="text-muted mb-0">Disponibles</p>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107, #fd7e14);">
                                <i class="bi bi-star"></i>
                            </div>
                            <h3 class="text-warning"><?= $stats['destacadas'] ?></h3>
                            <p class="text-muted mb-0">Destacadas</p>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="stats-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <h3 class="text-danger"><?= $reservas_stats['reservas_pendientes'] ?></h3>
                            <p class="text-muted mb-0">Reservas Pendientes</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Propiedades Recientes -->
                    <div class="col-lg-7">
                        <div class="recent-card">
                            <div class="card-header bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="bi bi-house-door me-2 text-primary"></i>
                                        Mis Propiedades Recientes
                                    </h5>
                                    <a href="propiedades.php" class="btn btn-outline-primary btn-sm btn-custom">
                                        Ver todas
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if ($propiedades_result->num_rows > 0): ?>
                                    <?php while ($propiedad = $propiedades_result->fetch_assoc()): ?>
                                        <div class="property-mini-card p-3 mb-3 bg-light rounded">
                                            <div class="row align-items-center">
                                                <div class="col-md-2">
                                                    <img src="../img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" 
                                                         class="img-fluid rounded" alt="Propiedad">
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="mb-1"><?= htmlspecialchars($propiedad['titulo']) ?></h6>
                                                    <small class="text-muted">
                                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                                                    </small>
                                                    <br>
                                                    <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?> me-1">
                                                        <?= ucfirst($propiedad['tipo']) ?>
                                                    </span>
                                                    <span class="badge bg-<?= $propiedad['estado'] == 'disponible' ? 'success' : 'secondary' ?>">
                                                        <?= ucfirst($propiedad['estado']) ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-3 text-end">
                                                    <h6 class="text-primary mb-1">
                                                        $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y', strtotime($propiedad['fecha_creacion'])) ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-1">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                type="button" data-bs-toggle="dropdown">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="../detalle_propiedad.php?id=<?= $propiedad['id'] ?>" target="_blank">
                                                                <i class="bi bi-eye me-2"></i>Ver
                                                            </a></li>
                                                            <li><a class="dropdown-item" href="editar_propiedad.php?id=<?= $propiedad['id'] ?>">
                                                                <i class="bi bi-pencil me-2"></i>Editar
                                                            </a></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-house-x display-4 text-muted"></i>
                                        <p class="text-muted mt-2">No tienes propiedades registradas</p>
                                        <a href="propiedades.php" class="btn btn-primary btn-custom">
                                            <i class="bi bi-plus-circle me-2"></i>Agregar Primera Propiedad
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Reservas Recientes -->
                    <div class="col-lg-5">
                        <div class="recent-card">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="bi bi-calendar-check me-2 text-info"></i>
                                    Reservas Recientes
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($reservas_recientes_result->num_rows > 0): ?>
                                    <?php while ($reserva = $reservas_recientes_result->fetch_assoc()): ?>
                                        <div class="reservation-item">
                                            <h6 class="mb-1"><?= htmlspecialchars($reserva['propiedad_titulo']) ?></h6>
                                            <p class="mb-1 small text-muted">
                                                <strong>Interés:</strong> <?= ucfirst($reserva['tipo_interes']) ?>
                                                <span class="badge bg-<?= $reserva['estado'] == 'pendiente' ? 'warning' : 'success' ?> ms-2">
                                                    <?= ucfirst($reserva['estado']) ?>
                                                </span>
                                            </p>
                                            <p class="mb-1 small">
                                                <?= htmlspecialchars(substr($reserva['mensaje'], 0, 80)) ?>...
                                            </p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> <?= date('d/m/Y H:i', strtotime($reserva['fecha_reserva'])) ?>
                                            </small>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="bi bi-calendar-x display-4 text-muted"></i>
                                        <p class="text-muted mt-2">No tienes reservas pendientes</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="recent-card">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <i class="bi bi-lightning me-2 text-warning"></i>
                                    Acciones Rápidas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-3 mb-3">
                                        <a href="propiedades.php?action=new" class="btn btn-primary btn-custom w-100">
                                            <i class="bi bi-plus-circle me-2"></i>
                                            Agregar Propiedad
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="propiedades.php" class="btn btn-outline-primary btn-custom w-100">
                                            <i class="bi bi-house-gear me-2"></i>
                                            Gestionar Propiedades
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="perfil.php" class="btn btn-outline-secondary btn-custom w-100">
                                            <i class="bi bi-person-gear me-2"></i>
                                            Actualizar Perfil
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="../index.php" class="btn btn-outline-success btn-custom w-100">
                                            <i class="bi bi-eye me-2"></i>
                                            Ver Sitio Web
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>