<?php
session_start();
require_once "../config/database.php";

// Verificar que sea admin
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

// Obtener estadísticas
$stats_query = "SELECT * FROM vista_estadisticas_admin";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4 sidebar-logo">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid" style="max-height: 100px;">
                        <h5 class="text-white mt-2">Admin Panel</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="personalizar.php">
                            <i class="bi bi-palette me-2"></i> Personalizar Página
                        </a>
                        <a class="nav-link" href="usuarios.php">
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
                <!-- Header -->
                <div class="header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-0">Dashboard de Administración</h2>
                            <p class="text-muted mb-0">Resumen general del sistema</p>
                        </div>
                        <div>
                            <button class="btn btn-primary btn-custom" onclick="location.href='../index.php'">
                                <i class="bi bi-house me-2"></i> Ver Sitio Web
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: var(--primary-color);">
                                    <i class="bi bi-people"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Total Clientes</div>
                                    <div class="h4 mb-0"><?= $stats['total_clientes'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: var(--secondary-color);">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Agentes de Ventas</div>
                                    <div class="h4 mb-0"><?= $stats['total_vendedores'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: var(--success-color);">
                                    <i class="bi bi-house"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Propiedades Disponibles</div>
                                    <div class="h4 mb-0"><?= $stats['propiedades_disponibles'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="stats-card">
                            <div class="d-flex align-items-center">
                                <div class="stats-icon" style="background: var(--warning-color);">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                                <div class="ms-3">
                                    <div class="text-muted small">Reservas Pendientes</div>
                                    <div class="h4 mb-0"><?= $stats['reservas_pendientes'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="stats-card text-center">
                            <i class="bi bi-currency-dollar text-success" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Propiedades en Venta</h5>
                            <h3 class="text-success"><?= $stats['propiedades_venta'] ?></h3>
                            <small class="text-muted">Disponibles para compra</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card text-center">
                            <i class="bi bi-key text-info" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Propiedades en Alquiler</h5>
                            <h3 class="text-info"><?= $stats['propiedades_alquiler'] ?></h3>
                            <small class="text-muted">Disponibles para renta</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card text-center">
                            <i class="bi bi-person-plus text-warning" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Solicitudes Vendedor</h5>
                            <h3 class="text-warning"><?= $stats['solicitudes_vendedor_pendientes'] ?></h3>
                            <small class="text-muted">Pendientes de revisión</small>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">
                            <i class="bi bi-activity me-2"></i>Actividad Reciente
                        </h5>
                        <small class="text-muted">Últimos movimientos del sistema</small>
                    </div>
                    
                    <?php
                    // Obtener actividad reciente - propiedades
                    $activity_query = "
                        SELECT 'propiedad' as tipo, titulo as descripcion, fecha_creacion as fecha, 
                               CONCAT('Nueva propiedad agregada: ', titulo) as mensaje
                        FROM propiedades 
                        ORDER BY fecha_creacion DESC 
                        LIMIT 3
                    ";
                    $activity_result = $conn->query($activity_query);

                    $has_activity = false;
                    if ($activity_result && $activity_result->num_rows > 0):
                        $has_activity = true;
                        while ($activity = $activity_result->fetch_assoc()):
                    ?>
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="stats-icon" style="background: var(--success-color); width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="bi bi-house"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold"><?= htmlspecialchars($activity['mensaje']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($activity['fecha'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>

                    <?php
                    // Agregar actividad de usuarios nuevos
                    $users_query = "SELECT nombre, fecha_creacion FROM usuarios WHERE rol = 'cliente' ORDER BY fecha_creacion DESC LIMIT 2";
                    $users_result = $conn->query($users_query);
                    if ($users_result && $users_result->num_rows > 0):
                        $has_activity = true;
                        while ($user = $users_result->fetch_assoc()):
                    ?>
                        <div class="activity-item">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="stats-icon" style="background: var(--primary-color); width: 40px; height: 40px; font-size: 1rem;">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">Nuevo usuario registrado: <?= htmlspecialchars($user['nombre']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-clock me-1"></i>
                                        <?= date('d/m/Y H:i', strtotime($user['fecha_creacion'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; endif; ?>

                    <?php if (!$has_activity): ?>
                        <div class="activity-item text-center py-5">
                            <i class="bi bi-clock-history display-4 text-muted"></i>
                            <h6 class="text-muted mt-3">No hay actividad reciente</h6>
                            <p class="text-muted small">La actividad del sistema aparecerá aquí</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="stats-card">
                            <h5 class="mb-3"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h5>
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="usuarios.php" class="btn btn-outline-primary btn-custom w-100">
                                        <i class="bi bi-person-plus me-2"></i>Crear Usuario
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="propiedades.php" class="btn btn-outline-success btn-custom w-100">
                                        <i class="bi bi-house-add me-2"></i>Nueva Propiedad
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="personalizar.php" class="btn btn-outline-info btn-custom w-100">
                                        <i class="bi bi-palette me-2"></i>Personalizar
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="../index.php" class="btn btn-outline-secondary btn-custom w-100">
                                        <i class="bi bi-eye me-2"></i>Ver Sitio
                                    </a>
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