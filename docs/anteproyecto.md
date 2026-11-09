# Rindete — Anteproyecto de alcance

Borrador del 9 de noviembre de 2026. Fija el circuito que ya está dicho: una empresa entrega fondos por rendir y el trabajador rinde esos gastos. No diseña pantallas, API ni base de datos.

El listado de ítems anunciado para cerrar el alcance no vino en la conversación. Lo que sigue es solo el flujo descrito. Cada ítem que se agregue entra en una de cuatro casillas: incluido, regla, decisión abierta o fuera de alcance.

## Qué resuelve

Rindete lleva la cuenta de un fondo que la empresa ya entregó. Para cada trabajador queda registrado cuánto se le dio, qué gastos lo justifican y cuánto saldo queda.

El circuito parte con el dinero ya asignado. No parte con una solicitud del trabajador ni con un reembolso de gastos pagados de su bolsillo.

## Definiciones

- **Fondo por rendir.** Dinero que la empresa entrega a un trabajador y que debe justificarse con gastos.
- **Gasto rendido.** Un desembolso cargado a un fondo, con su comprobante.
- **Saldo.** Monto entregado menos la suma de gastos de ese fondo. No se edita a mano.
- **Cierre.** Acto de la empresa que congela el fondo y su cuadre.

Mientras el fondo está abierto, un saldo positivo es dinero todavía no justificado. Al cerrar, ese mismo saldo se lee como monto a devolver. Un saldo negativo solo aparece si se aceptan gastos por sobre el fondo; al cierre se lee como monto a reembolsar.

## Actores

| Actor | En este circuito |
| --- | --- |
| Empresa | Entrega el fondo y cierra la rendición. |
| Trabajador | Ve sus fondos y carga los gastos que los rinden. |

«Usuario» es la cuenta de acceso. En esta etapa hay dos papeles: quien entrega y quien rinde. No hay un tercer actor (aprobador, tesorería o contabilidad) hasta que un ítem lo nombre.

## Objetos

| Objeto | Qué guarda | Relación |
| --- | --- | --- |
| Empresa | Nombre de la organización que entrega los fondos. | Dueña de los fondos y de sus trabajadores. |
| Trabajador | Persona que recibe un fondo y lo rinde. | Pertenece a una empresa. |
| Fondo por rendir | Monto entregado, moneda, motivo, fecha de entrega, estado y saldo. | Un fondo es de una empresa y de un trabajador. |
| Gasto | Fecha, concepto, monto, tipo de comprobante y número de comprobante. | Un gasto pertenece a un solo fondo. |

El archivo del comprobante queda como dato opcional hasta que el listado lo vuelva obligatorio. El comprobante no es un objeto aparte.

## Circuito

1. **Entrega.** La empresa asigna un fondo a un trabajador. El fondo nace con saldo igual al monto entregado.
2. **Carga.** El trabajador registra uno o más gastos contra ese fondo. Cada gasto trae su comprobante.
3. **Cuadre.** El saldo se recalcula: entregado menos la suma de gastos. Nadie lo tipea.
4. **Cierre.** La empresa da el fondo por rendido. El cuadre queda fijo: cuadrado, a devolver o a reembolsar.

Una rendición, en este alcance, es la suma de gastos de un fondo. No es un documento que agrupe varios fondos.

## Estados del fondo

| Estado | Cuándo |
| --- | --- |
| Asignado | La empresa entregó el fondo y todavía no hay gastos. |
| En rendición | Hay al menos un gasto y la empresa no ha cerrado. |
| Cerrado | La empresa dio por rendida la entrega. No se agregan gastos. |

## Reglas que ya se pueden fijar

1. No se carga un gasto sin un fondo asignado.
2. Un gasto descuenta un solo fondo.
3. El saldo es siempre entregado menos gastos. No hay ajuste manual.
4. Solo la empresa cierra el fondo. El trabajador carga gastos y no cierra.
5. Al cerrar, el resultado es uno de tres: cuadrado, a devolver o a reembolsar. Este anteproyecto muestra el resultado y no registra el pago ni la devolución del dinero.

## Dentro de este alcance

- Empresa y trabajadores vinculados.
- Alta de un fondo por rendir ya entregado.
- Gastos de ese fondo, con tipo y número de comprobante.
- Saldo calculado y cierre con cuadre fijo.

## Fuera de este alcance

Queda fuera hasta que un ítem lo traiga de vuelta:

- Solicitud del fondo por parte del trabajador y su aprobación.
- Gastos pagados del bolsillo, sin un fondo previo.
- Devolución del efectivo, reembolso pagado y conciliación bancaria.
- Contabilidad, centros de costo, impuestos y libro de compras.
- Varios niveles de aprobación, notificaciones y aplicación móvil.

## Decisiones abiertas

Cada fila trae una propuesta para no frenar. Si el listado dice otra cosa, se cambia la fila y se ajusta el objeto que corresponda.

| Decisión | Propuesta para avanzar | Si el listado dice otra cosa |
| --- | --- | --- |
| Varios gastos por fondo | Sí. La rendición es la suma de gastos de un fondo, y se pueden cargar en más de un momento. | Si se rinde de una sola vez, sobra el estado En rendición. |
| Varios fondos en una rendición | No. Un fondo se cierra solo. | Si una rendición agrupa fondos, aparece el objeto Rendición, distinto del fondo. |
| Gastar de más | Se permite cargar el gasto. El saldo negativo se lee, al cierre, como monto a reembolsar. | Si no se puede pasar del monto, el gasto que lo supera se rechaza. |
| Moneda | Pesos chilenos. Un monto y una moneda por fondo. | Otra moneda, o más de una, exige tipo de cambio en el cuadre. |
| Comprobante | Tipo y número van con el gasto. El archivo es opcional. | Si el archivo es obligatorio, pasa a ser parte del alcance y no un dato diferible. |
| Empresas | Se modela la empresa, y la operación de esta etapa es una empresa con sus trabajadores. | Si varias empresas operan a la vez, usuarios y fondos quedan aislados por empresa. |
| Quién más interviene | Nadie. Empresa entrega y cierra; trabajador carga. | Un aprobador, tesorería o contabilidad es un actor nuevo y un paso nuevo antes o después del cierre. |

## Cómo entra el listado

Cuando lleguen los ítems, cada uno se clasifica antes de diseñar pantallas:

- **Incluido**, si ya cabe en empresa, trabajador, fondo, gasto, saldo o cierre.
- **Regla**, si precisa algo que este documento ya nombra (montos, comprobantes, quién cierra).
- **Decisión**, si contradice una fila de la tabla anterior.
- **Fuera**, si abre solicitud, pagos, contabilidad, impuestos u otro circuito.

Con esa clasificación se actualiza este anteproyecto. El diseño de pantallas y de datos parte después, sobre el alcance ya clasificado.
