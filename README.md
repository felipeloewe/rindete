# Rindete

Rendición de gastos. El aplicativo usa el mismo stack que Repositoria: CodeIgniter 4, PHP 8.2 y MySQL. Apache apunta a `public/`. `app/` y `writable/` quedan fuera de la web.

Este corte cubre la cuenta, la empresa, los gastos, las rendiciones, la parametrización y la solicitud de fondos. La carga ya consulta tipos de documento, gastos aceptados y la cuenta de la categoría. Un fondo aceptado queda por rendir con el saldo del monto entregado. El administrador da de alta a las personas del plan Free, hasta 4, y marca quién puede abrir una rendición sin solicitud. Esa rendición usa el tope y los conceptos de viático y asignación de la persona, o los de la empresa si no tiene propios. Los informes muestran las rendiciones del período por persona, la cartola de fondos y gastos, las cuentas del preasiento y el nivel por tipo de gasto. El administrador ve a toda la empresa. En Chile, con el módulo de preasientos, al entregar un fondo sale el dinero y nace el fondo por rendir; al rendir, ese fondo pasa al gasto. El comprobante se agrupa por persona o por mes, y el RUT del trabajador entra como auxiliar solo si se pide. No hay un preasiento por rendición ni por fondo. El plan Free incluye la operación. Los preasientos, vengan del fondo o del gasto, y el seguimiento de cuentas son módulos que la empresa contrata cuando los necesita. Todo movimiento contable se llama preasiento porque todavía no entra al ERP. El token de Softnet o de Odoo se guarda en Parámetros, Conexiones. Esos sistemas consumen los preasientos cerrados; cuando los toman, en Rindete pasan a ser asientos. La nómina junta los fondos por rendir de las personas y genera el archivo de texto de Pago de Nómina en Línea, separado por punto y coma, para cargarlo en el banco. Los preasientos cerrados se contabilizan en el ERP: por la API, o a mano con un checkbox si no hay una conexión vigente. Ese cierre queda en el tablero de asientos contabilizados. Al cerrar una rendición el saldo decide: si es negativo pide aceptación y queda marcada como cierre pedido, si sobra cierra esta y abre otra con ese monto, y si cuadra se cierra. Lo que sigue abierto, también lo que nació de un traspaso, aparece en la cartola como rendiciones abiertas por rendir. En el cierre del mes, viático, asignación y bien pasan a la liquidación de sueldo con la cuenta de remuneración de la persona, o con la de la empresa si no tiene una propia. Los informes también muestran las rendiciones que el mes no cerró y el monto de cada persona que pasa a liquidación. El tablero de pagos de fondos distingue pendiente, en proceso y pagado, y una solicitud repetida no entra al archivo del banco. La carga puede anotar centro de costo, sucursal y otro concepto, y si la foto del comprobante trae fecha, monto, identificación o número, se completan para que la persona confirme. También parten el documento único, el cargo de tarjeta, la caja chica y el pago de socios, cada uno con su estado de cuenta. El alta pública entra al plan Free: 4 usuarios y 2 GB. El alcance de producto está en `docs/alcance-siete-etapas.docx`. El landing de rindete.cl usa esas siete etapas en frases comerciales.

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
