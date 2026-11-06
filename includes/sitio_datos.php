<?php

function web_home_faq() {
    return [
        [
            'q' => '¿Qué me ahorro si lo uso con el equipo?',
            'a' => 'Dejas de juntar boletas por WhatsApp. Quien gastó sube el comprobante, quien aprueba ve el duplicado o el tope antes de pagar, y finanzas cierra el viaje, la caja o el reembolso en un informe.',
        ],
        [
            'q' => '¿Quién entra a la cuenta?',
            'a' => 'Tres perfiles. El colaborador es quien gastó y arma su informe. El aprobador es quien decide si se paga. El administrador es quien fija topes, categorías y el equipo. Cada uno ve lo suyo.',
        ],
        [
            'q' => '¿Ustedes emiten la factura?',
            'a' => 'No. Si un cliente te tiene que pasar sus datos para que tú le factures, se los pides por correo o con un QR y te llegan a una bandeja. La factura la emites en tu facturador. El gasto de tu equipo es otro flujo: comprobante, política y aprobación.',
        ],
        [
            'q' => '¿Sirve si opero en más de un país?',
            'a' => 'Cada empresa se abre en un país. El formulario pide el documento de esa autoridad, RUT, NIT, RFC o RUC, y los topes quedan en la moneda local. Cubre Chile, Colombia, México, Perú, Ecuador y El Salvador.',
        ],
        [
            'q' => '¿Cuánto cuesta empezar?',
            'a' => 'El plan de inicio es gratis: hasta 3 usuarios, 50 documentos al mes y 1 rendición por persona. Al crear la empresa te llega la bienvenida con la clave.',
        ],
    ];
}

function web_temas() {
    static $cache;
    if ($cache) {
        return $cache;
    }
    $cache = [
        'pide-tu-factura' => [
            'slug' => 'pide-tu-factura',
            'grupo' => 'factura',
            'nav' => 'Pide tu factura',
            'titulo' => 'Onboarding para recolectar datos de facturación',
            'h1' => 'Pide los datos. La factura la emites tú.',
            'kicker' => 'Correo o QR · RUT · NIT · RFC · RUC',
            'description' => 'Onboarding para que un cliente envíe sus datos de facturación. Se manda por correo o se imprime como QR. Los adjuntos llegan a una bandeja.',
            'extracto' => 'Cualquier empresa recolecta RUT, NIT, RFC o RUC y los archivos que pida. PideTuFactura no emite la factura.',
            'lede' => 'Quieres que un cliente te mande sus datos para facturarle. Desde la cuenta le envías un onboarding a su correo. Si atiendes en un local, imprimes el mismo formulario como QR. La persona completa razón social, el documento de tu país y los adjuntos que pediste. Eso cae en tu bandeja. Nosotros no vendemos ni emitimos el comprobante.',
            'pasos' => [
                ['titulo' => 'Envías el onboarding', 'texto' => 'Escribes el correo del cliente y sale el enlace. En un restaurante o un negocio de mostrador, imprimes el QR de ese mismo enlace.'],
                ['titulo' => 'El cliente completa', 'texto' => 'Razón social, contacto y el documento del país donde facturas: RUT, NIT, RFC o RUC. Si pediste archivos, los adjunta ahí.'],
                ['titulo' => 'Llega a la bandeja', 'texto' => 'Ves la ficha y los archivos. Si ese documento ya existía, se actualiza el contacto y no se duplica el cliente.'],
                ['titulo' => 'Tú facturas', 'texto' => 'Con esos datos emites en tu facturador, ante el SII, la DIAN, el SAT, SUNAT, el SRI o Hacienda. PideTuFactura no da ese paso.'],
            ],
            'cuerpo' => [
                ['h2', 'No es un vendedor'],
                ['p', 'El nombre viene del momento en que alguien pide la factura y tiene que entregar sus datos. El producto se queda en esa recolección. No arma el XML, no timbra y no cobra por la venta. La empresa que contrata el servicio es la que factura.'],
                ['h2', 'Dos puertas, un formulario'],
                ['p', 'El correo sirve cuando ya conoces al cliente: le llega el onboarding y lo llena cuando pueda. El QR sirve cuando la persona está frente al mesón y no tienes su correo de antemano. Los dos abren la misma ficha y los mismos adjuntos.'],
                ['h2', 'El documento no se llama igual'],
                ['p', 'El formulario usa la palabra del país de la empresa que factura. En Chile pide RUT. En Colombia y El Salvador, NIT: en Bogotá no se dice RUT. En México, RFC. En Perú y Ecuador, RUC.'],
                ['tabla', [
                    ['País', 'Lo que se le pide al cliente', 'Autoridad'],
                    ['Chile', 'RUT', 'SII'],
                    ['Colombia', 'NIT', 'DIAN'],
                    ['México', 'RFC', 'SAT'],
                    ['Perú', 'RUC', 'SUNAT'],
                    ['Ecuador', 'RUC', 'SRI'],
                    ['El Salvador', 'NIT', 'Ministerio de Hacienda'],
                ]],
                ['nota', 'En la cuenta ya puedes enviar el onboarding, definir adjuntos, imprimir el QR y leer la bandeja. La emisión del comprobante sigue en el facturador de la empresa.'],
            ],
            'faq' => [
                [
                    'q' => '¿PideTuFactura emite la factura?',
                    'a' => 'No. Recolecta los datos y los archivos. La empresa los usa en su propio facturador.',
                ],
                [
                    'q' => '¿El QR es distinto del correo?',
                    'a' => 'No. Es la misma ficha. El correo se envía a una persona. El QR se imprime cuando el cliente está en el local.',
                ],
                [
                    'q' => '¿En Colombia se pide RUT?',
                    'a' => 'No. Una empresa colombiana pide NIT. El RUT es de Chile.',
                ],
            ],
            'enlaces' => [
                ['Rendición de gastos', 'rendicion-de-gastos'],
                ['Cómo se dice en cada país', 'blog/como-se-dice-el-gasto-en-cada-pais'],
                ['Chile', 'chile'],
                ['Colombia', 'colombia'],
            ],
        ],
        'rendicion-de-gastos' => [
            'slug' => 'rendicion-de-gastos',
            'grupo' => 'concepto',
            'nav' => 'Rendición de gastos',
            'titulo' => 'Rendición de gastos en Latam',
            'h1' => 'Rendición de gastos, con el verbo de cada país',
            'kicker' => 'Rendir · Legalizar · Comprobar · Liquidar',
            'description' => 'Qué es una rendición de gastos y cómo se dice en Chile, Colombia, México, Perú, Ecuador y El Salvador: legalizar, comprobar o liquidar.',
            'extracto' => 'El expediente del gasto. En tu país puede llamarse rendición, legalización, comprobación o liquidación.',
            'lede' => 'La rendición es el paquete que viaja a quien aprueba: comprobantes, motivo y total. El verbo cambia con la frontera. El trabajo no.',
            'pasos' => [
                ['titulo' => 'Cargar', 'texto' => 'Concepto, proveedor, categoría, monto, nota y la foto o el PDF del comprobante.'],
                ['titulo' => 'Avisar', 'texto' => 'Duplicado por número e identificación del proveedor, o por el mismo proveedor, monto y fecha cercana. También si el gasto pasa el tope.'],
                ['titulo' => 'Informar', 'texto' => 'El viaje, el mes o la caja se arma en un informe y se envía a revisión.'],
                ['titulo' => 'Aprobar', 'texto' => 'Quien aprueba ve el total, las alertas y el comprobante. Si falta algo, lo devuelve con una nota.'],
            ],
            'cuerpo' => [
                ['p', 'Una rendición de gastos documenta lo que ya se pagó en nombre de la empresa. No es el pago en sí. Puede cerrar un viaje, un mes, una caja o un anticipo. Si la persona puso dinero de su bolsillo, después de aprobar el expediente viene el reembolso.'],
                ['h2', 'El mismo expediente, cuatro verbos'],
                ['tabla', [
                    ['País', 'Cómo pide el equipo el expediente', 'Fondo menor', 'Devolución'],
                    ['Chile', 'Rendición de gastos', 'Caja chica', 'Reembolso'],
                    ['Colombia', 'Legalización de gastos', 'Caja menor', 'Reembolso'],
                    ['México', 'Comprobación de gastos', 'Caja chica', 'Reembolso'],
                    ['Perú', 'Rendición de gastos', 'Caja chica', 'Reembolso o reintegro'],
                    ['Ecuador', 'Liquidación o rendición', 'Caja chica', 'Reembolso'],
                    ['El Salvador', 'Liquidación de gastos', 'Caja chica', 'Reembolso'],
                ]],
                ['p', [
                    ['t', 'Las páginas de cada país bajan al documento: '],
                    ['a', 'chile', 'Chile'],
                    ['t', ', '],
                    ['a', 'colombia', 'Colombia'],
                    ['t', ', '],
                    ['a', 'mexico', 'México'],
                    ['t', ', '],
                    ['a', 'peru', 'Perú'],
                    ['t', ', '],
                    ['a', 'ecuador', 'Ecuador'],
                    ['t', ' y '],
                    ['a', 'el-salvador', 'El Salvador'],
                    ['t', '.'],
                ]],
                ['h2', 'Qué revisa la cuenta, de verdad'],
                ['p', 'Al guardar, el gasto queda observado si el número de documento y la identificación del proveedor ya existen, si el mismo proveedor repite monto en una fecha cercana, si el monto pasa el tope de la categoría, o si falta la foto cuando la política la pide. Si faltan el tipo de documento, la identificación o el número, aparece la marca Fiscal incompleto.'],
                ['p', 'El formulario pide además el código con el nombre de la autoridad: folio DTE, CUFE, UUID del CFDI, código de generación, clave de acceso o serie y número. Ese campo viaja con el gasto para quien aprueba. La cuenta no consulta en línea al SII, a la DIAN, al SAT, al SRI, a SUNAT ni al Ministerio de Hacienda.'],
                ['p', [
                    ['t', 'Si el dinero lo adelantó el colaborador, el siguiente concepto es el '],
                    ['a', 'reembolso-de-gastos', 'reembolso'],
                    ['t', '. Si el dinero ya era de la empresa y vive en un fondo fijo, mira '],
                    ['a', 'caja-menor', 'caja menor y caja chica'],
                    ['t', '.'],
                ]],
                ['nota', 'Esta página ordena el lenguaje del equipo. No dice qué gasto es deducible: eso lo ve tu contador con la norma del país.'],
            ],
            'faq' => [
                [
                    'q' => '¿Legalizar en Colombia es distinto de rendir en Chile?',
                    'a' => 'El nombre es el del país. En los dos casos el colaborador junta comprobantes, explica el gasto y alguien lo aprueba. En Colombia se dice legalizar y el fondo menor se llama caja menor. En Chile se rinde y el fondo se llama caja chica.',
                ],
                [
                    'q' => '¿La rendición incluye el reembolso?',
                    'a' => 'La rendición justifica. El reembolso paga de vuelta. Puedes rendir una caja chica sin reembolsar a nadie, porque el dinero ya era de la empresa.',
                ],
                [
                    'q' => '¿Hace falta un informe por cada gasto?',
                    'a' => 'No. Varios gastos de un viaje o de un mes entran en un solo informe. Quien aprueba ve el total y abre el comprobante que necesite.',
                ],
            ],
            'enlaces' => [
                ['Caja menor y caja chica', 'caja-menor'],
                ['Reembolso de gastos', 'reembolso-de-gastos'],
                ['Cómo se dice en cada país', 'blog/como-se-dice-el-gasto-en-cada-pais'],
            ],
        ],
        'caja-menor' => [
            'slug' => 'caja-menor',
            'grupo' => 'concepto',
            'nav' => 'Caja menor',
            'titulo' => 'Caja menor y caja chica',
            'h1' => 'Caja menor y caja chica',
            'kicker' => 'Fondo fijo · Reposición · Gastos menores',
            'description' => 'Caja menor en Colombia y caja chica en Chile, México, Perú, Ecuador y El Salvador: qué es el fondo fijo y cómo se rinde para reponerlo.',
            'extracto' => 'Un monto fijo para gastos menores. En Colombia se llama caja menor. En el resto de estos países, caja chica.',
            'lede' => 'La caja no es un reembolso. Es dinero de la empresa, quieto en un fondo, que se usa para lo menor y se repone cuando los comprobantes cierran el periodo.',
            'pasos' => [
                ['titulo' => 'Asignar', 'texto' => 'La empresa define el fondo y quién lo cuida. El tope de cada categoría vive en la política, no en un PDF suelto.'],
                ['titulo' => 'Gastar', 'texto' => 'Cada salida queda con proveedor, monto, fecha y comprobante. La foto acompaña al dato.'],
                ['titulo' => 'Rendir', 'texto' => 'Al cierre, esos gastos forman un informe distinto del viaje y distinto del reembolso personal.'],
                ['titulo' => 'Reponer', 'texto' => 'Con el informe aprobado, tesorería repone el fondo. Sin comprobantes, la caja no vuelve a su monto.'],
            ],
            'cuerpo' => [
                ['p', 'Caja menor y caja chica nombran el mismo control: un fondo fijo para gastos pequeños, urgentes o de operación diaria. No alcanza para esconder un viaje completo ni para pagar a un colaborador que adelantó dinero propio. Eso último es reembolso.'],
                ['h2', 'Cómo se dice'],
                ['tabla', [
                    ['País', 'Nombre del fondo', 'Cómo se cierra'],
                    ['Colombia', 'Caja menor', 'Se legaliza'],
                    ['Chile', 'Caja chica', 'Se rinde'],
                    ['México', 'Caja chica', 'Se comprueba'],
                    ['Perú', 'Caja chica', 'Se rinde'],
                    ['Ecuador', 'Caja chica', 'Se liquida o se rinde'],
                    ['El Salvador', 'Caja chica', 'Se liquida'],
                ]],
                ['p', 'En Colombia, caja menor es el término que entiende finanzas, sobre todo si el equipo viene del lenguaje del sector público o de un manual contable local. Decir caja chica en Bogotá no es incomprensible, pero no es la palabra de la casa. Al revés, pedir una caja menor en Santiago suena a traducción.'],
                ['h2', 'Qué no es la caja'],
                ['ul', [
                    'No es un anticipo de viaje. En Chile eso se acerca a un fondo por rendir. En Perú, a una entrega a rendir.',
                    'No es viático. El viático cubre alimentación, alojamiento o traslado de un viaje, fijo o contra comprobante, según la política.',
                    'No es reembolso. En el reembolso el dinero salió del bolsillo de la persona.',
                ]],
                ['p', [
                    ['t', 'La diferencia completa está en la guía '],
                    ['a', 'blog/rendicion-reembolso-y-caja-menor', 'rendición, reembolso y caja menor'],
                    ['t', '. Para Perú, la caja chica frente a la entrega a rendir está en '],
                    ['a', 'blog/caja-chica-en-peru', 'caja chica y entregas a rendir'],
                    ['t', '.'],
                ]],
                ['nota', 'El monto del fondo, qué se puede comprar y cada cuánto se repone los define la política de tu empresa. Esta página no fija un tope legal.'],
            ],
            'faq' => [
                [
                    'q' => '¿Por qué la página se llama caja menor si en mi país se dice caja chica?',
                    'a' => 'Porque caja menor es el término que un equipo colombiano busca, y caja chica es el que buscan en Chile, México, Perú, Ecuador y El Salvador. El control es el mismo y aquí están los dos nombres.',
                ],
                [
                    'q' => '¿La caja se mezcla con los gastos del viaje?',
                    'a' => 'Conviene que no. El viaje suele terminar en reembolso o en cierre de un anticipo. La caja se repone contra su propio informe, para que el fondo siga cuadrando.',
                ],
                [
                    'q' => '¿Qué comprobante acepta la caja?',
                    'a' => 'El que la política de la empresa permita y el que el país use: boleta, factura, cuenta de cobro, CFDI, DTE, nota de venta o recibo. El formulario muestra los tipos del país elegido.',
                ],
            ],
            'enlaces' => [
                ['Colombia y la caja menor', 'colombia'],
                ['Reembolso de gastos', 'reembolso-de-gastos'],
                ['Caja chica en Perú', 'blog/caja-chica-en-peru'],
            ],
        ],
        'reembolso-de-gastos' => [
            'slug' => 'reembolso-de-gastos',
            'grupo' => 'concepto',
            'nav' => 'Reembolsos',
            'titulo' => 'Reembolso de gastos',
            'h1' => 'Reembolso de gastos',
            'kicker' => 'Dinero del colaborador · Devolución · Comprobante',
            'description' => 'Qué es un reembolso de gastos y en qué se diferencia de la rendición, la legalización, la caja menor y la caja chica.',
            'extracto' => 'La devolución de lo que el colaborador pagó con su dinero. Primero el comprobante, después el pago de vuelta.',
            'lede' => 'Reembolso es la palabra más estable de la región. Casi nadie dice otra cosa cuando la empresa le devuelve al colaborador lo que adelantó. El expediente que autoriza esa devolución sí cambia de nombre.',
            'pasos' => [
                ['titulo' => 'Adelanta', 'texto' => 'La persona paga el almuerzo, el taxi o el hotel con su dinero o con una tarjeta personal.'],
                ['titulo' => 'Respalda', 'texto' => 'Sube el comprobante del país, el monto y el motivo. La alerta de duplicado o de tope sale antes de la aprobación.'],
                ['titulo' => 'Aprueba', 'texto' => 'El informe pasa a quien aprueba. Sin ese visto bueno, tesorería no tiene un paquete claro para pagar.'],
                ['titulo' => 'Devuelve', 'texto' => 'El reembolso es el pago de vuelta. La cuenta ordena el expediente; el giro lo hace la empresa por su banco o, más adelante, por un riel de pagos.'],
            ],
            'cuerpo' => [
                ['p', 'Un reembolso de gastos devuelve dinero. Una rendición, una legalización o una comprobación explica el gasto. Hacen falta las dos cosas cuando el colaborador puso plata propia: primero se aprueba el expediente y después se paga.'],
                ['h2', 'Tres salidas de dinero que se parecen y no lo son'],
                ['ul', [
                    'Reembolso: la persona ya pagó. La empresa le devuelve.',
                    'Caja menor o caja chica: la empresa ya había dejado un fondo. Se rinde para reponerlo, no para pagarle a alguien un adelanto personal.',
                    'Anticipo: la empresa entrega dinero antes del gasto. En Chile se habla de fondos por rendir. En Perú, de entregas a rendir. Al volver, se liquida contra comprobantes. Si sobra, se devuelve. Si falta y la política lo permite, ahí puede aparecer un reembolso.',
                ]],
                ['p', 'En Perú algunas áreas dicen reintegro en vez de reembolso. Es la misma devolución. En México el paquete se arma como comprobación de gastos o informe de viáticos, y el reembolso es el pago que sigue.'],
                ['p', [
                    ['t', 'El mapa de nombres está en '],
                    ['a', 'blog/como-se-dice-el-gasto-en-cada-pais', 'cómo se dice el gasto en cada país'],
                    ['t', '. Si el pago del reembolso o del proveedor cruza de país, el tramo de dinero se cuenta aparte en '],
                    ['a', 'tarjetas', 'tarjetas y pagos'],
                    ['t', '.'],
                ]],
                ['nota', 'PideTuFactura deja el gasto aprobado y el total del informe. No transfiere el reembolso al banco del colaborador.'],
            ],
            'faq' => [
                [
                    'q' => '¿Todo gasto aprobado se reembolsa?',
                    'a' => 'No. Solo se reembolsa lo que la persona pagó y la empresa aceptó. Un gasto de caja chica se repone al fondo, no al bolsillo de quien tenía la caja, salvo que la política diga otra cosa.',
                ],
                [
                    'q' => '¿Un viático fijo también se rinde?',
                    'a' => 'Depende de la política. Hay viáticos por monto diario, sin comprobante de cada café, y viáticos contra boleta o factura. Si tu empresa pide respaldo, el comprobante entra al informe igual que cualquier gasto.',
                ],
                [
                    'q' => '¿Se dice reembolso en todos estos países?',
                    'a' => 'Sí, es el término común. En Perú también aparece reintegro. El cambio fuerte de vocabulario está en el expediente y en la caja, no en la devolución.',
                ],
            ],
            'enlaces' => [
                ['Rendición de gastos', 'rendicion-de-gastos'],
                ['Caja menor y caja chica', 'caja-menor'],
                ['Tarjetas y pagos', 'tarjetas'],
            ],
        ],
        'tarjetas' => [
            'slug' => 'tarjetas',
            'grupo' => 'pago',
            'nav' => 'Tarjetas y pagos',
            'titulo' => 'Tarjetas y pagos para gastos de empresa',
            'h1' => 'Tarjetas y pagos para el gasto de la empresa',
            'kicker' => 'Comprobante local · Dinero entre países',
            'description' => 'Cómo se relaciona una tarjeta o un pago internacional con la rendición de gastos. La API transaccional de Global66 es un camino posible para el dinero, no un reemplazo del comprobante.',
            'extracto' => 'El comprobante sigue siendo del país donde se compró. La tarjeta o el pago entre países es otra capa.',
            'lede' => 'Una tarjeta corporativa evita que la persona adelante su plata. No evita la boleta, la factura, el CFDI o el DTE. El gasto se sigue rindiendo donde se compró.',
            'pasos' => [
                ['titulo' => 'Paga', 'texto' => 'Viaje, caja o compra menor, con tarjeta de la empresa o con dinero del colaborador.'],
                ['titulo' => 'Respalda', 'texto' => 'Entra el comprobante local: boleta, factura, cuenta de cobro, CFDI, DTE o el documento que use el país.'],
                ['titulo' => 'Aprueba', 'texto' => 'Política, duplicados y tope se ven en el informe, igual que en un reembolso clásico.'],
                ['titulo' => 'Concilia', 'texto' => 'El movimiento de dinero se cruza con ese informe. Si el pago cruza de país, hace falta un riel aparte.'],
            ],
            'cuerpo' => [
                ['h2', 'Por qué la tarjeta no cierra el gasto'],
                ['p', 'El extracto de una tarjeta dice monto, comercio y fecha. El SII, la DIAN, el SAT, SUNAT, el SRI o el Ministerio de Hacienda piden el comprobante del país. Sin ese documento, finanzas tiene un cargo y no tiene cómo explicarlo. Por eso el flujo sigue siendo cargar, avisar y aprobar, aunque nadie haya pedido reembolso.'],
                ['h2', 'Global66 como camino de pagos'],
                ['p', [
                    ['t', 'Global66 publica una API transaccional para que una empresa inicie pagos B2B entre países desde su propia plataforma. La documentación está en '],
                    ['a', 'https://documents-b2b.global66.com/transactional-api/introduction/', 'Global66 Developers'],
                    ['t', '. En su ecosistema también existe tarjeta prepago. Para un equipo de gastos, las dos ideas se tocan: un medio para mover dinero y un comprobante que igual hay que rendir.'],
                ]],
                ['p', 'Ese riel sirve, por ejemplo, para pagarle a un proveedor en otra moneda o para fondear el gasto de un equipo que viaja por la región. No sustituye el folio DTE, el CUFE, el UUID, la clave de acceso, el código de generación ni la serie y número.'],
                ['nota', 'Estado de esta capa: en la cuenta ya se registra el gasto, la foto y la rendición. Esta página describe tarjetas y pagos para el sitio. PideTuFactura todavía no emite tarjetas ni llama a la API de Global66, y no mueve dinero.'],
                ['p', [
                    ['t', 'Mientras tanto, el reembolso se aprueba en el '],
                    ['a', 'reembolso-de-gastos', 'expediente de gastos'],
                    ['t', ' y tesorería lo paga por el banco que ya usa la empresa.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Ya puedo pedir una tarjeta PideTuFactura?',
                    'a' => 'No. La cuenta cubre comprobantes, alertas, informes y aprobación. La tarjeta y la conexión con la API de Global66 están contadas en esta página y todavía no operan dentro del producto.',
                ],
                [
                    'q' => '¿La API de Global66 valida facturas?',
                    'a' => 'No. Su API transaccional pública está hecha para pagos B2B entre países. La factura, la boleta o el CFDI se cargan en la rendición, con los datos del país.',
                ],
                [
                    'q' => '¿Un pago con tarjeta igual se rinde?',
                    'a' => 'Sí. Cambia quién puso el dinero al inicio. No cambia la necesidad del comprobante ni de la política de la empresa.',
                ],
            ],
            'enlaces' => [
                ['Reembolso de gastos', 'reembolso-de-gastos'],
                ['Rendición de gastos', 'rendicion-de-gastos'],
                ['Cómo se dice en cada país', 'blog/como-se-dice-el-gasto-en-cada-pais'],
            ],
        ],
    ];
    return $cache;
}

function web_tema($slug) {
    $todos = web_temas();
    return $todos[$slug] ?? null;
}

function web_landings() {
    static $cache;
    if ($cache) {
        return $cache;
    }
    $nota = 'Guía operativa para el equipo. No reemplaza a tu contador ni una consulta a la autoridad fiscal.';
    $cache = [
        'chile' => [
            'codigo' => 'CL',
            'slug' => 'chile',
            'nombre' => 'Chile',
            'lang' => 'es-CL',
            'locale' => 'es_CL',
            'titulo' => 'Rendición de gastos en Chile',
            'h1' => 'Rendición de gastos en Chile',
            'kicker' => 'Control de gastos',
            'frase' => 'Rendición de gastos y caja chica',
            'description' => 'Control de rendición de gastos, caja chica y reembolsos en Chile. La cuenta pide RUT y folio DTE, avisa duplicados y deja el informe listo para aprobar.',
            'lede' => 'La cuenta donde el equipo sube la boleta, arma la rendición y aprueba antes de reembolsar o de reponer la caja chica.',
            'usos' => [
                ['titulo' => 'Rendición del viaje', 'detalle' => 'Viático, hotel y almuerzo van en un informe, con boleta o factura electrónica. Quien aprueba ve el total, no fotos sueltas.'],
                ['titulo' => 'Caja chica', 'detalle' => 'El fondo de lo menor se rinde para reponerlo. En Chile no se llama caja menor, y no se mezcla con el viaje.'],
                ['titulo' => 'Reembolso', 'detalle' => 'Si el colaborador pagó de su bolsillo, se devuelve después de aprobar. Un fondo por rendir es el anticipo, y se cierra en otro informe.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el gasto', 'texto' => 'Proveedor, monto, categoría y la foto. El formulario pide RUT y folio DTE.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en pesos o dato fiscal vacío, antes de que alguien lo pague dos veces.'],
                ['titulo' => 'Arma la rendición', 'texto' => 'Viaje, caja chica o fondo por rendir. Cada historia en su informe.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Ve el total y las alertas. Si falta algo, devuelve con una nota. La transferencia la hace tesorería.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en Chile. El colaborador deja la boleta o la factura en la cuenta. El aprobador decide con el comprobante a la vista. El administrador fija topes por categoría en pesos chilenos y el tipo de documento que se exige.'],
                ['p', [
                    ['t', 'Un almuerzo y un hotel pueden compartir la rendición del viaje. La caja chica del mes no. Si el mismo folio DTE y el mismo RUT ya estaban cargados, o el mismo proveedor repite monto en una fecha cercana, el gasto queda marcado. El detalle operativo está en '],
                    ['a', 'blog/rendicion-de-gastos-en-chile', 'la rendición de gastos en Chile'],
                    ['t', ', y el producto, en '],
                    ['a', 'rendicion-de-gastos', 'rendición de gastos'],
                    ['t', ' y '],
                    ['a', 'reembolso-de-gastos', 'reembolso de gastos'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa chilena?',
                    'a' => 'Para cerrar la rendición sin perseguir boletas por el chat. El equipo sube el comprobante, la cuenta avisa duplicado o tope, y alguien aprueba el informe antes del reembolso o de la reposición de caja chica.',
                ],
                [
                    'q' => '¿Caja chica o caja menor?',
                    'a' => 'Caja chica. Caja menor es el nombre que se usa en Colombia. En la cuenta, al crear la empresa en Chile, el fondo menor se trata como caja chica.',
                ],
                [
                    'q' => '¿Validan la boleta con el SII?',
                    'a' => 'No. Guardamos RUT, tipo de documento y folio DTE, y la foto. El duplicado se detecta con esos datos, o con proveedor, monto y fecha cercana. No hay consulta al SII.',
                ],
            ],
            'guias' => ['rendicion-de-gastos-en-chile', 'como-se-dice-el-gasto-en-cada-pais'],
            'nota' => $nota,
        ],
        'colombia' => [
            'codigo' => 'CO',
            'slug' => 'colombia',
            'nombre' => 'Colombia',
            'lang' => 'es-CO',
            'locale' => 'es_CO',
            'titulo' => 'Legalización de gastos y caja menor en Colombia',
            'h1' => 'Legalización de gastos y caja menor en Colombia',
            'kicker' => 'Control de gastos',
            'frase' => 'Legalización de gastos y caja menor',
            'description' => 'Legalización de gastos y caja menor en Colombia. La cuenta pide NIT y CUFE, separa el fondo del viaje y avisa antes de aprobar el reembolso.',
            'lede' => 'La cuenta donde el equipo legaliza el gasto, repone la caja menor y aprueba el reembolso con el soporte a la vista.',
            'usos' => [
                ['titulo' => 'Legalización del viaje', 'detalle' => 'Hotel, almuerzo y traslado en un informe. Quien aprueba ve el soporte y el total antes de devolver la plata.'],
                ['titulo' => 'Caja menor', 'detalle' => 'El fondo de lo menor se legaliza para reponerlo. En Colombia ese es el nombre. No se mezcla con el viaje.'],
                ['titulo' => 'Reembolso', 'detalle' => 'Si el colaborador pagó de su bolsillo, se devuelve después de legalizar. La caja menor no es un reembolso personal.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el soporte', 'texto' => 'Factura, cuenta de cobro o documento soporte, con NIT y CUFE cuando existan.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en pesos o dato fiscal vacío, antes de reponer la caja o de reembolsar.'],
                ['titulo' => 'Arma la legalización', 'texto' => 'Caja menor en un informe. Viaje en otro. Así el fondo cuadra.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Ve el mismo comprobante. Si no alcanza, lo devuelve con una nota. La transferencia la hace tesorería.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en Colombia. El colaborador carga el soporte. El aprobador decide si se legaliza. El administrador fija topes en pesos colombianos y qué documento se exige: factura electrónica, documento equivalente, cuenta de cobro o documento soporte.'],
                ['p', [
                    ['t', 'Un almuerzo en Medellín no debería entrar dos veces. Si el número del documento y el NIT ya estaban, o el mismo proveedor repite monto en una fecha cercana, la cuenta lo marca. El CUFE queda junto al soporte para quien aprueba. La guía está en '],
                    ['a', 'blog/legalizar-gastos-en-colombia', 'cómo legalizar un gasto en Colombia'],
                    ['t', ', y el fondo, en '],
                    ['a', 'caja-menor', 'caja menor'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa colombiana?',
                    'a' => 'Para legalizar el gasto sin cazar soportes por el chat. El equipo sube el comprobante, la cuenta avisa duplicado o tope, y alguien aprueba antes de reponer la caja menor o de reembolsar.',
                ],
                [
                    'q' => '¿Legalizar es lo mismo que reembolsar?',
                    'a' => 'No. Legalizar deja el informe listo. Reembolsar devuelve la plata. La caja menor se legaliza para reponer el fondo, sin un reembolso a una persona.',
                ],
                [
                    'q' => '¿Validan el CUFE con la DIAN?',
                    'a' => 'No. Pedimos NIT, tipo de soporte y CUFE, y la foto. El duplicado se detecta con el número y el NIT, o con proveedor, monto y fecha cercana. No hay consulta a la DIAN.',
                ],
            ],
            'guias' => ['legalizar-gastos-en-colombia', 'rendicion-reembolso-y-caja-menor'],
            'nota' => $nota,
        ],
        'mexico' => [
            'codigo' => 'MX',
            'slug' => 'mexico',
            'nombre' => 'México',
            'lang' => 'es-MX',
            'locale' => 'es_MX',
            'titulo' => 'Comprobación de gastos en México',
            'h1' => 'Comprobación de gastos en México',
            'kicker' => 'Control de gastos',
            'frase' => 'Comprobación de gastos y caja chica',
            'description' => 'Comprobación de gastos, caja chica y viáticos en México. La cuenta guarda RFC y UUID del CFDI, y deja el informe listo para aprobar.',
            'lede' => 'La cuenta donde el equipo comprueba el gasto con el CFDI, separa la caja chica del viático y aprueba antes de reembolsar.',
            'usos' => [
                ['titulo' => 'Comprobación del viaje', 'detalle' => 'Viáticos de alimentos, hospedaje y traslado en un informe. El CFDI queda con RFC y UUID a la vista de quien aprueba.'],
                ['titulo' => 'Caja chica', 'detalle' => 'El fondo de lo menor se comprueba para reponerlo. En México no se llama caja menor, y no va con el viaje.'],
                ['titulo' => 'Reembolso', 'detalle' => 'Si el colaborador pagó de su bolsillo, se devuelve después de la comprobación. La caja chica se repone al fondo, no a una persona.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el comprobante', 'texto' => 'CFDI, nota de venta o recibo, con RFC y UUID del CFDI cuando el proveedor lo emitió.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en pesos mexicanos o dato fiscal vacío, antes de que el informe se apruebe.'],
                ['titulo' => 'Arma el informe', 'texto' => 'Viáticos de un viaje en un informe. Caja chica en otro.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Ve el total y el comprobante. Si falta el UUID o la foto, lo devuelve con una nota.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en México. El colaborador sube el comprobante. El aprobador decide si se reembolsa o si se repone la caja chica. El administrador fija topes en pesos mexicanos y el tipo de documento que la política acepta.'],
                ['p', [
                    ['t', 'El UUID de un hotel no debería repetirse en dos gastos. Si el número y el RFC ya estaban, o el mismo proveedor repite monto en una fecha cercana, la cuenta lo marca. No timbramos ni consultamos al SAT. La guía está en '],
                    ['a', 'blog/comprobar-gastos-en-mexico', 'comprobar gastos en México'],
                    ['t', ', y el reembolso, en '],
                    ['a', 'reembolso-de-gastos', 'reembolso de gastos'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa mexicana?',
                    'a' => 'Para comprobar viáticos y caja chica sin una pila de XML en el correo. El equipo sube el CFDI, la cuenta avisa duplicado o tope, y alguien aprueba el informe antes de reembolsar.',
                ],
                [
                    'q' => '¿Sin CFDI no hay comprobación?',
                    'a' => 'La política de tu empresa lo decide. El formulario también ofrece nota de venta y recibo. Cuando el proveedor puede emitir CFDI, ese es el respaldo que el informe guarda, con RFC y UUID.',
                ],
                [
                    'q' => '¿Timbran o validan el XML con el SAT?',
                    'a' => 'No. Guardamos RFC, tipo, número y UUID, y la foto o el PDF. No hay consulta al SAT ni timbrado.',
                ],
            ],
            'guias' => ['comprobar-gastos-en-mexico', 'como-se-dice-el-gasto-en-cada-pais'],
            'nota' => $nota,
        ],
        'el-salvador' => [
            'codigo' => 'SV',
            'slug' => 'el-salvador',
            'nombre' => 'El Salvador',
            'lang' => 'es-SV',
            'locale' => 'es_SV',
            'titulo' => 'Liquidación de gastos en El Salvador',
            'h1' => 'Liquidación de gastos en El Salvador',
            'kicker' => 'Control de gastos',
            'frase' => 'Liquidación de gastos y caja chica',
            'description' => 'Liquidación de gastos y caja chica en El Salvador. La cuenta pide NIT y código de generación del DTE, y separa el reembolso de la caja.',
            'lede' => 'La cuenta donde el equipo liquida el viaje, repone la caja chica y aprueba el reembolso con el DTE a la vista.',
            'usos' => [
                ['titulo' => 'Liquidación del viaje', 'detalle' => 'DTE de hotel, traslado y comidas en un informe. Quien aprueba distingue la factura del ticket.'],
                ['titulo' => 'Caja chica', 'detalle' => 'El fondo de lo menor se liquida para reponerlo. No se llama caja menor y no se mezcla con el viaje.'],
                ['titulo' => 'Reembolso', 'detalle' => 'Si el colaborador pagó de su bolsillo, se devuelve después de aprobar. La caja chica vuelve al fondo, no a una persona.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el documento', 'texto' => 'DTE factura, DTE crédito fiscal, ticket o recibo, con NIT y código de generación.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en dólares o dato fiscal vacío, antes de cerrar la liquidación.'],
                ['titulo' => 'Arma el informe', 'texto' => 'El viaje en uno. La caja chica del periodo en otro.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Ve el total y el tipo de documento. Tesorería reembolsa o repone. La cuenta no transfiere.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en El Salvador. El colaborador sube el DTE o el ticket que la política acepte. El aprobador cierra la liquidación con el código de generación a la vista. El administrador fija topes en dólares.'],
                ['p', [
                    ['t', 'La caja chica de la oficina y el reembolso del viaje no son el mismo movimiento: una repone un fondo y el otro devuelve plata personal. Si el código de generación y el NIT ya estaban, la cuenta marca el duplicado. No consultamos al Ministerio de Hacienda. El mapa de la región está en '],
                    ['a', 'blog/como-se-dice-el-gasto-en-cada-pais', 'cómo se dice el gasto en cada país'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa salvadoreña?',
                    'a' => 'Para liquidar el gasto del viaje y de la caja chica en un informe, no en el chat. La cuenta pide el DTE, avisa si está duplicado o fuera de tope, y alguien aprueba antes de reembolsar.',
                ],
                [
                    'q' => '¿Ticket y DTE valen igual?',
                    'a' => 'No para Hacienda, y a veces tampoco para tu política. El formulario guarda el tipo para que quien aprueba distinga un DTE de un ticket.',
                ],
                [
                    'q' => '¿Consultan el DTE con Hacienda?',
                    'a' => 'No. Guardamos NIT, tipo y código de generación, y la foto. El duplicado se detecta con esos datos, o con proveedor, monto y fecha cercana.',
                ],
            ],
            'guias' => ['como-se-dice-el-gasto-en-cada-pais', 'rendicion-reembolso-y-caja-menor'],
            'nota' => $nota,
        ],
        'ecuador' => [
            'codigo' => 'EC',
            'slug' => 'ecuador',
            'nombre' => 'Ecuador',
            'lang' => 'es-EC',
            'locale' => 'es_EC',
            'titulo' => 'Liquidación de gastos en Ecuador',
            'h1' => 'Liquidación de gastos en Ecuador',
            'kicker' => 'Control de gastos',
            'frase' => 'Liquidación de gastos y caja chica',
            'description' => 'Liquidación de gastos y caja chica en Ecuador. La cuenta pide RUC y clave de acceso, y deja el informe listo para aprobar el reembolso.',
            'lede' => 'La cuenta donde el equipo liquida el viaje, repone la caja chica y aprueba con la factura y la clave de acceso a la vista.',
            'usos' => [
                ['titulo' => 'Liquidación del viaje', 'detalle' => 'Facturas y notas del viaje en un informe. Quien aprueba ve el RUC y la clave de acceso, no solo el monto.'],
                ['titulo' => 'Caja chica', 'detalle' => 'El fondo de lo menor se liquida para reponerlo. En Ecuador no se llama caja menor, y no va con el viaje.'],
                ['titulo' => 'Reembolso', 'detalle' => 'Si el colaborador pagó de su bolsillo, se devuelve después de aprobar. Un anticipo se cierra en su propio informe.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el comprobante', 'texto' => 'Factura electrónica, nota de venta, liquidación de compra o recibo, con RUC y clave de acceso.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en dólares o dato fiscal vacío, antes de cerrar la liquidación.'],
                ['titulo' => 'Arma el informe', 'texto' => 'Caja chica para reponer. Viaje para reembolsar o para cerrar un anticipo.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Si falta la clave, el tipo o la foto, devuelve el informe con una nota.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en Ecuador. El colaborador sube la factura. El aprobador liquida el informe. El administrador fija topes en dólares y el tipo de documento: factura electrónica, nota de venta, liquidación de compra o recibo.'],
                ['p', [
                    ['t', 'Liquidar el viaje no es lo mismo que una liquidación de compra: la primera es el cierre interno del gasto y la segunda es un comprobante del SRI. La cuenta guarda las dos cosas con su tipo. No consultamos al SRI. El producto está en '],
                    ['a', 'rendicion-de-gastos', 'rendición de gastos'],
                    ['t', ' y la comparación regional, en '],
                    ['a', 'blog/como-se-dice-el-gasto-en-cada-pais', 'cómo se dice el gasto en cada país'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa ecuatoriana?',
                    'a' => 'Para liquidar el gasto del viaje y de la caja chica con el comprobante en la cuenta. Alguien aprueba el informe antes de reembolsar o de reponer el fondo.',
                ],
                [
                    'q' => '¿Liquidación de compra es lo mismo que liquidar el viaje?',
                    'a' => 'No. La liquidación de compra es un comprobante del SRI. Liquidar el viaje es juntar esos comprobantes y aprobarlos. El formulario guarda el tipo para no mezclarlos.',
                ],
                [
                    'q' => '¿Validan la clave de acceso con el SRI?',
                    'a' => 'No. La guardamos junto al RUC y al número. El duplicado se detecta con esos datos, o con proveedor, monto y fecha cercana.',
                ],
            ],
            'guias' => ['como-se-dice-el-gasto-en-cada-pais', 'rendicion-reembolso-y-caja-menor'],
            'nota' => $nota,
        ],
        'peru' => [
            'codigo' => 'PE',
            'slug' => 'peru',
            'nombre' => 'Perú',
            'lang' => 'es-PE',
            'locale' => 'es_PE',
            'titulo' => 'Rendición de gastos en Perú',
            'h1' => 'Rendición de gastos y caja chica en Perú',
            'kicker' => 'Control de gastos',
            'frase' => 'Rendición de gastos y caja chica',
            'description' => 'Rendición de gastos, caja chica y entregas a rendir en Perú. La cuenta pide RUC, serie y número, y separa el anticipo del reembolso.',
            'lede' => 'La cuenta donde el equipo rinde el gasto, repone la caja chica y cierra la entrega a rendir antes de reembolsar.',
            'usos' => [
                ['titulo' => 'Rendición del viaje', 'detalle' => 'Boleta de venta y factura del viaje en un informe, con RUC, serie y número. Quien aprueba ve el total sustentado.'],
                ['titulo' => 'Caja chica', 'detalle' => 'El fondo fijo se rinde para reponerlo. No es una entrega a rendir ni un reembolso, y no se llama caja menor.'],
                ['titulo' => 'Entrega a rendir', 'detalle' => 'Anticipo que la empresa entregó antes. Se sustenta y se cierra. Si no hubo anticipo y la persona pagó, el camino es el reembolso o reintegro.'],
            ],
            'pasos' => [
                ['titulo' => 'Sube el comprobante', 'texto' => 'Factura, boleta de venta, recibo por honorarios o ticket, con RUC, serie y número.'],
                ['titulo' => 'La cuenta avisa', 'texto' => 'Duplicado, tope en soles o dato fiscal vacío, antes de que el aprobador abra una pila de fotos.'],
                ['titulo' => 'Arma la rendición', 'texto' => 'Caja chica, entrega a rendir o gasto por reembolsar. Un informe para cada una.'],
                ['titulo' => 'Alguien aprueba', 'texto' => 'Después se repone la caja, se cierra el anticipo o se reembolsa. El plazo lo fija tu política. La cuenta no lo impone.'],
            ],
            'cuerpo' => [
                ['p', 'PideTuFactura es el control del gasto para una empresa en Perú. El colaborador sustenta con el comprobante. El aprobador cierra la rendición. El administrador fija topes en soles y separa la caja chica de la entrega a rendir, para que el anticipo no se confunda con el fondo fijo.'],
                ['p', [
                    ['t', 'Serie y número identifican la boleta o la factura mejor que el monto solo. Si ese comprobante y ese RUC ya estaban, o el mismo proveedor repite monto en una fecha cercana, la cuenta lo marca. No consultamos a SUNAT. La diferencia entre caja y anticipo está en '],
                    ['a', 'blog/caja-chica-en-peru', 'caja chica en Perú'],
                    ['t', '.'],
                ]],
            ],
            'faq' => [
                [
                    'q' => '¿Para qué sirve en una empresa peruana?',
                    'a' => 'Para rendir el gasto con el comprobante en la cuenta. La caja chica se repone, la entrega a rendir se cierra contra el anticipo y el reembolso devuelve lo que la persona pagó, cada uno en su informe.',
                ],
                [
                    'q' => '¿Caja chica y entrega a rendir se rinden igual?',
                    'a' => 'Las dos piden comprobante. La caja chica es un fondo que permanece y se repone. La entrega a rendir es un anticipo de un fin concreto y se cierra cuando ese fin se sustenta.',
                ],
                [
                    'q' => '¿Consultan el comprobante en SUNAT?',
                    'a' => 'No. Guardamos RUC, tipo, serie y número, y la foto. El duplicado se detecta con esos datos, o con proveedor, monto y fecha cercana.',
                ],
            ],
            'guias' => ['caja-chica-en-peru', 'como-se-dice-el-gasto-en-cada-pais'],
            'nota' => $nota,
        ],
    ];
    return $cache;
}

function web_landing($slug) {
    $todos = web_landings();
    return $todos[$slug] ?? null;
}

function web_articulos() {
    static $cache;
    if ($cache) {
        return $cache;
    }
    $nota = 'Esta guía ordena el lenguaje y el flujo del equipo. No es un consejo tributario.';
    $cache = [
        'pedir-la-factura-en-el-local' => [
            'slug' => 'pedir-la-factura-en-el-local',
            'titulo' => 'Cómo pedir los datos de facturación, país por país',
            'extracto' => 'La empresa recolecta el documento del cliente para facturarle. Se llama RUT, NIT, RFC o RUC según el país.',
            'fecha' => '2026-09-30',
            'cuerpo' => [
                ['p', 'Para facturar a un cliente hacen falta sus datos, y a veces un archivo: el RUT escaneado, la cámara de comercio, un correo que no se dicta mal. Si eso se anota en un papel, se pierde. PideTuFactura manda un onboarding o deja un QR, y la respuesta cae en una bandeja.'],
                ['h2', 'Correo o código impreso'],
                ['p', [
                    ['t', 'Si ya tienes el correo, se lo envías. Si la persona está en el mesón, imprimes el QR. Completa razón social, el documento y los adjuntos que pediste. El detalle del flujo está en '],
                    ['a', 'pide-tu-factura', 'pide tu factura'],
                    ['t', '. Quien emite el comprobante es la empresa, no el formulario.'],
                ]],
                ['h2', 'Cómo se llama el documento'],
                ['tabla', [
                    ['País', 'Lo que se escribe', 'No lo llames'],
                    ['Chile', 'RUT', 'NIT ni RUC'],
                    ['Colombia', 'NIT', 'RUT'],
                    ['México', 'RFC', 'RUT ni NIT'],
                    ['Perú', 'RUC', 'RUT'],
                    ['Ecuador', 'RUC', 'NIT'],
                    ['El Salvador', 'NIT', 'RUT'],
                ]],
                ['p', 'El mismo local no debería mostrar un formulario chileno a un cliente y uno colombiano a otro si la empresa que factura es una sola: pide el documento del país donde está constituido el local, porque es el que va en la factura electrónica de esa autoridad.'],
                ['h2', 'Después viene el expediente'],
                ['p', [
                    ['t', 'Con la factura en la mano, el colaborador la rinde, la legaliza o la comprueba, según el país de su empresa. Esa parte es otra: '],
                    ['a', 'blog/como-se-dice-el-gasto-en-cada-pais', 'cómo se dice el gasto en cada país'],
                    ['t', '.'],
                ]],
                ['nota', $nota],
            ],
        ],
        'como-se-dice-el-gasto-en-cada-pais' => [
            'slug' => 'como-se-dice-el-gasto-en-cada-pais',
            'titulo' => 'Cómo se dice el gasto de empresa en cada país',
            'extracto' => 'Rendición, legalización, comprobación, caja menor y caja chica no viajan con el mismo nombre. Tabla para equipos con sedes en Latam.',
            'fecha' => '2026-09-02',
            'cuerpo' => [
                ['p', 'Un mismo almuerzo de trabajo no se pide igual en Santiago, Bogotá, Ciudad de México, Lima, Quito y San Salvador. Cambia el comprobante y cambia el verbo: rendir, legalizar, comprobar o liquidar. Si el manual interno usa una sola palabra para todo, el aprobador del otro país duda.'],
                ['p', [
                    ['t', 'PideTuFactura pide el dato fiscal con el nombre de la autoridad del país de la empresa. Esta guía fija el vocabulario para que el equipo hable claro. El producto, visto desde el expediente, está en '],
                    ['a', 'rendicion-de-gastos', 'rendición de gastos'],
                    ['t', '.'],
                ]],
                ['h2', 'Tres ideas que no se deben mezclar'],
                ['ul', [
                    'El expediente: rendición, legalización, comprobación o liquidación. Es el paquete de comprobantes y el contexto.',
                    'La devolución: reembolso. Es el dinero que vuelve a quien pagó con su bolsillo. En Perú también se dice reintegro.',
                    'El fondo fijo: caja menor en Colombia y caja chica en Chile, México, Perú, Ecuador y El Salvador.',
                ]],
                ['p', 'Un reembolso casi siempre necesita expediente. Un expediente no siempre es un reembolso: puede cerrar una caja o un anticipo.'],
                ['h2', 'País por país'],
                ['tabla', [
                    ['País', 'Expediente', 'Fondo menor', 'Documento que manda', 'Código'],
                    ['Chile', 'Rendición de gastos', 'Caja chica', 'Boleta o factura electrónica', 'Folio DTE'],
                    ['Colombia', 'Legalización', 'Caja menor', 'Factura, cuenta de cobro o documento soporte', 'CUFE'],
                    ['México', 'Comprobación', 'Caja chica', 'CFDI', 'UUID'],
                    ['Perú', 'Rendición', 'Caja chica', 'Factura, boleta de venta o recibo por honorarios', 'Serie y número'],
                    ['Ecuador', 'Liquidación o rendición', 'Caja chica', 'Factura, nota de venta o liquidación de compra', 'Clave de acceso'],
                    ['El Salvador', 'Liquidación', 'Caja chica', 'DTE, ticket o recibo', 'Código de generación'],
                ]],
                ['p', [
                    ['t', 'Cada fila tiene su página: '],
                    ['a', 'chile', 'Chile'],
                    ['t', ', '],
                    ['a', 'colombia', 'Colombia'],
                    ['t', ', '],
                    ['a', 'mexico', 'México'],
                    ['t', ', '],
                    ['a', 'peru', 'Perú'],
                    ['t', ', '],
                    ['a', 'ecuador', 'Ecuador'],
                    ['t', ' y '],
                    ['a', 'el-salvador', 'El Salvador'],
                    ['t', '.'],
                ]],
                ['h2', 'Anticipo no es caja'],
                ['p', 'En Chile se habla de fondos por rendir. En Perú, de entregas a rendir. Es dinero que la empresa entrega antes de gastar. La caja chica o la caja menor también es dinero de la empresa, pero queda como fondo fijo para lo menor y se repone. El reembolso va al revés: primero paga la persona.'],
                ['p', 'El viático es otra pieza. Cubre alimentación, alojamiento o traslado de un viaje. Puede ser un monto diario o un gasto contra comprobante. Si tu política pide boleta o factura, ese papel entra al mismo expediente del viaje.'],
                ['h2', 'Qué hace la cuenta con eso'],
                ['p', 'Al crear la empresa eliges el país. El formulario pide RUT, NIT, RFC o RUC, y el código del comprobante con su nombre local. Los topes de categoría quedan en la moneda de ese país: peso chileno, peso colombiano, peso mexicano, dólar o sol. El informe que se envía a aprobar junta varios gastos. En la operación de Chile o Perú lo llamarás rendición; en Colombia, legalización; en México, comprobación; en Ecuador o El Salvador, liquidación.'],
                ['nota', $nota],
            ],
        ],
        'rendicion-reembolso-y-caja-menor' => [
            'slug' => 'rendicion-reembolso-y-caja-menor',
            'titulo' => 'Rendición, reembolso y caja menor no son lo mismo',
            'extracto' => 'Tres palabras que los equipos mezclan. Una documenta, otra devuelve dinero y la tercera repone un fondo fijo.',
            'fecha' => '2026-08-12',
            'cuerpo' => [
                ['p', 'En una misma semana, finanzas puede recibir la rendición de un viaje, el reembolso de un taxi y la reposición de la caja. Si las tres llegan en un solo correo llamado gastos, alguien va a pagar dos veces o a dejar la caja corta.'],
                ['h2', 'Rendición: el expediente'],
                ['p', [
                    ['t', 'La rendición dice qué se gastó, con qué comprobante y por qué. En Colombia ese paquete se legaliza. En México se comprueba. En Ecuador y El Salvador se liquida. El contenido es el mismo: documentos más contexto más un aprobador. El recorrido del producto está en '],
                    ['a', 'rendicion-de-gastos', 'rendición de gastos'],
                    ['t', '.'],
                ]],
                ['h2', 'Reembolso: la devolución'],
                ['p', [
                    ['t', 'El reembolso existe solo si el dinero salió del colaborador. Aprobar el papel no mueve la plata por sí solo: tesorería paga después. En Perú oirás reintegro. La definición larga está en '],
                    ['a', 'reembolso-de-gastos', 'reembolso de gastos'],
                    ['t', '.'],
                ]],
                ['h2', 'Caja menor: el fondo'],
                ['p', [
                    ['t', 'La caja menor colombiana, o caja chica en los otros cinco países, ya es dinero de la empresa. Rendirse sirve para reponer el fondo, no para pagarle un adelanto a una persona. Si el manual dice caja menor en una sede chilena, cámbialo: allá es caja chica. Más detalle en '],
                    ['a', 'caja-menor', 'caja menor y caja chica'],
                    ['t', '.'],
                ]],
                ['h2', 'Un ejemplo para no mezclarlas'],
                ['ul', [
                    'El fondo de la oficina paga tintas y cafés de la semana. Eso es caja. Al cierre se rinde y se repone.',
                    'La empresa le gira a una persona el estimado de un viaje antes de salir. Eso es anticipo: fondo por rendir o entrega a rendir. Al volver se liquida.',
                    'La persona paga el hotel con su tarjeta porque el anticipo no alcanzó. Eso, si se aprueba, es reembolso.',
                ]],
                ['p', 'Tres informes cortos le ganan a uno largo con las tres historias adentro. Quien aprueba entiende qué está autorizando: reponer, cerrar un anticipo o devolver plata.'],
                ['nota', $nota],
            ],
        ],
        'legalizar-gastos-en-colombia' => [
            'slug' => 'legalizar-gastos-en-colombia',
            'titulo' => 'Cómo legalizar un gasto en Colombia',
            'extracto' => 'Legalizar no es reembolsar. Es dejar el soporte listo: factura, cuenta de cobro o documento soporte, con NIT y CUFE cuando existen.',
            'fecha' => '2026-07-16',
            'cuerpo' => [
                ['p', [
                    ['t', 'En un equipo colombiano, pedir la rendición suena a manual traducido. Lo que la gente espera oír es la legalización. Esta nota recorre ese cierre sin convertirla en una clase de la DIAN. La página del país está en '],
                    ['a', 'colombia', 'legalización y caja menor'],
                    ['t', '.'],
                ]],
                ['h2', 'Qué soportes aparecen en la práctica'],
                ['ul', [
                    'Factura electrónica, con NIT del proveedor y CUFE.',
                    'Documento equivalente, cuando el gasto no viene en factura.',
                    'Cuenta de cobro, frecuente con personas que prestan un servicio sin estar obligadas a facturar electrónicamente.',
                    'Documento soporte, para compras a quien no está obligado a expedir factura, cuando la empresa lo usa.',
                ]],
                ['p', 'Cuál de esos papeles sirve para deducir no lo decide esta guía. Lo decide la norma y el contador. Lo que el equipo de gastos sí puede hacer es no mezclarlos: el tipo de documento queda escrito al cargar el gasto.'],
                ['h2', 'Caja menor y viaje, en colas distintas'],
                ['p', 'La caja menor es un fondo. Legalizarla significa mostrar en qué se fue y reponerla hasta el monto acordado. El viaje del comercial es otra cola: si él pagó, la legalización habilita el reembolso. Juntar tintas de la oficina con el hotel de Medellín en el mismo informe es la forma más rápida de perder el saldo de la caja.'],
                ['h2', 'Qué alerta sale sola'],
                ['p', 'Si el mismo NIT y el mismo número de documento ya están, el gasto queda marcado como duplicado. También si el mismo proveedor, el mismo monto y una fecha cercana se repiten. Si falta el tipo, el NIT o el número, la marca es Fiscal incompleto. El CUFE se pide en su campo para quien aprueba; la cuenta no lo verifica contra la DIAN.'],
                ['p', 'Los topes de categoría, si la empresa es de Colombia, parten en pesos colombianos. Un almuerzo por encima del tope no se esconde: queda fuera de tope para que el aprobador decida con los ojos abiertos.'],
                ['nota', $nota],
            ],
        ],
        'rendicion-de-gastos-en-chile' => [
            'slug' => 'rendicion-de-gastos-en-chile',
            'titulo' => 'Qué lleva una rendición de gastos en Chile',
            'extracto' => 'Boleta o factura, RUT del proveedor y folio DTE. Además, no llames caja menor a la caja chica ni fondo por rendir al reembolso.',
            'fecha' => '2026-06-20',
            'cuerpo' => [
                ['p', [
                    ['t', 'Una rendición chilena bien armada se lee en un minuto: para qué fue el gasto, quién emitió el documento, cuánto es y si ese folio ya pasó por la empresa. El contexto del país está en '],
                    ['a', 'chile', 'rendición de gastos en Chile'],
                    ['t', '.'],
                ]],
                ['h2', 'El comprobante'],
                ['p', 'Lo más común es la boleta o la factura electrónica. El RUT identifica al proveedor. El folio DTE identifica el documento tributario electrónico. Esos dos datos, juntos con el tipo, le alcanzan a quien aprueba para no tratar dos fotos del mismo papel como dos gastos.'],
                ['p', 'La cuenta marca duplicado cuando el número y el RUT coinciden con otro gasto, o cuando proveedor, monto y una fecha cercana se repiten. No entra al SII a confirmar el folio. Si faltan tipo, RUT o número, verás Fiscal incompleto aunque el resto del gasto esté en orden.'],
                ['h2', 'Tres bolsillos distintos'],
                ['ul', [
                    'Caja chica: plata de la empresa, fondo fijo, se rinde para reponer. No digas caja menor.',
                    'Fondo por rendir: anticipo entregado antes del gasto. Se justifica al volver.',
                    'Reembolso: la persona pagó y la empresa le devuelve, si la rendición se aprueba.',
                    'Viático: puede ser un monto por día o un gasto con boleta. La política elige. Si pide papel, el papel va en la rendición del viaje.',
                ]],
                ['h2', 'Cómo se manda'],
                ['p', 'Varios gastos de la misma salida se agrupan en un informe, con una nota de contexto. Quien aprueba ve el total en pesos, abre la foto y, si el hotel no trae folio, devuelve esa rendición con una observación en vez de rechazar a ciegas todo el mes.'],
                ['nota', $nota],
            ],
        ],
        'comprobar-gastos-en-mexico' => [
            'slug' => 'comprobar-gastos-en-mexico',
            'titulo' => 'Comprobar gastos en México con CFDI',
            'extracto' => 'En México el viático se comprueba. El CFDI aporta RFC y UUID. La caja chica y el reembolso no deberían compartir informe.',
            'fecha' => '2026-05-08',
            'cuerpo' => [
                ['p', [
                    ['t', 'Comprobar un gasto es dejar el CFDI, o el recibo que la política tolere, explicado y listo para quien aprueba. Rendición se entiende en empresas con sede regional, pero el término que no hay que traducir es comprobación. Más contexto en '],
                    ['a', 'mexico', 'México'],
                    ['t', '.'],
                ]],
                ['h2', 'Qué datos importan'],
                ['p', 'El RFC dice quién cobró. El UUID es el folio fiscal del CFDI y no debería repetirse. En el formulario de una empresa mexicana ese UUID tiene su campo, junto al tipo: CFDI de ingreso, CFDI de egreso, nota de venta o recibo. La cuenta no timbra ni consulta al SAT. Avisa si el número y el RFC ya estaban, si el proveedor repite monto en fechas cercanas, o si falta tipo, RFC o número.'],
                ['h2', 'Viáticos, caja y reembolso'],
                ['p', 'Los viáticos de un viaje se comprueban juntos: alimentos, hospedaje, traslado. La caja chica de la oficina va en otro informe, porque ese dinero se repone al fondo. El reembolso solo aparece cuando el colaborador usó su dinero. Un cargo de tarjeta de la empresa igual se comprueba: cambia el bolsillo, no la necesidad del CFDI.'],
                ['p', 'Los topes iniciales de una empresa mexicana están en pesos. Superar el de alimentación no bloquea el guardado por arte de magia contable: deja el gasto fuera de tope para que la aprobación sea consciente.'],
                ['nota', $nota],
            ],
        ],
        'caja-chica-en-peru' => [
            'slug' => 'caja-chica-en-peru',
            'titulo' => 'Caja chica y entregas a rendir en Perú',
            'extracto' => 'La caja chica se repone. La entrega a rendir es un anticipo. El reembolso, o reintegro, devuelve lo que el trabajador puso.',
            'fecha' => '2026-04-15',
            'cuerpo' => [
                ['p', [
                    ['t', 'En Perú las tres figuras conviven y el error caro es nombrarlas todas rendición y pagarlas igual. La página del país resume el lenguaje en '],
                    ['a', 'peru', 'rendición de gastos en Perú'],
                    ['t', '.'],
                ]],
                ['h2', 'Caja chica'],
                ['p', 'Es un fondo fijo para gastos menores. Sigue existiendo después de cada cierre: se rinde y se repone hasta el monto definido. No se llama caja menor. Ese nombre es de Colombia. El comprobante típico es factura electrónica, boleta de venta o, si corresponde, recibo por honorarios, con RUC y con serie y número.'],
                ['h2', 'Entrega a rendir'],
                ['p', 'Es dinero entregado antes, para un fin concreto. Cuando el fin termina, los comprobantes tienen que cuadrar con ese anticipo. Si sobra, el trabajador devuelve. Si la política acepta un faltante pagado por él, ese faltante puede volverse reembolso. El plazo para sustentar no es un número mágico nacional: en una empresa privada lo escribe la política y en el Estado lo escribe la directiva de cada entidad.'],
                ['h2', 'Reembolso'],
                ['p', 'También le dicen reintegro. Es la devolución de un gasto que el trabajador ya pagó. Necesita el mismo tipo de comprobante que cualquier rendición, porque SUNAT y el auditor interno preguntan por el documento, no por el apodo del proceso.'],
                ['p', 'Serie y número son la pareja que evita cargar dos veces la misma boleta. La cuenta usa ese número junto con el RUC para el duplicado, y también compara proveedor, monto y fechas cercanas. No consulta el comprobante en SUNAT.'],
                ['nota', $nota],
            ],
        ],
    ];
    return $cache;
}

function web_articulo($slug) {
    $todos = web_articulos();
    return $todos[$slug] ?? null;
}
