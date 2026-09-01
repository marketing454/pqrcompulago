-- Agrega el rol administrativo intermedio a instalaciones existentes.
USE pqr_db;

ALTER TABLE `users_admin`
    MODIFY `role` ENUM('superadmin', 'admin', 'agent') NOT NULL DEFAULT 'agent';
