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

// Obtener propiedades destacadas (3 más recientes)
$destacadas_query = "SELECT * FROM vista_propiedades_completa WHERE destacada = 1 ORDER BY fecha_creacion DESC LIMIT 3";
$destacadas_result = $conn->query($destacadas_query);

// Obtener propiedades en venta (3 más recientes)
$ventas_query = "SELECT * FROM vista_propiedades_completa WHERE tipo = 'venta' ORDER BY fecha_creacion DESC LIMIT 3";
$ventas_result = $conn->query($ventas_query);

// Obtener propiedades en alquiler (3 más recientes)
$alquiler_query = "SELECT * FROM vista_propiedades_completa WHERE tipo = 'alquiler' ORDER BY fecha_creacion DESC LIMIT 3";
$alquiler_result = $conn->query($alquiler_query);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solutions Real State</title>

    <link rel="stylesheet" href="proyecto-web-II/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-pap3x...tu-integrity-hash..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!----Navbar---->
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
                <li><a href="#quienes-somos">QUIENES SOMOS |</a></li>
                <li><a href="alquileres.php">ALQUILERES |</a></li>
                <li><a href="ventas.php">VENTAS |</a></li>
                <li><a href="#contacto">CONTACTENOS</a></li>
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

    <!----Imagen de encabezado---->
    <div class="img-header">
        <img src="img/<?= $config['imagen_banner'] ?? 'img-1.jpg' ?>" alt="Banner principal" />
        <div class="text-banner">
            <h1>
                <?= htmlspecialchars($config['mensaje_banner'] ?? 'PERMITENOS AYUDARTE A CUMPLIR TUS SUEÑOS') ?>
            </h1>
        </div>
    </div>

    <section class="quienes-somos" id="quienes-somos">
        <h1>QUIENES SOMOS</h1>
        <div class="contenido-quienes-somos">
            <div class="text-quienes-somos">
                <p><?= nl2br(htmlspecialchars($config['quienes_somos_texto'] ?? 'Somos una empresa dedicada a ayudarte a encontrar la propiedad de tus sueños.')) ?></p>
            </div>
            <div>
                <img class="img-quienes-somos" src="img/<?= $config['quienes_somos_imagen'] ?? 'quienes-somos.jpg' ?>" alt="Quienes Somos">
            </div>
        </div>
    </section>

    <!----Propiedades Destacadas---->
    <section class="propiedad">
        <h1>PROPIEDADES DESTACADAS</h1>

        <div class="contenido-propiedad">
            <?php if ($destacadas_result->num_rows > 0): ?>
                <?php while ($propiedad = $destacadas_result->fetch_assoc()): ?>
                    <div class="card-propiedad">
                        <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" alt="<?= htmlspecialchars($propiedad['titulo']) ?>" />
                        <div class="card-content">
                            <h3><?= htmlspecialchars($propiedad['titulo']) ?></h3>
                            <p>
                                <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...
                                <br><small><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?></small>
                                <br><small><i class="bi bi-house"></i> <?= $propiedad['habitaciones'] ?> hab • <i class="bi bi-droplet"></i> <?= $propiedad['banos'] ?> baños</small>
                            </p>
                            <div class="precio-prop">
                                $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                                <?= $propiedad['tipo'] == 'alquiler' ? '/mes' : '' ?>
                            </div>
                            <a href="detalle_propiedad.php?id=<?= $propiedad['id'] ?>" class="btn btn-primary btn-sm mt-2">Ver Detalles</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay propiedades destacadas disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="btn-vermas-container">
            <a href="destacadas.php" class="btn-vermas">Ver más</a>
        </div>
    </section>

    <!----Propiedades Venta---->
    <section class="propiedad-venta">
        <h1>PROPIEDADES EN VENTA</h1>

        <div class="contenido-propiedad-venta">
            <?php if ($ventas_result->num_rows > 0): ?>
                <?php while ($propiedad = $ventas_result->fetch_assoc()): ?>
                    <div class="card-propiedad-venta">
                        <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" alt="<?= htmlspecialchars($propiedad['titulo']) ?>" />
                        <div class="card-content-venta">
                            <h3><?= htmlspecialchars($propiedad['titulo']) ?></h3>
                            <p>
                                <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...
                                <br><small><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?></small>
                                <br><small><i class="bi bi-house"></i> <?= $propiedad['habitaciones'] ?> hab • <i class="bi bi-droplet"></i> <?= $propiedad['banos'] ?> baños</small>
                            </p>
                            <div class="precio-prop-venta">
                                $<?= number_format($propiedad['precio'], 0, ',', '.') ?>
                            </div>
                            <a href="detalle_propiedad.php?id=<?= $propiedad['id'] ?>" class="btn btn-success btn-sm mt-2">Ver Detalles</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay propiedades en venta disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="btn-vermas-container-venta">
            <a href="ventas.php" class="btn-vermas-venta">Ver más</a>
        </div>
    </section>

    <!----Propiedades Alquiler---->
    <section class="propiedad-alquiler">
        <h1>PROPIEDADES EN ALQUILER</h1>

        <div class="contenido-propiedad-alquiler">
            <?php if ($alquiler_result->num_rows > 0): ?>
                <?php while ($propiedad = $alquiler_result->fetch_assoc()): ?>
                    <div class="card-propiedad-alquiler">
                        <img src="img/<?= $propiedad['imagen_destacada'] ?? 'prop-dest1.png' ?>" alt="<?= htmlspecialchars($propiedad['titulo']) ?>" />
                        <div class="card-content-alquiler">
                            <h3><?= htmlspecialchars($propiedad['titulo']) ?></h3>
                            <p>
                                <?= htmlspecialchars(substr($propiedad['descripcion_breve'], 0, 100)) ?>...
                                <br><small><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($propiedad['ubicacion']) ?></small>
                                <br><small><i class="bi bi-house"></i> <?= $propiedad['habitaciones'] ?> hab • <i class="bi bi-droplet"></i> <?= $propiedad['banos'] ?> baños</small>
                            </p>
                            <div class="precio-prop-alquiler">
                                $<?= number_format($propiedad['precio'], 0, ',', '.') ?>/mes
                            </div>
                            <a href="detalle_propiedad.php?id=<?= $propiedad['id'] ?>" class="btn btn-info btn-sm mt-2">Ver Detalles</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No hay propiedades en alquiler disponibles en este momento.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="btn-vermas-container-alquiler">
            <a href="alquileres.php" class="btn-vermas-alquiler">Ver más</a>
        </div>
    </section>

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