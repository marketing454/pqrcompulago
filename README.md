# PQR Plataforma

## Configuracion local y produccion

1. Copiar `.env.example` como `.env`.
2. Reemplazar todos los valores `replace-*` y configurar un dominio HTTPS.
3. En Resend, verificar el dominio que se usara en `MAIL_FROM_EMAIL` y configurar SPF, DKIM y DMARC.
4. Arrancar la plataforma:

```bash
docker compose up -d --build
```

El puerto web queda ligado a `127.0.0.1`. Se debe publicar mediante un proxy HTTPS con el dominio real. MySQL y phpMyAdmin no se publican en el host.

## Crear el primer administrador

La base no incluye usuarios ni contrasenas por defecto. Crear el primer administrador desde el contenedor:

```bash
docker compose exec web php /var/www/html/docker/create_admin.php --name="Administrador" --email="admin@compulago.com"
```

El script solicitara la contrasena y exige al menos 12 caracteres.
El rol inicial es `superadmin`; para crear un administrador normal desde CLI se puede usar `--role=admin`.

### Roles administrativos

- `superadmin`: acceso completo, incluida la gestion de usuarios y roles.
- `admin`: acceso operativo a dashboard, tickets, clientes, reportes y configuracion, sin gestion de usuarios/roles.
- `agent`: acceso operativo al panel sin gestion de usuarios/roles.

Para actualizar una base existente y habilitar el nuevo rol:

```bash
docker exec -i pqr_db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" pqr_db' < docker/migrations/001_add_admin_role.sql
```

## Resend por SMTP

Resend usa estos valores:

```dotenv
SMTP_HOST=smtp.resend.com
SMTP_PORT=587
SMTP_USER=resend
SMTP_SECURE=tls
RESEND_API_KEY=re_...
MAIL_FROM_EMAIL=notificaciones@dominio-verificado.com
MAIL_REPLY_TO=soporte@compulago.com
```

La API key se entrega al relay como `SMTP_PASS` dentro del contenedor y nunca debe guardarse en el repositorio.

## Datos existentes

El Compose nuevo requiere el usuario de aplicacion `pqr_app`. Si se conserva un volumen MySQL existente, crear ese usuario con una contrasena nueva y revisar los permisos antes de reiniciar la aplicacion. No ejecutar `docker compose down -v` si la base contiene datos que deban conservarse.

Los archivos antiguos de `public/uploads` deben revisarse y migrarse al volumen privado de adjuntos antes de eliminarlos del host.
