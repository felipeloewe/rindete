<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\ConexionErp;
use App\Libraries\GastoServicio;
use App\Libraries\Global66Cuenta;
use App\Libraries\MediosConexion;
use App\Libraries\Modulos;
use App\Libraries\Montos;
use App\Libraries\Paises;
use App\Libraries\ParametrosBase;
use App\Libraries\Sesion;
use App\Libraries\SistemasErp;
use App\Libraries\TarjetaIdentidad;
use App\Libraries\TrazadorMedios;
use App\Models\AceptadorModel;
use App\Models\CategoriaModel;
use App\Models\ConexionModel;
use App\Models\CuentaModel;
use App\Models\MedioModel;
use App\Models\TarjetaModel;
use App\Models\TipoDocumentoModel;
use App\Models\UsuarioModel;

class Parametros extends BaseController
{
    private const DESTINOS = [
        'asientos' => 'Preasientos',
        'pagos'    => 'Nómina de fondos',
        'ambos'    => 'Preasientos y nómina de fondos',
    ];

    private const CONCEPTOS = [
        'viatico'    => 'Viático',
        'asignacion' => 'Asignación',
        'bien'       => 'Bien',
    ];

    private const PIEZAS = [
        'cuentas' => [
            'titulo' => 'Cuentas',
            'bajada' => 'La genérica clasifica el gasto. La de remuneración se usa cuando el concepto se traspasa a la nómina: el de la persona, si existe, y si no el general.',
        ],
        'clasificacion' => [
            'titulo' => 'Clasificación',
            'bajada' => 'Al confirmar un gasto, Rindete propone el preasiento en la cuenta genérica de su categoría. Si la categoría es viático, asignación o bien, el cierre del mes lo pasa a la liquidación de sueldo.',
        ],
        'conexiones' => [
            'titulo' => 'Conexiones',
            'bajada' => 'Aquí se guarda el token de Softnet o de Odoo. Sirve para los preasientos y para crear en el banco la nómina de fondos por rendir.',
        ],
        'flujo' => [
            'titulo' => 'Flujo de aceptación',
            'bajada' => 'Quién acepta y en qué orden. La solicitud de fondos y el cierre con saldo negativo se detienen si el monto supera el máximo, si el tipo no está permitido o si la persona no está habilitada.',
        ],
        'tarjetas' => [
            'titulo' => 'Tarjetas',
            'bajada' => 'Identifica la tarjeta de quien rinde. Si es de la empresa, queda exclusiva del proceso de rendiciones.',
        ],
        'medios' => [
            'titulo' => 'Integraciones',
            'bajada' => 'Opcional. Aquí viven las credenciales de la API. Al guardar Global66, el alta de personas puede anotar el accountID de cada cuenta.',
        ],
        'aceptados' => [
            'titulo' => 'Gastos aceptados',
            'bajada' => 'Cada persona carga solo lo que marcas. Si nadie queda marcado, la restricción se apaga.',
        ],
        'tipos' => [
            'titulo' => 'Tipos de documento',
            'bajada' => 'La carga ofrece estos tipos. Un monto sobre el aceptado queda observado. Sin comprobante solo cuando el tipo lo permite y el monto cabe.',
        ],
        'preasientos' => [
            'titulo' => 'Preasientos',
            'bajada' => 'En Chile el preasiento del mes junta la salida del dinero, el paso al gasto y el traspaso de viático, asignación y bien a la liquidación de sueldo.',
        ],
        'lugares' => [
            'titulo' => 'Centros y sucursales',
            'bajada' => 'Centro de costo, sucursal y otro concepto. En un plan de pago el administrador decide si quien rinde los anota.',
        ],
        'formas' => [
            'titulo' => 'Formas de pago',
            'bajada' => 'Se mantienen una vez y se aplican al documento único y a los documentos del socio. Efectivo exige el comprobante de pago al contado.',
        ],
    ];

    public function index()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        ParametrosBase::sembrar($empresa);

        return view('parametros/index', [
            'preasientos' => Modulos::activo($empresa, 'preasientos'),
            'dePago'      => (new \App\Libraries\Lugares())->dePago($empresa),
        ]);
    }

    public function ver(string $pieza)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if (! isset(self::PIEZAS[$pieza])) {
            return redirect()->to('/parametros');
        }
        if ($pieza === 'preasientos' && ! Modulos::activo($empresa, 'preasientos')) {
            return view('plan/fuera', [
                'aviso'   => Modulos::aviso('preasientos'),
                'detalle' => Modulos::CATALOGO['preasientos']['detalle'],
                'esAdmin' => true,
            ]);
        }
        ParametrosBase::sembrar($empresa);

        return view('parametros/seccion', [
            'pieza'  => $pieza,
            'titulo' => self::PIEZAS[$pieza]['titulo'],
            'bajada' => self::PIEZAS[$pieza]['bajada'],
        ] + $this->contexto($empresa));
    }

    /**
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>
     */
    private function contexto(array $empresa): array
    {
        $empresaId  = (int) $empresa['id'];
        $usuarios   = model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll();
        usort($usuarios, static fn (array $a, array $b): int => strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? '')));
        $categorias = model(CategoriaModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll();
        $cuentas    = model(CuentaModel::class)->where('empresa_id', $empresaId)->orderBy('codigo', 'ASC')->findAll();
        $persona    = (int) ($this->request->getGet('persona') ?? 0);
        $ids        = array_map(static fn (array $fila): int => (int) $fila['id'], $usuarios);
        if (! in_array($persona, $ids, true)) {
            $persona = $ids[0] ?? 0;
        }
        $db = db_connect();
        $aceptados = [];
        if ($persona > 0) {
            foreach ($db->table('gastos_aceptados')->where('usuario_id', $persona)->get()->getResultArray() as $fila) {
                $aceptados[] = (int) $fila['categoria_id'];
            }
        }
        $enlaces = [];
        $idsCategoria = array_map(static fn (array $fila): int => (int) $fila['id'], $categorias);
        if ($idsCategoria !== []) {
            foreach ($db->table('categoria_cuentas')->whereIn('categoria_id', $idsCategoria)->get()->getResultArray() as $fila) {
                $enlaces[(int) $fila['categoria_id']] = (int) $fila['cuenta_id'];
            }
        }
        $tarjetas = [];
        foreach (model(TarjetaModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll() as $tarjeta) {
            $tarjeta['personas'] = array_map(
                static fn (array $fila): int => (int) $fila['usuario_id'],
                $db->table('tarjeta_usuarios')->where('tarjeta_id', $tarjeta['id'])->get()->getResultArray(),
            );
            $tarjeta['gastos'] = array_map(
                static fn (array $fila): int => (int) $fila['categoria_id'],
                $db->table('tarjeta_categorias')->where('tarjeta_id', $tarjeta['id'])->get()->getResultArray(),
            );
            $tarjetas[] = $tarjeta;
        }
        $empresa = model(\App\Models\EmpresaModel::class)->find($empresaId);

        return [
            'cuentas'     => $cuentas,
            'enlaces'     => $enlaces,
            'conexiones'  => model(ConexionModel::class)->where('empresa_id', $empresaId)->orderBy('nombre', 'ASC')->findAll(),
            'aceptadores' => model(AceptadorModel::class)->where('empresa_id', $empresaId)->orderBy('orden', 'ASC')->findAll(),
            'tarjetas'    => $tarjetas,
            'tipos'       => ParametrosBase::tipos($empresaId),
            'usuarios'    => $usuarios,
            'categorias'  => $categorias,
            'aceptados'   => $aceptados,
            'persona'     => $persona,
            'empresa'     => $empresa,
            'destinos'    => self::DESTINOS,
            'sistemas'    => SistemasErp::CATALOGO,
            'conceptos'   => self::CONCEPTOS,
            'moneda'      => Paises::pais((string) ($empresa['pais'] ?? ''))['moneda'] ?? 'USD',
            'nombres'     => $this->nombres($usuarios, $categorias, $cuentas),
            'formas'      => (new \App\Libraries\FormasPago())->lista($empresaId),
            'medios'      => model(MedioModel::class)->where('empresa_id', $empresaId)->orderBy('proveedor', 'ASC')->findAll(),
            'emisores'    => TarjetaIdentidad::EMISORES,
            'proveedores' => MediosConexion::PROVEEDORES,
            'lugares'     => [
                'centro'   => (new \App\Libraries\Lugares())->lista($empresaId, 'centro'),
                'sucursal' => (new \App\Libraries\Lugares())->lista($empresaId, 'sucursal'),
                'concepto' => (new \App\Libraries\Lugares())->lista($empresaId, 'concepto'),
            ],
            'dePago'      => (new \App\Libraries\Lugares())->dePago($empresa),
            'lugaresUso'  => [
                'centro'   => (new \App\Libraries\Lugares())->ofrece($empresa, 'centro'),
                'sucursal' => (new \App\Libraries\Lugares())->ofrece($empresa, 'sucursal'),
                'concepto' => (new \App\Libraries\Lugares())->ofrece($empresa, 'concepto'),
            ],
        ];
    }

    public function cuenta()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $clase    = (string) $this->request->getPost('clase');
        $codigo   = mb_substr(trim((string) $this->request->getPost('codigo')), 0, 32);
        $nombre   = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120);
        $concepto = (string) $this->request->getPost('concepto');
        $dueno    = (int) $this->request->getPost('usuario_id');
        if (! in_array($clase, ['generica', 'remuneracion'], true) || $codigo === '' || $nombre === '') {
            return redirect()->to('/parametros/cuentas')->with('error', 'Escribe el código y el nombre de la cuenta.');
        }
        if ($clase === 'generica') {
            $concepto = '';
            $dueno    = 0;
        } elseif (! isset(self::CONCEPTOS[$concepto])) {
            return redirect()->to('/parametros/cuentas')->with('error', 'Elige viático, asignación o bien.');
        }
        if ($dueno > 0 && $this->miembro($dueno, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/cuentas')->with('error', 'Esa persona no es de la empresa.');
        }
        $consulta = model(CuentaModel::class)->where('empresa_id', $empresa['id'])->where('clase', $clase)->where('codigo', $codigo);
        $consulta = $dueno > 0 ? $consulta->where('usuario_id', $dueno) : $consulta->where('usuario_id', null);
        if ($consulta->countAllResults() > 0) {
            return redirect()->to('/parametros/cuentas')->with('error', 'Esa cuenta ya está cargada.');
        }
        model(CuentaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'clase'      => $clase,
            'codigo'     => $codigo,
            'nombre'     => $nombre,
            'concepto'   => $concepto,
            'usuario_id' => $dueno > 0 ? $dueno : null,
            'activa'     => 1,
        ]);

        return redirect()->to('/parametros/cuentas')->with('ok', 'Cuenta guardada.');
    }

    public function eliminarCuenta(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $cuenta = $this->fila(CuentaModel::class, $id, (int) $empresa['id']);
        if ($cuenta === null) {
            return redirect()->to('/parametros/cuentas')->with('error', 'No encontramos esa cuenta.');
        }
        $db   = db_connect();
        $usada = $db->table('categoria_cuentas')->where('cuenta_id', $id)->countAllResults() > 0
            || $db->table('pre_asientos')->where('cuenta_id', $id)->countAllResults() > 0
            || (int) ($empresa['preasiento_cuenta_id'] ?? 0) === $id
            || (int) ($empresa['preasiento_salida_id'] ?? 0) === $id
            || $db->table('preasiento_lineas')->where('cuenta_id', $id)->countAllResults() > 0;
        if ($usada) {
            return redirect()->to('/parametros/cuentas')->with('error', 'Esa cuenta ya clasifica gastos. Quítala de la clasificación primero.');
        }
        model(CuentaModel::class)->delete($id);

        return redirect()->to('/parametros/cuentas')->with('ok', 'Cuenta eliminada.');
    }

    public function preasiento()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return redirect()->to('/modulos')->with('error', Modulos::aviso('preasientos'));
        }
        $agrupacion = (string) $this->request->getPost('agrupacion') === 'usuario' ? 'usuario' : 'mes';
        $cuentaId = (int) $this->request->getPost('cuenta_id');
        $salidaId = (int) $this->request->getPost('salida_id');
        $liquidacionId = (int) $this->request->getPost('liquidacion_id');
        if ($cuentaId > 0 && $this->fila(CuentaModel::class, $cuentaId, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/preasientos')->with('error', 'Elige una cuenta de la empresa.');
        }
        if ($salidaId > 0 && $this->fila(CuentaModel::class, $salidaId, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/preasientos')->with('error', 'Elige una cuenta de la empresa.');
        }
        if ($liquidacionId > 0 && $this->fila(CuentaModel::class, $liquidacionId, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/preasientos')->with('error', 'Elige una cuenta de la empresa.');
        }
        if ($cuentaId > 0 && $cuentaId === $salidaId) {
            return redirect()->to('/parametros/preasientos')->with('error', 'Elige otra cuenta para la salida. El fondo por rendir ya está en la contrapartida.');
        }
        if ($liquidacionId > 0 && ($liquidacionId === $cuentaId || $liquidacionId === $salidaId)) {
            return redirect()->to('/parametros/preasientos')->with('error', 'La liquidación de sueldo es otra cuenta.');
        }
        model(\App\Models\EmpresaModel::class)->update($empresa['id'], [
            'preasiento_agrupacion'     => $agrupacion,
            'preasiento_auxiliar'       => $this->request->getPost('auxiliar') ? 1 : 0,
            'preasiento_cuenta_id'      => $cuentaId > 0 ? $cuentaId : null,
            'preasiento_salida_id'      => $salidaId > 0 ? $salidaId : null,
            'preasiento_liquidacion_id' => $liquidacionId > 0 ? $liquidacionId : null,
        ]);

        return redirect()->to('/parametros/preasientos')->with('ok', 'Preasiento guardado.');
    }

    public function clasificacion()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $categoria = $this->fila(CategoriaModel::class, (int) $this->request->getPost('categoria_id'), (int) $empresa['id']);
        $cuentaId  = (int) $this->request->getPost('cuenta_id');
        $cuenta    = $cuentaId > 0 ? $this->fila(CuentaModel::class, $cuentaId, (int) $empresa['id']) : null;
        $concepto = (string) $this->request->getPost('concepto');
        if (! in_array($concepto, ['', 'viatico', 'asignacion', 'bien'], true)) {
            return redirect()->to('/parametros/clasificacion')->with('error', 'Elige viático, asignación o bien.');
        }
        if ($categoria === null || ($cuentaId > 0 && ($cuenta === null || $cuenta['clase'] !== 'generica'))) {
            return redirect()->to('/parametros/clasificacion')->with('error', 'Elige una categoría y una cuenta genérica.');
        }
        model(CategoriaModel::class)->update($categoria['id'], ['concepto' => $concepto]);
        $db = db_connect();
        $db->table('categoria_cuentas')->where('categoria_id', $categoria['id'])->delete();
        if ($cuentaId > 0) {
            $db->table('categoria_cuentas')->insert([
                'categoria_id' => $categoria['id'],
                'cuenta_id'    => $cuentaId,
            ]);
        }
        (new GastoServicio())->reevaluar((int) $empresa['id']);

        return redirect()->to('/parametros/clasificacion')->with('ok', 'Clasificación guardada. Los gastos legalizados ya tienen su preasiento.');
    }

    public function forma()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $creada = (new \App\Libraries\FormasPago())->crear(
            (int) $empresa['id'],
            (string) $this->request->getPost('nombre'),
            (bool) $this->request->getPost('efectivo'),
        );
        if (! $creada['ok']) {
            return redirect()->to('/parametros/formas')->with('error', $creada['errores'][0]);
        }

        return redirect()->to('/parametros/formas')->with('ok', 'Forma de pago lista para el documento y para el socio.');
    }

    public function lugar()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $lugares = new \App\Libraries\Lugares();
        $tipo = (string) $this->request->getPost('tipo');
        if (! $lugares->ofrece($empresa, $tipo)) {
            return redirect()->to('/parametros/lugares')->with('error', 'Habilita ese dato para la empresa antes de agregarlo.');
        }
        $creado = $lugares->crear(
            (int) $empresa['id'],
            $tipo,
            (string) $this->request->getPost('nombre'),
        );
        if (! $creado['ok']) {
            return redirect()->to('/parametros/lugares')->with('error', $creado['errores'][0]);
        }

        return redirect()->to('/parametros/lugares')->with('ok', 'Quedó disponible para la carga.');
    }

    public function lugarUso()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $lugares = new \App\Libraries\Lugares();
        if (! $lugares->dePago($empresa)) {
            return redirect()->to('/parametros/lugares')->with('error', 'Centro de costo, sucursal y otro concepto están en los planes de pago.');
        }
        $tipo = (string) $this->request->getPost('tipo');
        if (! isset(\App\Libraries\Lugares::USO[$tipo])) {
            return redirect()->to('/parametros/lugares')->with('error', 'Ese dato no existe.');
        }
        $prendido = (string) $this->request->getPost('habilitado') === '1';
        $lugares->marcar((int) $empresa['id'], $tipo, $prendido);

        return redirect()->to('/parametros/lugares')->with(
            'ok',
            $prendido ? 'Quien rinde ya puede anotarlo en el gasto.' : 'Quien rinde ya no lo ve en el gasto.',
        );
    }

    public function quitarLugar()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        (new \App\Libraries\Lugares())->quitar(
            (int) $empresa['id'],
            (string) $this->request->getPost('tipo'),
            (int) $this->request->getPost('id'),
        );

        return redirect()->to('/parametros/lugares')->with('ok', 'Se quitó de la carga.');
    }

    public function conexion()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $creada = (new ConexionErp())->crear(
            (int) $empresa['id'],
            mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120),
            (string) $this->request->getPost('sistema'),
            (string) $this->request->getPost('destino'),
            (bool) $this->request->getPost('vigente'),
            (string) $this->request->getPost('url'),
            (string) $this->request->getPost('token_api'),
        );
        if (! $creada['ok']) {
            return redirect()->to('/parametros/conexiones')->with('error', $creada['errores'][0]);
        }

        return redirect()->to('/parametros/conexiones')
            ->with('ok', 'Conexión guardada en Parámetros. El token de la API queda aquí y no se vuelve a mostrar.')
            ->with('token', $creada['token']);
    }

    public function eliminarConexion(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if ($this->fila(ConexionModel::class, $id, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/conexiones')->with('error', 'No encontramos esa conexión.');
        }
        model(ConexionModel::class)->delete($id);

        return redirect()->to('/parametros/conexiones')->with('ok', 'Conexión eliminada.');
    }

    public function flujo()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $monto = Montos::parse((string) ($this->request->getPost('monto_maximo_rendicion') ?? '0'));
        if ($monto === null) {
            return redirect()->to('/parametros/flujo')->with('error', 'El monto máximo no es válido. Usa 0 si no hay máximo.');
        }
        model(\App\Models\EmpresaModel::class)->update($empresa['id'], [
            'monto_maximo_rendicion' => $monto,
            'detener_monto'          => $this->request->getPost('detener_monto') ? 1 : 0,
            'detener_tipo'           => $this->request->getPost('detener_tipo') ? 1 : 0,
            'detener_habilitacion'   => $this->request->getPost('detener_habilitacion') ? 1 : 0,
        ]);

        return redirect()->to('/parametros/flujo')->with('ok', 'Reglas de aceptación guardadas.');
    }

    public function aceptador()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $miembro = $this->miembro((int) $this->request->getPost('usuario_id'), (int) $empresa['id']);
        $orden   = (int) $this->request->getPost('orden');
        if ($miembro === null || $orden < 1 || $orden > 20) {
            return redirect()->to('/parametros/flujo')->with('error', 'Elige a la persona y un orden entre 1 y 20.');
        }
        $existe = model(AceptadorModel::class)->where('empresa_id', $empresa['id'])->where('usuario_id', $miembro['id'])->first();
        if (is_array($existe)) {
            model(AceptadorModel::class)->update($existe['id'], ['orden' => $orden]);
        } else {
            model(AceptadorModel::class)->insert([
                'empresa_id' => $empresa['id'],
                'usuario_id' => $miembro['id'],
                'orden'      => $orden,
            ]);
        }

        return redirect()->to('/parametros/flujo')->with('ok', 'Orden de aceptación guardado.');
    }

    public function eliminarAceptador(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if ($this->fila(AceptadorModel::class, $id, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/flujo')->with('error', 'No encontramos ese paso.');
        }
        model(AceptadorModel::class)->delete($id);

        return redirect()->to('/parametros/flujo')->with('ok', 'Paso quitado.');
    }

    public function tarjeta()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 120);
        $monto  = Montos::parse((string) ($this->request->getPost('monto_aceptado') ?? '0'));
        $personas = $this->deLaEmpresa($this->ids('usuario_id'), (int) $empresa['id'], UsuarioModel::class);
        $gastos   = $this->deLaEmpresa($this->ids('categoria_id'), (int) $empresa['id'], CategoriaModel::class);
        if ($nombre === '' || $monto === null || $personas === []) {
            return redirect()->to('/parametros/tarjetas')->with('error', 'Escribe la tarjeta, el monto y al menos una persona con acceso.');
        }
        $resultado = (new TarjetaIdentidad())->guardar(
            (int) $empresa['id'],
            $nombre,
            $monto,
            (bool) $this->request->getPost('personal'),
            $personas,
            $gastos,
            (string) $this->request->getPost('ultimos4'),
            (string) $this->request->getPost('emisor'),
            (string) $this->request->getPost('referencia'),
            (string) $this->request->getPost('billetera'),
            (int) $this->request->getPost('titular_id'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/parametros/tarjetas')->with('error', $resultado['errores'][0]);
        }

        return redirect()->to('/parametros/tarjetas')->with('ok', 'Tarjeta guardada.');
    }

    public function sincronizarTarjeta(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $tarjeta = $this->fila(TarjetaModel::class, $id, (int) $empresa['id']);
        if ($tarjeta === null || (string) $tarjeta['emisor'] !== 'global66' || (string) $tarjeta['billetera'] === '') {
            return redirect()->to('/parametros/tarjetas')->with('error', 'Esa tarjeta no tiene una billetera Global66.');
        }
        $medio = (new MediosConexion())->de((int) $empresa['id'], 'global66');
        if ($medio === null) {
            return redirect()->to('/parametros/tarjetas')->with('error', 'Primero guarda la conexión de Global66, con la clave del webhook.');
        }
        $token = (new MediosConexion())->tokenConsulta($medio);
        if ($token === '') {
            return redirect()->to('/parametros/tarjetas')->with('error', 'Pega el token de consulta de Global66 para traer los movimientos de la cuenta.');
        }
        $consulta = (new Global66Cuenta())->consultar($token, (string) $tarjeta['billetera']);
        if (! $consulta['ok']) {
            return redirect()->to('/parametros/tarjetas')->with('error', $consulta['errores'][0]);
        }
        $nuevos = (new TrazadorMedios())->incorporar((int) $empresa['id'], $id, $consulta['movements']);
        $texto = $nuevos === 1
            ? 'Quedó 1 movimiento de la cuenta.'
            : ($nuevos === 0 ? 'La cuenta no tenía movimientos nuevos.' : 'Quedaron ' . $nuevos . ' movimientos de la cuenta.');

        return redirect()->to('/parametros/tarjetas')->with('ok', $texto);
    }

    public function medio()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $proveedor = (string) $this->request->getPost('proveedor');
        $resultado = (new MediosConexion())->guardar(
            (int) $empresa['id'],
            $proveedor,
            (string) $this->request->getPost('secreto'),
            (string) $this->request->getPost('token_consulta'),
            $this->request->getPost('client_id'),
            $this->request->getPost('client_secret'),
            $this->request->getPost('credencial'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/parametros/medios')->with('error', $resultado['errores'][0]);
        }
        $nombre = MediosConexion::PROVEEDORES[$proveedor] ?? 'El medio';

        return redirect()->to('/parametros/medios')->with('ok', 'Conexión de ' . $nombre . ' guardada.');
    }

    public function probarMedio(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $medio = $this->fila(MedioModel::class, $id, (int) $empresa['id']);
        if ($medio === null || (string) $medio['proveedor'] !== 'global66') {
            return redirect()->to('/parametros/medios')->with('error', 'Esa conexión no es de Global66.');
        }
        $conexion = new MediosConexion();
        $prueba = (new Global66Cuenta())->probar((string) ($medio['client_id'] ?? ''), $conexion->clientSecret($medio));
        if (! $prueba['ok']) {
            return redirect()->to('/parametros/medios')->with('error', $prueba['errores'][0]);
        }
        $conexion->guardarConsulta((int) $medio['id'], $prueba['token']);
        $partes = [];
        foreach ($prueba['cuentas'] as $cuenta) {
            $partes[] = trim($cuenta['currency'] . ' ' . $cuenta['walletId']);
        }
        $texto = $partes === []
            ? 'Global66 aceptó la credencial y no tiene cuentas activas.'
            : 'Global66 aceptó la credencial. Cuentas: ' . implode(', ', array_slice($partes, 0, 6)) . '.';

        return redirect()->to('/parametros/medios')->with('ok', $texto);
    }

    public function eliminarMedio(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if ($this->fila(MedioModel::class, $id, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/medios')->with('error', 'No encontramos esa conexión.');
        }
        model(MedioModel::class)->delete($id);

        return redirect()->to('/parametros/medios')->with('ok', 'Conexión quitada.');
    }

    public function eliminarTarjeta(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        if ($this->fila(TarjetaModel::class, $id, (int) $empresa['id']) === null) {
            return redirect()->to('/parametros/tarjetas')->with('error', 'No encontramos esa tarjeta.');
        }
        $db = db_connect();
        $db->table('tarjeta_usuarios')->where('tarjeta_id', $id)->delete();
        $db->table('tarjeta_categorias')->where('tarjeta_id', $id)->delete();
        model(TarjetaModel::class)->delete($id);

        return redirect()->to('/parametros/tarjetas')->with('ok', 'Tarjeta eliminada.');
    }

    public function aceptados()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $miembro = $this->miembro((int) $this->request->getPost('usuario_id'), (int) $empresa['id']);
        if ($miembro === null) {
            return redirect()->to('/parametros/aceptados')->with('error', 'Elige a la persona.');
        }
        $categorias = $this->deLaEmpresa($this->ids('categoria_id'), (int) $empresa['id'], CategoriaModel::class);
        $propias    = array_map(
            static fn (array $fila): int => (int) $fila['id'],
            model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->findAll(),
        );
        $db = db_connect();
        if ($propias !== []) {
            $db->table('gastos_aceptados')->where('usuario_id', $miembro['id'])->whereIn('categoria_id', $propias)->delete();
        }
        foreach ($categorias as $categoriaId) {
            $db->table('gastos_aceptados')->insert([
                'usuario_id'   => $miembro['id'],
                'categoria_id' => $categoriaId,
            ]);
        }
        (new GastoServicio())->reevaluar((int) $empresa['id']);

        return redirect()->to('/parametros/aceptados?persona=' . $miembro['id'])->with('ok', 'Gastos aceptados guardados.');
    }

    public function tipo()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $datos = $this->datosTipo();
        if ($datos === null) {
            return redirect()->to('/parametros/tipos')->with('error', 'Escribe el tipo y un monto válido. Usa 0 si no hay tope.');
        }
        if (ParametrosBase::tipo((int) $empresa['id'], $datos['nombre']) !== null) {
            return redirect()->to('/parametros/tipos')->with('error', 'Ese tipo ya existe.');
        }
        model(TipoDocumentoModel::class)->insert([
            'empresa_id'      => $empresa['id'],
            'nombre'          => $datos['nombre'],
            'monto_aceptado'  => $datos['monto'],
            'sin_comprobante' => $datos['sin'],
            'activo'          => 1,
        ]);

        return redirect()->to('/parametros/tipos')->with('ok', 'Tipo de documento guardado.');
    }

    public function actualizarTipo(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera($empresa);
        }
        $tipo  = $this->fila(TipoDocumentoModel::class, $id, (int) $empresa['id']);
        $datos = $this->datosTipo();
        if ($tipo === null || $datos === null) {
            return redirect()->to('/parametros/tipos')->with('error', 'Escribe el tipo y un monto válido. Usa 0 si no hay tope.');
        }
        $otro = ParametrosBase::tipo((int) $empresa['id'], $datos['nombre']);
        if (is_array($otro) && (int) $otro['id'] !== $id) {
            return redirect()->to('/parametros/tipos')->with('error', 'Ese tipo ya existe.');
        }
        model(TipoDocumentoModel::class)->update($id, [
            'nombre'          => $datos['nombre'],
            'monto_aceptado'  => $datos['monto'],
            'sin_comprobante' => $datos['sin'],
            'activo'          => $this->request->getPost('activo') ? 1 : 0,
        ]);
        (new GastoServicio())->reevaluar((int) $empresa['id']);

        return redirect()->to('/parametros/tipos')->with('ok', 'Tipo actualizado. Volvimos a leer los gastos.');
    }

    /**
     * @param array<string, mixed>|null $empresa
     */
    private function fuera(?array $empresa)
    {
        return $empresa === null
            ? redirect()->to('/login')
            : redirect()->to('/panel')->with('error', 'Solo el administrador deja vigentes las reglas.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function admin(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        if ($usuario === null || $empresa === null) {
            return [null, null];
        }
        if ($usuario['rol'] !== 'administrador') {
            return [null, $empresa];
        }

        return [$usuario, $empresa];
    }

    /**
     * @param class-string $modelo
     *
     * @return array<string, mixed>|null
     */
    private function fila(string $modelo, int $id, int $empresaId): ?array
    {
        $fila = model($modelo)->find($id);
        if (! is_array($fila) || (int) ($fila['empresa_id'] ?? 0) !== $empresaId) {
            return null;
        }

        return $fila;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function miembro(int $id, int $empresaId): ?array
    {
        if ($id < 1) {
            return null;
        }
        $miembro = model(UsuarioModel::class)->where('empresa_id', $empresaId)->where('activo', 1)->find($id);

        return is_array($miembro) ? $miembro : null;
    }

    /**
     * @return list<int>
     */
    private function ids(string $campo): array
    {
        $valor = $this->request->getPost($campo);
        if (! is_array($valor)) {
            return [];
        }
        $ids = [];
        foreach ($valor as $id) {
            $numero = (int) $id;
            if ($numero > 0) {
                $ids[$numero] = $numero;
            }
        }

        return array_values($ids);
    }

    /**
     * @param list<int>   $ids
     * @param class-string $modelo
     *
     * @return list<int>
     */
    private function deLaEmpresa(array $ids, int $empresaId, string $modelo): array
    {
        $validos = [];
        foreach ($ids as $id) {
            if ($this->fila($modelo, $id, $empresaId) !== null) {
                $validos[] = $id;
            }
        }

        return $validos;
    }

    /**
     * @return array{nombre: string, monto: float, sin: int}|null
     */
    private function datosTipo(): ?array
    {
        $nombre = mb_substr(trim((string) $this->request->getPost('nombre')), 0, 80);
        $monto  = Montos::parse((string) ($this->request->getPost('monto_aceptado') ?? '0'));
        if ($nombre === '' || $monto === null) {
            return null;
        }

        return [
            'nombre' => $nombre,
            'monto'  => $monto,
            'sin'    => $this->request->getPost('sin_comprobante') ? 1 : 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $usuarios
     * @param list<array<string, mixed>> $categorias
     * @param list<array<string, mixed>> $cuentas
     *
     * @return array{usuarios: array<int, string>, categorias: array<int, string>, cuentas: array<int, string>}
     */
    private function nombres(array $usuarios, array $categorias, array $cuentas): array
    {
        $mapa = ['usuarios' => [], 'categorias' => [], 'cuentas' => []];
        foreach ($usuarios as $fila) {
            $mapa['usuarios'][(int) $fila['id']] = (string) $fila['nombre'];
        }
        foreach ($categorias as $fila) {
            $mapa['categorias'][(int) $fila['id']] = (string) $fila['nombre'];
        }
        foreach ($cuentas as $fila) {
            $mapa['cuentas'][(int) $fila['id']] = (string) $fila['codigo'] . ' ' . $fila['nombre'];
        }

        return $mapa;
    }
}
