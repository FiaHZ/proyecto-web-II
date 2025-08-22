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
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 80px 0 40px;
        }
        
        .property-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .property-image {
            height: 250px;
            object-fit: cover;
        }
        
        .price-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(52, 152, 219, 0.9);
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: bold;
        }
        
        .featured-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(241, 196, 15, 0.9);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .property-details {
            padding: 20px;
        }
        
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Header de Búsqueda -->
    <div class="search-header">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1>Resultados de Búsqueda</h1>
                    <p class="lead">
                        <?php if (!empty($termino)): ?>
                            Mostrando resultados para: <strong>"<?= htmlspecialchars($termino) ?>"</strong>
                        <?php else: ?>
                            Explorando todas las propiedades disponibles
                        <?php endif; ?>
                    </p>
                    <p>Se encontraron <strong><?= $total_resultados ?></strong> propiedades</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Filtros de Búsqueda -->
        <div class="filter-section">
            <h5 class="mb-3"><i class="bi bi-funnel me-2"></i> Filtros de Búsqueda</h5>
            <form method="GET" action="buscar.php">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($termino) ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="venta" <?= $tipo == 'venta' ? 'selected' : '' ?>>Venta</option>
                            <option value="alquiler" <?= $tipo == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                        </select>
                    </div>
                    <div class="col-md-2">
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
                        <input type="number" class="form-control" name="precio_min" placeholder="Precio mín." value="<?= $precio_min ?>">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="precio_max" placeholder="Precio máx." value="<?= $precio_max ?>">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
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
                <div class="col-lg-4 col-md-6">
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
                                <span class="featured-badge">Destacada</span>
                            <?php endif; ?>
                            
                            <span class="price-badge">
                                <?= formatearPrecio($propiedad['precio'], $propiedad['tipo']) ?>
                            </span>
                        </div>
                        
                        <div class="property-details">
                            <h5 class="card-title">
                                <a href="propiedad.php?id=<?= $propiedad['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($propiedad['titulo']) ?>
                                </a>
                            </h5>
                            
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?= htmlspecialchars($propiedad['ubicacion']) ?>
                            </p>
                            
                            <p class="card-text"><?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...</p>
                            
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
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="bi bi-person me-1"></i>
                                    <?= htmlspecialchars($propiedad['vendedor_nombre']) ?>
                                </small>
                                <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                    <?= ucfirst($propiedad['tipo']) ?>
                                </span>
                            </div>
                            
                            <div class="mt-3">
                                <a href="propiedad.php?id=<?= $propiedad['id'] ?>" class="btn btn-primary w-100">
                                    Ver Detalles <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-search text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">No se encontraron propiedades</h3>
                        <p class="text-muted">Intenta con otros criterios de búsqueda</p>
                        <a href="index.php" class="btn btn-primary">Volver al Inicio</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Botón Volver -->
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-2"></i> Volver al Inicio
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>