# Guía de instalación de ALASKA 🐾

Esta guía resume los pasos para levantar el proyecto **ALASKA** (red social de cuidado de animales) en un entorno local basado en XAMPP.

## Requisitos previos

- **Sistema operativo**: Windows 10/11 (probado) u otro con Apache + PHP + MySQL compatibles.
- **Servidor web + PHP**: XAMPP 8.2+ (incluye Apache 2.4 y PHP 8.2) o equivalente.
- **Base de datos**: MySQL 8.0+ o MariaDB 10.4+.
- **Extensiones de PHP**: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` (activas por defecto en XAMPP).
- **Git (opcional)** para clonar el repositorio.

## Estructura recomendada

```
C:\xampp\htdocs\red-social\
└── alaska\ (carpeta del proyecto)
    ├── index.php
    ├── ...
    ├── database.sql
    └── migracion_completa.sql
```

## Paso a paso

1. **Clonar o copiar el proyecto**
   - Opción Git: `git clone https://github.com/viscoooo/red-social.git c:\xampp\htdocs\red-social`
   - O copiar manualmente la carpeta `alaska` dentro de `C:\xampp\htdocs\red-social`.

2. **Crear la base de datos**
   - Inicia MySQL desde el panel de control de XAMPP.
   - Abre phpMyAdmin (`http://localhost/phpmyadmin`).
   - Ejecuta el contenido de `database.sql` para crear las tablas base y datos de ejemplo.

3. **Ejecutar la migración acumulativa**
   - Sigue en phpMyAdmin (pestaña SQL) o usa `mysql` por consola.
   - Ejecuta `migracion_completa.sql` para normalizar columnas (`username`, `email`, `password`) y crear las tablas más recientes (guardados, listas, notificaciones, mensajería, comunidades, etc.).
   - El script es idempotente: puedes ejecutarlo cuantas veces necesites, sólo aplicará cambios faltantes.

4. **Configurar la conexión a la base**
   - Revisa `includes/db.php`:
     - Host: `localhost`
     - Usuario: `root`
     - Contraseña: `""` (vacía)
     - Base de datos: `alaska`
   - Ajusta estos valores si tu entorno difiere.

5. **Permisos de escritura**
   - Asegura que las carpetas `uploads/`, `uploads/perfiles/` y `uploads/mascotas/` sean escribibles por Apache.
   - En Windows basta con mantener el usuario por defecto; en Linux/macOS ejecuta `chmod -R 775 uploads` y asigna el grupo del servidor web.

6. **Iniciar el servidor web**
   - Desde el panel de XAMPP, inicia Apache y MySQL.
   - Accede en el navegador a `http://localhost/red-social/alaska/login.php`.

7. **Probar credenciales iniciales**
   - Usuario de ejemplo (si importaste `database.sql`):
     - Email: `maria@ejemplo.com`
     - Contraseña: `password`
   - El script de migración garantiza que cualquier registro previo sin contraseña válida reciba la clave por defecto `alaska123` (puedes cambiarla en la base de datos o desde Ajustes tras iniciar sesión).

## Opcional: resetear datos

Si deseas restaurar el entorno desde cero:

1. En phpMyAdmin, elimina la base `alaska`.
2. Vuelve a ejecutar `database.sql`.
3. Ejecuta nuevamente `migracion_completa.sql`.
4. Limpia la carpeta `uploads/` si quieres remover imágenes antiguas.

## Troubleshooting rápido

| Problema | Causa posible | Solución |
| --- | --- | --- |
| "Error de conexión" en PHP | Credenciales de MySQL incorrectas | Ajusta `includes/db.php` o restablece la contraseña del usuario `root`. |
| 404 al acceder a páginas | Ruta errónea | Verifica `BASE_URL` en `includes/config.php`. Para la estructura actual debería ser `/red-social/alaska/`. |
| Columnas faltantes (`email`, `password`, etc.) | BD antigua sin migraciones | Ejecuta `migracion_completa.sql`. |
| Likes/seguimiento no responden | JS no cargado o BASE_URL incorrecta | Comprueba `js/script.js` y que `window.BASE_URL` apunte a la ruta correcta. |

## Siguientes pasos sugeridos

- Cambiar `BASE_URL` según el dominio final.
- Configurar certificados SSL si publicas en producción.
- Crear un usuario administrador y endurecer las contraseñas por defecto.
- Revisar `README.md` para conocer módulos, endpoints y roadmap.

¡Listo! Con esto deberías tener ALASKA funcionando en tu entorno local. 🐶🐱
