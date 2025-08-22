<?php
session_start();
require_once "config/database.php";
require_once "config/functions.php";

// Obtener ID de la propiedad
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header("Location: index.php");
    exit;
}

// Obtener configuraciones del sitio
$config = obtenerConfiguracion($conn);

// Obtener datos de la propiedad
$query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono, u.correo as vendedor_correo 
          FROM propiedades p 
          JOIN usuarios u ON p.vendedor_id = u.id 
          WHERE p.id = ? AND p.estado = 'disponible'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: index.php");
    exit;
}

$propiedad = $result->fetch_assoc();

// Obtener propiedades relacionadas
$query_relacionadas = "SELECT p.*, u.nombre as vendedor_nombre 
                       FROM propiedades p 
                       JOIN usuarios u ON p.vendedor_id = u.id 
                       WHERE p.id != ? AND p.categoria = ? AND p.estado = 'disponible' 
                       ORDER BY p.destacada DESC, p.fecha_creacion DESC 
                       LIMIT 3";
$stmt_relacionadas = $conn->prepare($query_relacionadas);
$stmt_relacionadas->bind_param("is", $id, $propiedad['categoria']);
$stmt_relacionadas->execute();
$propiedades_relacionadas = $stmt_relacionadas->get_result();

// Procesar formulario de contacto
$mensaje_contacto = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enviar_consulta'])) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $telefono = $_POST['telefono'];
    $mensaje = $_POST['mensaje'];
    
    // Insertar en la base de datos
    $insert_query = "INSERT INTO mensajes_contacto (nombre, correo, telefono, mensaje, propiedad_id) VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ssssi", $nombre, $correo, $telefono, $mensaje, $id);
    
    if ($insert_stmt->execute()) {
        $mensaje_contacto = "success";
        
        // Enviar notificación al vendedor (opcional)
        $asunto = "Nueva consulta sobre: " . $propiedad['titulo'];
        $mensaje_email = "
            <h3>Nueva consulta sobre tu propiedad</h3>
            <p><strong>Propiedad:</strong> {$propiedad['titulo']}</p>
            <p><strong>De:</strong> $nombre ($correo)</p>
            <p><strong>Teléfono:</strong> $telefono</p>
            <p><strong>Mensaje:</strong></p>
            <p>$mensaje</p>
        ";
        enviarEmail($propiedad['vendedor_correo'], $asunto, $mensaje_email);
    } else {
        $mensaje_contacto = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($propiedad['titulo']) ?> - <?= htmlspecialchars($config['mensaje_banner']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/propiedad.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #2c3e50, #3498db);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="img/<?= $config['icono_blanco'] ?>" alt="Logo" style="height: 40px;">
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left me-2"></i> Volver al Inicio
                </a>
            </div>
        </div>
    </nav>

    <!-- Property Hero Section -->
    <div class="container my-5">
        <div class="property-hero" style="background-image: url('img/<?= $propiedad['imagen_destacada'] ?: 'default-property.jpg' ?>');">
            <?php if ($propiedad['destacada']): ?>
                <span class="featured-tag">Propiedad Destacada</span>
            <?php endif; ?>
            
            <span class="price-tag">
                <?= formatearPrecio($propiedad['precio'], $propiedad['tipo']) ?>
            </span>
            
            <div class="property-hero-content">
                <span class="badge bg-<?= $propiedad['tipo'] == 'venta' ? 'success' : 'info' ?> mb-2">
                    <?= ucfirst($propiedad['tipo']) ?>
                </span>
                <h1 class="mb-2"><?= htmlspecialchars($propiedad['titulo']) ?></h1>
                <p class="mb-0">
                    <i class="bi bi-geo-alt me-2"></i>
                    <?= htmlspecialchars($propiedad['ubicacion']) ?>
                </p>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Property Details -->
                <div class="property-details-card">
                    <h3 class="mb-4">Descripción de la Propiedad</h3>
                    <p class="lead text-muted"><?= htmlspecialchars($propiedad['descripcion_breve']) ?></p>
                    <hr>
                    <div class="mb-4">
                        <?= nl2br(htmlspecialchars($propiedad['descripcion_larga'])) ?>
                    </div>
                    
                    <!-- Property Features -->
                    <h4 class="mb-4">Características</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="feature-item">
                                <i class="bi bi-door-closed"></i>
                                <h5><?= $propiedad['habitaciones'] ?: 'N/A' ?></h5>
                                <small class="text-muted">Habitaciones</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="feature-item">
                                <i class="bi bi-droplet"></i>
                                <h5><?= $propiedad['banos'] ?: 'N/A' ?></h5>
                                <small class="text-muted">Baños</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="feature-item">
                                <i class="bi bi-rulers"></i>
                                <h5><?= $propiedad['area_m2'] ? $propiedad['area_m2'] . ' m²' : 'N/A' ?></h5>
                                <small class="text-muted">Área Total</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="feature-item">
                                <i class="bi bi-car-front"></i>
                                <h5><?= $propiedad['parqueos'] ?: 'N/A' ?></h5>
                                <small class="text-muted">Parqueos</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <?php if ($propiedad['direccion_completa'] || $propiedad['mapa']): ?>
                <div class="property-details-card">
                    <h4 class="mb-4">Ubicación</h4>
                    <?php if ($propiedad['direccion_completa']): ?>
                        <p><i class="bi bi-geo-alt me-2"></i> <?= htmlspecialchars($propiedad['direccion_completa']) ?></p>
                    <?php endif; ?>
                    
                    <?php if ($propiedad['mapa']): ?>
                        <div class="map-container">
                            <iframe src="<?= htmlspecialchars($propiedad['mapa']) ?>" 
                                    width="100%" height="400" style="border:0;" 
                                    allowfullscreen="" loading="lazy">
                            </iframe>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Agent Info -->
                <div class="agent-card mb-4">
                    <div class="agent-avatar">
                        <i class="bi bi-person"></i>
                    </div>
                    <h5 class="mb-3"><?= htmlspecialchars($propiedad['vendedor_nombre']) ?></h5>
                    <p class="mb-2">Agente de Ventas</p>
                    <hr style="border-color: rgba(255,255,255,0.3);">
                    <div class="d-flex justify-content-between mb-2">
                        <span><i class="bi bi-telephone me-2"></i></span>
                        <span><?= htmlspecialchars($propiedad['vendedor_telefono']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span><i class="bi bi-envelope me-2"></i></span>
                        <span><?= htmlspecialchars($propiedad['vendedor_correo']) ?></span>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="contact-form">
                    <h5 class="mb-4">¿Interesado en esta propiedad?</h5>
                    
                    <?php if ($mensaje_contacto == "success"): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i>
                            ¡Mensaje enviado exitosamente! El agente se contactará contigo pronto.
                        </div>
                    <?php elseif ($mensaje_contacto == "error"): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Hubo un error al enviar el mensaje. Inténtalo de nuevo.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="correo" name="correo" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" 
                                      placeholder="Estoy interesado en esta propiedad. Me gustaría más información..." required></textarea>
                        </div>
                        <button type="submit" name="enviar_consulta" class="btn btn-primary w-100">
                            <i class="bi bi-send me-2"></i> Enviar Consulta
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Related Properties -->
        <?php if ($propiedades_relacionadas->num_rows > 0): ?>
        <div class="mt-5">
            <h3 class="mb-4">Propiedades Similares</h3>
            <div class="row">
                <?php while ($relacionada = $propiedades_relacionadas->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card related-property">
                        <img src="img/<?= $relacionada['imagen_destacada'] ?: 'default-property.jpg' ?>" 
                             class="card-img-top" style="height: 200px; object-fit: cover;"
                             alt="<?= htmlspecialchars($relacionada['titulo']) ?>">
                        <div class="card-body">
                            <h6 class="card-title">
                                <a href="propiedad.php?id=<?= $relacionada['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($relacionada['titulo']) ?>
                                </a>
                            </h6>
                            <p class="text-muted small">
                                <i class="bi bi-geo-alt me-1"></i>
                                <?= htmlspecialchars($relacionada['ubicacion']) ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-primary">
                                    <?= formatearPrecio($relacionada['precio'], $relacionada['tipo']) ?>
                                </strong>
                                <span class="badge bg-<?= $relacionada['tipo'] == 'venta' ? 'success' : 'info' ?>">
                                    <?= ucfirst($relacionada['tipo']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Contacto</h5>
                    <p><i class="bi bi-geo-alt me-2"></i> <?= htmlspecialchars($config['direccion']) ?></p>
                    <p><i class="bi bi-telephone me-2"></i> <?= htmlspecialchars($config['telefono']) ?></p>
                    <p><i class="bi bi-envelope me-2"></i> <?= htmlspecialchars($config['email_contacto']) ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5>Síguenos</h5>
                    <div class="social-links">
                        <a href="<?= htmlspecialchars($config['facebook_url']) ?>" class="text-white me-3">
                            <i class="bi bi-facebook"></i> Facebook
                        </a>
                        <a href="<?= htmlspecialchars($config['instagram_url']) ?>" class="text-white me-3">
                            <i class="bi bi-instagram"></i> Instagram
                        </a>
                        <a href="<?= htmlspecialchars($config['youtube_url']) ?>" class="text-white">
                            <i class="bi bi-youtube"></i> YouTube
                        </a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 Real Estate. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>