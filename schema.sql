USE banco_db;

-- ── 1. Usuarios ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `nombre`         VARCHAR(100)  NOT NULL,
  `apellidos`      VARCHAR(100)  NOT NULL,
  `email`          VARCHAR(100)  NOT NULL UNIQUE,
  `telefono`       VARCHAR(20)   NOT NULL,
  `direccion`      VARCHAR(255)  NOT NULL,
  `password_hash`  VARCHAR(255)  NOT NULL,
  `fecha_registro` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── 2. Cuentas bancarias (una o más por usuario) ─────────
CREATE TABLE IF NOT EXISTS `cuentas` (
  `id`             INT(11)                    NOT NULL AUTO_INCREMENT,
  `usuario_id`     INT(11)                    NOT NULL,
  `tipo`           ENUM('ahorro','corriente') NOT NULL DEFAULT 'ahorro',
  `numero_cuenta`  VARCHAR(20)                NOT NULL UNIQUE,
  `saldo`          DECIMAL(15,2)              NOT NULL DEFAULT 0.00,
  `fecha_apertura` TIMESTAMP                  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ── 3. Transacciones ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transacciones` (
  `id`                 INT(11)                                                                   NOT NULL AUTO_INCREMENT,
  `cuenta_id`          INT(11)                                                                   NOT NULL,
  `tipo`               ENUM('deposito','retiro','transferencia_enviada','transferencia_recibida') NOT NULL,
  `monto`              DECIMAL(15,2)                                                             NOT NULL,
  `descripcion`        VARCHAR(255)                                                              DEFAULT NULL,
  `cuenta_relacionada` VARCHAR(20)                                                               DEFAULT NULL,
  `saldo_despues`      DECIMAL(15,2)                                                             NOT NULL,
  `fecha`              TIMESTAMP                                                                 NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
