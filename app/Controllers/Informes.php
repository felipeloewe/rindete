<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\InformeServicio;
use App\Libraries\Paises;
use App\Libraries\Sesion;
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
            'bajada' => 'Gastos rendidos del período, por persona y por la cuenta del asiento.',
        ],
        'asientos' => [
            'titulo' => 'Asientos',
            'bajada' => 'El asiento propuesto de cada gasto, por persona y agrupado por mes.',
        ],
        'tipos' => [
            'titulo' => 'Tipo de gasto',
            'bajada' => 'Nivel de gasto de la compañía en el período, por tipo.',
        ],
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
        $servicio = new InformeServicio();
        $persona = $filtro['persona'];
        $datos = match ($pieza) {
            'rendiciones' => $servicio->rendiciones((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'cartola'     => $servicio->cartola((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'gastos'      => $servicio->gastos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'asientos'    => $servicio->asientos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
            'tipos'       => $servicio->tipos((int) $empresa['id'], $filtro['anio'], $filtro['mes'], $persona),
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
        ];
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
