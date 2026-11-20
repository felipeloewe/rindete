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

## Deploy

`deploy/cpanel.py` publica esta rama en `app.rindete.cl` con la API de cPanel. Verifica TLS contra el almacén de CAs del sistema (o un PEM en `RINDETE_SSL_CA_FILE` para pin documentado). Las migraciones corren una sola vez vía cron acotado + lockfile; el deploy falla si migrate no confirma. El health-check solo toca `app.rindete.cl` (no otros sitios del hosting).

```bash
export RINDETE_API_KEY=...          # obligatorio (token cPanel; no commitear)
# opcionales:
# export RINDETE_CPANEL_HOST=...
# export RINDETE_CPANEL_USER=...
# export RINDETE_CPANEL_HOME=...
# export RINDETE_SSL_CA_FILE=/path/to/ca-or-pin.pem
python3 deploy/cpanel.py
```

La clave de la base y `encryption.key` quedan en el `.env` del servidor, fuera de Git. Un deploy siguiente conserva ese archivo.
