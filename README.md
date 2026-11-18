# Rindete

Rendición de gastos. El aplicativo usa el mismo stack que Repositoria: CodeIgniter 4, PHP 8.2 y MySQL. Apache apunta a `public/`. `app/` y `writable/` quedan fuera de la web.

Este corte cubre la cuenta, la empresa, los gastos, las rendiciones, la parametrización y la solicitud de fondos. La carga ya consulta tipos de documento, gastos aceptados y la cuenta de la categoría. Un fondo aceptado queda por rendir con el saldo del monto entregado. El administrador da de alta a las personas del plan Free, hasta 4, y marca quién puede abrir una rendición sin solicitud. Esa rendición usa el tope y los conceptos de viático y asignación de la persona, o los de la empresa si no tiene propios. Los informes muestran las rendiciones del período por persona, la cartola de fondos y gastos, las cuentas del preasiento y el nivel por tipo de gasto. El administrador ve a toda la empresa. En Chile, con el módulo de preasientos, al entregar un fondo sale el dinero y nace el fondo por rendir; al rendir, ese fondo pasa al gasto. El comprobante se agrupa por persona o por mes, y el RUT del trabajador entra como auxiliar solo si se pide. No hay un preasiento por rendición ni por fondo. El plan Free incluye la operación. Los preasientos, vengan del fondo o del gasto, y el seguimiento de cuentas son módulos que la empresa contrata cuando los necesita. Todo movimiento contable se llama preasiento porque todavía no entra al ERP. El alta pública entra al plan Free: 4 usuarios y 2 GB. El alcance de producto está en `docs/alcance-siete-etapas.docx`. El landing de rindete.cl usa esas siete etapas en frases comerciales.

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

`deploy/cpanel.py` publica esta rama en `app.rindete.cl` con la API de cPanel. Usa la cuenta `rindete` y la variable `RINDETE_API_KEY`. No toca `pidetufactura.com`.

```bash
python3 deploy/cpanel.py
```

La clave de la base y `encryption.key` quedan en el `.env` del servidor, fuera de Git. Un deploy siguiente conserva ese archivo.
