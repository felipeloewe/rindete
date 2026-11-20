<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\BancosChile;
use App\Libraries\NominaFondos;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Libraries\SolicitudServicio;
use App\Models\CategoriaModel;
use App\Models\FondoModel;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;

class Fondos extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        $solicitudes = $this->visibles($usuario, (int) $empresa['id']);
        $fondos = model(FondoModel::class)->where('empresa_id', $empresa['id']);
        if ($usuario['rol'] === 'rendidor') {
            $fondos->where('usuario_id', $usuario['id']);
        }

        return view('fondos/index', [
            'solicitudes' => $solicitudes,
            'fondos'      => $fondos->orderBy('id', 'DESC')->findAll(),
            'nombres'     => $this->nombres((int) $empresa['id']),
            'esAdmin'     => $usuario['rol'] === 'administrador',
        ]);
    }

    public function nomina()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(false);
        if ($usuario === null || $empresa === null) {
            return $this->soloAdmin();
        }
        $servicio = new NominaFondos();

        return view('fondos/nomina', [
            'anio'        => $anio,
            'mes'         => $mes,
            'meses'       => $this->meses(),
            'anios'       => range((int) date('Y') + 1, (int) date('Y') - 4),
            'pendientes'  => $servicio->pendientes((int) $empresa['id'], $anio, $mes),
            'repetidas'   => $servicio->repetidas((int) $empresa['id'], $anio, $mes),
            'nominas'     => $servicio->delMes((int) $empresa['id'], $anio, $mes),
            'nombres'      => $this->nombres((int) $empresa['id']),
            'cuentas'      => $this->cuentasBanco((int) $empresa['id']),
            'bancosPersona'=> $this->bancosPersona((int) $empresa['id']),
            'bancos'       => BancosChile::CODIGOS,
            'chile'        => (string) $empresa['pais'] === 'CL',
            'cuentaCargo'  => (string) ($empresa['cuenta_cargo'] ?? ''),
        ]);
    }

    public function pagos()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(false);
        if ($usuario === null || $empresa === null) {
            return $this->soloAdmin();
        }
        $meses = $this->meses();

        return view('fondos/pagos', [
            'anio'     => $anio,
            'mes'      => $mes,
            'meses'    => $meses,
            'anios'    => range((int) date('Y') + 1, (int) date('Y') - 4),
            'periodo'  => $meses[$mes] . ' ' . $anio,
            'consulta' => 'anio=' . $anio . '&mes=' . $mes,
            'filas'    => array_merge(
                (new NominaFondos())->tablero((int) $empresa['id'], $anio, $mes),
                (new \App\Libraries\DocumentoUnico())->delMes((int) $empresa['id'], $anio, $mes),
            ),
        ]);
    }

    public function archivoNomina(int $id)
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null || ($usuario['rol'] ?? '') !== 'administrador') {
            return $this->soloAdmin();
        }
        $resultado = (new NominaFondos())->archivo((int) $empresa['id'], $id);
        if (! $resultado['ok']) {
            return redirect()->to('/fondos/nomina')->with('errores', $resultado['errores']);
        }
        $bytes = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $resultado['contenido']);

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=Windows-1252')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $resultado['nombre'] . '"')
            ->setBody($bytes === false ? $resultado['contenido'] : $bytes);
    }

    public function cerrarNomina()
    {
        [$usuario, $empresa, $anio, $mes] = $this->adminPeriodo(true);
        if ($usuario === null || $empresa === null) {
            return $this->soloAdmin();
        }
        $resultado = (new NominaFondos())->cerrar(
            (int) $empresa['id'],
            $anio,
            $mes,
            (string) $this->request->getPost('cuenta_cargo'),
        );
        $vuelta = '/fondos/nomina?anio=' . $anio . '&mes=' . $mes;
        if (! $resultado['ok']) {
            return redirect()->to($vuelta)->with('errores', $resultado['errores']);
        }

        return redirect()->to($vuelta)
            ->with('ok', 'Nómina creada en el banco. Cada persona queda con su fondo por rendir. El archivo ya se puede descargar.')
            ->with('avisos', $resultado['avisos'] ?? []);
    }

    public function nueva()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        $pais = Paises::pais((string) $empresa['pais']);

        return view('fondos/nueva', [
            'categorias' => model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('activa', 1)->orderBy('nombre', 'ASC')->findAll(),
            'moneda'     => $pais['moneda'] ?? 'USD',
            'maximo'     => (float) ($empresa['monto_maximo_rendicion'] ?? 0),
        ]);
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        $resultado = (new SolicitudServicio())->pedir(
            $usuario,
            $empresa,
            (string) $this->request->getPost('motivo'),
            (string) $this->request->getPost('monto'),
            (int) $this->request->getPost('categoria_id'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/fondos/nueva')->withInput()->with('errores', $resultado['errores']);
        }
        $solicitud = $resultado['solicitud'];

        return redirect()->to('/fondos/' . $solicitud['id'])->with('ok', $this->aviso((string) $solicitud['estado']));
    }

    public function ver(int $id)
    {
        [$usuario, $empresa, $solicitud] = $this->visible($id);
        if ($solicitud === null || $usuario === null) {
            return redirect()->to('/fondos')->with('error', 'No encontramos esa solicitud.');
        }
        $categoria = model(CategoriaModel::class)->find($solicitud['categoria_id']);
        $fondo = model(FondoModel::class)->where('solicitud_id', $solicitud['id'])->first();
        $pasos = db_connect()->table('solicitud_pasos')->where('solicitud_id', $solicitud['id'])->orderBy('id', 'ASC')->get()->getResultArray();

        return view('fondos/ver', [
            'solicitud' => $solicitud,
            'categoria' => is_array($categoria) ? $categoria : null,
            'fondo'     => is_array($fondo) ? $fondo : null,
            'pasos'     => $pasos,
            'nombres'   => $this->nombres((int) $empresa['id']),
            'turno'     => (int) ($solicitud['turno_usuario_id'] ?? 0) === (int) $usuario['id'],
            'esAdmin'   => $usuario['rol'] === 'administrador',
        ]);
    }

    public function aceptar(int $id)
    {
        [$usuario, , $solicitud] = $this->visible($id);
        if ($solicitud === null || $usuario === null) {
            return redirect()->to('/fondos')->with('error', 'No encontramos esa solicitud.');
        }
        $resultado = (new SolicitudServicio())->aceptar($solicitud, $usuario);
        if (! $resultado['ok']) {
            return redirect()->to('/fondos/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/fondos/' . $id)->with('ok', $this->aviso((string) $resultado['solicitud']['estado']));
    }

    public function rechazar(int $id)
    {
        [$usuario, , $solicitud] = $this->visible($id);
        if ($solicitud === null || $usuario === null) {
            return redirect()->to('/fondos')->with('error', 'No encontramos esa solicitud.');
        }
        $resultado = (new SolicitudServicio())->rechazar($solicitud, $usuario, (string) $this->request->getPost('detalle'));
        if (! $resultado['ok']) {
            return redirect()->to('/fondos/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/fondos/' . $id)->with('ok', 'Solicitud rechazada.');
    }

    public function entregar(int $id)
    {
        [$usuario, , $solicitud] = $this->visible($id);
        if ($solicitud === null || $usuario === null) {
            return redirect()->to('/fondos')->with('error', 'No encontramos esa solicitud.');
        }
        $resultado = (new SolicitudServicio())->entregar($solicitud, $usuario);
        if (! $resultado['ok']) {
            return redirect()->to('/fondos/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/fondos/' . $id)->with('ok', 'Fondo entregado. El saldo inicial es el monto aceptado.');
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return list<array<string, mixed>>
     */
    private function visibles(array $usuario, int $empresaId): array
    {
        $items = model(SolicitudModel::class)->where('empresa_id', $empresaId)->where('estado !=', 'interna')->orderBy('id', 'DESC')->findAll();
        if ($usuario['rol'] === 'administrador') {
            return $items;
        }

        return array_values(array_filter($items, function (array $item) use ($usuario): bool {
            return (int) $item['usuario_id'] === (int) $usuario['id']
                || ((string) $item['estado'] === 'en_aceptacion' && (int) $item['turno_usuario_id'] === (int) $usuario['id']);
        }));
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: array<string, mixed>|null}
     */
    private function visible(int $id): array
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return [null, null, null];
        }
        $solicitud = model(SolicitudModel::class)->find($id);
        if (! is_array($solicitud) || (int) $solicitud['empresa_id'] !== (int) $empresa['id']) {
            return [$usuario, $empresa, null];
        }
        $propias = $this->visibles($usuario, (int) $empresa['id']);
        foreach ($propias as $item) {
            if ((int) $item['id'] === $id) {
                return [$usuario, $empresa, $solicitud];
            }
        }

        return [$usuario, $empresa, null];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: int, 3: int}
     */
    private function adminPeriodo(bool $post): array
    {
        [$usuario, $empresa] = $this->cuenta();
        $origen = $post ? $this->request->getPost() : $this->request->getGet();
        $anio = (int) ($origen['anio'] ?? date('Y'));
        $mes = (int) ($origen['mes'] ?? date('n'));
        if ($anio < 2000 || $anio > (int) date('Y') + 1) {
            $anio = (int) date('Y');
        }
        if ($mes < 1 || $mes > 12) {
            $mes = (int) date('n');
        }
        if ($usuario === null || $empresa === null || ($usuario['rol'] ?? '') !== 'administrador') {
            return [null, null, $anio, $mes];
        }

        return [$usuario, $empresa, $anio, $mes];
    }

    /**
     * @return array<int, string>
     */
    private function meses(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }

    private function soloAdmin()
    {
        $sesion = new Sesion();
        if ($sesion->usuario() === null) {
            return redirect()->to('/login');
        }

        return redirect()->to('/fondos')->with('error', 'La nómina la cierra el administrador.');
    }

    private function cuenta(): array
    {
        $sesion = new Sesion();

        return [$sesion->usuario(), $sesion->empresa()];
    }

    /**
     * @return array<int, string>
     */
    private function nombres(int $empresaId): array
    {
        $nombres = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $nombres[(int) $persona['id']] = (string) $persona['nombre'];
        }

        return $nombres;
    }

    /**
     * @return array<int, string>
     */
    private function cuentasBanco(int $empresaId): array
    {
        $cuentas = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $cuentas[(int) $persona['id']] = (string) ($persona['cuenta_banco'] ?? '');
        }

        return $cuentas;
    }

    /**
     * @return array<int, string>
     */
    private function bancosPersona(int $empresaId): array
    {
        $bancos = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll() as $persona) {
            $bancos[(int) $persona['id']] = (string) ($persona['banco_destino'] ?? '');
        }

        return $bancos;
    }

    private function aviso(string $estado): string
    {
        return [
            'detenida'      => 'La solicitud se detuvo por una regla.',
            'en_aceptacion' => 'La solicitud entró al flujo de aceptación.',
            'aceptada'      => 'Solicitud aceptada. Tesorería puede entregar el fondo.',
            'entregada'     => 'Fondo entregado. El saldo inicial es el monto aceptado.',
        ][$estado] ?? 'Solicitud guardada.';
    }
}
