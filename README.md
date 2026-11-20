# Rindete

Rendición de gastos. El aplicativo usa el mismo stack que Repositoria: CodeIgniter 4, PHP 8.2 y MySQL. Apache apunta a `public/`. `app/` y `writable/` quedan fuera de la web.

Este corte cubre la cuenta, la empresa y los gastos. Rendiciones y pedido de factura vienen después.

## Local

```bash
composer install
cp env .env
php spark key:generate
php spark migrate
php spark serve
```

En `.env`, completa la base MySQL y `app.baseURL` (p. ej. `http://localhost:8080/`).

Con `CI_ENVIRONMENT` en `development` o `testing`, la app no fuerza HTTPS ni marca cookies `Secure`, así que `php spark serve` sigue funcionando por HTTP.

## HTTPS y cookies (producción)

En producción (`CI_ENVIRONMENT = production`, `app.baseURL` con `https://`):

- `app.forceGlobalSecureRequests` queda en `true` (redirige HTTP→HTTPS y activa HSTS en la app).
- `cookie.secure` queda en `true` (sesión y CSRF solo por HTTPS; se mantienen `HttpOnly` y `SameSite=Lax`).
- `public/.htaccess` redirige HTTP→HTTPS (excepto localhost) y envía HSTS (`max-age=31536000`, sin preload).
- Filtros `secureheaders` + `hsts`: `X-Frame-Options`, `X-Content-Type-Options`, etc.

Se pueden forzar o apagar con `.env` (`app.forceGlobalSecureRequests`, `cookie.secure`). No se activa CSP todavía (vistas/error pages).

## Deploy

`deploy/cpanel.py` publica esta rama en `app.rindete.cl` con la API de cPanel. Usa la cuenta `rindete` y la variable `RINDETE_API_KEY`. No toca `pidetufactura.com`.

```bash
python3 deploy/cpanel.py
```

La clave de la base y `encryption.key` quedan en el `.env` del servidor, fuera de Git. Un deploy siguiente conserva ese archivo. Con `CI_ENVIRONMENT = production` el endurecimiento de transporte aplica sin claves extra; conviene dejar explícito en el `.env` del server:

```
app.forceGlobalSecureRequests = true
cookie.secure = true
```
