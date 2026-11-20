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

En `.env`, completa la base MySQL y `app.baseURL`.

### Correo (invitaciones de rendidores)

Opcional. Sin SMTP la app igual crea la invitación y muestra el enlace al administrador para compartirlo a mano.

```
email.fromEmail = noreply@tu-dominio.cl
email.fromName  = Rindete
email.protocol  = smtp
email.SMTPHost  = smtp.tu-proveedor.cl
email.SMTPUser  = ...
email.SMTPPass  = ...
email.SMTPPort  = 587
email.SMTPCrypto = tls
```

La zona horaria por defecto es `America/Santiago` (configurable por empresa en Empresa → Políticas).

## Deploy

`deploy/cpanel.py` publica esta rama en `app.rindete.cl` con la API de cPanel. Usa la cuenta `rindete` y la variable `RINDETE_API_KEY`. No toca `pidetufactura.com`.

```bash
python3 deploy/cpanel.py
```

La clave de la base y `encryption.key` quedan en el `.env` del servidor, fuera de Git. Un deploy siguiente conserva ese archivo.
