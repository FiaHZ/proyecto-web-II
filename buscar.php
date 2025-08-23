<?php
session_start();
require_once "config/database.php";
require_once "config/functions.php";

// Obtener parámetros de búsqueda
$termino = isset($_GET['q']) ? trim($_GET['q']) : '';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : null;
$precio_min = isset($_GET['precio_min']) ? (float)$_GET['precio_min'] : null;
$precio_max = isset($_GET['precio_max']) ? (float)$_GET['precio_max'] : null;

// Obtener configuraciones del sitio
$config = obtenerConfiguracion($conn);

// Construir query de búsqueda
$where_conditions = ["p.estado = 'disponible'"];
$params = [];
$param_types = "";

if (!empty($termino)) {
    $termino_like = "%$termino%";
    $where_conditions[] = "(p.titulo LIKE ? OR p.descripcion_breve LIKE ? OR p.descripcion_larga LIKE ? OR p.ubicacion LIKE ?)";
    $params = array_merge($params, [$termino_like, $termino_like, $termino_like, $termino_like]);
    $param_types .= "ssss";
}

if ($tipo) {
    $where_conditions[] = "p.tipo = ?";
    $params[] = $tipo;
    $param_types .= "s";
}

if ($categoria) {
    $where_conditions[] = "p.categoria = ?";
    $params[] = $categoria;
    $param_types .= "s";
}

if ($precio_min) {
    $where_conditions[] = "p.precio >= ?";
    $params[] = $precio_min;
    $param_types .= "d";
}

if ($precio_max) {
    $where_conditions[] = "p.precio <= ?";
    $params[] = $precio_max;
    $param_types .= "d";
}

$where_clause = implode(" AND ", $where_conditions);

$query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono, u.correo as vendedor_correo 
          FROM propiedades p 
          JOIN usuarios u ON p.vendedor_id = u.id 
          WHERE $where_clause
          ORDER BY p.destacada DESC, p.fecha_creacion DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$propiedades = $stmt->get_result();

$total_resultados = $propiedades->num_rows;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados de Búsqueda - <?= htmlspecialchars($config['mensaje_banner']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/buscar.css">
</head>
<body>
    <div class="container-fluid">
        <!-- Header de Búsqueda -->
        <div class="search-header">
            <div class="container">
                <div class="row">
                    <div class="col-12 text-center">
                        <h1><i class="bi bi-search me-3"></i>Resultados de Búsqueda</h1>
                        <p class="lead mb-2">
                            <?php if (!empty($termino)): ?>
                                Mostrando resultados para: <strong>"<?= htmlspecialchars($termino) ?>"</strong>
                            <?php else: ?>
                                Explorando todas las propiedades disponibles
                            <?php endif; ?>
                        </p>
                        <p class="mb-0">Se encontraron <strong><?= $total_resultados ?></strong> propiedades</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros de Búsqueda -->
        <div class="filter-section">
            <h5 class="mb-4"><i class="bi bi-funnel me-2"></i> Filtros de Búsqueda</h5>
            <form method="GET" action="buscar.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Término de búsqueda</label>
                        <input type="text" class="form-control" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($termino) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo</label>
                        <select class="form-select" name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="venta" <?= $tipo == 'venta' ? 'selected' : '' ?>>Venta</option>
                            <option value="alquiler" <?= $tipo == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" name="categoria">
                            <option value="">Todas las categorías</option>
                            <option value="casa" <?= $categoria == 'casa' ? 'selected' : '' ?>>Casa</option>
                            <option value="apartamento" <?= $categoria == 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                            <option value="local" <?= $categoria == 'local' ? 'selected' : '' ?>>Local</option>
                            <option value="terreno" <?= $categoria == 'terreno' ? 'selected' : '' ?>>Terreno</option>
                            <option value="oficina" <?= $categoria == 'oficina' ? 'selected' : '' ?>>Oficina</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio mínimo</label>
                        <input type="number" class="form-control" name="precio_min" placeholder="Precio mín." value="<?= $precio_min ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio máximo</label>
                        <input type="number" class="form-control" name="precio_max" placeholder="Precio máx." value="<?= $precio_max ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-custom w-100">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Resultados -->
        <div class="row">
            <?php if ($total_resultados > 0): ?>
                <?php while ($propiedad = $propiedades->fetch_assoc()): ?>
                <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                    <div class="card property-card">
                        <div class="position-relative">
                            <?php if ($propiedad['imagen_destacada']): ?>
                                <img src="img/<?= $propiedad['imagen_destacada'] ?>" class="card-img-top property-image" alt="<?= htmlspecialchars($propiedad['titulo']) ?>">
                            <?php else: ?>
                                <div class="property-image bg-light d-flex align-items-center justify-content-center">
                                    <i class="bi bi-house text-muted" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($propiedad['destacada']): ?>
                                <span class="featured-badge">
                                    <i class="bi bi-star-fill me-1"></i>Destacada
                                </span>
                            <?php endif; ?>
                            
                            <span class="price-badge">
                                <?= formatearPrecio($propiedad['precio'], $propiedad['tipo']) ?>
                            </span>
                        </div>
                        
                        <div class="property-details">
                            <h5 class="card-title mb-3">
                                <a href="propiedad.php?id=<?= $propiedad['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($propiedad['titulo']) ?>
                                </a>
                            </h5>
                            
                            <p class="text-muted mb-3">
                                <i class="bi bi-geo-alt me-2"></i>
                                <?= htmlspecialchars($propiedad['ubicacion']) ?>
                            </p>
                            
                            <p class="card-text mb-3"><?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...</p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="property-features">
                                    <?php if ($propiedad['habitaciones']): ?>
                                        <small class="text-muted me-3">
                                            <i class="bi bi-door-closed me-1"></i> <?= $propiedad['habitaciones'] ?> hab.
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($propiedad['banos']): ?>
                                        <small class="text-muted me-3">
                                            <i class="bi bi-droplet me-1"></i> <?= $propiedad['banos'] ?> baños
                                        </small>
                                    <?php endif; ?>
                                    <?php if ($propiedad['area_m2']): ?>
                                        <small class="text-muted">
                                            <i class="bi bi-rulers me-1"></i> <?= $propiedad['area_m2'] ?> m²
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($propiedad['vendedor_nombre']) ?>
                                </small>
                                <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                    <?= ucfirst($propiedad['tipo']) ?>
                                </span>
                            </div>
                            
                            <div class="mt-3">
                                <a href="propiedad.php?id=<?= $propiedad['id'] ?>" class="btn btn-primary btn-custom w-100">
                                    Ver Detalles <i class="bi bi-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-results">
                        <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">No se encontraron propiedades</h3>
                        <p class="text-muted mb-4">Intenta con otros criterios de búsqueda</p>
                        <a href="index.php" class="btn btn-primary btn-custom">
                            <i class="bi bi-house me-2"></i>Volver al Inicio
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botón Volver -->
        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-outline-primary btn-custom">
                <i class="bi bi-arrow-left me-2"></i> Volver al Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>