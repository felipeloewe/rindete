# Rindete — Anteproyecto de alcance

Actualizado el 9 de noviembre de 2026. Incorpora el listado de inicios, parametrización, rendición, reportería y asientos.

Rindete cubre cómo nace una rendición, con qué reglas se carga, qué pasa con el saldo y cómo se ve en estados de cuenta, dashboards, cierres de mes y asientos. El listado queda incluido entero. Este documento lo ordena; no deja piezas fuera.

## Actores

| Actor | Participa en |
| --- | --- |
| Usuario que rinde | Carga la rendición, sus gastos, montos y fotos. |
| Aceptador | Flujo de aceptación y reglas generales de la solicitud. |
| Tesorería | Procesa fondos solicitados, el documento único y la planilla banco. |
| Usuario autorizado | Rinde sin solicitud, dentro de parámetros máximos. |
| Usuario con privilegios | Documento único y tarjeta. En una tarjeta puede haber más de uno. |
| Caja | Caja chica centralizada, fondos a usuarios y reposición. |
| Socio | Paga varios documentos y centraliza formas de pago. |
| Empresa o entidad | Dueña de fondos, tarjetas, caja y cuentas. Los socios recuperan rendiciones hacia distintas entidades. |

## Siete formas de iniciar

Cada inicio trae su recorrido y el parámetro que el listado le asoció.

### 1. Solicitud de fondos por rendir

Recorrido: solicitud, aceptación, procesa tesorería, fondo por rendir.

Parámetros propios:

- Montos máximos de rendición.
- Tipos de gasto aceptados e ítems específicos.

Usa también el flujo de aceptación y las reglas generales.

### 2. Usuario autorizado a rendir sin solicitud

Recorrido: usuario autorizado, parámetros máximos, sujeto a viáticos y asignación (ítems de remuneración).

Parámetros propios:

- Parámetros por usuario, cuando no son los generales.
- Concepto de remuneraciones por usuario, cuando no es el general.

### 3. Documento único

Recorrido: documento único, forma de pago, informa, procesa tesorería.

Parámetros propios:

- Usuarios con privilegios.
- Comprobante de pago efectivo (contado).

### 4. Tarjeta personal o corporativa

Recorrido: usos de tarjeta personal o corporativa, pagos efectuados con tarjeta, y de ahí tres destinos: facturas por pagar con disminución (−), fondo por rendir de tarjeta, y validación.

Parámetros propios:

- Usuarios con privilegios, más de uno por tarjeta.
- Flujo de gastos.
- Flujo de DTE pagados, sin documento físico.

La tarjeta corporativa, además, tiene mantención, gastos propios de la tarjeta y montos aceptados.

### 5. Nómina de fondos solicitados

Recorrido: nómina de fondos solicitados y planilla banco.

Parámetro propio: control de documentos duplicados.

### 6. Caja chica centralizada

Recorrido: fondos directos a usuarios, centralizados en caja. La reposición de fondos sale de la caja chica.

Parámetro propio: auditoría de posible duplicidad de gasto.

### 7. Socios

Recorrido: pago de múltiples documentos con distintas formas de pago. Esas formas de pago se centralizan.

Parámetro propio: formas de pago de socios.

## Parametrización general

| Parámetro | Qué cubre |
| --- | --- |
| Cuentas contables genéricas | Cuentas de uso general y pre-asiento. |
| Cuentas contables de remuneraciones | Filtro por el usuario que rinde, ligado a la liquidación de sueldo. |
| Conexiones API | Integraciones disponibles para el sistema. |
| Flujo de aceptación | Reglas generales de quién acepta y cuándo. |
| Tarjeta corporativa | Mantención, gastos propios de la tarjeta y montos aceptados. |
| Gastos aceptados | Filtro por el usuario que rinde. |
| Tipos de documento | Vouchers, Transbank y sin comprobante, cada uno con montos aceptados. |

Los parámetros de cada inicio pisan al general cuando el listado dice «si no son generales»: parámetros por usuario y concepto de remuneraciones por usuario.

## Carga de la rendición

La carga asigna gastos, monto y foto.

- La foto se parametriza y se lee con OCR.
- En cada gasto se elige tipo de documento, clasificación del gasto, centro de costo (CECO), sucursal y otros conceptos.
- El tipo de documento respeta los tipos parametrizados: vouchers, Transbank, sin comprobante y el resto que se dé de alta, con sus montos aceptados.
- Los gastos aceptados y los montos máximos filtran lo que ese usuario puede cargar.

## Saldos

| Saldo | Qué ocurre |
| --- | --- |
| Negativo | Se solicita el cierre de la rendición. |
| Positivo | Se cierra la rendición y el saldo pasa a una rendición nueva. Esa rendición nueva puede sumar un monto adicional a completar. |

El seguimiento de montos por rendir muestra lo que sigue abierto después de ese traspaso.

## Seguimiento y dashboards

| Vista | Qué muestra |
| --- | --- |
| Montos por rendir | Seguimiento de lo que cada usuario todavía tiene que rendir. |
| Desviaciones de tarjeta ex-post | Desvíos de la tarjeta detectados después del pago. |
| Sumatoria | Totales por tipo de gasto y por tipo de documento. |
| Remuneraciones | Conceptos y desviaciones de viáticos, asignaciones y bienes. |
| Socios por recuperar | Rendiciones por recuperar desde socios hacia distintas entidades. Incluye gastos con tarjeta o con bancos, según la empresa. |
| Flujo de pagos | Dashboard del flujo de pagos, con API. |

## Estados de cuenta

| Estado de cuenta | Quién o qué agrupa |
| --- | --- |
| EECC usuario | El usuario que rinde. |
| EECC tarjeta del usuario | La tarjeta de ese usuario. |
| EECC agregado | La suma por sobre los usuarios. |
| EECC caja chica | La caja centralizada. |
| EECC tarjeta de la empresa | Las tarjetas de la empresa. |
| EECC socios | Los socios y lo que se les recupera. |

## Cierre de mes y asientos

| Pieza | Para qué |
| --- | --- |
| Asientos por mes | Asientos del período. |
| Cierres por mes | Cierre mensual de la rendición y sus saldos. |
| Pendientes del mes | Saldos que siguen abiertos al cierre. |
| Libro diario | Saldos contables día a día. |
| Asiento de centralización | Centraliza la rendición en contabilidad. |
| Asiento de traspaso | Pasa los conceptos de remuneraciones (viáticos, asignaciones, bienes) a la liquidación. |
| Pre-asiento | Sale de las cuentas contables genéricas antes del asiento definitivo. |
| Pull de asientos por API | Baja los asientos hacia o desde el sistema contable. |

Las cuentas usadas en esos asientos son las dos familias parametrizadas: genéricas y de remuneraciones. Las de remuneraciones se filtran por el usuario que rinde y por su liquidación de sueldo.

## APIs

| Superficie | Uso |
| --- | --- |
| Conexiones API | Parámetro general de integración. |
| Pull de asientos | Intercambio de asientos. |
| Flujo de pagos | Dashboard y API del circuito de pagos, incluida la planilla banco y lo que tesorería procesa. |

## Controles de duplicidad

- Nóminas: control de documentos duplicados.
- Caja chica: auditoría de posible duplicidad de gasto.

Son controles distintos. Uno mira el documento. El otro mira el gasto.

## Lecturas usadas para no dejar huecos

Estas tres lecturas ordenan frases del listado. El ítem sigue dentro del alcance aunque se ajuste la lectura.

- En la tarjeta, «más de uno por TC» queda como más de un usuario con privilegio sobre la misma tarjeta.
- El saldo negativo pide el cierre. Quién confirma ese cierre usa el flujo de aceptación.
- El saldo positivo no se devuelve en efectivo dentro de esta regla: se traspasa a una rendición nueva, que puede recibir un monto adicional.

## Orden para construir

1. Parámetros generales y los siete inicios, con tesorería, caja, tarjeta y socios.
2. Carga con foto, OCR, tipo de documento, clasificación, CECO, sucursal y conceptos.
3. Saldos, traspaso a rendición nueva y seguimiento de montos por rendir.
4. Dashboards y los seis estados de cuenta.
5. Cierre de mes, asientos, pull de asientos y flujo de pagos por API.
