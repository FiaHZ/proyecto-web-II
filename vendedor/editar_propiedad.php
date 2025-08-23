<?php
session_start();
require_once "../config/database.php";

// Verificar que sea vendedor
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "vendedor") {
    header("Location: ../index.php");
    exit;
}

$vendedor_id = $_SESSION["usuario_id"];
$propiedad_id = $_GET['id'] ?? 0;
$mensaje = "";
$tipo_mensaje = "";

// Verificar que la propiedad pertenece al vendedor
$query = "SELECT * FROM propiedades WHERE id = ? AND vendedor_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $propiedad_id, $vendedor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: propiedades.php");
    exit;
}

$propiedad = $result->fetch_assoc();

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['actualizar_propiedad'])) {
        $tipo = $_POST['tipo'];
        $categoria = $_POST['categoria'];
        $destacada = isset($_POST['destacada']) ? 1 : 0;
        $titulo = $_POST['titulo'];
        $descripcion_breve = $_POST['descripcion_breve'];
        $descripcion_larga = $_POST['descripcion_larga'];
        $precio = $_POST['precio'];
        $ubicacion = $_POST['ubicacion'];
        $direccion_completa = $_POST['direccion_completa'];
        $mapa = $_POST['mapa'];
        $habitaciones = $_POST['habitaciones'];
        $banos = $_POST['banos'];
        $area_m2 = $_POST['area_m2'];
        $parqueos = $_POST['parqueos'];

        // Manejo de imagen
        $imagen_destacada = $propiedad['imagen_destacada'];
        if (isset($_FILES['imagen_destacada']) && $_FILES['imagen_destacada']['error'] == 0) {
            $extension = pathinfo($_FILES['imagen_destacada']['name'], PATHINFO_EXTENSION);
            $nombre_imagen = 'prop_' . time() . '.' . $extension;
            $ruta_destino = "../img/" . $nombre_imagen;
            
            if (move_uploaded_file($_FILES['imagen_destacada']['tmp_name'], $ruta_destino)) {
                // Eliminar imagen anterior si no es la por defecto
                if ($imagen_destacada !== 'prop-dest1.png' && file_exists("../img/" . $imagen_destacada)) {
                    unlink("../img/" . $imagen_destacada);
                }
                $imagen_destacada = $nombre_imagen;
            }
        }

        $update_query = "UPDATE propiedades SET 
            tipo = ?, categoria = ?, destacada = ?, titulo = ?, descripcion_breve = ?, 
            descripcion_larga = ?, precio = ?, ubicacion = ?, direccion_completa = ?, 
            mapa = ?, imagen_destacada = ?, habitaciones = ?, banos = ?, area_m2 = ?, parqueos = ? 
            WHERE id = ? AND vendedor_id = ?";
        
        $stmt_update = $conn->prepare($update_query);
        $stmt_update->bind_param("ssisssdssssiiidii", 
            $tipo, $categoria, $destacada, $titulo, $descripcion_breve, 
            $descripcion_larga, $precio, $ubicacion, $direccion_completa, 
            $mapa, $imagen_destacada, $habitaciones, $banos, $area_m2, $parqueos, 
            $propiedad_id, $vendedor_id);
        
        if ($stmt_update->execute()) {
            $mensaje = "Propiedad actualizada exitosamente";
            $tipo_mensaje = "success";
            
            // Recargar datos actualizados
            $stmt->execute();
            $result = $stmt->get_result();
            $propiedad = $result->fetch_assoc();
        } else {
            $mensaje = "Error al actualizar la propiedad: " . $conn->error;
            $tipo_mensaje = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Propiedad - Panel Vendedor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/editar_propiedad.css">
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="propiedades.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Editar Propiedad</h2>
                        <p class="text-muted">Modifica los datos de tu propiedad</p>
                    </div>
                    <div>
                        <a href="propiedades.php" class="btn btn-outline-secondary btn-custom me-2">
                            <i class="bi bi-arrow-left me-2"></i>Volver
                        </a>
                        <a href="../detalle_propiedad.php?id=<?= $propiedad['id'] ?>" 
                           class="btn btn-outline-primary btn-custom" target="_blank">
                            <i class="bi bi-eye me-2"></i>Vista Previa
                        </a>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formulario de Edición -->
                <div class="form-card">
                    <form method="POST" enctype="multipart/form-data">
                        <!-- Información Básica -->
                        <h5 class="mb-4">
                            <i class="bi bi-info-circle me-2 text-primary"></i>
                            Información Básica
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipo de Propiedad *</label>
                                    <select class="form-select" name="tipo" required>
                                        <option value="venta" <?= $propiedad['tipo'] == 'venta' ? 'selected' : '' ?>>Venta</option>
                                        <option value="alquiler" <?= $propiedad['tipo'] == 'alquiler' ? 'selected' : '' ?>>Alquiler</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Categoría *</label>
                                    <select class="form-select" name="categoria" required>
                                        <option value="casa" <?= $propiedad['categoria'] == 'casa' ? 'selected' : '' ?>>Casa</option>
                                        <option value="apartamento" <?= $propiedad['categoria'] == 'apartamento' ? 'selected' : '' ?>>Apartamento</option>
                                        <option value="local" <?= $propiedad['categoria'] == 'local' ? 'selected' : '' ?>>Local</option>
                                        <option value="terreno" <?= $propiedad['categoria'] == 'terreno' ? 'selected' : '' ?>>Terreno</option>
                                        <option value="oficina" <?= $propiedad['categoria'] == 'oficina' ? 'selected' : '' ?>>Oficina</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Título *</label>
                            <input type="text" class="form-control" name="titulo" 
                                   value="<?= htmlspecialchars($propiedad['titulo']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Breve *</label>
                            <textarea class="form-control" name="descripcion_breve" rows="3" required><?= htmlspecialchars($propiedad['descripcion_breve']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Completa</label>
                            <textarea class="form-control" name="descripcion_larga" rows="5"><?= htmlspecialchars($propiedad['descripcion_larga']) ?></textarea>
                        </div>

                        <hr class="my-4">

                        <!-- Precio y Ubicación -->
                        <h5 class="mb-4">
                            <i class="bi bi-currency-dollar me-2 text-success"></i>
                            Precio y Ubicación
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Precio *</label>
                                    <input type="number" class="form-control" name="precio" 
                                           value="<?= $propiedad['precio'] ?>" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Ubicación *</label>
                                    <input type="text" class="form-control" name="ubicacion" 
                                           value="<?= htmlspecialchars($propiedad['ubicacion']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Dirección Completa</label>
                            <textarea class="form-control" name="direccion_completa" rows="2"><?= htmlspecialchars($propiedad['direccion_completa']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Enlace de Mapa (Google Maps)</label>
                            <input type="url" class="form-control" name="mapa" 
                                   value="<?= htmlspecialchars($propiedad['mapa']) ?>">
                        </div>

                        <hr class="my-4">

                        <!-- Características -->
                        <h5 class="mb-4">
                            <i class="bi bi-house-gear me-2 text-info"></i>
                            Características
                        </h5>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Habitaciones</label>
                                    <input type="number" class="form-control" name="habitaciones" 
                                           value="<?= $propiedad['habitaciones'] ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Baños</label>
                                    <input type="number" class="form-control" name="banos" 
                                           value="<?= $propiedad['banos'] ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Área (m²)</label>
                                    <input type="number" class="form-control" name="area_m2" 
                                           value="<?= $propiedad['area_m2'] ?>" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label">Parqueos</label>
                                    <input type="number" class="form-control" name="parqueos" 
                                           value="<?= $propiedad['parqueos'] ?>">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Imagen -->
                        <h5 class="mb-4">
                            <i class="bi bi-image me-2 text-warning"></i>
                            Imagen Destacada
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nueva Imagen (opcional)</label>
                                    <input type="file" class="form-control" name="imagen_destacada" accept="image/*">
                                    <small class="text-muted">Deja vacío para mantener la imagen actual</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="property-preview">
                                    <p class="text-muted mb-2">Imagen Actual:</p>
                                    <img src="../img/<?= $propiedad['imagen_destacada'] ?>" 
                                         alt="Imagen actual" class="current-image">
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="destacada" id="destacada" 
                                   <?= $propiedad['destacada'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="destacada">
                                <i class="bi bi-star me-1"></i>
                                Marcar como propiedad destacada
                            </label>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="propiedades.php" class="btn btn-outline-secondary btn-custom">
                                <i class="bi bi-x-circle me-2"></i>Cancelar
                            </a>
                            <button type="submit" name="actualizar_propiedad" class="btn btn-primary btn-custom">
                                <i class="bi bi-check-circle me-2"></i>Actualizar Propiedad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-cerrar alertas después de 5 segundos
        setTimeout(function() {
            const alert = document.querySelector('.alert-dismissible');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);

        // Preview de nueva imagen
        document.querySelector('input[name="imagen_destacada"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.current-image').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>