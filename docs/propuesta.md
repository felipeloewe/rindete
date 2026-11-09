# SN SOFTNET

Desarrollo de Software y Consultoría Informática

# COTIZACIÓN

**Borrador de propuesta**

**N° COT-2026-RINDETE**

**SOFTNET SpA**  
Av. del Parque 5275 Oficina 103  
Huechuraba, Santiago  
Región Metropolitana — CHILE  
WWW.SOFTNET.CL  
Fonos: 02 - 2947 9389  
R.U.T.: 76.017.114-K

## DATOS DEL CLIENTE

| Campo | Dato |
| --- | --- |
| Señor(es) | A completar por el cliente |
| Proyecto | Sistema de gestión de rendiciones de gastos (Rindete) |
| Giro | A completar por el cliente |
| Dirección | A completar por el cliente |
| Comuna / Ciudad | Santiago |

## ANTECEDENTES COMERCIALES

| Campo | Dato |
| --- | --- |
| Fecha | Santiago, 9 de noviembre de 2026 |
| Validez | 15 días corridos |
| Condición | Pago por etapa / hito de avance |
| Ejecutivo | FELIPE V |
| Moneda | UF |
| Valor UF del día | A completar |
| Honorarios netos | A completar |

## Alcance

Alcance: implementación del sistema de gestión de rendiciones de gastos (Rindete). Siete etapas, desde la parametrización de cuentas, documentos, tarjetas y conexiones hasta el cierre de mes y los asientos. El inicio tiene siete caminos. La carga registra el gasto con foto y clasificación. Los saldos, el seguimiento y el flujo de pagos cierran el recorrido hasta el asiento del mes.

La parametrización queda primera: el inicio, la carga, los pagos y el cierre leen solo las reglas que esa etapa tiene vigentes. Los honorarios profesionales quedan a completar y se distribuyen en las siete etapas. Este borrador no fija cantidad en UF, precio unitario, descuento, valor de la UF en pesos ni totales.

## DISTRIBUCIÓN DEL PRESUPUESTO

| Etapa | UF |
| --- | --- |
| Etapa 1 · Parametrización | A completar |
| Etapa 2 · Inicio de la rendición (siete caminos) | A completar |
| Etapa 3 · Carga | A completar |
| Etapa 4 · Saldos | A completar |
| Etapa 5 · Seguimiento | A completar |
| Etapa 6 · Flujo de pagos | A completar |
| Etapa 7 · Cierre de mes y asientos | A completar |

## Ítems

Cada etapa es un ítem. La descripción abre con el propósito y sigue con lo incluido. Cantidad, precio unitario, descuento y total quedan a completar.

| ÍTEM | DESCRIPCIÓN | CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- | --- | --- |
| 1 | Etapa 1: Parametrización. Dejar vigentes las reglas, cuentas, tarjetas, documentos y conexiones que consultan el resto de las etapas. El detalle incluido está en la descripción del ítem 1. | A completar | A completar | A completar | A completar |
| 2 | Etapa 2: Inicio de la rendición (siete caminos). Abrir lo que se rinde o se paga y dejar el objeto que sigue a la carga o a tesorería. El detalle incluido está en la descripción del ítem 2. | A completar | A completar | A completar | A completar |
| 3 | Etapa 3: Carga. Registrar gastos con foto, lectura y clasificación, y dejar el gasto confirmado con su saldo y su pre-asiento. El detalle incluido está en la descripción del ítem 3. | A completar | A completar | A completar | A completar |
| 4 | Etapa 4: Saldos. Resolver el faltante, el sobrante y la rendición cuadrada. El detalle incluido está en la descripción del ítem 4. | A completar | A completar | A completar | A completar |
| 5 | Etapa 5: Seguimiento. Mostrar estados de cuenta y dashboards del saldo, sin entregar dinero y sin contabilizar. El detalle incluido está en la descripción del ítem 5. | A completar | A completar | A completar | A completar |
| 6 | Etapa 6: Flujo de pagos. Reunir lo que tesorería paga y dejar el pago en proceso o la planilla banco emitida. El detalle incluido está en la descripción del ítem 6. | A completar | A completar | A completar | A completar |
| 7 | Etapa 7: Cierre de mes y asientos. Cerrar el período, armar los asientos y dejarlos disponibles por API. El detalle incluido está en la descripción del ítem 7. | A completar | A completar | A completar | A completar |

### Ítem 1. Etapa 1: Parametrización

Dejar vigentes las reglas, cuentas, tarjetas, documentos y conexiones que consultan el inicio, la carga, los pagos y el cierre.

- Cuentas contables genéricas. Guarda las cuentas de uso general y la relación entre clasificación de gasto y cuenta. Cuando la carga confirma un gasto, arma el pre-asiento con esa cuenta. El pre-asiento espera el asiento de centralización del mes.
- Cuentas contables de remuneraciones. Guarda las cuentas de viáticos, asignaciones y bienes. Las filtra por el usuario que rinde, para que el traspaso llegue a su liquidación de sueldo. Si ese usuario tiene un concepto propio, el traspaso usa el concepto del usuario.
- Conexiones API. Registra cada sistema externo y la etapa que alimenta. Una conexión puede alimentar el pull de asientos, el flujo de pagos, o los dos. Las etapas 6 y 7 usan solo conexiones vigentes.
- Flujo de aceptación. Define quién acepta y en qué orden. Define también la condición que detiene el paso: monto sobre el máximo, tipo de gasto no permitido o usuario sin habilitación. Lo usan la solicitud de fondos y el cierre que pide un saldo negativo.
- Tarjeta corporativa. Mantiene las tarjetas de la empresa, marca los gastos propios de la tarjeta y fija los montos aceptados. La validación de un pago con tarjeta compara el cargo contra esta mantención.
- Gastos aceptados. Lista, para cada usuario que rinde, los gastos que puede cargar. En la carga, un gasto fuera de esa lista queda detenido.
- Tipos de documento. Administra voucher, Transbank, sin comprobante y los demás tipos que se den de alta. Cada tipo tiene un monto aceptado. La carga ofrece solo esos tipos y detiene el gasto que supera el monto del tipo.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 2. Etapa 2: Inicio de la rendición (siete caminos)

Abrir lo que se va a rendir o a pagar y dejar el objeto que sigue hacia la carga o hacia tesorería: fondo, rendición autorizada, documento, cargo de tarjeta, nómina, movimiento de caja o pago de socio.

**1. Solicitud de fondos por rendir**

- El usuario que rinde ingresa la solicitud con monto y motivo.
- El aceptador corre el flujo de aceptación. Revisa el monto máximo de rendición y que el tipo de gasto o el ítem específico esté permitido. Si una regla falla, la solicitud se detiene ahí.
- Tesorería toma lo aceptado y procesa la entrega del dinero.
- Al terminar queda un fondo por rendir a nombre de quien rinde. El saldo inicial es el monto entregado.

**2. Usuario autorizado a rendir sin solicitud**

- El usuario autorizado abre la rendición directo, sin solicitud previa.
- El sistema aplica los parámetros máximos. Si el usuario tiene parámetros propios, usa esos. Si no, usa los generales.
- La rendición queda sujeta a viáticos y asignación, tratados como ítems de remuneración.
- Si el usuario tiene un concepto de remuneración propio, la rendición usa ese concepto. Si no, usa el general.

**3. Documento único y comprobante al contado**

- Un usuario con privilegio registra un solo documento y elige la forma de pago.
- Si la forma de pago es efectivo, exige el comprobante de pago al contado antes de seguir.
- El documento informa a tesorería.
- Tesorería lo procesa y el documento queda pagado o en el flujo de pagos, según el estado que tesorería le dé.

**4. Tarjeta personal o corporativa**

- Entran los pagos efectuados con tarjeta personal o corporativa.
- Cada pago produce tres resultados: disminuye facturas por pagar (−), abre un fondo por rendir de tarjeta, y queda en validación.
- La validación compara el pago con la mantención de la tarjeta, los gastos propios de la tarjeta y los montos aceptados.
- Operan la tarjeta los usuarios con privilegio. En una tarjeta puede haber más de un usuario con privilegio. Esta lectura queda a corrección del cliente.
- El flujo de gastos sigue hacia la carga, como cualquier rendición.
- El flujo de DTE pagados entra sin documento físico: el DTE ya pagado respalda el cargo.
- Lo que no calza después del pago queda para el dashboard de desviaciones de tarjeta ex-post.

**5. Nómina y planilla banco**

- Junta en una nómina los fondos ya solicitados y aceptados.
- Arma la planilla banco para pagar esa nómina.
- Antes de emitir la planilla, controla documentos duplicados y aparta de la planilla el documento repetido.

**6. Caja chica centralizada**

- La caja entrega el fondo directo al usuario.
- Centraliza ese fondo: el usuario lo recibe y la caja lleva el saldo.
- La reposición del fondo sale de la caja chica.
- Antes de reponer, audita una posible duplicidad de gasto y deja marcado el gasto que ya parece rendido, para revisión de caja.

**7. Socios y formas de pago centralizadas**

- El socio paga varios documentos en un mismo acto.
- Cada documento puede usar una forma de pago distinta.
- Las formas de pago se centralizan: se mantienen una vez y se aplican a los documentos.
- El estado de cuenta de socios y el dashboard de recuperación leen esas formas de pago y la entidad a la que se recupera. La empresa o entidad es dueña de fondos, tarjetas, caja y cuentas, y recibe esa recuperación.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 3. Etapa 3: Carga

Registrar los gastos de quien rinde, sobre el fondo, la rendición autorizada, el cargo de tarjeta o el movimiento de caja que le corresponda, y dejar el gasto confirmado, el saldo recalculado y el pre-asiento propuesto.

- El usuario que rinde abre la rendición y agrega gastos. Cada gasto lleva monto y foto.
- La foto obedece la parametrización: cuáles se piden y cómo se leen.
- El OCR lee la foto y propone los datos del documento. El usuario confirma o corrige antes de dejar el gasto.
- Selecciona tipo de documento, clasificación del gasto, centro de costo (CECO), sucursal y otros conceptos.
- El sistema detiene la línea cuando el gasto no está aceptado para ese usuario, cuando el monto supera el máximo de rendición o el monto del tipo de documento, o cuando falta el comprobante que la forma de pago exige.
- Sin comprobante solo sigue cuando ese tipo está parametrizado y el monto cabe en lo aceptado para el tipo.
- Cada gasto confirmado recalcula el saldo y propone el pre-asiento con la cuenta de su clasificación.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 4. Etapa 4: Saldos

Resolver el saldo, que es el monto por rendir menos los gastos confirmados, y dejar un cierre pedido o una rendición cerrada con otra abierta. En una solicitud, el monto por rendir es el fondo entregado. En tarjeta, es el cargo validado. En la rendición que recibió un traspaso, es el saldo que llegó más el monto nuevo que se le haya sumado.

- Saldo negativo. Los gastos superan lo que había por rendir. El sistema solicita el cierre y ese pedido entra al flujo de aceptación. Mientras nadie lo acepta, la rendición sigue abierta y marcada con cierre pedido. Esta lectura queda a corrección del cliente.
- Saldo positivo. Sobra monto. El sistema cierra esta rendición y abre una nueva con ese saldo. La nueva puede sumar un monto adicional, y ese monto pasa a formar parte de lo que hay que rendir. En esta regla el sobrante no se devuelve en efectivo. Esta lectura queda a corrección del cliente.
- Saldo en cero. La rendición puede cerrarse cuadrada. No abre otra y no pide un cierre por exceso.
- El seguimiento de montos por rendir toma las rendiciones que siguen abiertas, incluidas las que nacieron de un traspaso.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 5. Etapa 5: Seguimiento

Leer el saldo para el usuario, la caja, la tarjeta y los socios. Esta etapa muestra. La entrega de dinero y la contabilización quedan en las etapas de pagos y de cierre.

- Dashboard de montos por rendir. Por usuario, muestra lo recibido, lo cargado y lo que falta. Incluye las rendiciones nuevas que arrastran saldo.
- EECC usuario. Lista entregas, gastos, cierres y saldo de ese usuario. El saldo coincide con el de sus rendiciones.
- EECC tarjeta del usuario. Lista cargos de sus tarjetas, lo validado, lo rendido y el fondo por rendir de tarjeta que sigue abierto.
- Dashboard de desviaciones de tarjeta ex-post. Compara el pago de la tarjeta con lo rendido después y muestra la diferencia.
- Sumatoria. Suma el período por tipo de gasto y por tipo de documento.
- Remuneraciones. Muestra viáticos, asignaciones y bienes por usuario, y la desviación contra el concepto parametrizado. Esa desviación es la que después toma el asiento de traspaso.
- EECC agregado. Suma los estados de cuenta de los usuarios y conserva el detalle por persona.
- EECC caja chica. Muestra entregas a usuarios, reposiciones y saldo de la caja centralizada.
- EECC tarjeta de la empresa. Junta las tarjetas de la empresa: cargos, validación, fondos por rendir y desviaciones.
- EECC socios. Muestra documentos pagados, forma de pago y saldo por recuperar, separado por entidad.
- Socios por recuperar. Muestra qué rendiciones hay que recuperar desde socios hacia cada entidad. Incluye gastos de tarjeta o de bancos, según la empresa.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 6. Etapa 6: Flujo de pagos

Reunir lo que tesorería tiene que pagar y dejar el pago en proceso o la planilla banco emitida.

- Reúne fondos aceptados, documentos únicos informados y nóminas.
- El dashboard de flujo de pagos muestra cada pago como pendiente, en proceso o pagado.
- La planilla banco sale de la nómina que ya pasó el control de documentos duplicados.
- La API de pagos informa el estado a la conexión parametrizada para pagos.
- La reposición de caja chica se mueve dentro de la caja y se mantiene separada de la planilla banco.
- Los socios siguen con sus formas de pago centralizadas. El dashboard las muestra y las conserva.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

### Ítem 7. Etapa 7: Cierre de mes y asientos

Tomar las rendiciones del mes y sus pre-asientos, cerrar el período y dejar los asientos disponibles para el sistema contable, con los saldos que siguen abiertos.

- Arma los asientos por mes.
- El asiento de centralización junta esos movimientos y los lleva a contabilidad, con las cuentas genéricas de la clasificación.
- El asiento de traspaso de remuneraciones mueve viáticos, asignaciones y bienes a la liquidación de sueldo del usuario que rinde, con la cuenta de remuneraciones de ese usuario.
- Los cierres por mes dejan fijos los asientos del período.
- Los saldos que no se cerraron quedan como pendientes del mes. Siguen visibles en montos por rendir y en el estado de cuenta.
- El libro diario muestra los saldos contables de los asientos del mes.
- El pull de asientos deja esos asientos disponibles en la API para que el sistema contable los retire, por la conexión parametrizada.

| CANTIDAD | PRECIO UNIT. | DCTO. | TOTAL |
| --- | --- | --- | --- |
| A completar | A completar | A completar | A completar |

## Observaciones

Corresponde a la implementación del sistema de gestión de rendiciones de gastos (Rindete), según el alcance de siete etapas de esta cotización, desde la parametrización hasta el cierre de mes y los asientos.

Honorarios netos: A completar. El IVA se calcula sobre el equivalente en pesos al valor UF del día de facturación.

El valor UF del día y los montos en pesos quedan a completar. Los montos en pesos se reajustarán a la UF vigente en cada factura.

No incluye licenciamiento de sistemas externos, hardware, digitalización masiva histórica ni trabajos fuera de este alcance.

Cada etapa se factura a su término o según hito acordado con el cliente.

Estas tres lecturas quedan dentro de la etapa correspondiente y el cliente puede corregirlas:

- Más de un usuario con privilegio puede operar la misma tarjeta.
- El saldo negativo solicita el cierre, y ese pedido lo confirma el flujo de aceptación.
- El saldo positivo se traspasa a una rendición nueva, que puede recibir un monto adicional. En esta regla el sobrante no se devuelve en efectivo.

Envíe su comprobante de pago a: pagos@softnet.cl

Banco Santander Santiago · Cta. Cte. 621 52656

Cheques nominativos y cruzados a: SOFTNET SpA

## Totales

| Concepto | Monto |
| --- | --- |
| EXENTO | A completar |
| MONTO NETO | A completar |
| DESCUENTO | A completar |
| 19% IVA | A completar |
| RECARGO TOTAL | A completar |
| TOTAL | A completar |

**SON:** A completar

Giro: Desarrollo de Software, Consultoría Informática, Venta de Productos y Alimentos.

Estamos en redes para estar más cerca de ti.

Instagram · LinkedIn · Facebook

Desarrollado por www.softnet.cl

Fono: 56 2 2947 9389
