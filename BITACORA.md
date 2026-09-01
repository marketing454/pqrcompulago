# Bitacora de trabajo

## 2026-09-01

### Objetivo

Dejar la plataforma PQR lista para continuar la configuracion de produccion, con seguridad, notificaciones por Resend, gestion de roles y una interfaz estable.

### Cambios completados

- Se agrego el rol `admin` como administrador normal.
- El rol `admin` puede operar dashboard, tickets, clientes, reportes y configuracion.
- La gestion de usuarios y roles continua restringida a `superadmin` en interfaz y backend.
- Se actualizo el selector de roles y la etiqueta del rol en el panel.
- Se agrego la migracion `docker/migrations/001_add_admin_role.sql`.
- Se actualizo `docker/init.sql` para instalaciones nuevas.
- Se habilito `--role=admin` en `docker/create_admin.php`.
- Se aplico la migracion al MySQL local existente sin eliminar volumenes.
- Se agregaron loaders compartidos para navegacion, formularios, subida de archivos, consulta PQR y filtros AJAX.
- Se mantuvo la apariencia blanca original de la plataforma; la propuesta de tema oscuro tipo Linear no se dejo activa.
- Se conservaron los tipos PQR, filtros de dashboard, consulta segura y proteccion de adjuntos implementados previamente.

### Validaciones

- PHP lint sin errores en los archivos modificados.
- JavaScript validado con `node --check`.
- Portal publico y login responden correctamente en el entorno local.
- La base local acepta `enum('superadmin','admin','agent')`.
- La imagen Docker web se construyo correctamente.

### Pendientes

- Crear la cuenta real del jefe desde un `superadmin` con rol `Administrador`.
- Configurar `.env`, DNS, HTTPS y Resend para produccion.
- Crear o vincular el repositorio remoto de GitHub.
- Rotar credenciales iniciales y revisar vulnerabilidades Docker antes de publicar.
