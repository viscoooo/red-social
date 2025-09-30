# ALASKA - Documentación para Desarrolladores

Esta guía resume los módulos principales añadidos/mejorados y las convenciones que se están siguiendo para mantener el código organizado.

## Estructura de Módulos
Cada dominio funcional vive en `includes/` y expone funciones puras o con side-effects mínimos. Algunos módulos ahora usan namespaces con *wrappers* globales para no romper código existente.

| Módulo | Archivo | Descripción | Namespace | Notas |
|--------|---------|-------------|-----------|-------|
| Funciones base | `includes/funciones.php` | Utilidades generales: usuarios, publicaciones, notificaciones, mensajes, moderación, etc. | (global) | Punto central de helpers legacy. |
| Servicios profesionales | `includes/servicios.php` | Listado, calificaciones y categorías de servicios | `Alaska\Servicios` + wrappers | Transición gradual a namespaces. |
| Comunidades | `includes/comunidades.php` | Crear/unirse/listar comunidades y obtener publicaciones | (global) | Listo para migrar a namespace. |
| Donaciones | `includes/donaciones.php` | Campañas, registro de donaciones y Stripe (opcional) | (global) | Stripe se carga dinámicamente. |
| 2FA | `includes/2fa.php` | Activación y verificación de doble factor (Google2FA opcional) | (global) | Fallback si falta dependencia. |
| Consejos | `includes/consejos.php` | Base de conocimiento y generación de consejo contextual | (global) | Fácil de extender con BD. |

## Convenciones de Código
- Validar y castear límites numéricos (`$limit = max(1,(int)$limit)`)
- Prepared statements siempre para interactuar con la base de datos.
- Evitar referencias directas a clases externas sin `class_exists` (Stripe, Google2FA) para tolerancia a entornos incompletos.
- PHPDoc sucinto para funciones nuevas o refactorizadas.
- Uso de constantes configurables para umbrales (`SERVICIO_CALIFICACION_MIN`, thresholds Perspective, etc.).
- Namespaces nuevos: estilo *bracketed* para permitir coexistencia con wrappers globales.

## Migración a Namespaces
Se inició con `Alaska\Servicios`. Patrón a replicar:
```php
namespace Alaska\Dominio {
    // funciones internas tipadas
}
namespace {
    // wrappers globales si hace falta retrocompatibilidad
}
```
Cuando todo el código consumidor esté actualizado, se podrán eliminar los wrappers globales.

## Moderación (Perspective API)
- Configuración en `config/api.php`. Si falta clave, el análisis retorna no tóxico para no bloquear al usuario.
- Función: `analizarContenidoModeracion($texto)` devuelve estructura `{ is_toxic, scores, raw_response? }`.
- Auto-reporte: `crearReporteAutomatico` registra en tablas `reportes` y `advertencias`.

## Donaciones y Pagos
- Stripe es opcional. Si no existen clases, las funciones devuelven error amigable.
- `procesarDonacionStripe` crea `PaymentIntent` y retorna `client_secret`.
- A futuro: mover lógica de pagos a un sub-namespace y separar métodos por pasarela.

## 2FA
- Fallback genera un secreto hex y nunca validará códigos (retorna false). Esto evita falsa sensación de seguridad.
- Cuando se instale la dependencia:
```bash
composer require pragmarx/google2fa
```
- UI debe reaccionar a si la verificación falla constantemente (mostrar reconfigurar).

## Servicios
- Recalcula promedio y total de calificaciones dentro de la misma transacción.
- Posible mejora futura: cachear promedio en tabla y usar triggers o colas.

## Comunidades
- `INSERT IGNORE` para membresía idempotente.
- TODO sugerido: agregar roles adicionales (moderador) y paginación de publicaciones.

## Consejos Inteligentes
- Estructura in-memory. Escalable a BD: crear tablas `categorias_consejo`, `items_consejo` y `tips_consejo`.
- Fallback genérico siempre recomienda visita profesional.

## Estándares de Seguridad
- Escapar siempre salida con `htmlspecialchars` (ver helper `limpiarTexto`).
- Validar IDs con `validarId` antes de consultas sensibles.
- Prevenir auto-notificaciones (ver lógica en `crearNotificacion`).

## Próximos Pasos Recomendados
1. Uniformar resto de módulos a namespaces.
2. Añadir pruebas unitarias (PHPUnit) para lógica pura (calificaciones, consejos, moderación stub).
3. Centralizar configuración y constantes en `config/constants.php`.
4. Implementar capa de repositorios para separar SQL de lógica de formato.
5. Añadir índices a columnas de filtrado frecuente (ej. `servicios.calificacion`, `comunidades.ubicacion`).
6. Integrar caching simple (APCu / file) para catálogos de categorías.

## Contacto / Maintainers
Actualizar esta sección según equipo.

---
Este documento debe crecer junto con la base de código. Mantenerlo breve y accionable.
