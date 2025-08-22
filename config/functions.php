<?php
// Funciones helper para el proyecto

/**
 * Obtener configuración del sitio
 */
function obtenerConfiguracion($conn, $clave = null) {
    if ($clave) {
        $query = "SELECT valor FROM configuraciones WHERE clave = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $clave);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row['valor'];
        }
        return null;
    } else {
        $query = "SELECT clave, valor FROM configuraciones";
        $result = $conn->query($query);
        $config = [];
        while ($row = $result->fetch_assoc()) {
            $config[$row['clave']] = $row['valor'];
        }
        return $config;
    }
}

/**
 * Verificar si el usuario está logueado
 */
function verificarLogin() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: index.php");
        exit;
    }
}

/**
 * Verificar rol del usuario
 */
function verificarRol($roles_permitidos) {
    verificarLogin();
    if (!in_array($_SESSION['usuario_rol'], $roles_permitidos)) {
        header("Location: index.php");
        exit;
    }
}

/**
 * Obtener propiedades destacadas
 */
function obtenerPropiedadesDestacadas($conn, $limite = 3) {
    $query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono 
              FROM propiedades p 
              JOIN usuarios u ON p.vendedor_id = u.id 
              WHERE p.destacada = 1 AND p.estado = 'disponible' 
              ORDER BY p.fecha_creacion DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limite);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener propiedades por tipo
 */
function obtenerPropiedadesPorTipo($conn, $tipo, $limite = 3) {
    $query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono 
              FROM propiedades p 
              JOIN usuarios u ON p.vendedor_id = u.id 
              WHERE p.tipo = ? AND p.estado = 'disponible' 
              ORDER BY p.fecha_creacion DESC 
              LIMIT ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $tipo, $limite);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Buscar propiedades
 */
function buscarPropiedades($conn, $termino, $tipo = null) {
    $termino = "%$termino%";
    
    if ($tipo) {
        $query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono 
                  FROM propiedades p 
                  JOIN usuarios u ON p.vendedor_id = u.id 
                  WHERE p.estado = 'disponible' 
                  AND p.tipo = ? 
                  AND (p.titulo LIKE ? OR p.descripcion_breve LIKE ? OR p.descripcion_larga LIKE ? OR p.ubicacion LIKE ?)
                  ORDER BY p.fecha_creacion DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $tipo, $termino, $termino, $termino, $termino);
    } else {
        $query = "SELECT p.*, u.nombre as vendedor_nombre, u.telefono as vendedor_telefono 
                  FROM propiedades p 
                  JOIN usuarios u ON p.vendedor_id = u.id 
                  WHERE p.estado = 'disponible' 
                  AND (p.titulo LIKE ? OR p.descripcion_breve LIKE ? OR p.descripcion_larga LIKE ? OR p.ubicacion LIKE ?)
                  ORDER BY p.fecha_creacion DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $termino, $termino, $termino, $termino);
    }
    
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Formatear precio
 */
function formatearPrecio($precio, $tipo = 'venta') {
    $precio_formateado = '$' . number_format($precio, 0, ',', '.');
    return $tipo == 'alquiler' ? $precio_formateado . '/mes' : $precio_formateado;
}

/**
 * Obtener estadísticas generales
 */
function obtenerEstadisticasGenerales($conn) {
    $stats = [];
    
    // Total de propiedades disponibles
    $query = "SELECT COUNT(*) as total FROM propiedades WHERE estado = 'disponible'";
    $result = $conn->query($query);
    $stats['total_propiedades'] = $result->fetch_assoc()['total'];
    
    // Propiedades en venta
    $query = "SELECT COUNT(*) as total FROM propiedades WHERE estado = 'disponible' AND tipo = 'venta'";
    $result = $conn->query($query);
    $stats['propiedades_venta'] = $result->fetch_assoc()['total'];
    
    // Propiedades en alquiler
    $query = "SELECT COUNT(*) as total FROM propiedades WHERE estado = 'disponible' AND tipo = 'alquiler'";
    $result = $conn->query($query);
    $stats['propiedades_alquiler'] = $result->fetch_assoc()['total'];
    
    // Total de agentes
    $query = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'vendedor'";
    $result = $conn->query($query);
    $stats['total_agentes'] = $result->fetch_assoc()['total'];
    
    return $stats;
}

/**
 * Validar email
 */
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Limpiar input
 */
function limpiarInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Generar slug para URLs
 */
function generarSlug($texto) {
    $texto = strtolower($texto);
    $texto = preg_replace('/[áàâäã]/u', 'a', $texto);
    $texto = preg_replace('/[éèêë]/u', 'e', $texto);
    $texto = preg_replace('/[íìîï]/u', 'i', $texto);
    $texto = preg_replace('/[óòôöõ]/u', 'o', $texto);
    $texto = preg_replace('/[úùûü]/u', 'u', $texto);
    $texto = preg_replace('/[ñ]/u', 'n', $texto);
    $texto = preg_replace('/[^a-z0-9\-\s]/', '', $texto);
    $texto = preg_replace('/[\-\s]+/', '-', $texto);
    return trim($texto, '-');
}

/**
 * Subir imagen
 */
function subirImagen($archivo, $directorio = 'img/', $prefijo = 'img_') {
    if ($archivo['error'] != 0) {
        return false;
    }
    
    $extension = pathinfo($archivo['name'], PATHINFO_EXTENSION);
    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array(strtolower($extension), $extensiones_permitidas)) {
        return false;
    }
    
    $nombre_archivo = $prefijo . time() . '.' . $extension;
    $ruta_completa = $directorio . $nombre_archivo;
    
    if (move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        return $nombre_archivo;
    }
    
    return false;
}

/**
 * Enviar email (función base)
 */
function enviarEmail($destinatario, $asunto, $mensaje, $de = null) {
    if (!$de) {
        $de = 'noreply@realestate.com';
    }
    
    $headers = "From: $de\r\n";
    $headers .= "Reply-To: $de\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($destinatario, $asunto, $mensaje, $headers);
}

/**
 * Registrar actividad del usuario
 */
function registrarActividad($conn, $usuario_id, $accion, $descripcion) {
    $query = "INSERT INTO log_actividades (usuario_id, accion, descripcion, fecha) VALUES (?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $usuario_id, $accion, $descripcion);
    return $stmt->execute();
}
?>