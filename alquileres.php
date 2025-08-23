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
$por_pagina = 12;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : 0;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : 0;
$ubicacion_filtro = isset($_GET['ubicacion']) ? $_GET['ubicacion'] : '';
$habitaciones_filtro = isset($_GET['habitaciones']) ? (int)$_GET['habitaciones'] : 0;
$banos_filtro = isset($_GET['banos']) ? (int)$_GET['banos'] : 0;
$amueblada_filtro = isset($_GET['amueblada']) ? $_GET['amueblada'] : '';

// Construir consulta WHERE
$where_conditions = ["tipo = 'alquiler'", "estado = 'disponible'"];
$params = [];
$types = "";

if ($categoria_filtro) {
    $where_conditions[] = "categoria = ?";
    $params[] = $categoria_filtro;
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

if ($habitaciones_filtro > 0) {
    $where_conditions[] = "habitaciones >= ?";
    $params[] = $habitaciones_filtro;
    $types .= "i";
}

if ($banos_filtro > 0) {
    $where_conditions[] = "banos >= ?";
    $params[] = $banos_filtro;
    $types .= "i";
}

if ($amueblada_filtro) {
    if ($amueblada_filtro == 'si') {
        $where_conditions[] = "(descripcion_breve LIKE '%amueblad%' OR descripcion_larga LIKE '%amueblad%' OR titulo LIKE '%amueblad%')";
    }
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
$categorias_query = "SELECT DISTINCT categoria FROM propiedades WHERE tipo = 'alquiler' AND estado = 'disponible' ORDER BY categoria";
$categorias_result = $conn->query($categorias_query);

$ubicaciones_query = "SELECT DISTINCT ubicacion FROM propiedades WHERE tipo = 'alquiler' AND estado = 'disponible' ORDER BY ubicacion";
$ubicaciones_result = $conn->query($ubicaciones_query);

// Obtener estadísticas de precios para el rango
$stats_query = "SELECT MIN(precio) as min_precio, MAX(precio) as max_precio, AVG(precio) as promedio_precio FROM propiedades WHERE tipo = 'alquiler' AND estado = 'disponible'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Propiedades en Alquiler - Solutions Real Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/listado.css">
    <link rel="stylesheet" href="css/alquileres.css">
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
                <li><a href="alquileres.php" class="active">ALQUILERES |</a></li>
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
    <div class="container-fluid bg-info text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1><i class="bi bi-house-door me-2"></i>Propiedades en Alquiler</h1>
                    <p class="lead">Encuentra el lugar perfecto para vivir con las mejores opciones de alquiler</p>
                </div>
                <div class="col-lg-4">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3><?= $total_propiedades ?></h3>
                                <small>Propiedades disponibles</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3>$<?= number_format($stats['promedio_precio'], 0, ',', '.') ?></h3>
                                <small>Precio promedio/mes</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h4>$<?= number_format($stats['min_precio'], 0, ',', '.') ?></h4>
                    <small>Desde / mensual</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h4>$<?= number_format($stats['max_precio'], 0, ',', '.') ?></h4>
                    <small>Hasta / mensual</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h4><?= $total_propiedades ?></h4>
                    <small>Opciones disponibles</small>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtrar Propiedades en Alquiler</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
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
                        <label class="form-label">Precio Desde</label>
                        <input type="number" class="form-control" name="precio_min" value="<?= $precio_min ?>" placeholder="Mínimo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio Hasta</label>
                        <input type="number" class="form-control" name="precio_max" value="<?= $precio_max ?>" placeholder="Máximo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Habitaciones</label>
                        <select class="form-select" name="habitaciones">
                            <option value="0">Cualquiera</option>
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= $habitaciones_filtro == $i ? 'selected' : '' ?>>
                                    <?= $i ?>+ habitación<?= $i > 1 ? 'es' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Baños</label>
                        <select class="form-select" name="banos">
                            <option value="0">Cualquiera</option>
                            <?php for($i = 1; $i <= 4; $i++): ?>
                                <option value="<?= $i ?>" <?= $banos_filtro == $i ? 'selected' : '' ?>>
                                    <?= $i ?>+ baño<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">¿Amueblada?</label>
                                <select class="form-select" name="amueblada">
                                    <option value="">No importa</option>
                                    <option value="si" <?= $amueblada_filtro == 'si' ? 'selected' : '' ?>>Sí, amueblada</option>
                                    <option value="no" <?= $amueblada_filtro == 'no' ? 'selected' : '' ?>>Sin muebles</option>
                                </select>
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-info">
                                        <i class="bi bi-search"></i> Buscar Alquileres
                                    </button>
                                    <a href="alquileres.php" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Limpiar Filtros
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Propiedades en Alquiler</h3>
            <span class="badge rental-badge fs-6">
                <?= $total_propiedades ?> propiedad<?= $total_propiedades != 1 ? 'es' : '' ?> encontrada<?= $total_propiedades != 1 ? 's' : '' ?>
            </span>
        </div>

        <div class="row">
            <?php if ($propiedades_result->num_rows > 0): ?>
                <?php while ($propiedad = $propiedades_result->fetch_assoc()): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 property-card">
                            <div class="position-relative">
                                <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" 
                                     class="card-img-top" style="height: 250px; object-fit: cover;" 
                                     alt="<?= htmlspecialchars($propiedad['titulo']) ?>">
                                
                                <!-- Badge de alquiler -->
                                <span class="position-absolute top-0 start-0 m-2 badge rental-badge">
                                    <i class="bi bi-calendar-month"></i> Alquiler
                                </span>
                                
                                <!-- Badge destacada si aplica -->
                                <?php if ($propiedad['destacada']): ?>
                                    <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">
                                        <i class="bi bi-star-fill"></i> Destacada
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Badge amueblada -->
                                <?php 
                                $es_amueblada = stripos($propiedad['descripcion_breve'], 'amueblad') !== false || 
                                               stripos($propiedad['descripcion_larga'], 'amueblad') !== false ||
                                               stripos($propiedad['titulo'], 'amueblad') !== false;
                                if ($es_amueblada): 
                                ?>
                                    <span class="position-absolute top-0 start-50 translate-middle-x mt-2 badge bg-success">
                                        <i class="bi bi-house-fill"></i> Amueblada
                                    </span>
                                <?php endif; ?>
                                
                                <!-- Precio -->
                                <div class="position-absolute bottom-0 start-0 m-2">
                                    <span class="price-highlight">
                                        $<?= number_format($propiedad['precio'], 0, ',', '.') ?>/mes
                                    </span>
                                </div>
                                
                                <!-- Favoritos -->
                                <?php if (isset($_SESSION['usuario_id'])): ?>
                                    <button class="btn btn-outline-light btn-sm position-absolute bottom-0 end-0 m-2" 
                                            onclick="toggleFavorito(<?= $propiedad['id'] ?>)">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2">
                                    <span class="badge bg-secondary"><?= ucfirst($propiedad['categoria']) ?></span>
                                </div>
                                
                                <h5 class="card-title"><?= htmlspecialchars($propiedad['titulo']) ?></h5>
                                
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...
                                </p>
                                
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                                    </small>
                                </div>
                                
                                <!-- Características -->
                                <div class="row text-center mb-3 border-top pt-2">
                                    <div class="col-3">
                                        <small class="text-muted">
                                            <i class="bi bi-house text-primary"></i><br>
                                            <strong><?= $propiedad['habitaciones'] ?></strong><br>
                                            <span style="font-size: 0.7rem;">habitaciones</span>
                                        </small>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">
                                            <i class="bi bi-droplet text-info"></i><br>
                                            <strong><?= $propiedad['banos'] ?></strong><br>
                                            <span style="font-size: 0.7rem;">baños</span>
                                        </small>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">
                                            <i class="bi bi-arrows-fullscreen text-success"></i><br>
                                            <strong><?= $propiedad['area_m2'] ?? 'N/A' ?></strong><br>
                                            <span style="font-size: 0.7rem;">m²</span>
                                        </small>
                                    </div>
                                    <div class="col-3">
                                        <small class="text-muted">
                                            <i class="bi bi-car-front text-warning"></i><br>
                                            <strong><?= $propiedad['parqueos'] ?></strong><br>
                                            <span style="font-size: 0.7rem;">parqueos</span>
                                        </small>
                                    </div>
                                </div>
                                
                                <!-- Información del vendedor -->
                                <div class="border-top pt-2 mb-3">
                                    <div class="row">
                                        <div class="col-8">
                                            <small class="text-muted">
                                                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($propiedad['vendedor_nombre']) ?>
                                            </small>
                                        </div>
                                        <div class="col-4 text-end">
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($propiedad['fecha_creacion'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="mt-auto">
                                    <div class="d-flex gap-2 mb-2">
                                        <a href="detalle_propiedad.php?id=<?= $propiedad['id'] ?>" 
                                           class="btn btn-info flex-fill text-white">
                                            <i class="bi bi-eye"></i> Ver Detalles
                                        </a>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="tel:<?= $propiedad['vendedor_telefono'] ?>" 
                                           class="btn btn-outline-info btn-sm flex-fill" title="Llamar">
                                            <i class="bi bi-telephone"></i>
                                        </a>
                                        <a href="https://wa.me/506<?= str_replace(['-', ' '], '', $propiedad['vendedor_telefono']) ?>?text=Hola, me interesa el alquiler: <?= urlencode($propiedad['titulo']) ?>" 
                                           class="btn btn-outline-success btn-sm flex-fill" target="_blank" title="WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                        <a href="mailto:<?= $propiedad['vendedor_correo'] ?>?subject=Consulta sobre alquiler: <?= urlencode($propiedad['titulo']) ?>" 
                                           class="btn btn-outline-info btn-sm flex-fill" title="Email">
                                            <i class="bi bi-envelope"></i>
                                        </a>
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
                            <h4 class="mt-3">No se encontraron propiedades en alquiler</h4>
                            <p class="text-muted">Intenta ajustar los filtros de búsqueda o revisa más tarde</p>
                            <a href="alquileres.php" class="btn btn-info">Ver todos los alquileres</a>
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

        <!-- Información útil para inquilinos -->
        <div class="row mt-5">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-check display-4 text-info mb-3"></i>
                        <h5>Propiedades Verificadas</h5>
                        <p class="card-text small">Todas nuestras propiedades pasan por un proceso de verificación riguroso</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-headset display-4 text-info mb-3"></i>
                        <h5>Soporte 24/7</h5>
                        <p class="card-text small">Nuestro equipo está disponible para ayudarte en cualquier momento</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="bi bi-hand-thumbs-up display-4 text-info mb-3"></i>
                        <h5>Sin Comisiones Ocultas</h5>
                        <p class="card-text small">Precios transparentes y sin sorpresas en el proceso de alquiler</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Llamada a la acción -->
        <div class="card bg-info text-white mt-5">
            <div class="card-body text-center">
                <h4>¿Necesitas ayuda para encontrar tu próximo hogar?</h4>
                <p class="mb-3">Nuestros especialistas en alquileres pueden asesorarte personalmente</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="tel:<?= $config['telefono'] ?? '2222-3333' ?>" class="btn btn-light">
                        <i class="bi bi-telephone"></i> Llamar Especialista
                    </a>
                    <a href="index.php#contacto" class="btn btn-outline-light">
                        <i class="bi bi-envelope"></i> Solicitar Asesoría
                    </a>
                </div>
            </div>
        </div>
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
    <script>
        // Función para favoritos (puedes implementarla después)
        function toggleFavorito(propiedadId) {
            alert('Funcionalidad de favoritos en desarrollo');
        }

        // Efecto de hover en las cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.property-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>