-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 25-08-2025 a las 23:54:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `real_estate_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuraciones`
--

CREATE TABLE `configuraciones` (
  `id` int(11) NOT NULL,
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuraciones`
--

INSERT INTO `configuraciones` (`id`, `clave`, `valor`, `descripcion`, `fecha_actualizacion`) VALUES
(1, 'color_tema', 'azul', 'Color principal del sitio (azul, amarillo, gris, blanco)', '2025-08-25 21:37:49'),
(2, 'icono_principal', 'logo.png', 'Nombre del archivo del ícono principal', '2025-08-21 06:20:14'),
(3, 'icono_blanco', 'logo-black.png', 'Nombre del archivo del ícono en blanco', '2025-08-21 06:20:14'),
(4, 'imagen_banner', 'img-1.jpg', 'Imagen principal del banner', '2025-08-21 06:20:14'),
(5, 'mensaje_banner', 'PERMITENOS AYUDARTE A CUMPLIR TUS SUEÑOS', 'Mensaje del banner principal', '2025-08-23 21:59:07'),
(6, 'quienes_somos_texto', 'Somos una empresa dedicada a ayudarte a encontrar la propiedad de tus sueños. Con años de experiencia en el mercado inmobiliario, nos especializamos en brindar el mejor servicio tanto para compra como para alquiler de propiedades.', 'Texto de la sección Quienes Somos', '2025-08-21 06:20:14'),
(7, 'quienes_somos_imagen', 'quienes-somos.jpg', 'Imagen de la sección Quienes Somos', '2025-08-21 06:20:14'),
(8, 'direccion', 'San José, Costa Rica', 'Dirección de la empresa', '2025-08-21 06:20:14'),
(9, 'telefono', '2222-3333', 'Teléfono principal', '2025-08-21 06:20:14'),
(10, 'email_contacto', 'info@realestate.com', 'Email de contacto', '2025-08-21 06:20:14'),
(11, 'facebook_url', 'https://facebook.com/realestate', 'URL de Facebook', '2025-08-21 06:20:14'),
(12, 'youtube_url', 'https://www.youtube.com/', 'URL de YouTube', '2025-08-23 21:59:57'),
(13, 'instagram_url', 'https://instagram.com/realestate', 'URL de Instagram', '2025-08-21 06:20:14'),
(14, 'permitir_registro_vendedor', '1', 'Permitir que usuarios se conviertan en vendedores', '2025-08-21 06:20:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `fecha_agregado` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `imagenes_propiedades`
--

CREATE TABLE `imagenes_propiedades` (
  `id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `ruta_imagen` varchar(255) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes_contacto`
--

CREATE TABLE `mensajes_contacto` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `mensaje` text NOT NULL,
  `fecha_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) DEFAULT 0,
  `respondido` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propiedades`
--

CREATE TABLE `propiedades` (
  `id` int(11) NOT NULL,
  `tipo` enum('alquiler','venta') NOT NULL,
  `categoria` enum('casa','apartamento','local','terreno','oficina') NOT NULL DEFAULT 'casa',
  `destacada` tinyint(1) DEFAULT 0,
  `titulo` varchar(200) NOT NULL,
  `descripcion_breve` text DEFAULT NULL,
  `descripcion_larga` text DEFAULT NULL,
  `precio` decimal(12,2) NOT NULL,
  `ubicacion` varchar(255) DEFAULT NULL,
  `direccion_completa` text DEFAULT NULL,
  `mapa` text DEFAULT NULL,
  `imagen_destacada` varchar(255) DEFAULT NULL,
  `habitaciones` int(11) DEFAULT 0,
  `banos` int(11) DEFAULT 0,
  `area_m2` decimal(8,2) DEFAULT NULL,
  `parqueos` int(11) DEFAULT 0,
  `vendedor_id` int(11) NOT NULL,
  `estado` enum('disponible','reservada','vendida','alquilada','inactiva') NOT NULL DEFAULT 'disponible',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `propiedades`
--

INSERT INTO `propiedades` (`id`, `tipo`, `categoria`, `destacada`, `titulo`, `descripcion_breve`, `descripcion_larga`, `precio`, `ubicacion`, `direccion_completa`, `mapa`, `imagen_destacada`, `habitaciones`, `banos`, `area_m2`, `parqueos`, `vendedor_id`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'venta', 'casa', 1, 'Casa Mora Premium', 'Ubicada en las faldas del volcán Arenal, contiene 1000 mts de terreno y una de las mejores vistas del lugar', 'Hermosa casa ubicada en una de las zonas más privilegiadas del volcán Arenal. Cuenta con 1000 metros cuadrados de terreno, 3 habitaciones, 2 baños completos, sala amplia, comedor, cocina equipada con electrodomésticos de primera y una vista espectacular al volcán. La propiedad se encuentra en perfecto estado y lista para habitar. Incluye jardín con árboles frutales, rancho para BBQ y espacio para 2 vehículos.', 65000.00, 'Volcán Arenal, Alajuela', 'La Fortuna de San Carlos, Alajuela, Costa Rica', '', 'prop_1755980305.jpg', 3, 2, 150.00, 2, 2, 'disponible', '2025-08-21 06:20:14', '2025-08-23 20:18:25'),
(2, 'venta', 'casa', 1, 'Casa Moderna Deluxe', 'Casa de lujo con acabados premium y piscina privada', 'Exclusiva propiedad con acabados de lujo, ubicada en zona premium del volcán Arenal. Incluye 4 habitaciones amplias con walk-in closets, 3 baños completos con jacuzzi, sala de estar, comedor formal, cocina gourmet, piscina privada, jardín amplio y todas las comodidades modernas. Sistema de seguridad incluido.', 95000.00, 'Volcán Arenal, Alajuela', 'La Fortuna de San Carlos, Alajuela, Costa Rica', '', 'prop_1755980416.jpg', 4, 3, 200.00, 3, 2, 'disponible', '2025-08-21 06:20:14', '2025-08-23 20:20:16'),
(4, 'alquiler', 'casa', 1, 'Casa Amueblada Arenal', 'Casa completamente amueblada con vista al volcán', 'Casa completamente amueblada disponible para alquiler mensual. Incluye todos los servicios, internet, cable, mantenimiento del jardín y limpieza semanal. 3 habitaciones, 2 baños, cocina equipada, sala, comedor y terraza con vista al volcán.', 1200.00, 'Volcán Arenal, Alajuela', 'La Fortuna de San Carlos, Alajuela, Costa Rica', '', 'prop_1755980473.jpg', 3, 2, 120.00, 2, 2, 'disponible', '2025-08-21 06:20:14', '2025-08-23 20:21:13'),
(6, 'alquiler', 'casa', 0, 'Casa Económica Familiar', 'Opción económica en excelente ubicación', 'Casa sencilla pero cómoda, perfecta para familias que buscan una opción económica sin sacrificar ubicación. 2 habitaciones, 1 baño, cocina, sala-comedor y patio trasero.', 500.00, 'Cartago Centro', 'Cartago, Costa Rica', '', 'prop_1755980966.jpeg', 2, 1, 70.00, 1, 2, 'disponible', '2025-08-21 06:20:14', '2025-08-23 20:29:26'),
(7, 'venta', 'apartamento', 0, 'Apartamento de Lujo', 'Apartamento amueblado de lujo', 'Apartamento ubicado central con una hermosa vista', 40.00, 'Tilarán - Guanacaste', 'Tilarán Centro', '', 'prop_1755983321.jpg', 2, 1, 100.00, 0, 12, 'disponible', '2025-08-23 21:08:41', '2025-08-23 21:08:41'),
(8, 'alquiler', 'casa', 0, 'Casa Playa', 'Casa en la playa', 'Casa en playa Hermosa para pasar un fin de semana en vacaciones', 50.00, 'Playa Hermosa - Guanacaste', 'Playa Hermosa', '', 'prop_1755986075.jpg', 5, 2, 5000.00, 1, 12, 'disponible', '2025-08-23 21:54:35', '2025-08-23 21:54:35');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservas`
--

CREATE TABLE `reservas` (
  `id` int(11) NOT NULL,
  `propiedad_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `tipo_interes` enum('compra','alquiler','informacion') NOT NULL,
  `mensaje` text DEFAULT NULL,
  `telefono_contacto` varchar(20) DEFAULT NULL,
  `fecha_preferida` date DEFAULT NULL,
  `estado` enum('pendiente','confirmada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `fecha_reserva` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` timestamp NULL DEFAULT NULL,
  `notas_vendedor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_vendedor`
--

CREATE TABLE `solicitudes_vendedor` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `motivo` text DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha_solicitud` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_respuesta` timestamp NULL DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL,
  `comentarios_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `rol` enum('cliente','vendedor','admin') NOT NULL DEFAULT 'cliente',
  `estado` enum('activo','inactivo','pendiente') NOT NULL DEFAULT 'activo',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `telefono`, `correo`, `usuario`, `contrasena`, `rol`, `estado`, `fecha_creacion`, `fecha_actualizacion`) VALUES
(1, 'Administrador Principal', '2222-3333', 'admin@realestate.com', 'admin', '0192023a7bbd73250516f069df18b500', 'admin', 'activo', '2025-08-21 06:20:14', '2025-08-21 06:20:14'),
(2, 'Juan Pérez Vendedor', '8888-9999', 'juan@realestate.com', 'juan_vendedor', 'a60c36fc7c825e68bb5371a0e08f828a', 'vendedor', 'activo', '2025-08-21 06:20:14', '2025-08-21 06:20:14'),
(3, 'María González Vendedor', '7777-6666', 'maria@realestate.com', 'maria_vendedor', 'vendedor123\r\n', 'vendedor', 'activo', '2025-08-21 06:20:14', '2025-08-23 21:04:24'),
(11, 'sofia', '7202-2947', 'sofiaherrerazuniga@gmail.com', 'sofiaherrerazuniga@gmail.com', '202cb962ac59075b964b07152d234b70', 'cliente', 'activo', '2025-08-22 21:13:28', '2025-08-22 21:13:28'),
(12, 'Sofía Herrera Zúñiga', '72022947', 'sofia@gmail.com', 'fiahz', '202cb962ac59075b964b07152d234b70', 'vendedor', 'activo', '2025-08-23 20:58:59', '2025-08-23 21:00:11');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_estadisticas_admin`
-- 
--
CREATE TABLE `vista_estadisticas_admin` (
`total_clientes` bigint(21)
,`total_vendedores` bigint(21)
,`total_admins` bigint(21)
,`propiedades_disponibles` bigint(21)
,`propiedades_venta` bigint(21)
,`propiedades_alquiler` bigint(21)
,`reservas_pendientes` bigint(21)
,`solicitudes_vendedor_pendientes` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vista_propiedades_completa`
-- 
--
CREATE TABLE `vista_propiedades_completa` (
`id` int(11)
,`tipo` enum('alquiler','venta')
,`categoria` enum('casa','apartamento','local','terreno','oficina')
,`destacada` tinyint(1)
,`titulo` varchar(200)
,`descripcion_breve` text
,`descripcion_larga` text
,`precio` decimal(12,2)
,`ubicacion` varchar(255)
,`direccion_completa` text
,`habitaciones` int(11)
,`banos` int(11)
,`area_m2` decimal(8,2)
,`parqueos` int(11)
,`mapa` text
,`imagen_destacada` varchar(255)
,`estado` enum('disponible','reservada','vendida','alquilada','inactiva')
,`fecha_creacion` timestamp
,`vendedor_nombre` varchar(100)
,`vendedor_telefono` varchar(20)
,`vendedor_correo` varchar(100)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_estadisticas_admin`
--
DROP TABLE IF EXISTS `vista_estadisticas_admin`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_estadisticas_admin`  AS SELECT (select count(0) from `usuarios` where `usuarios`.`rol` = 'cliente') AS `total_clientes`, (select count(0) from `usuarios` where `usuarios`.`rol` = 'vendedor') AS `total_vendedores`, (select count(0) from `usuarios` where `usuarios`.`rol` = 'admin') AS `total_admins`, (select count(0) from `propiedades` where `propiedades`.`estado` = 'disponible') AS `propiedades_disponibles`, (select count(0) from `propiedades` where `propiedades`.`tipo` = 'venta' and `propiedades`.`estado` = 'disponible') AS `propiedades_venta`, (select count(0) from `propiedades` where `propiedades`.`tipo` = 'alquiler' and `propiedades`.`estado` = 'disponible') AS `propiedades_alquiler`, (select count(0) from `reservas` where `reservas`.`estado` = 'pendiente') AS `reservas_pendientes`, (select count(0) from `solicitudes_vendedor` where `solicitudes_vendedor`.`estado` = 'pendiente') AS `solicitudes_vendedor_pendientes` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vista_propiedades_completa`
--
DROP TABLE IF EXISTS `vista_propiedades_completa`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vista_propiedades_completa`  AS SELECT `p`.`id` AS `id`, `p`.`tipo` AS `tipo`, `p`.`categoria` AS `categoria`, `p`.`destacada` AS `destacada`, `p`.`titulo` AS `titulo`, `p`.`descripcion_breve` AS `descripcion_breve`, `p`.`descripcion_larga` AS `descripcion_larga`, `p`.`precio` AS `precio`, `p`.`ubicacion` AS `ubicacion`, `p`.`direccion_completa` AS `direccion_completa`, `p`.`habitaciones` AS `habitaciones`, `p`.`banos` AS `banos`, `p`.`area_m2` AS `area_m2`, `p`.`parqueos` AS `parqueos`, `p`.`mapa` AS `mapa`, `p`.`imagen_destacada` AS `imagen_destacada`, `p`.`estado` AS `estado`, `p`.`fecha_creacion` AS `fecha_creacion`, `u`.`nombre` AS `vendedor_nombre`, `u`.`telefono` AS `vendedor_telefono`, `u`.`correo` AS `vendedor_correo` FROM (`propiedades` `p` left join `usuarios` `u` on(`p`.`vendedor_id` = `u`.`id`)) WHERE `p`.`estado` in ('disponible','reservada') ;


ALTER TABLE `configuraciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorito` (`usuario_id`,`propiedad_id`),
  ADD KEY `propiedad_id` (`propiedad_id`);

--
-- Indices de la tabla `imagenes_propiedades`
--
ALTER TABLE `imagenes_propiedades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `propiedad_id` (`propiedad_id`);

--
-- Indices de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_propiedades_tipo` (`tipo`),
  ADD KEY `idx_propiedades_categoria` (`categoria`),
  ADD KEY `idx_propiedades_destacada` (`destacada`),
  ADD KEY `idx_propiedades_vendedor` (`vendedor_id`),
  ADD KEY `idx_propiedades_estado` (`estado`);

--
-- Indices de la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservas_cliente` (`cliente_id`),
  ADD KEY `idx_reservas_propiedad` (`propiedad_id`);

--
-- Indices de la tabla `solicitudes_vendedor`
--
ALTER TABLE `solicitudes_vendedor`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `respondido_por` (`respondido_por`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `idx_usuarios_usuario` (`usuario`),
  ADD KEY `idx_usuarios_correo` (`correo`),
  ADD KEY `idx_usuarios_rol` (`rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `configuraciones`
--
ALTER TABLE `configuraciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `imagenes_propiedades`
--
ALTER TABLE `imagenes_propiedades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes_contacto`
--
ALTER TABLE `mensajes_contacto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `propiedades`
--
ALTER TABLE `propiedades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `reservas`
--
ALTER TABLE `reservas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `solicitudes_vendedor`
--
ALTER TABLE `solicitudes_vendedor`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favoritos_ibfk_2` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `imagenes_propiedades`
--
ALTER TABLE `imagenes_propiedades`
  ADD CONSTRAINT `imagenes_propiedades_ibfk_1` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `propiedades`
--
ALTER TABLE `propiedades`
  ADD CONSTRAINT `propiedades_ibfk_1` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reservas`
--
ALTER TABLE `reservas`
  ADD CONSTRAINT `reservas_ibfk_1` FOREIGN KEY (`propiedad_id`) REFERENCES `propiedades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservas_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_vendedor`
--
ALTER TABLE `solicitudes_vendedor`
  ADD CONSTRAINT `solicitudes_vendedor_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_vendedor_ibfk_2` FOREIGN KEY (`respondido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
