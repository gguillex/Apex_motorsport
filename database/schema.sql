-- =============================================================================
-- APEX Motorsport — Esquema y datos iniciales
-- Compatible con MariaDB 10.4+ / MySQL 8+
--
-- Uso:  mysql -u root < database/schema.sql
--       (o importar desde phpMyAdmin)
--
-- Usuario inicial:  admin / admin123   (cámbialo tras el primer acceso)
-- =============================================================================

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS `apex_motorsport`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `apex_motorsport`;

DROP TABLE IF EXISTS `eventos`;
DROP TABLE IF EXISTS `compras`;
DROP TABLE IF EXISTS `coches`;
DROP TABLE IF EXISTS `usuarios`;

-- -----------------------------------------------------------------------------
-- Usuarios
-- -----------------------------------------------------------------------------
CREATE TABLE `usuarios` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario`    VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL COMMENT 'Hash generado con password_hash()',
  `rol`        ENUM('usuario','admin') NOT NULL DEFAULT 'usuario',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Coches
-- -----------------------------------------------------------------------------
CREATE TABLE `coches` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `marca`       VARCHAR(50)   NOT NULL,
  `modelo`      VARCHAR(80)   NOT NULL,
  `anio`        SMALLINT UNSIGNED NOT NULL,
  `precio`      DECIMAL(12,2) UNSIGNED NOT NULL,
  `motor`       VARCHAR(100)  NOT NULL,
  `potencia`    SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'CV',
  `aceleracion` DECIMAL(4,1)  UNSIGNED NOT NULL DEFAULT 0.0 COMMENT 'Segundos 0-100 km/h',
  `velocidad`   SMALLINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'km/h',
  `stock`       INT UNSIGNED  NOT NULL DEFAULT 0,
  `imagen`      VARCHAR(255)  NOT NULL COMMENT 'Ruta relativa a public/',
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_coches_marca` (`marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Compras
--   · `precio` guarda el importe pagado en el momento de la compra, de modo que
--     las facturas no cambian si después se modifica el precio del coche.
--   · ON DELETE RESTRICT: una compra es un documento contable; no se permite
--     borrar coches ni usuarios que tengan compras asociadas.
-- -----------------------------------------------------------------------------
CREATE TABLE `compras` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `id_usuario` INT UNSIGNED  NOT NULL,
  `id_coche`   INT UNSIGNED  NOT NULL,
  `precio`     DECIMAL(12,2) UNSIGNED NOT NULL,
  `fecha`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_compras_usuario` (`id_usuario`),
  KEY `idx_compras_coche`   (`id_coche`),
  CONSTRAINT `fk_compras_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_coche`   FOREIGN KEY (`id_coche`)   REFERENCES `coches` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Eventos del calendario de la portada (uno por día)
-- -----------------------------------------------------------------------------
CREATE TABLE `eventos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`       DATE         NOT NULL,
  `nombre`      VARCHAR(120) NOT NULL,
  `descripcion` VARCHAR(1000) NOT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_eventos_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Datos iniciales
-- -----------------------------------------------------------------------------
INSERT INTO `usuarios` (`usuario`, `password`, `rol`) VALUES
  ('admin', '$2y$10$h1ZxLZCAiluumUUU0Avj/enBD.6zCJ1Zd9lbT1xNFFPAEEv6Em1su', 'admin');

INSERT INTO `coches`
  (`marca`, `modelo`, `anio`, `precio`, `motor`, `potencia`, `aceleracion`, `velocidad`, `stock`, `imagen`)
VALUES
  ('Ferrari',      '488 GTB',     2022,  245000, 'V8 Biturbo',         670, 3.0, 330, 5, 'assets/img/coches/ferrari-488-gtb.jpg'),
  ('Lamborghini',  'Huracán EVO', 2023,  198000, 'V10 Atmosférico',    640, 2.9, 325, 4, 'assets/img/coches/lamborghini-huracan-evo.jpg'),
  ('Porsche',      '911 GT3',     2024,  174000, 'Flat-6 Atmosférico', 510, 3.4, 318, 6, 'assets/img/coches/porsche-911-gt3.jpg'),
  ('McLaren',      '720S',        2022,  263000, 'V8 Twin-Turbo',      720, 2.9, 341, 3, 'assets/img/coches/mclaren-720s.jpg'),
  ('Bugatti',      'Chiron',      2023, 2950000, 'W16 Quad-Turbo',    1500, 2.4, 420, 1, 'assets/img/coches/bugatti-chiron.jpg'),
  ('Aston Martin', 'DB11',        2024,  189000, 'V12 Twin-Turbo',     630, 3.7, 334, 5, 'assets/img/coches/aston-martin-db11.jpg');

INSERT INTO `eventos` (`fecha`, `nombre`, `descripcion`) VALUES
  ('2026-04-10', 'APEX Motorsport Showcase Madrid', 'Exhibición exclusiva de los últimos modelos disponibles en el showroom de APEX Madrid. Acceso por invitación.'),
  ('2026-04-18', 'Monaco Grand Prix — Preview Night', 'Presentación especial de la temporada de Fórmula 1 con pilotos invitados y exposición de monoplazas históricos.'),
  ('2026-04-28', 'Test Drive Day — Circuito del Jarama', 'Jornada de pruebas en pista abierta a clientes APEX. Disponibilidad limitada a 20 plazas por sesión.'),
  ('2026-05-10', 'Salón del Automóvil de Barcelona', 'La feria más importante del automóvil en España, con los últimos lanzamientos y prototipos exclusivos.'),
  ('2026-05-25', 'Gran Premio de Mónaco 2026', 'El Grand Prix más glamuroso del campeonato mundial de Fórmula 1, en las calles del Principado.'),
  ('2026-06-13', '24 Horas de Le Mans', 'La carrera de resistencia más legendaria del mundo en el circuito de la Sarthe (Francia), desde 1923.'),
  ('2026-07-09', 'Goodwood Festival of Speed', 'El festival más exclusivo del automovilismo, en los jardines del castillo de Goodwood (Reino Unido).'),
  ('2026-07-22', 'Gran Premio de Gran Bretaña — Silverstone', 'El Gran Premio más antiguo del campeonato mundial de F1, celebrado en el icónico circuito de Silverstone.'),
  ('2026-09-10', 'IAA Mobility Múnich', 'El salón internacional del automóvil de referencia global: innovación, movilidad eléctrica y supercars del futuro.'),
  ('2026-10-15', 'APEX Autumn Collection Preview', 'Presentación privada de la colección de otoño con más de 30 vehículos de alta gama disponibles para entrega inmediata.'),
  ('2026-11-14', 'Track Day APEX — Circuito de Jerez', 'Jornada exclusiva en pista para propietarios APEX, con instructores profesionales y telemetría en tiempo real.'),
  ('2026-12-05', 'Gala de Invierno APEX Motorsport', 'Cena de gala en Madrid con la presentación de las novedades de 2027 para nuestros clientes más fieles.'),
  ('2027-01-23', 'Rallye Monte-Carlo — Experiencia VIP', 'Viaje organizado para vivir en primera línea el rally más emblemático del calendario mundial.'),
  ('2027-03-06', 'Salón del Automóvil de Ginebra', 'Visita guiada al salón suizo, escaparate de los superdeportivos y prototipos más esperados del año.');
