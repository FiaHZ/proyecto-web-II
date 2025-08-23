<?php
session_start();
require_once "config/database.php";

// Obtener configuraciones
$config_query = "SELECT clave, valor FROM configuraciones";
$config_result = $conn->query($config_query);
$config = [];
while ($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

// Parámetros de paginación
$por_pagina = 9;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$tipo_filtro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 0;
$ubicacion_filtro = isset($_GET['ubicacion']) ? $_GET['ubicacion'] : '';

// Construir consulta WHERE
$where_conditions = ["destacada = 1", "estado = 'disponible'"];
$params = [];
$types = "";

if ($categoria_filtro) {
    $where_conditions[] = "categoria = ?";
    $params[] = $categoria_filtro;
    $types .= "s";
}

if ($tipo_filtro) {
    $where_conditions[] = "tipo = ?";
    $params[] = $tipo_filtro;
    $types .= "s";
}

if ($precio_min > 0) {
    $where_conditions[] = "precio >= ?";
    $params[] = $precio_min;
    $types .= "d";
}

if ($precio_max > 0) {
    $where_conditions[] = "precio <= ?";
    $params[] = $precio_max;
    $types .= "d";
}

if ($ubicacion_filtro) {
    $where_conditions[] = "ubicacion LIKE ?";
    $params[] = "%$ubicacion_filtro%";
    $types .= "s";
}

$where_clause = implode(" AND ", $where_conditions);

// Contar total de propiedades
$count_query = "SELECT COUNT(*) as total FROM vista_propiedades_completa WHERE $where_clause";
if ($params) {
    $stmt_count = $conn->prepare($count_query);
    if ($types) $stmt_count->bind_param($types, ...$params);
    $stmt_count->execute();
    $total_result = $stmt_count->get_result();
} else {
    $total_result = $conn->query($count_query);
}
$total_propiedades = $total_result->fetch_assoc()['total'];
$total_paginas = ceil($total_propiedades / $por_pagina);

// Obtener propiedades de la página actual
$propiedades_query = "SELECT * FROM vista_propiedades_completa WHERE $where_clause ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?";
$all_params = array_merge($params, [$por_pagina, $offset]);
$all_types = $types . "ii";

$stmt = $conn->prepare($propiedades_query);
if ($all_types) $stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$propiedades_result = $stmt->get_result();

// Obtener datos para filtros
$categorias_query = "SELECT DISTINCT categoria FROM propiedades WHERE destacada = 1 AND estado = 'disponible' ORDER BY categoria";
$categorias_result = $conn->query($categorias_query);

$ubicaciones_query = "SELECT DISTINCT ubicacion FROM propiedades WHERE destacada = 1 AND estado = 'disponible' ORDER BY ubicacion";
$ubicaciones_result = $conn->query($ubicaciones_query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propiedades Destacadas - Solutions Real Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/listado.css">
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">
            <img src="img/<?= $config['icono_principal'] ?? 'logo.png' ?>" alt="logo de la empresa" />
            <div class="social-icons-logo">
                <a href="<?= $config['facebook_url'] ?? '#' ?>"><i class="fa-brands fa-facebook"></i></a>
                <a href="<?= $config['youtube_url'] ?? '#' ?>"><i class="fa-brands fa-youtube"></i></a>
                <a href="<?= $config['instagram_url'] ?? '#' ?>"><i class="fa-brands fa-instagram"></i></a>
            </div>
        </div>

        <nav>
            <ul class="nav-links">
                <li><a href="index.php">INICIO |</a></li>
                <li><a href="index.php#quienes-somos">QUIENES SOMOS |</a></li>
                <li><a href="alquileres.php">ALQUILERES |</a></li>
                <li><a href="ventas.php">VENTAS |</a></li>
                <li><a href="index.php#contacto">CONTACTENOS</a></li>
                <?php if (isset($_SESSION['usuario_id'])): ?>
                    <li><a href="#"><i class="fa-regular fa-user"></i> <?= $_SESSION['usuario_nombre'] ?></a></li>
                    <li><a href="logout.php" title="Cerrar Sesión"><i class="bi bi-box-arrow-right"></i></a></li>
                    <?php if ($_SESSION['usuario_rol'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php" title="Panel Admin"><i class="bi bi-speedometer"></i></a></li>
                    <?php elseif ($_SESSION['usuario_rol'] === 'vendedor'): ?>
                        <li><a href="vendedor/dashboard.php" title="Panel Vendedor"><i class="bi bi-house-gear"></i></a></li>
                    <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php"><i class="fa-regular fa-circle-user"></i></a></li>
                <?php endif; ?>
            </ul>

            <div class="search-bar">
                <form method="GET" action="buscar.php">
                    <input type="text" name="buscar" placeholder="Buscar propiedades..." />
                    <button type="submit"><i class="fa fa-search"></i></button>
                </form>
            </div>
        </nav>
    </header>

    <!-- Banner -->
    <div class="container-fluid bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1><i class="bi bi-star-fill me-2"></i>Propiedades Destacadas</h1>
                    <p class="lead">Descubre nuestras mejores propiedades seleccionadas especialmente para ti</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtrar Propiedades</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo">
                            <option value="">Todos</option>
                            <option value="venta" <?= $tipo_filtro == 'venta' ? 'selected' : '' ?>>Venta</option>
                            <option value="alquiler" <?= $tipo_filtro == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="categoria">
                            <option value="">Todas</option>
                            <?php while ($cat = $categorias_result->fetch_assoc()): ?>
                                <option value="<?= $cat['categoria'] ?>" <?= $categoria_filtro == $cat['categoria'] ? 'selected' : '' ?>>
                                    <?= ucfirst($cat['categoria']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ubicación</label>
                        <select class="form-select" name="ubicacion">
                            <option value="">Todas</option>
                            <?php while ($ub = $ubicaciones_result->fetch_assoc()): ?>
                                <option value="<?= $ub['ubicacion'] ?>" <?= $ubicacion_filtro == $ub['ubicacion'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ub['ubicacion']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio Mínimo</label>
                        <input type="number" class="form-control" name="precio_min" value="<?= $precio_min ?>" placeholder="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio Máximo</label>
                        <input type="number" class="form-control" name="precio_max" value="<?= $precio_max ?>" placeholder="Sin límite">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Propiedades Destacadas</h3>
            <span class="badge bg-secondary fs-6">
                <?= $total_propiedades ?> propiedad<?= $total_propiedades != 1 ? 'es' : '' ?> encontrada<?= $total_propiedades != 1 ? 's' : '' ?>
            </span>
        </div>

        <div class="row">
            <?php if ($propiedades_result->num_rows > 0): ?>
                <?php while ($propiedad = $propiedades_result->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm property-card">
                            <div class="position-relative">
                                <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" 
                                     class="card-img-top" style="height: 250px; object-fit: cover;" 
                                     alt="<?= htmlspecialchars($propiedad['titulo']) ?>">
                                
                                <!-- Badge de tipo -->
                                <span class="position-absolute top-0 start-0 m-2 badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                    <?= ucfirst($propiedad['tipo']) ?>
                                </span>
                                
                                <!-- Badge destacada -->
                                <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Destacada
                                </span>
                                
                                <!-- Precio -->
                                <div class="position-absolute bottom-0 end-0 m-2">
                                    <span class="badge bg-dark fs-6">
                                        $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                                        <?= $propiedad['tipo'] == 'alquiler' ? '/mes' : '' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?= ucfirst($propiedad['categoria']) ?></span>
                                </div>
                                
                                <h5 class="card-title"><?= htmlspecialchars($propiedad['titulo']) ?></h5>
                                
                                <p class="card-text text-muted">
                                    <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 120)) ?>...
                                </p>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                                    </small>
                                </div>
                                
                                <!-- Características -->
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
                                
                                <!-- Información del vendedor -->
                                <div class="border-top pt-3 mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($propiedad['vendedor_nombre']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($propiedad['fecha_creacion'])) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Botón Ver Detalles únicamente -->
                                <div class="d-flex gap-2 mt-3">
                                    <a href="detalle_propiedad.php?id=<?= $propiedad['id'] ?>" 
                                       class="btn btn-primary w-100">
                                        <i class="bi bi-eye"></i> Ver Detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <i class="bi bi-search display-1 text-muted"></i>
                            <h4 class="mt-3">No se encontraron propiedades destacadas</h4>
                            <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
                            <a href="destacadas.php" class="btn btn-primary">Ver todas las destacadas</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de propiedades" class="mt-5">
                <ul class="pagination justify-content-center">
                    <!-- Página anterior -->
                    <?php if ($pagina_actual > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])) ?>">
                                <i class="bi bi-chevron-left"></i> Anterior
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Páginas numeradas -->
                    <?php
                    $rango_inicio = max(1, $pagina_actual - 2);
                    $rango_fin = min($total_paginas, $pagina_actual + 2);
                    
                    if ($rango_inicio > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">1</a>
                        </li>
                        <?php if ($rango_inicio > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $rango_inicio; $i <= $rango_fin; $i++): ?>
                        <li class="page-item <?= $i == $pagina_actual ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($rango_fin < $total_paginas): ?>
                        <?php if ($rango_fin < $total_paginas - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>"><?= $total_paginas ?></a>
                        </li>
                    <?php endif; ?>

                    <!-- Página siguiente -->
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])) ?>">
                                Siguiente <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer" id="contacto">
        <div class="footer-content">
            <!-- Información de contacto -->
            <div class="contact-info">
                <div class="contact-item">
                    <div>
                        <i class="fas fa-map-marker-alt contact-icon"></i>
                        <strong>Dirección:</strong> <?= htmlspecialchars($config['direccion'] ?? 'San José, Costa Rica') ?>
                    </div>
                </div>

                <div class="contact-item">
                    <i class="fas fa-phone contact-icon"></i><br>
                    <div><strong>Teléfono:</strong> <?= htmlspecialchars($config['telefono'] ?? '2222-3333') ?></div>
                </div>

                <div class="contact-item">
                    <i class="fas fa-envelope contact-icon"></i>
                    <div><strong>Email:</strong> <?= htmlspecialchars($config['email_contacto'] ?? 'info@realestate.com') ?></div>
                </div>
            </div>

            <!-- Logo y redes sociales -->
            <div class="center-section">
                <div class="logo">
                    <img src="img/<?= $config['icono_blanco'] ?? 'logo-black.png' ?>" alt="Logo">
                </div>

                <div class="social-icons">
                    <a href="<?= $config['facebook_url'] ?? '#' ?>"><i class="fa-brands fa-facebook"></i></a>
                    <a href="<?= $config['youtube_url'] ?? '#' ?>"><i class="fa-brands fa-youtube"></i></a>
                    <a href="<?= $config['instagram_url'] ?? '#' ?>"><i class="fa-brands fa-instagram"></i></a>
                </div>
            </div>

            <!-- Formulario de contacto -->
            <div class="contact-form-section">
                <form class="contact-form" action="enviar_formulario.php" method="POST">
                    <h3 class="form-title">Contáctanos</h3>

                    <div class="form-group">
                        <label class="form-label">Nombre:</label>
                        <input type="text" class="form-input" name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email:</label>
                        <input type="email" class="form-input" name="correo" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono:</label>
                        <input type="tel" class="form-input" name="telefono">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mensaje:</label>
                        <textarea class="form-textarea" name="mensaje" required></textarea>
                    </div>

                    <button type="submit" class="form-submit">Enviar</button>
                </form>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; Derechos Reservados | <?= date('Y') ?>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>