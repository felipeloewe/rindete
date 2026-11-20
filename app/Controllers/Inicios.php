<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\CajaChica;
use App\Libraries\CargoTarjeta;
use App\Libraries\DocumentoUnico;
use App\Libraries\FormasPago;
use App\Libraries\Montos;
use App\Libraries\PagoSocio;
use App\Libraries\Paises;
use App\Libraries\Sesion;
use App\Models\CategoriaModel;
use App\Models\UsuarioModel;

class Inicios extends BaseController
{
    public function documentos()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $formas = new FormasPago();

        return view('inicios/documentos', [
            'documentos' => (new DocumentoUnico())->lista((int) $empresa['id']),
            'formas'     => $formas->lista((int) $empresa['id'], true),
            'nombres'    => $this->nombresForma((int) $empresa['id']),
            'moneda'     => $this->moneda($empresa),
        ]);
    }

    public function crearDocumento()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $resultado = (new DocumentoUnico())->crear(
            (int) $empresa['id'],
            (int) $usuario['id'],
            (int) $this->request->getPost('forma_pago_id'),
            (string) $this->request->getPost('motivo'),
            $this->monto((string) $this->request->getPost('monto')),
            $this->moneda($empresa),
            $this->request->getFile('comprobante'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/documentos')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/documentos')->with('ok', 'Documento informado a tesorería.');
    }

    public function resolverDocumento(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $estado = (string) $this->request->getPost('estado');
        $resultado = (new DocumentoUnico())->resolver((int) $empresa['id'], $id, $estado);
        if (! $resultado['ok']) {
            return redirect()->to('/documentos')->with('errores', $resultado['errores']);
        }
        $aviso = $estado === 'pagado'
            ? 'Documento pagado.'
            : 'Documento en el flujo de pagos.';

        return redirect()->to('/documentos')->with('ok', $aviso);
    }

    public function cargos()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        $cargos = new CargoTarjeta();
        $tarjetas = $cargos->tarjetasDe($usuario, (int) $empresa['id']);
        if ($tarjetas === [] && ($usuario['rol'] ?? '') !== 'administrador') {
            return redirect()->to('/panel')->with('error', 'Esta tarjeta la opera quien tiene acceso.');
        }

        return view('inicios/cargos', [
            'tarjetas'   => $tarjetas,
            'categorias' => model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('activa', 1)->orderBy('nombre', 'ASC')->findAll(),
            'cargos'     => $cargos->estado((int) $empresa['id'], (int) date('Y'), 0, ($usuario['rol'] ?? '') === 'administrador' ? null : (int) $usuario['id'])['filas'],
            'moneda'     => $this->moneda($empresa),
        ]);
    }

    public function crearCargo()
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }
        $resultado = (new CargoTarjeta())->crear(
            $usuario,
            (int) $empresa['id'],
            (int) $this->request->getPost('tarjeta_id'),
            (int) $this->request->getPost('categoria_id'),
            (string) $this->request->getPost('motivo'),
            $this->monto((string) $this->request->getPost('monto')),
            $this->moneda($empresa),
            (bool) $this->request->getPost('dte'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/cargos')->with('errores', $resultado['errores']);
        }
        $aviso = ($resultado['estado'] ?? '') === 'validado'
            ? 'Cargo validado. Quedó el fondo por rendir y la rendición abierta. Facturas por pagar baja en ese monto.'
            : 'Cargo observado. ' . 'No calza con la mantención de la tarjeta.';

        return redirect()->to('/cargos')->with('ok', $aviso);
    }

    public function caja()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $caja = new CajaChica();

        return view('inicios/caja', [
            'saldo'      => $caja->saldo((int) $empresa['id']),
            'filas'      => $caja->estado((int) $empresa['id'], (int) date('Y'), 0, null)['filas'],
            'personas'   => $this->personasPorNombre((int) $empresa['id']),
            'categorias' => model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('activa', 1)->orderBy('nombre', 'ASC')->findAll(),
            'moneda'     => $this->moneda($empresa),
        ]);
    }

    public function entregarCaja()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $resultado = (new CajaChica())->entregar(
            (int) $empresa['id'],
            (int) $this->request->getPost('usuario_id'),
            (int) $this->request->getPost('categoria_id'),
            (string) $this->request->getPost('motivo'),
            $this->monto((string) $this->request->getPost('monto')),
            $this->moneda($empresa),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/caja')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/caja')->with('ok', 'Entrega centralizada. La persona quedó con el fondo por rendir y la caja con el saldo.');
    }

    public function reponerCaja()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $resultado = (new CajaChica())->reponer(
            (int) $empresa['id'],
            (string) $this->request->getPost('motivo'),
            $this->monto((string) $this->request->getPost('monto')),
            $this->moneda($empresa),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/caja')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/caja')->with('ok', 'Reposición hecha desde la caja chica.')->with('avisos', $resultado['avisos']);
    }

    public function socios()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $socios = new PagoSocio();

        return view('inicios/socios', [
            'entidades' => $socios->entidades((int) $empresa['id']),
            'formas'    => (new FormasPago())->lista((int) $empresa['id'], true),
            'pagos'     => $socios->estado((int) $empresa['id'], (int) date('Y'), 0, false)['filas'],
            'moneda'    => $this->moneda($empresa),
        ]);
    }

    public function entidad()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $resultado = (new PagoSocio())->entidad((int) $empresa['id'], (string) $this->request->getPost('nombre'));
        if (! $resultado['ok']) {
            return redirect()->to('/socios')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/socios')->with('ok', 'Entidad lista para recuperar.');
    }

    public function crearSocio()
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $motivos = (array) $this->request->getPost('motivo_linea');
        $montos = (array) $this->request->getPost('monto_linea');
        $formas = (array) $this->request->getPost('forma_linea');
        $lineas = [];
        foreach ($motivos as $indice => $motivo) {
            $lineas[] = [
                'motivo'        => (string) $motivo,
                'monto'         => $this->monto((string) ($montos[$indice] ?? '')),
                'forma_pago_id' => (int) ($formas[$indice] ?? 0),
            ];
        }
        $resultado = (new PagoSocio())->crear(
            (int) $empresa['id'],
            (int) $usuario['id'],
            (int) $this->request->getPost('entidad_id'),
            (string) $this->request->getPost('nota'),
            $lineas,
        );
        if (! $resultado['ok']) {
            return redirect()->to('/socios')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/socios')->with('ok', 'Pago de socio registrado. Cada documento quedó con su forma de pago.');
    }

    public function recuperar(int $id)
    {
        [$usuario, $empresa] = $this->admin();
        if ($usuario === null || $empresa === null) {
            return $this->fuera();
        }
        $resultado = (new PagoSocio())->recuperar((int) $empresa['id'], $id);
        if (! $resultado['ok']) {
            return redirect()->to('/socios')->with('errores', $resultado['errores']);
        }

        return redirect()->to('/socios')->with('ok', 'Quedó recuperado hacia la entidad.');
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function cuenta(): array
    {
        $sesion = new Sesion();

        return [$sesion->usuario(), $sesion->empresa()];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function admin(): array
    {
        [$usuario, $empresa] = $this->cuenta();
        if ($usuario === null || $empresa === null || ($usuario['rol'] ?? '') !== 'administrador') {
            return [null, $empresa];
        }

        return [$usuario, $empresa];
    }

    private function fuera()
    {
        $sesion = new Sesion();
        if ($sesion->usuario() === null) {
            return redirect()->to('/login');
        }

        return redirect()->to('/panel')->with('error', 'Lo opera quien administra la empresa.');
    }

    /**
     * @param array<string, mixed> $empresa
     */
    private function moneda(array $empresa): string
    {
        return Paises::pais((string) ($empresa['pais'] ?? ''))['moneda'] ?? 'USD';
    }

    private function monto(string $raw): float
    {
        return Montos::parse($raw) ?? 0.0;
    }

    /**
     * @return array<int, string>
     */
    private function nombresForma(int $empresaId): array
    {
        $nombres = [];
        foreach ((new FormasPago())->lista($empresaId) as $forma) {
            $nombres[(int) $forma['id']] = (string) $forma['nombre'];
        }

        return $nombres;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function personasPorNombre(int $empresaId): array
    {
        $personas = model(UsuarioModel::class)->where('empresa_id', $empresaId)->findAll();
        usort($personas, static fn (array $a, array $b): int => strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? '')));

        return $personas;
    }
}
