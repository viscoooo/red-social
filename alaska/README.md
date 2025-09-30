# ALASKA - Red Social para Cuidado de Animales 🐾

ALASKA es una red social sencilla construida en PHP (PDO) + MySQL/MariaDB que permite a las personas publicar contenido, dar "me gusta", comentar y seguir a otros usuarios. El objetivo es promover el cuidado responsable de animales y la conexión entre amantes de las mascotas.

## Tecnologías

- PHP 7+ con PDO (compatible con PHP 8)
- MySQL/MariaDB
- HTML5/CSS3 + JavaScript (sin frameworks)
- XAMPP recomendado para entorno local (Apache + MySQL)

> Nota de mantenimiento: este README se mantiene actualizado automáticamente cuando se agregan o cambian funcionalidades relevantes.

## Aviso importante (24/09/2025)

Se revirtió una reestructuración previa que introducía carpetas `public/` y `src/` con un front controller. El proyecto vuelve a operar con la estructura original (archivos PHP en la raíz del proyecto), sin rutas limpias vía front controller.

- Estructura activa: la descrita más abajo en "Estructura del Proyecto" (sin `public/` ni `src/`).
- Acceso: usa rutas clásicas, por ejemplo `http://localhost/red-social/alaska/login.php` o `index.php` (ajusta si mantienes la carpeta original).
- Impacto: sin cambios funcionales; se eliminaron `public/`, `src/` y el `.htaccess` asociado a esa reescritura. Si ves referencias antiguas a `public/` en notas previas, ignóralas.


## Estructura del Proyecto

```
alaska/
├── index.php                 # Feed principal (requiere login)
├── perfil.php                # Perfil de usuario (versión compacta con tabs y sección de Mascotas)
├── ajustes.php               # Ajustes de cuenta (foto de perfil y fotos de mascotas)
├── publicar.php              # Crear nuevas publicaciones
├── login.php                 # Iniciar sesión
├── registro.php              # Registro de usuarios
├── logout.php                # Cerrar sesión
├── like.php                  # AJAX: dar/quitar like
├── seguir.php                # AJAX: seguir/dejar de seguir
├── comentario.php            # AJAX: crear comentario
├── cargar_publicaciones.php  # AJAX: carga infinita del feed
├── cargar_comentarios.php    # AJAX: listar comentarios (ver todos)
├── explorar.php              # Descubrir contenido
├── notificaciones.php        # Centro de notificaciones
├── mensajes.php              # Bandeja de entrada de mensajes
├── chat.php                  # Conversación directa (detalle)
├── guardados.php             # Publicaciones guardadas
├── listas.php                # Listas personalizadas
├── guardar.php               # AJAX: guardar/quitar de guardados
├── enviar_mensaje.php        # AJAX: enviar mensaje/abrir conversación
├── includes/
│   ├── config.php            # BASE_URL + helpers url()/redirect_to()
│   ├── db.php                # Conexión PDO a la BD
│   ├── funciones.php         # Funciones de negocio y utilidades
│   └── header.php            # Cabecera y barra de navegación
├── css/
│   └── estilo.css            # Estilos principales (responsive)
├── js/
│   └── script.js             # Interactividad: likes, seguir, comentarios, guardados, scroll infinito
├── uploads/                  # Imágenes subidas
│   ├── perfiles/             # Avatares de usuarios
│   └── mascotas/             # Fotos de mascotas (hasta 5 por usuario)
└── database.sql              # Script SQL de esquema y datos iniciales
```

## Instalación y Configuración

> Consulta la guía paso a paso en [`INSTALL.md`](../INSTALL.md) si necesitas más detalle.

1. Clona o copia esta carpeta dentro de tu servidor (por ejemplo `C:\xampp\htdocs\red-social\alaska`).
2. Crea la base de datos ejecutando `database.sql` en tu MySQL/MariaDB (puedes usar phpMyAdmin: http://localhost/phpmyadmin).
3. Normaliza el esquema ejecutando `migracion_completa.sql` (idempotente; agrega columnas `username`, `email`, `password`, tablas de guardados, notificaciones, mensajería, comunidades, etc.).
4. Credenciales por defecto en `includes/db.php`:
   - Usuario: `root`
   - Contraseña: `""` (vacía, por defecto en XAMPP)
  - Base de datos: `alaska`
5. Migración ligera: si por alguna razón no importas `migracion_completa.sql`, `includes/db.php` intentará crear las tablas y columnas más recientes en la primera carga (recomendamos igualmente ejecutar el script para mantener control sobre el esquema).
6. Asegura permisos de escritura en `uploads/` (en Windows, habilita escritura para el usuario del servicio Apache).
7. Abre `http://localhost/red-social/alaska/login.php` en tu navegador (ajusta si mantienes el nombre de carpeta anterior).
8. Usuario de prueba incluido (si ejecutaste el SQL tal cual):
   - Email: `maria@ejemplo.com`
   - Contraseña: `password`

## Funcionalidades

- Autenticación completa: registro e inicio de sesión (contraseñas hasheadas).
- Feed con publicaciones (texto + imagen opcional), ordenado por fecha.
- Likes a publicaciones, con contadores en vivo.
- Comentarios a publicaciones:
  - Envío por AJAX, inserción en el DOM sin recargar.
  - Contador de comentarios actualizado en vivo.
  - “Ver todos los comentarios” por AJAX para expandir la lista completa.
- Perfiles de usuarios: datos básicos, estadísticas y perfil compacto con:
  - Header compacto con banner y avatar.
  - Tabs: Publicaciones, Multimedia y Mascotas (muestra fotos subidas en Ajustes).
  - Grid de publicaciones con overlay de interacciones.
- Seguir / dejar de seguir usuarios vía AJAX.
- Carga infinita del feed (scroll): las nuevas tarjetas se insertan con interacciones activas.
- Guardar publicaciones (toggle) con persistencia y actualización de UI.
- Guardados: listado de publicaciones guardadas.
- Listas: creación y consulta de listas personales (miembros pendientes de CRUD avanzado).
- Notificaciones: like, comentario y seguimiento generan notificaciones; centro de notificaciones con marca como leídas.
- Mensajería: conversaciones (creación automática) y envío de mensajes.
- Estilos modernos y responsive.

### (Nuevo) Tipos de Publicaciones y Gestión

Ahora las publicaciones soportan clasificación y administración avanzada:

- Tipos disponibles: `general`, `mascota`, `evento`, `consejo`.
- Campo opcional `nombre_mascota` para publicaciones de tipo `mascota`.
- Filtros por tipo en el perfil conmutando sin romper otras secciones.
- Edición de publicaciones propias (contenido, tipo y nombre de mascota) mediante modal dinámico.
- Eliminación segura (borra likes, comentarios e imagen asociada si existe).
- Badges visuales sobre cada tarjeta según el tipo.
- Sección de fotos de mascotas sigue mostrando imágenes gestionadas desde Ajustes.

## Páginas y Endpoints Clave

- `index.php`: muestra el feed y un acceso rápido a publicar. Requiere sesión (redirige a `login.php` si no hay usuario autenticado).
- `perfil.php`: muestra la información del usuario y sus publicaciones (rediseñado con tabs y grid; mantiene likes/seguir/guardar).
- `publicar.php`: formulario para crear publicaciones con validación de imagen (jpeg/png/gif hasta 5MB).
- `ajustes.php`: editar nombre, username, biografía, ubicación y privacidad, subir foto de perfil y gestionar hasta 5 fotos de mascotas (subir y eliminar).
- `explorar.php`: explorar contenido reciente.
- `notificaciones.php`: ver notificaciones y marcarlas como leídas.
- `mensajes.php` y `chat.php`: lista de conversaciones y vista de conversación; envío de mensajes.
- `guardados.php`: ver publicaciones guardadas.
- `listas.php`: crear y ver listas personales.
- `editar_publicacion.php`: endpoint AJAX para editar publicaciones propias.

AJAX endpoints:
- `like.php`: POST `publicacion_id`. Devuelve `{ success, liked, likes }`.
- `seguir.php`: POST `usuario_id`. Devuelve `{ success, following }`.
- `comentario.php`: POST `publicacion_id`, `contenido`. Devuelve `{ success, comentario, totalComentarios }`.
- `cargar_publicaciones.php`: GET `page`. Devuelve HTML de publicaciones (para scroll infinito).
- `cargar_comentarios.php`: GET `publicacion_id`, `all=1` (opcional). Devuelve HTML con comentarios.
- `guardar.php`: POST `publicacion_id`. Devuelve `{ success, guardado }`.
- `enviar_mensaje.php`: POST `destinatario_id`, `contenido`. Devuelve `{ success }`.
- `editar_publicacion.php`: POST `publicacion_id`, `contenido`, `tipo`, `nombre_mascota` (opcional). Devuelve `{ success }`.

## includes/funciones.php (Funciones Principales)

- `obtenerUsuarioPorId($pdo, $id)`: datos del usuario.
- `obtenerPublicaciones($pdo, $limit, $offset)`: lista de publicaciones con datos del autor (usa bindParam con PARAM_INT para evitar errores SQL en LIMIT/OFFSET).
- `obtenerPublicacionesUsuario($pdo, $usuario_id)`: publicaciones de un usuario.
- `contarLikes($pdo, $publicacion_id)`, `contarComentarios($pdo, $publicacion_id)`.
- `usuarioYaDioLike($pdo, $usuario_id, $publicacion_id)`, `usuarioEstaSiguiendo($pdo, $seguidor_id, $seguido_id)`.
- `obtenerSeguidores($pdo, $usuario_id)`, `obtenerSiguiendo($pdo, $usuario_id)`, `contarPublicaciones($pdo, $usuario_id)`.
- `obtenerComentarios($pdo, $publicacion_id, $limit)` y `agregarComentario($pdo, $publicacion_id, $usuario_id, $contenido)`.
- `tiempoTranscurrido($fecha)`: devuelve cadenas como “hace 2 horas”.
- Guardados: `publicacionGuardada`, `obtenerPublicacionesGuardadas`, `toggleGuardado`.
- Listas: `obtenerListas`, `crearLista`.
- Notificaciones: `crearNotificacion`, `obtenerNotificaciones`, `marcarNotificacionesLeidas`.
- Mensajes: `obtenerConversaciones`, `obtenerMensajesConversacion`, `enviarMensaje`.
- Publicaciones avanzadas (nuevas):
  - `obtenerPublicacionesPorTipo($pdo, $usuario_id, $tipo)`
  - `eliminarPublicacion($pdo, $publicacion_id, $usuario_id)`
  - `editarPublicacion($pdo, $publicacion_id, $usuario_id, $contenido, $tipo, $nombre_mascota)`

## includes/config.php

- Define `BASE_URL` para centralizar rutas.
- Helpers: `url($path)`, `redirect_to($path)`.
- Inyecta `window.BASE_URL` en el frontend vía `includes/header.php`.

## includes/db.php

- Conexión PDO a MySQL/MariaDB con credenciales XAMPP por defecto.
- Migración ligera para crear tablas nuevas si no existen: guardados, listas, miembros_lista, notificaciones, mensajes, conversaciones (idempotente), y columna `usuarios.fotos_mascotas`.
- Crea automáticamente las carpetas `uploads/perfiles` y `uploads/mascotas` si no existen.
- Asegura también (auto-migración) las columnas en `publicaciones`:
  - `tipo ENUM('general','mascota','evento','consejo') DEFAULT 'general'`
  - `nombre_mascota VARCHAR(100) NULL`

## js/script.js (Interactividad)

- `initializeButtons()` añade listeners a:
  - Likes (.like-btn): POST a `like.php`, cambia el icono, y actualiza el contador junto al botón y en la estadística.
  - Seguimiento (.follow-btn): POST a `seguir.php`, alterna texto/clase.
  - Comentarios (.comment-form): POST a `comentario.php`, inserta el nuevo comentario en el DOM y actualiza contador.
  - “Ver todos los comentarios” (.ver-todos-comentarios): GET a `cargar_comentarios.php?all=1`, reemplaza la lista visible y oculta el enlace.
  - Guardados (.save-btn): POST a `guardar.php`, alterna icono/etiqueta.
- Carga infinita: obtiene HTML desde `cargar_publicaciones.php?page=n`, lo añade y reejecuta `initializeButtons()`.
- `toggleCommentForm(id)`: muestra/oculta la sección de comentarios del post.
- Usa `window.BASE_URL` para construir rutas robustas.
- (Pendiente de mejora futura) Podría enriquecerse para precargar datos reales en el modal de edición de publicaciones.

## Seguridad y buenas prácticas

- Contraseñas en BD con `password_hash()` y verificación con `password_verify()`.
- Consultas SQL preparadas (PDO) para evitar inyecciones.
- Salida con `htmlspecialchars()` para evitar XSS.
- Validación de archivos subidos (tipo y tamaño) en `publicar.php`.

## Personalización

- Variables de color y estilos en `css/estilo.css`.
- Ajusta la paginación/carga infinita cambiando el `limit` en `cargar_publicaciones.php`.
- Cambia el avatar por iniciales/foto en `index.php`/`perfil.php`.

## Problemas comunes

- Error de conexión (1045): revisa credenciales en `includes/db.php`.
- Error SQL 1064 con LIMIT/OFFSET: ya resuelto convirtiendo y bindeando enteros.
- Carga infinita no agrega eventos: `initializeButtons()` se vuelve a ejecutar tras cada inserción de HTML.
- Tabla `notificaciones` u otras faltantes: ya se crean automáticamente en la primera carga (migración ligera en `includes/db.php`).
- No se ve el avatar o fotos de mascotas: confirma que existen en `uploads/perfiles` y `uploads/mascotas`, y que el servidor (Apache) tiene permisos de lectura/escritura.

## Próximos pasos (sugerencias)

- Mostrar previsualización de imagen en `publicar.php` dentro del formulario.
- Edición/eliminación de publicaciones y comentarios por el autor.
- Paginación real de comentarios con offset/limit.
- Subida de foto de perfil y portada.

## Registro de cambios (reciente)

- Perfil compacto: header reducido, tabs y nueva sección “Mascotas” que muestra fotos subidas desde Ajustes.
- Ajustes ahora permite subir foto de perfil y hasta 5 fotos de mascotas (con eliminación individual).
- Nuevas páginas: explorar, notificaciones, mensajes (lista) y chat (detalle), guardados, listas.
- Nuevos endpoints: guardar/quitar guardados, enviar mensajes; notificaciones en like/comentario/seguimiento.
- BASE_URL centralizado y helpers de rutas; `window.BASE_URL` en frontend.
- Migración ligera en `includes/db.php` para crear tablas nuevas de soporte y columna `fotos_mascotas`; creación automática de carpetas de uploads por tipo.
- Nueva funcionalidad: Tipos de publicaciones (general/mascota/evento/consejo) con filtro en perfil, edición y eliminación segura.
- Campos añadidos a `publicaciones`: `tipo` y `nombre_mascota` (auto-migración si no existen).
- Nuevo endpoint AJAX: `editar_publicacion.php`.
- Formularios actualizados en `publicar.php` para capturar tipo y nombre de mascota.
- Nuevas funciones de negocio para filtrar, editar y eliminar publicaciones.

---

¡Listo! Con esto tienes una red social funcional, limpia y extensible. Si quieres que añada edición/eliminación de comentarios o una previsualización más rica de imágenes antes de publicar, dímelo y lo implemento. 
