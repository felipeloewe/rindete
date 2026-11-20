<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\ConexionErp;
use App\Libraries\InformeServicio;
use App\Libraries\Modulos;
use App\Libraries\Paises;
use App\Libraries\PreAsientoChile;
use App\Libraries\Sesion;
use App\Models\CuentaModel;
use App\Models\UsuarioModel;

class Informes extends BaseController
{
    private const PIEZAS = [
        'rendiciones' => [
            'titulo' => 'Rendiciones del período',
            'bajada' => 'Las rendiciones abiertas en el mes, agrupadas por persona.',
        ],
        'cartola' => [
            'titulo' => 'Cartola',
            'bajada' => 'Fondos entregados, lo rendido y el saldo. El saldo del período es lo entregado menos lo rendido.',
        ],
        'gastos' => [
            'titulo' => 'Gastos y cuentas',
            'bajada' => 'Gastos rendidos del período, por persona y por la cuenta del preasiento.',
        ],
        'asientos' => [
            'titulo' => 'Preasientos',
            'bajada' => 'Cada movimiento del período, del fondo o del gasto, antes de pasarlo al ERP.',
        ],
        'tipos' => [
            'titulo' => 'Tipo de gasto',
            'bajada' => 'Nivel de gasto de la compañía en el período, por tipo.',
        ],
        'cuentas' => [
            'titulo' => 'Cuentas',
            'bajada' => 'Movimientos de cada cuenta: el fondo nace cuando sale el dinero y pasa al gasto cuando se rinde.',
        ],
    ];

    /** @var array<string, string> */
    private const DE_PAGO = [
        'asientos' => 'preasientos',
        'cuentas'  => 'cuentas',
    ];

    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function index()
    {
        [$usuario, $empresa, $filtro] = $this->contexto();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        return view('informes/index', $this->comun($usuario, $empresa, $filtro) + [
            'piezas' => self::PIEZAS,
            'accion' => base_url('informes'),
        ]);
    }

    public function ver(string $pieza)
    {
        [$usuario, $empresa, $filtro] = $this->contexto();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        if (! isset(self::PIEZAS[$pieza])) {
            return redirect()->to('/informes');
        }
        if (isset(self::DE_PAGO[$pieza]) && ! Modulos::activo($empresa, self::DE_PAGO[$pieza])) {
            return view('plan/fuera', [
                'aviso'   => Modulos::aviso(self::DE_PAGO[$pieza]),
                'detalle' => Modulos::CATALOGO[self::DE_PAGO[$pieza]]['detalle'],
                'esAdmin' => $filtro['esAdmin'],
            ]);
        }
        if ($pieza === 'cuentas' && ! $filtro['esAdmin']) {
            return redirect()->to('/panel')->with('error', 'Solo el administrador ve el seguimiento de cuentas.');
        }
        $servicio = new InformeServicio();
        $persona = $filtro['persona'];
        $datos = match ($pieza) {
            'rendiciones' => $servicio->rendiciones((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'cartola'     => $servicio->cartola((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'gastos'      => $servicio->gastos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'asientos'    => $servicio->asientos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'tipos'       => $servicio->tipos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'cuentas'     => (new PreAsientoChile())->mayor((int) $empresa['id'], $filtro['anio'], $filtro['mes']) + [
                'puedeArmar' => Modulos::activo($empresa, 'preasientos'),
            ],
        };

        return view('informes/' . $pieza, $this->comun($usuario, $empresa, $filtro) + $datos + [
            'pieza'  => self::PIEZAS[$pieza],
            'accion' => base_url('informes/' . $pieza),
        ]);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     * @param array{anio: int, mes: int, persona: int|null, esAdmin: bool} $filtro
     *
     * @return array<string, mixed>
     */
    private function comun(array $usuario, array $empresa, array $filtro): array
    {
        $personas = model(UsuarioModel::class)->where('empresa_id', $empresa['id'])->orderBy('nombre', 'ASC')->findAll();
        $consulta = 'anio=' . $filtro['anio'] . '&mes=' . $filtro['mes'] . '&persona=' . (int) ($filtro['persona'] ?? 0);

        return [
            'anio'     => $filtro['anio'],
            'mes'      => $filtro['mes'],
            'persona'  => (int) ($filtro['persona'] ?? 0),
            'esAdmin'  => $filtro['esAdmin'],
            'personas' => $personas,
            'meses'    => self::MESES,
            'anios'    => range((int) date('Y') + 1, (int) date('Y') - 4),
            'moneda'   => Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP',
            'consulta' => $consulta,
            'periodo'  => $filtro['mes'] >= 1 && $filtro['mes'] <= 12
                ? self::MESES[$filtro['mes']] . ' ' . $filtro['anio']
                : 'Año ' . $filtro['anio'],
            'incluidos' => [
                'preasientos' => Modulos::activo($empresa, 'preasientos'),
                'cuentas'     => Modulos::activo($empresa, 'cuentas'),
            ],
        ];
    }

    public function preasientos()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(false);
        if ($usuario === null || $empresa === null) {
            return $this->fueraPreasiento($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return $this->modulo('preasientos');
        }

        return view('informes/preasientos', $this->datosPreasiento($empresa, $anio, $mes));
    }

    public function armarPreasientos()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(true);
        if ($usuario === null || $empresa === null) {
            return $this->fueraPreasiento($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return redirect()->to('/modulos')->with('error', Modulos::aviso('preasientos'));
        }
        $resultado = (new PreAsientoChile())->armar($empresa, $anio, $mes);
        $vuelta = '/informes/preasientos?anio=' . $anio . '&mes=' . $mes;
        if (! $resultado['ok']) {
            return redirect()->to($vuelta)->with('errores', $resultado['errores']);
        }

        return redirect()->to($vuelta)->with('ok', 'Preasientos armados.')->with('avisos', $resultado['avisos']);
    }

    public function cerrarPreasientos()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(true);
        if ($usuario === null || $empresa === null) {
            return $this->fueraPreasiento($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return redirect()->to('/modulos')->with('error', Modulos::aviso('preasientos'));
        }
        $resultado = (new PreAsientoChile())->cerrar((int) $empresa['id'], $anio, $mes);
        $vuelta = '/informes/preasientos?anio=' . $anio . '&mes=' . $mes;
        if (! $resultado['ok']) {
            return redirect()->to($vuelta)->with('error', $resultado['errores'][0]);
        }

        return redirect()->to($vuelta)->with('ok', 'Mes cerrado. El ERP ya puede tomar estos preasientos.');
    }

    public function contabilizar()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(true);
        if ($usuario === null || $empresa === null) {
            return $this->fueraPreasiento($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return redirect()->to('/modulos')->with('error', Modulos::aviso('preasientos'));
        }
        $ids = $this->request->getPost('ids');
        if (! is_array($ids)) {
            $ids = $ids === null || $ids === '' ? [] : [$ids];
        }
        $resultado = (new PreAsientoChile())->contabilizar((int) $empresa['id'], $ids);
        $vuelta = '/informes/preasientos?anio=' . $anio . '&mes=' . $mes;
        if (! $resultado['ok']) {
            return redirect()->to($vuelta)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/informes/contabilizados?anio=' . $anio . '&mes=' . $mes)
            ->with('ok', 'Quedó contabilizado. Es el cierre en el ERP externo.');
    }

    public function contabilizados()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(false);
        if ($usuario === null || $empresa === null) {
            return $this->fueraPreasiento($empresa);
        }
        if (! Modulos::activo($empresa, 'preasientos')) {
            return $this->modulo('preasientos');
        }
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }
        $movimientos = (new PreAsientoChile())->contabilizados((int) $empresa['id'], $anio, $mes);

        return view('informes/contabilizados', [
            'anio'         => $anio,
            'mes'          => $mes,
            'meses'        => self::MESES,
            'anios'        => range((int) date('Y') + 1, (int) date('Y') - 4),
            'movimientos'  => $movimientos,
            'manual'       => ! (new ConexionErp())->hayAsientos((int) $empresa['id']),
            'moneda'       => Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP',
        ]);
    }

    /**
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>
     */
    private function datosPreasiento(array $empresa, int $anio, int $mes): array
    {
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }
        $cuentaId = (int) ($empresa['preasiento_cuenta_id'] ?? 0);
        $salidaId = (int) ($empresa['preasiento_salida_id'] ?? 0);
        $cuenta = $cuentaId > 0 ? model(CuentaModel::class)->find($cuentaId) : null;
        $salida = $salidaId > 0 ? model(CuentaModel::class)->find($salidaId) : null;

        return [
            'anio'         => $anio,
            'mes'          => $mes,
            'meses'        => self::MESES,
            'anios'        => range((int) date('Y') + 1, (int) date('Y') - 4),
            'chile'        => (string) $empresa['pais'] === 'CL',
            'agrupacion'   => (string) ($empresa['preasiento_agrupacion'] ?? 'mes') === 'usuario' ? 'usuario' : 'mes',
            'auxiliar'     => (int) ($empresa['preasiento_auxiliar'] ?? 0) === 1,
            'cuenta'       => is_array($cuenta) ? $cuenta : null,
            'salida'       => is_array($salida) ? $salida : null,
            'comprobantes' => (new PreAsientoChile())->leer((int) $empresa['id'], $anio, $mes),
            'tomado'       => (new PreAsientoChile())->tomado((int) $empresa['id'], $anio, $mes),
            'manual'       => ! (new ConexionErp())->hayAsientos((int) $empresa['id']),
            'moneda'       => Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP',
        ];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: int, 3: int}
     */
    private function adminPeriodo(bool $post): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        $origen = $post ? $this->request->getPost() : $this->request->getGet();
        $anio = (int) ($origen['anio'] ?? date('Y'));
        $mes = (int) ($origen['mes'] ?? date('n'));
        if ($anio < 2000 || $anio > (int) date('Y') + 1) {
            $anio = (int) date('Y');
        }
        if ($usuario === null || $empresa === null || ($usuario['rol'] ?? '') !== 'administrador') {
            return [null, $empresa, $anio, $mes];
        }

        return [$usuario, $empresa, $anio, $mes];
    }

    /**
     * @param array<string, mixed>|null $empresa
     */
    private function modulo(string $codigo)
    {
        return view('plan/fuera', [
            'aviso'   => Modulos::aviso($codigo),
            'detalle' => Modulos::CATALOGO[$codigo]['detalle'],
            'esAdmin' => true,
        ]);
    }

    /**
     * @param array<string, mixed>|null $empresa
     */
    private function fueraPreasiento(?array $empresa)
    {
        return $empresa === null
            ? redirect()->to('/login')
            : redirect()->to('/panel')->with('error', 'Solo el administrador arma los preasientos.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: array{anio: int, mes: int, persona: int|null, esAdmin: bool}}
     */
    private function contexto(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();
        $anio = (int) ($this->request->getGet('anio') ?: date('Y'));
        $mesDato = $this->request->getGet('mes');
        $mes = $mesDato === null ? (int) date('n') : (int) $mesDato;
        if ($anio < 2000 || $anio > (int) date('Y') + 1) {
            $anio = (int) date('Y');
        }
        if ($mes < 0 || $mes > 12) {
            $mes = (int) date('n');
        }
        $esAdmin = is_array($usuario) && ($usuario['rol'] ?? '') === 'administrador';
        $persona = null;
        if (! $esAdmin && is_array($usuario)) {
            $persona = (int) $usuario['id'];
        } elseif ((int) $this->request->getGet('persona') > 0 && is_array($empresa)) {
            $elegida = model(UsuarioModel::class)->find((int) $this->request->getGet('persona'));
            if (is_array($elegida) && (int) $elegida['empresa_id'] === (int) $empresa['id']) {
                $persona = (int) $elegida['id'];
            }
        }

        return [$usuario, $empresa, [
            'anio'    => $anio,
            'mes'     => $mes,
            'persona' => $persona,
            'esAdmin' => $esAdmin,
        ]];
    }
}
