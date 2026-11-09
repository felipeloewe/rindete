# Rindete — Etapas del alcance

Actualizado el 9 de noviembre de 2026. Los puntos ya acordados siguen todos. Aquí quedan ordenados en siete etapas y, en cada una, qué hace cada pieza. La propuesta se arma después, sobre este orden.

La parametrización va primero porque el inicio, la carga y el cierre leen esas reglas.

| Etapa | Qué resuelve | Qué deja lista |
| --- | --- | --- |
| 1. Parametrización | Reglas, cuentas, tarjetas, documentos y conexiones. | Parámetros que el resto de las etapas consulta. |
| 2. Inicio | Cómo nace lo que se va a rendir o a pagar. | Fondo, rendición autorizada, documento, cargo de tarjeta, nómina, movimiento de caja o pago de socio. |
| 3. Carga | Gastos, foto y clasificación. | Gastos confirmados, saldo recalculado y pre-asiento propuesto. |
| 4. Saldos | Qué pasa cuando sobra o falta. | Cierre pedido, o rendición cerrada y otra abierta con el saldo. |
| 5. Seguimiento | Estados de cuenta y dashboards. | Lectura del saldo para usuario, caja, tarjeta y socios. |
| 6. Flujo de pagos | Lo que tesorería paga. | Pago en proceso o planilla banco emitida. |
| 7. Cierre de mes | Asientos y pendientes. | Mes cerrado, asientos disponibles por API y saldos que siguen abiertos. |

## Actores

| Actor | Qué hace |
| --- | --- |
| Usuario que rinde | Pide fondos cuando corresponde y carga gastos, montos y fotos. |
| Aceptador | Recorre el flujo de aceptación en la solicitud y en el cierre pedido por saldo negativo. |
| Tesorería | Procesa la entrega del fondo, el documento único y la planilla banco. |
| Usuario autorizado | Abre una rendición sin solicitud, dentro de sus parámetros. |
| Usuario con privilegios | Registra el documento único y opera tarjetas. En una tarjeta puede haber más de uno. |
| Caja | Entrega fondos a usuarios, los centraliza y repone desde caja chica. |
| Socio | Paga varios documentos, con formas de pago centralizadas. |
| Empresa o entidad | Dueña de fondos, tarjetas, caja y cuentas. Recibe la recuperación de los socios. |

## Etapa 1. Parametrización

Se mantiene antes de operar. Una carga, un asiento o un pago usa solo lo que esta etapa tiene vigente.

### Cuentas contables genéricas

Guarda las cuentas de uso general y la relación entre clasificación de gasto y cuenta. Cuando la carga confirma un gasto, arma el pre-asiento con esa cuenta. El pre-asiento espera el asiento de centralización del mes.

### Cuentas contables de remuneraciones

Guarda las cuentas de viáticos, asignaciones y bienes. Las filtra por el usuario que rinde, para que el traspaso llegue a su liquidación de sueldo. Si ese usuario tiene un concepto propio, el traspaso usa el concepto del usuario.

### Conexiones API

Registra cada sistema externo y para qué etapa sirve. Una conexión puede alimentar el pull de asientos, el flujo de pagos, o los dos. Las etapas 6 y 7 usan solo conexiones vigentes.

### Flujo de aceptación y reglas generales

Define quién acepta y en qué orden. Define también qué condición detiene el paso: monto sobre el máximo, tipo de gasto no permitido o usuario sin habilitación. Lo usan la solicitud de fondos y el cierre que pide un saldo negativo.

### Tarjeta corporativa

Mantiene las tarjetas de la empresa, marca los gastos que son propios de la tarjeta y fija los montos aceptados. La validación de un pago con tarjeta compara el cargo contra esta mantención.

### Gastos aceptados

Lista, para cada usuario que rinde, los gastos que puede cargar. En la carga, un gasto fuera de esa lista queda detenido.

### Tipos de documento

Administra vouchers, Transbank, sin comprobante y los demás tipos que se den de alta. Cada tipo tiene un monto aceptado. La carga ofrece solo esos tipos y detiene el gasto que supera el monto del tipo.

## Etapa 2. Inicio de la rendición

Hay siete caminos. Cada uno deja un objeto distinto para la carga o para tesorería.

### 1. Solicitud de fondos por rendir

1. El usuario ingresa la solicitud: monto y motivo.
2. La aceptación corre el flujo general. Revisa el monto máximo de rendición y que el tipo de gasto o el ítem específico esté permitido. Si una regla falla, la solicitud se detiene ahí.
3. Tesorería toma lo aceptado y procesa la entrega del dinero.
4. Al terminar, queda un fondo por rendir a nombre de quien rinde. El saldo inicial es el monto entregado.

### 2. Usuario autorizado a rendir sin solicitud

1. El usuario autorizado abre la rendición directo, sin solicitud previa.
2. El sistema aplica los parámetros máximos. Si el usuario tiene parámetros propios, usa esos. Si no, usa los generales.
3. La rendición queda sujeta a viáticos y asignación, tratados como ítems de remuneración.
4. Si el usuario tiene un concepto de remuneración propio, la rendición usa ese concepto. Si no, usa el general.

### 3. Documento único

1. Un usuario con privilegio registra un solo documento y elige la forma de pago.
2. Si la forma de pago es efectivo, exige el comprobante de pago al contado antes de seguir.
3. El documento informa a tesorería.
4. Tesorería lo procesa y el documento queda pagado o en el flujo de pagos, según el estado que tesorería le dé.

### 4. Tarjeta personal o corporativa

1. Entran los pagos efectuados con tarjeta personal o corporativa.
2. Cada pago produce tres resultados: disminuye facturas por pagar (−), abre un fondo por rendir de tarjeta, y queda en validación.
3. La validación compara el pago con la mantención de la tarjeta, los gastos propios de la tarjeta y los montos aceptados.
4. Operan la tarjeta los usuarios con privilegio. Puede haber más de uno por tarjeta.
5. El flujo de gastos sigue hacia la carga, como cualquier rendición.
6. El flujo de DTE pagados entra sin documento físico: el DTE ya pagado respalda el cargo.
7. Lo que no calza después del pago queda para el dashboard de desviaciones ex-post.

### 5. Nómina de fondos solicitados

1. Junta en una nómina los fondos ya solicitados y aceptados.
2. Arma la planilla banco para pagar esa nómina.
3. Antes de emitir la planilla, controla documentos duplicados. El documento repetido no entra a la planilla.

### 6. Caja chica centralizada

1. Entrega el fondo directo al usuario.
2. Centraliza ese fondo en caja: el usuario lo recibe y la caja lleva el saldo.
3. La reposición del fondo sale de la caja chica.
4. Antes de reponer, audita una posible duplicidad de gasto y deja marcado el gasto que ya parece rendido, para revisión de caja.

### 7. Socios

1. El socio paga varios documentos en un mismo acto.
2. Cada documento puede usar una forma de pago distinta.
3. Las formas de pago se centralizan: se mantienen una vez y se aplican a los documentos.
4. El estado de cuenta de socios y el dashboard de recuperación leen esas formas de pago y la entidad a la que se recupera.

## Etapa 3. Carga de la rendición

La hace el usuario que rinde, sobre el fondo, la rendición autorizada, el cargo de tarjeta o el movimiento de caja que le corresponda.

1. Abre la rendición y agrega gastos. Cada gasto lleva monto y foto.
2. La foto obedece la parametrización: cuáles se piden y cómo se leen.
3. El OCR lee la foto y propone los datos del documento. El usuario confirma o corrige antes de dejar el gasto.
4. Selecciona tipo de documento, clasificación del gasto, centro de costo, sucursal y otros conceptos.
5. El sistema detiene la línea cuando el gasto no está aceptado para ese usuario, cuando el monto supera el máximo de rendición o el monto del tipo de documento, o cuando falta el comprobante que la forma de pago exige.
6. Sin comprobante solo sigue cuando ese tipo está parametrizado y el monto cabe en lo aceptado para el tipo.
7. Cada gasto confirmado recalcula el saldo y propone el pre-asiento con la cuenta de su clasificación.

## Etapa 4. Saldos

El saldo es el monto por rendir menos los gastos confirmados. En una solicitud, el monto por rendir es el fondo entregado. En tarjeta, es el cargo validado. En la rendición que recibió un traspaso, es el saldo que llegó más el monto nuevo que se le haya sumado.

| Situación | Qué hace |
| --- | --- |
| Saldo negativo | Los gastos superan lo que había por rendir. El sistema solicita el cierre. Esa solicitud entra al flujo de aceptación. Mientras nadie la acepta, la rendición sigue abierta y marcada con cierre pedido. |
| Saldo positivo | Sobra monto. El sistema cierra esta rendición y abre una nueva con ese saldo. La nueva puede sumar un monto adicional, y ese monto pasa a formar parte de lo que hay que rendir. |
| Saldo en cero | La rendición puede cerrarse cuadrada. No abre otra y no pide un cierre por exceso. |

El seguimiento de montos por rendir toma las rendiciones que siguen abiertas, incluidas las que nacieron de un traspaso.

## Etapa 5. Seguimiento

Esta etapa lee. No entrega dinero y no contabiliza. Cada vista responde una pregunta distinta.

| Vista | Qué hace |
| --- | --- |
| Montos por rendir | Por usuario, muestra lo recibido, lo cargado y lo que falta. Incluye las rendiciones nuevas que arrastran saldo. |
| EECC usuario | Lista entregas, gastos, cierres y saldo de ese usuario. El saldo coincide con el de sus rendiciones. |
| EECC tarjeta del usuario | Lista cargos de sus tarjetas, lo validado, lo rendido y el fondo por rendir de tarjeta que sigue abierto. |
| Desviaciones de tarjeta ex-post | Compara el pago de la tarjeta con lo rendido después y muestra la diferencia. |
| Sumatoria | Suma el período por tipo de gasto y por tipo de documento. |
| Remuneraciones | Muestra viáticos, asignaciones y bienes por usuario, y la desviación contra el concepto parametrizado. Esa desviación es la que después toma el asiento de traspaso. |
| EECC agregado | Suma los estados de cuenta de los usuarios y conserva el detalle por persona. |
| EECC caja chica | Muestra entregas a usuarios, reposiciones y saldo de la caja centralizada. |
| EECC tarjeta de la empresa | Junta las tarjetas de la empresa: cargos, validación, fondos por rendir y desviaciones. |
| EECC socios | Muestra documentos pagados, forma de pago y saldo por recuperar, separado por entidad. |
| Socios por recuperar | Muestra qué rendiciones hay que recuperar desde socios hacia cada entidad. Incluye gastos de tarjeta o de bancos, según la empresa. |

## Etapa 6. Flujo de pagos

1. Reúne lo que tesorería tiene que pagar: fondos aceptados, documentos únicos informados y nóminas.
2. El dashboard muestra cada pago como pendiente, en proceso o pagado.
3. La planilla banco sale de la nómina que ya pasó el control de documentos duplicados.
4. La API de pagos informa el estado a la conexión parametrizada para pagos.
5. La reposición de caja chica se mueve dentro de la caja y no sale en la planilla banco.
6. Los socios siguen con sus formas de pago centralizadas. El dashboard las muestra y no las reemplaza.

## Etapa 7. Cierre de mes y asientos

1. Toma las rendiciones del mes y sus pre-asientos.
2. Arma los asientos por mes.
3. El asiento de centralización junta esos movimientos y los lleva a contabilidad, con las cuentas genéricas de la clasificación.
4. El asiento de traspaso mueve viáticos, asignaciones y bienes a la liquidación de sueldo del usuario que rinde, con la cuenta de remuneraciones de ese usuario.
5. Cierra el mes. El cierre deja fijos los asientos del período.
6. Los saldos que no se cerraron quedan como pendientes del mes. Siguen visibles en montos por rendir y en el estado de cuenta.
7. El libro diario muestra los saldos contables de los asientos del mes.
8. El pull de asientos deja esos asientos disponibles en la API para que el sistema contable los retire, por la conexión parametrizada.

## Lecturas que siguen abiertas a corrección

El punto permanece dentro de la etapa aunque se ajuste la lectura.

- «Más de uno por TC» está leído como más de un usuario con privilegio sobre la misma tarjeta.
- El saldo negativo pide el cierre, y ese pedido lo confirma el flujo de aceptación.
- El saldo positivo se traspasa a una rendición nueva, que puede recibir un monto adicional. En esta regla el sobrante no se devuelve en efectivo.

## Siguiente paso

El documento para leer y compartir está en `docs/alcance-siete-etapas.pdf`. La propuesta en borrador, etapa por etapa, está en docs/propuesta.md.
