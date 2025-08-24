<?php
session_start();
require_once "../config/database.php";

// Verificar que sea admin
if (!isset($_SESSION["usuario_rol"]) || $_SESSION["usuario_rol"] !== "admin") {
    header("Location: ../index.php");
    exit;
}

$mensaje = "";
$tipo_mensaje = "";

// Obtener configuraciones actuales
$config_query = "SELECT clave, valor FROM configuraciones";
$config_result = $conn->query($config_query);
$configuraciones = [];
while ($row = $config_result->fetch_assoc()) {
    $configuraciones[$row['clave']] = $row['valor'];
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['actualizar_configuracion'])) {
        $campos_config = [
            'color_tema' => $_POST['color_tema'],
            'mensaje_banner' => $_POST['mensaje_banner'], 
            'quienes_somos_texto' => $_POST['quienes_somos_texto'],
            'direccion' => $_POST['direccion'],
            'telefono' => $_POST['telefono'],
            'email_contacto' => $_POST['email_contacto'],
            'facebook_url' => $_POST['facebook_url'],
            'youtube_url' => $_POST['youtube_url'],
            'instagram_url' => $_POST['instagram_url']
        ];

        $conn->begin_transaction();
        try {
            foreach ($campos_config as $clave => $valor) {
                // Verificar si la configuración existe
                $check_query = "SELECT id FROM configuraciones WHERE clave = ?";
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->bind_param("s", $clave);
                $check_stmt->execute();
                $result_check = $check_stmt->get_result();
                
                if ($result_check->num_rows > 0) {
                    // Actualizar configuración existente
                    $update_query = "UPDATE configuraciones SET valor = ? WHERE clave = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("ss", $valor, $clave);
                } else {
                    // Insertar nueva configuración
                    $insert_query = "INSERT INTO configuraciones (clave, valor) VALUES (?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ss", $clave, $valor);
                }
                $stmt->execute();
            }

            // Manejo de archivos subidos
            $archivos = ['icono_principal', 'icono_blanco', 'imagen_banner', 'quienes_somos_imagen'];
            foreach ($archivos as $archivo) {
                if (isset($_FILES[$archivo]) && $_FILES[$archivo]['error'] == 0) {
                    $extension = pathinfo($_FILES[$archivo]['name'], PATHINFO_EXTENSION);
                    $nombre_archivo = $archivo . '_' . time() . '.' . $extension;
                    $ruta_destino = "../img/" . $nombre_archivo;
                    
                    if (move_uploaded_file($_FILES[$archivo]['tmp_name'], $ruta_destino)) {
                        // Verificar si la configuración existe
                        $check_query = "SELECT id FROM configuraciones WHERE clave = ?";
                        $check_stmt = $conn->prepare($check_query);
                        $check_stmt->bind_param("s", $archivo);
                        $check_stmt->execute();
                        $result_check = $check_stmt->get_result();
                        
                        if ($result_check->num_rows > 0) {
                            $update_img = "UPDATE configuraciones SET valor = ? WHERE clave = ?";
                            $stmt_img = $conn->prepare($update_img);
                            $stmt_img->bind_param("ss", $nombre_archivo, $archivo);
                        } else {
                            $insert_img = "INSERT INTO configuraciones (clave, valor) VALUES (?, ?)";
                            $stmt_img = $conn->prepare($insert_img);
                            $stmt_img->bind_param("ss", $archivo, $nombre_archivo);
                        }
                        $stmt_img->execute();
                    }
                }
            }

            $conn->commit();
            $mensaje = "Configuración actualizada exitosamente. Los cambios se aplicarán inmediatamente.";
            $tipo_mensaje = "success";
            
            // Recargar configuraciones
            $config_result = $conn->query($config_query);
            $configuraciones = [];
            while ($row = $config_result->fetch_assoc()) {
                $configuraciones[$row['clave']] = $row['valor'];
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $mensaje = "Error al actualizar la configuración: " . $e->getMessage();
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
    <title>Personalizar Página - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/personalizar.css">
    <style>
        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-left: 10px;
            border: 1px solid #ddd;
        }
        .theme-preview {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .preview-colors {
            display: flex;
            gap: 5px;
            margin-top: 5px;
        }
        .preview-color {
            width: 30px;
            height: 30px;
            border-radius: 3px;
            border: 1px solid #ccc;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <div class="p-4">
                    <div class="text-center mb-4">
                        <img src="../img/logo.png" alt="Logo" class="img-fluid" style="max-height: 100px;">
                        <h5 class="text-white">Admin Panel</h5>
                        <p class="text-muted small">Bienvenido, <?= $_SESSION["usuario_nombre"] ?></p>
                    </div>

                    <nav class="nav nav-pills flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="personalizar.php">
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
                        <a class="nav-link text-light" href="../index.php" target="_blank">
                            <i class="bi bi-house-door me-2"></i> Ver Sitio Web
                        </a>
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>Personalizar Página Web</h2>
                        <p class="text-muted">Configure la apariencia y contenido del sitio web</p>
                    </div>
                    <div>
                    </div>
                </div>

                <?php if ($mensaje): ?>
                    <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-<?= $tipo_mensaje == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                        <?= $mensaje ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <!-- Configuración de Colores -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-palette me-2 text-primary"></i> Configuración de Colores</h4>
                        <div class="row">
                            <div class="col-md-8">
                                <label for="color_tema" class="form-label">Esquema de Colores del Sitio Web</label>
                                <select class="form-select" id="color_tema" name="color_tema" onchange="mostrarPreviewColores(this.value)">
                                    <option value="azul" <?= ($configuraciones['color_tema'] ?? 'azul') == 'azul' ? 'selected' : '' ?>>
                                        🔵 Azul
                                    </option>
                                    <option value="amarillo" <?= ($configuraciones['color_tema'] ?? 'azul') == 'amarillo' ? 'selected' : '' ?>>
                                        🟡 Amarillo
                                    </option>
                                    <option value="gris" <?= ($configuraciones['color_tema'] ?? 'azul') == 'gris' ? 'selected' : '' ?>>
                                        ⚫ Gris
                                    </option>
                                    <option value="blanco" <?= ($configuraciones['color_tema'] ?? 'azul') == 'blanco' ? 'selected' : '' ?>>
                                        ⚪ Blanco
                                    </option>
                                </select>
                                <small class="text-muted">Este cambio afectará todos los colores del sitio web</small>
                                
                                <!-- Preview de colores -->
                                <div id="color-preview" class="theme-preview mt-3">
                                    <strong>Vista previa del tema:</strong>
                                    <div class="preview-colors" id="preview-colors">
                                        <!-- Se llenará con JavaScript -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración de Iconos -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-image me-2 text-primary"></i> Configuración de Iconos</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="icono_principal" class="form-label">Icono Principal (Header)</label>
                                <input type="file" class="form-control" id="icono_principal" name="icono_principal" accept="image/*">
                                <?php if(isset($configuraciones['icono_principal']) && file_exists("../img/".$configuraciones['icono_principal'])): ?>
                                <small class="text-muted">Actual: 
                                    <img src="../img/<?= $configuraciones['icono_principal'] ?>" alt="Icono actual" class="preview-image ms-2" style="max-width: 50px; max-height: 50px;">
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="icono_blanco" class="form-label">Icono Footer</label>
                                <input type="file" class="form-control" id="icono_blanco" name="icono_blanco" accept="image/*">
                                <?php if(isset($configuraciones['icono_blanco']) && file_exists("../img/".$configuraciones['icono_blanco'])): ?>
                                <small class="text-muted">Actual: 
                                    <img src="../img/<?= $configuraciones['icono_blanco'] ?>" alt="Icono footer actual" class="preview-image ms-2" style="max-width: 50px; max-height: 50px;">
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración del Banner -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-card-image me-2 text-primary"></i> Configuración del Banner Principal</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="imagen_banner" class="form-label">Imagen del Banner</label>
                                <input type="file" class="form-control" id="imagen_banner" name="imagen_banner" accept="image/*">
                                <?php if(isset($configuraciones['imagen_banner']) && file_exists("../img/".$configuraciones['imagen_banner'])): ?>
                                <small class="text-muted">Actual: 
                                    <img src="../img/<?= $configuraciones['imagen_banner'] ?>" alt="Banner actual" class="preview-image ms-2" style="max-width: 100px; max-height: 60px; object-fit: cover;">
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="mensaje_banner" class="form-label">Mensaje del Banner</label>
                                <textarea class="form-control" id="mensaje_banner" name="mensaje_banner" rows="3" placeholder="Ingrese el mensaje principal del banner"><?= htmlspecialchars($configuraciones['mensaje_banner'] ?? '') ?></textarea>
                                <small class="text-muted">Este mensaje aparecerá sobre la imagen del banner</small>
                            </div>
                        </div>
                    </div>

                    <!-- Configuración Quienes Somos -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-info-circle me-2 text-primary"></i> Sección "Quiénes Somos"</h4>
                        <div class="row">
                            <div class="col-md-8">
                                <label for="quienes_somos_texto" class="form-label">Texto de la Sección</label>
                                <textarea class="form-control" id="quienes_somos_texto" name="quienes_somos_texto" rows="5" placeholder="Describa su empresa..."><?= htmlspecialchars($configuraciones['quienes_somos_texto'] ?? '') ?></textarea>
                                <small class="text-muted">Descripción de la empresa que aparecerá en la sección "Quiénes Somos"</small>
                            </div>
                            <div class="col-md-4">
                                <label for="quienes_somos_imagen" class="form-label">Imagen de la Sección</label>
                                <input type="file" class="form-control mb-3" id="quienes_somos_imagen" name="quienes_somos_imagen" accept="image/*">
                                <?php if(isset($configuraciones['quienes_somos_imagen']) && file_exists("../img/".$configuraciones['quienes_somos_imagen'])): ?>
                                <small class="text-muted">Actual:</small><br>
                                <img src="../img/<?= $configuraciones['quienes_somos_imagen'] ?>" alt="Imagen Quienes Somos" class="preview-image mt-2" style="max-width: 150px; max-height: 100px; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Redes Sociales -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-share me-2 text-primary"></i> Redes Sociales</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="facebook_url" class="form-label">
                                    <i class="bi bi-facebook text-primary me-1"></i> Facebook URL
                                </label>
                                <input type="url" class="form-control" id="facebook_url" name="facebook_url" value="<?= htmlspecialchars($configuraciones['facebook_url'] ?? '') ?>" placeholder="https://facebook.com/tu-pagina">
                            </div>
                            <div class="col-md-4">
                                <label for="youtube_url" class="form-label">
                                    <i class="bi bi-youtube text-danger me-1"></i> YouTube URL
                                </label>
                                <input type="url" class="form-control" id="youtube_url" name="youtube_url" value="<?= htmlspecialchars($configuraciones['youtube_url'] ?? '') ?>" placeholder="https://youtube.com/tu-canal">
                            </div>
                            <div class="col-md-4">
                                <label for="instagram_url" class="form-label">
                                    <i class="bi bi-instagram text-warning me-1"></i> Instagram URL
                                </label>
                                <input type="url" class="form-control" id="instagram_url" name="instagram_url" value="<?= htmlspecialchars($configuraciones['instagram_url'] ?? '') ?>" placeholder="https://instagram.com/tu-perfil">
                            </div>
                        </div>
                    </div>

                    <!-- Información de Contacto -->
                    <div class="config-section">
                        <h4 class="mb-4"><i class="bi bi-telephone me-2 text-primary"></i> Información de Contacto</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <label for="direccion" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i> Dirección
                                </label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="<?= htmlspecialchars($configuraciones['direccion'] ?? '') ?>" placeholder="Ciudad, País">
                            </div>
                            <div class="col-md-4">
                                <label for="telefono" class="form-label">
                                    <i class="bi bi-telephone me-1"></i> Teléfono
                                </label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($configuraciones['telefono'] ?? '') ?>" placeholder="2222-3333">
                            </div>
                            <div class="col-md-4">
                                <label for="email_contacto" class="form-label">
                                    <i class="bi bi-envelope me-1"></i> Email de Contacto
                                </label>
                                <input type="email" class="form-control" id="email_contacto" name="email_contacto" value="<?= htmlspecialchars($configuraciones['email_contacto'] ?? '') ?>" placeholder="contacto@empresa.com">
                            </div>
                        </div>
                    </div>

                    <!-- Botón de Guardar -->
                    <div class="text-center mb-5">
                        <button type="submit" name="actualizar_configuracion" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-check-circle me-2"></i> Guardar Todos los Cambios
                        </button>
                        <p class="text-muted mt-2">Los cambios se aplicarán inmediatamente en el sitio web</p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configuración de colores para preview
        const temasColores = {
            'azul': {
                'Principal': '#02050e',
                'Primario': '#3498db', 
                'Secundario': '#150d3e',
                'Accent': '#efb820'
            },
            'amarillo': {
                'Principal': '#02050e',
                'Primario': '#f1c40f',
                'Secundario': '#f39c12', 
                'Accent': '#e67e22'
            },
            'gris': {
                'Principal': '#2c3e50',
                'Primario': '#95a5a6',
                'Secundario': '#34495e',
                'Accent': '#e74c3c'
            },
            'blanco': {
                'Principal': '#ecf0f1',
                'Primario': '#7f8c8d',
                'Secundario': '#bdc3c7',
                'Accent': '#3498db'
            }
        };

        function mostrarPreviewColores(tema) {
            const previewContainer = document.getElementById('preview-colors');
            const colores = temasColores[tema];
            
            previewContainer.innerHTML = '';
            
            Object.keys(colores).forEach(nombreColor => {
                const colorDiv = document.createElement('div');
                colorDiv.className = 'preview-color';
                colorDiv.style.backgroundColor = colores[nombreColor];
                colorDiv.title = `${nombreColor}: ${colores[nombreColor]}`;
                previewContainer.appendChild(colorDiv);
            });
        }

        // Mostrar preview inicial
        document.addEventListener('DOMContentLoaded', function() {
            const colorSelect = document.getElementById('color_tema');
            mostrarPreviewColores(colorSelect.value);
        });

        // Preview de imágenes al seleccionar archivo
        function previewImage(input, targetId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector(`#${targetId} + small img`);
                    if (img) {
                        img.src = e.target.result;
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Agregar event listeners para preview de imágenes
        document.getElementById('icono_principal').addEventListener('change', function() {
            previewImage(this, 'icono_principal');
        });
        
        document.getElementById('icono_blanco').addEventListener('change', function() {
            previewImage(this, 'icono_blanco');
        });
        
        document.getElementById('imagen_banner').addEventListener('change', function() {
            previewImage(this, 'imagen_banner');
        });
        
        document.getElementById('quienes_somos_imagen').addEventListener('change', function() {
            previewImage(this, 'quienes_somos_imagen');
        });
    </script>
</body>
</html>