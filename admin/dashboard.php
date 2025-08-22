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
    <link rel="stylesheet" href="../proyecto-web-II/css/dashboard.css">
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4 sidebar-logo">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid">
                        <h5 class="text-white">Admin Panel</h5>
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
                        <a class="nav-link" href="mensajes.php">
                            <i class="bi bi-envelope me-2"></i> Mensajes
                        </a>
                        <a class="nav-link" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Mi Perfil
                        </a>
                        <hr class="my-3">
                        <a class="nav-link text-danger" href="logout.php">
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
                        <div class="stats-card primary">
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
                        <div class="stats-card secondary">
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
                        <div class="stats-card success">
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
                        <div class="stats-card warning">
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
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card text-center">
                            <i class="bi bi-key text-info" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Propiedades en Alquiler</h5>
                            <h3 class="text-info"><?= $stats['propiedades_alquiler'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="stats-card text-center">
                            <i class="bi bi-person-plus text-warning" style="font-size: 2rem;"></i>
                            <h5 class="mt-2">Solicitudes Vendedor</h5>
                            <h3 class="text-warning"><?= $stats['solicitudes_vendedor_pendientes'] ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h5 class="mb-4">Actividad Reciente</h5>
                    <?php
                    // Obtener actividad reciente
                    $activity_query = "
                        SELECT 'propiedad' as tipo, titulo as descripcion, fecha_creacion as fecha, 
                               CONCAT('Nueva propiedad agregada: ', titulo) as mensaje
                        FROM propiedades 
                        ORDER BY fecha_creacion DESC 
                        LIMIT 3
                        UNION ALL
                        SELECT 'usuario' as tipo, nombre as descripcion, fecha_creacion as fecha,
                               CONCAT('Nuevo usuario registrado: ', nombre) as mensaje  
                        FROM usuarios 
                        WHERE rol = 'cliente'
                        ORDER BY fecha_creacion DESC 
                        LIMIT 2
                    ";
                    $activity_result = $conn->query($activity_query);

                    if ($activity_result->num_rows > 0):
                        while ($activity = $activity_result->fetch_assoc()):
                            ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i
                                            class="bi <?= $activity['tipo'] == 'propiedad' ? 'bi-house' : 'bi-person' ?> text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div><?= htmlspecialchars($activity['mensaje']) ?></div>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($activity['fecha'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <p class="text-muted text-center">No hay actividad reciente</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>