-- Base de datos inicial para PQR-Plataforma
CREATE DATABASE IF NOT EXISTS pqr_db;
USE pqr_db;
CREATE TABLE IF NOT EXISTS `users_admin` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('superadmin', 'admin', 'agent') DEFAULT 'agent',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `radicado` varchar(20) NOT NULL,
    `client_name` varchar(150) NOT NULL,
    `client_email` varchar(150) NOT NULL,
    `client_phone` varchar(30) NOT NULL,
    `client_document` varchar(100) DEFAULT NULL,
    `type` varchar(60) NOT NULL,
    `department` varchar(100) DEFAULT NULL,
    `ciudad` varchar(100) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `subject` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `status` enum('Abierto', 'En Proceso', 'Resuelto', 'Cerrado') DEFAULT 'Abierto',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `radicado` (`radicado`),
    KEY `idx_tickets_status_created` (`status`, `created_at`),
    KEY `idx_tickets_type_created` (`type`, `created_at`),
    KEY `idx_tickets_email` (`client_email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ticket_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    CONSTRAINT `fk_ticket_files` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ticket_replies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` int(11) NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    -- NULL significa que lo envió el cliente, si se da el caso de respuestas del cliente
    `message` text NOT NULL,
    `is_internal` tinyint(1) DEFAULT '0',
    -- Para notas internas entre agentes que el cliente no ve
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ticket_id` (`ticket_id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `fk_reply_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reply_user` FOREIGN KEY (`user_id`) REFERENCES `users_admin` (`id`) ON DELETE
    SET NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `ticket_reply_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reply_id` int(11) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `reply_id` (`reply_id`),
    CONSTRAINT `fk_reply_files` FOREIGN KEY (`reply_id`) REFERENCES `ticket_replies` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
