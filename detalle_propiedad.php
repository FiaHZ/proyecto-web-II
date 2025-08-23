<?php
session_start();
require_once "config/database.php";

// Verificar que se haya enviado un ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$propiedad_id = (int)$_GET['id'];

// Obtener configuraciones
$config_query = "SELECT clave, valor FROM configuraciones";
$config_result = $conn->query($config_query);
$config = [];
while ($row = $config_result->fetch_assoc()) {
    $config[$row['clave']] = $row['valor'];
}

// Obtener datos completos de la propiedad
$propiedad_query = "SELECT * FROM vista_propiedades_completa WHERE id = ?";
$stmt = $conn->prepare($propiedad_query);
$stmt->bind_param("i", $propiedad_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$propiedad = $result->fetch_assoc();

// Obtener imágenes adicionales de la propiedad
$imagenes_query = "SELECT * FROM imagenes_propiedades WHERE propiedad_id = ? ORDER BY orden";
$stmt_img = $conn->prepare($imagenes_query);
$stmt_img->bind_param("i", $propiedad_id);
$stmt_img->execute();
$imagenes_result = $stmt_img->get_result();

// Procesar formulario de contacto/reserva
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_reserva'])) {
    if (isset($_SESSION['usuario_id'])) {
        $cliente_id = $_SESSION['usuario_id'];
        $tipo_interes = $_POST['tipo_interes'];
        $mensaje_contacto = $_POST['mensaje'];
        $telefono_contacto = $_POST['telefono_contacto'];
        $fecha_preferida = !empty($_POST['fecha_preferida']) ? $_POST['fecha_preferida'] : null;

        $insert_reserva = "INSERT INTO reservas (propiedad_id, cliente_id, tipo_interes, mensaje, telefono_contacto, fecha_preferida) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_reserva = $conn->prepare($insert_reserva);
        $stmt_reserva->bind_param("iissss", $propiedad_id, $cliente_id, $tipo_interes, $mensaje_contacto, $telefono_contacto, $fecha_preferida);

        if ($stmt_reserva->execute()) {
            $mensaje = "¡Tu consulta ha sido enviada exitosamente! El vendedor se pondrá en contacto contigo pronto.";
            $tipo_mensaje = "success";
        } else {
            $mensaje = "Error al enviar la consulta. Por favor, inténtalo de nuevo.";
            $tipo_mensaje = "danger";
        }
    } else {
        $mensaje = "Debes iniciar sesión para contactar al vendedor.";
        $tipo_mensaje = "warning";
    }
}

// Obtener propiedades relacionadas del mismo vendedor
$relacionadas_query = "SELECT * FROM vista_propiedades_completa WHERE vendedor_nombre = ? AND id != ? AND estado = 'disponible' ORDER BY fecha_creacion DESC LIMIT 3";
$stmt_rel = $conn->prepare($relacionadas_query);
$stmt_rel->bind_param("si", $propiedad['vendedor_nombre'], $propiedad_id);
$stmt_rel->execute();
$relacionadas_result = $stmt_rel->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($propiedad['titulo']) ?> - Solutions Real Estate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/detalle_propiedades.css">
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

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="<?= $propiedad['tipo'] == 'venta' ? 'ventas.php' : 'alquileres.php' ?>"><?= ucfirst($propiedad['tipo']) ?></a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($propiedad['titulo']) ?></li>
            </ol>
        </nav>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <?= $mensaje ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Contenido Principal -->
            <div class="col-lg-8">
                <!-- Título y badges -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="h2"><?= htmlspecialchars($propiedad['titulo']) ?></h1>
                        <p class="text-muted mb-2">
                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?>
                        </p>
                        <div class="mb-3">
                            <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?> me-2">
                                <?= ucfirst($propiedad['tipo']) ?>
                            </span>
                            <span class="badge bg-secondary me-2">
                                <?= ucfirst($propiedad['categoria']) ?>
                            </span>
                            <?php if ($propiedad['destacada']): ?>
                                <span class="badge bg-warning text-dark">
                                    <i class="bi bi-star-fill"></i> Destacada
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <h3 class="text-primary mb-0">
                            $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                            <?= $propiedad['tipo'] == 'alquiler' ? '/mes' : '' ?>
                        </h3>
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <button class="btn btn-outline-danger btn-sm mt-2" onclick="toggleFavorito(<?= $propiedad['id'] ?>)">
                                <i class="bi bi-heart"></i> Favorito
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Galería de Imágenes -->
                <div class="property-gallery mb-4">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" 
                                 class="img-fluid rounded main-image w-100" 
                                 alt="<?= htmlspecialchars($propiedad['titulo']) ?>"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#imageModal"
                                 data-bs-image="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>">
                        </div>
                        
                        <?php if ($imagenes_result->num_rows > 0): ?>
                            <?php while ($imagen = $imagenes_result->fetch_assoc()): ?>
                                <div class="col-md-4 mb-3">
                                    <img src="img/<?= $imagen['ruta_imagen'] ?>" 
                                         class="img-fluid rounded w-100" 
                                         alt="<?= htmlspecialchars($imagen['descripcion'] ?? 'Imagen de la propiedad') ?>"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal"
                                         data-bs-image="img/<?= $imagen['ruta_imagen'] ?>">
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Características -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Características</h4>
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <i class="bi bi-house feature-icon text-primary"></i>
                                <h5><?= $propiedad['habitaciones'] ?></h5>
                                <small class="text-muted">Habitaciones</small>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <i class="bi bi-droplet feature-icon text-info"></i>
                                <h5><?= $propiedad['banos'] ?></h5>
                                <small class="text-muted">Baños</small>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <i class="bi bi-arrows-fullscreen feature-icon text-success"></i>
                                <h5><?= $propiedad['area_m2'] ?? 'N/A' ?></h5>
                                <small class="text-muted">m² Construidos</small>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <i class="bi bi-car-front feature-icon text-warning"></i>
                                <h5><?= $propiedad['parqueos'] ?></h5>
                                <small class="text-muted">Parqueos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Descripción</h4>
                        <p class="mb-3"><strong>Descripción breve:</strong></p>
                        <p><?= nl2br(htmlspecialchars($propiedad['descripcion_breve'])) ?></p>
                        
                        <?php if ($propiedad['descripcion_larga']): ?>
                            <p class="mb-3 mt-4"><strong>Descripción detallada:</strong></p>
                            <p><?= nl2br(htmlspecialchars($propiedad['descripcion_larga'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ubicación -->
                <?php if ($propiedad['direccion_completa'] || $propiedad['mapa']): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h4 class="card-title">Ubicación</h4>
                        <?php if ($propiedad['direccion_completa']): ?>
                            <p><i class="bi bi-geo-alt text-danger"></i> <?= htmlspecialchars($propiedad['direccion_completa']) ?></p>
                        <?php endif; ?>
                        
                        <?php if ($propiedad['mapa']): ?>
                            <div class="map-container d-flex align-items-center justify-content-center">
                                <a href="<?= htmlspecialchars($propiedad['mapa']) ?>" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-map"></i> Ver en Google Maps
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Propiedades Relacionadas -->
                <?php if ($relacionadas_result->num_rows > 0): ?>
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Más propiedades de este vendedor</h4>
                        <div class="row">
                            <?php while ($relacionada = $relacionadas_result->fetch_assoc()): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <img src="img/<?= $relacionada['imagen_destacada'] ?? 'prop-dest1.png' ?>" 
                                             class="card-img-top" style="height: 150px; object-fit: cover;" 
                                             alt="<?= htmlspecialchars($relacionada['titulo']) ?>">
                                        <div class="card-body p-2">
                                            <h6 class="card-title small"><?= htmlspecialchars($relacionada['titulo']) ?></h6>
                                            <p class="card-text small text-muted">
                                                $<?= number_format($relacionada['precio'], 0, ',', '.') ?>
                                                <?= $relacionada['tipo'] == 'alquiler' ? '/mes' : '' ?>
                                            </p>
                                            <a href="detalle_propiedad.php?id=<?= $relacionada['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">Ver</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Información del Vendedor -->
                <div class="card contact-card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información del Vendedor</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <i class="bi bi-person-circle fs-1 text-primary me-3"></i>
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($propiedad['vendedor_nombre']) ?></h6>
                                <small class="text-muted">Agente de Ventas</small>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <i class="bi bi-telephone text-success me-2"></i>
                            <a href="tel:<?= $propiedad['vendedor_telefono'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($propiedad['vendedor_telefono']) ?>
                            </a>
                        </div>
                        
                        <div class="mb-3">
                            <i class="bi bi-envelope text-primary me-2"></i>
                            <a href="mailto:<?= $propiedad['vendedor_correo'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($propiedad['vendedor_correo']) ?>
                            </a>
                        </div>

                        <!-- Formulario de Contacto -->
                        <?php if (isset($_SESSION['usuario_id'])): ?>
                            <form method="POST" class="mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Interés</label>
                                    <select class="form-select" name="tipo_interes" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="<?= $propiedad['tipo'] ?>"><?= ucfirst($propiedad['tipo']) ?></option>
                                        <option value="informacion">Solo información</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Tu teléfono</label>
                                    <input type="tel" class="form-control" name="telefono_contacto" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Fecha preferida para visita (opcional)</label>
                                    <input type="date" class="form-control" name="fecha_preferida" min="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mensaje</label>
                                    <textarea class="form-control" name="mensaje" rows="4" required 
                                              placeholder="Hola, estoy interesado en esta propiedad..."></textarea>
                                </div>

                                <button type="submit" name="enviar_reserva" class="btn btn-primary w-100">
                                    <i class="bi bi-envelope"></i> Contactar Vendedor
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="text-center">
                                <p class="text-muted">Inicia sesión para contactar al vendedor</p>
                                <a href="login.php" class="btn btn-primary w-100">Iniciar Sesión</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Botones de Acción Rápida -->
                <div class="d-grid gap-2">
                    <a href="tel:<?= $propiedad['vendedor_telefono'] ?>" class="btn btn-success">
                        <i class="bi bi-telephone"></i> Llamar Ahora
                    </a>
                    <a href="https://wa.me/506<?= str_replace(['-', ' '], '', $propiedad['vendedor_telefono']) ?>?text=Hola, estoy interesado en la propiedad: <?= urlencode($propiedad['titulo']) ?>" 
                       class="btn btn-success" target="_blank">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <?php if ($propiedad['mapa']): ?>
                        <a href="<?= htmlspecialchars($propiedad['mapa']) ?>" target="_blank" class="btn btn-info">
                            <i class="bi bi-map"></i> Ver Ubicación
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Imágenes -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" style="z-index: 1000;"></button>
                    <img id="modalImage" class="img-fluid w-100" alt="Imagen de la propiedad">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modal de imágenes
        document.addEventListener('DOMContentLoaded', function() {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            
            imageModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const imageSrc = button.getAttribute('data-bs-image');
                modalImage.src = imageSrc;
            });
        });

        // Función para favoritos (puedes implementarla después)
        function toggleFavorito(propiedadId) {
            // Aquí puedes implementar la funcionalidad de favoritos
            alert('Funcionalidad de favoritos en desarrollo');
        }
    </script>
</body>
</html>