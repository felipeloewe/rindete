<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\Montos;
use App\Libraries\Paises;
use App\Libraries\RendicionDirecta;
use App\Libraries\RendicionServicio;
use App\Libraries\SaldoRendicion;
use App\Libraries\Sesion;
use App\Models\CuentaModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;

class Rendiciones extends BaseController
{
    public function index()
    {
        [$usuario, $empresa] = $this->cuentaSesion();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        $consulta = model(RendicionModel::class)->where('empresa_id', $empresa['id']);
        if ($usuario['rol'] === 'rendidor') {
            $consulta->groupStart()
                ->where('usuario_id', $usuario['id'])
                ->orWhere('turno_usuario_id', $usuario['id'])
                ->groupEnd();
        }
        $items = $consulta->orderBy('id', 'DESC')->findAll();
        $monedaEmpresa = Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP';
        $totales = [];
        foreach ($items as $item) {
            $gastos = model(GastoModel::class)->where('rendicion_id', $item['id'])->findAll();
            $monto = array_sum(array_map(static fn (array $gasto): float => (float) $gasto['monto'], $gastos));
            $totales[(int) $item['id']] = [
                'cantidad' => count($gastos),
                'monto'    => $monto,
                'saldo'    => round((float) ($item['monto_por_rendir'] ?? 0) - $monto, 2),
                'moneda'   => $gastos[0]['moneda'] ?? $monedaEmpresa,
            ];
        }

        $nombres = [];
        foreach (model(UsuarioModel::class)->where('empresa_id', $empresa['id'])->findAll() as $persona) {
            $nombres[(int) $persona['id']] = (string) $persona['nombre'];
        }

        return view('rendiciones/index', [
            'items'      => $items,
            'totales'    => $totales,
            'nombres'    => $nombres,
            'autorizada' => RendicionDirecta::autorizada($usuario),
        ]);
    }

    public function nueva()
    {
        [$usuario, $empresa] = $this->cuentaSesion();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        if (! RendicionDirecta::autorizada($usuario)) {
            return redirect()->to('/rendiciones')->with('error', 'Esta persona no está autorizada a rendir sin solicitud.');
        }
        $reglas = RendicionDirecta::de($usuario, $empresa);
        $moneda = Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP';

        return view('rendiciones/nueva', [
            'tope'       => $this->tope($reglas, $moneda),
            'viatico'    => RendicionDirecta::etiqueta($reglas['viatico']),
            'asignacion' => RendicionDirecta::etiqueta($reglas['asignacion']),
            'fondos'     => (new SaldoRendicion())->fondosLibres((int) $empresa['id'], (int) $usuario['id']),
            'moneda'     => $moneda,
        ]);
    }

    public function crear()
    {
        [$usuario, $empresa] = $this->cuentaSesion();
        if ($usuario === null || $empresa === null) {
            return redirect()->to('/login');
        }

        $resultado = (new RendicionServicio())->crear(
            $usuario,
            (string) $this->request->getPost('titulo'),
            (string) $this->request->getPost('nota'),
            (int) $this->request->getPost('fondo_id'),
        );
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/nueva')->with('errores', $resultado['errores'])->withInput();
        }

        return redirect()->to('/rendiciones/' . $resultado['id'])->with('ok', 'Rendición abierta. Suma los gastos legalizados.');
    }

    public function ver(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null || $empresa === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }

        $gastos = model(GastoModel::class)->where('rendicion_id', $rendicion['id'])->orderBy('fecha', 'DESC')->findAll();
        $disponibles = [];
        if ((string) $rendicion['estado'] === 'abierta') {
            $disponibles = model(GastoModel::class)
                ->where('empresa_id', $rendicion['empresa_id'])
                ->where('usuario_id', $rendicion['usuario_id'])
                ->where('estado', 'legalizado')
                ->where('rendicion_id', null)
                ->orderBy('fecha', 'DESC')
                ->findAll();
        }
        $moneda = $gastos[0]['moneda'] ?? (Paises::pais((string) $empresa['pais'])['moneda'] ?? 'CLP');
        $directa = (string) ($rendicion['origen'] ?? '') === 'directa';
        $saldo = (new SaldoRendicion())->de($rendicion);
        $turnoId = (int) ($rendicion['turno_usuario_id'] ?? 0);
        $turno = $turnoId > 0 ? model(UsuarioModel::class)->find($turnoId) : null;
        $previa = (int) ($rendicion['rendicion_previa_id'] ?? 0) > 0
            ? model(RendicionModel::class)->find((int) $rendicion['rendicion_previa_id'])
            : null;
        $hija = model(RendicionModel::class)->where('rendicion_previa_id', $rendicion['id'])->first();

        return view('rendiciones/ver', [
            'rendicion'   => $rendicion,
            'gastos'      => $gastos,
            'disponibles' => $disponibles,
            'total'       => array_sum(array_map(static fn (array $gasto): float => (float) $gasto['monto'], $gastos)),
            'moneda'      => $moneda,
            'abierta'     => (string) $rendicion['estado'] === 'abierta',
            'saldo'       => $saldo,
            'puedeAceptar'=> $usuario !== null && (string) $rendicion['estado'] === 'cierre_pedido' && $turnoId === (int) $usuario['id'],
            'turnoNombre' => is_array($turno) ? (string) $turno['nombre'] : '',
            'previa'      => is_array($previa) ? $previa : null,
            'hija'        => is_array($hija) ? $hija : null,
            'directa'     => $directa,
            'tope'        => (float) ($rendicion['monto_maximo'] ?? 0) > 0
                ? Montos::formato((float) $rendicion['monto_maximo'], $moneda)
                : 'Sin tope',
            'viatico'     => RendicionDirecta::etiqueta($this->cuenta((int) ($rendicion['cuenta_viatico_id'] ?? 0))),
            'asignacion'  => RendicionDirecta::etiqueta($this->cuenta((int) ($rendicion['cuenta_asignacion_id'] ?? 0))),
            'puedeDevolver' => $usuario !== null && ($usuario['rol'] ?? '') === 'administrador' && (new \App\Libraries\DevolucionFondos())->puede($rendicion),
        ]);
    }

    public function agregar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $gasto = model(GastoModel::class)->find((int) $this->request->getPost('gasto_id'));
        if (! is_array($gasto)) {
            return redirect()->to('/rendiciones/' . $id)->with('error', 'Elige un gasto legalizado.');
        }
        $resultado = (new RendicionServicio())->agregar($rendicion, $gasto);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Gasto sumado. Quedó en rendición.');
    }

    public function quitar(int $id, int $gastoId)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $gasto = model(GastoModel::class)->find($gastoId);
        if (! is_array($gasto)) {
            return redirect()->to('/rendiciones/' . $id)->with('error', 'No encontramos ese gasto.');
        }
        $resultado = (new RendicionServicio())->quitar($rendicion, $gasto);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Gasto devuelto a legalizado.');
    }

    public function enviar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new SaldoRendicion())->cerrar($rendicion, (string) $this->request->getPost('monto_adicional'));
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }
        $destino = ($resultado['nueva'] ?? 0) > 0 ? '/rendiciones/' . $resultado['nueva'] : '/rendiciones/' . $id;
        $mensaje = match ($resultado['caso'] ?? '') {
            'negativo' => 'Cierre pedido. El saldo es negativo y espera aceptación.',
            'positivo' => 'Rendición cerrada. El saldo quedó en una rendición nueva.',
            default    => 'Rendición cerrada. El saldo cuadra.',
        };

        return redirect()->to($destino)->with('ok', $mensaje);
    }

    public function aceptar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($usuario === null || $rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new SaldoRendicion())->aceptar($rendicion, $usuario);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }
        $queda = model(RendicionModel::class)->find($id);
        $mensaje = 'Cierre aceptado. Sigue en el flujo.';
        if (is_array($queda) && (string) $queda['estado'] === 'cerrada') {
            $mensaje = (string) ($queda['reembolso'] ?? '') === 'por_devolver'
                ? 'Cierre aceptado. Falta devolver los fondos para cerrar el flujo.'
                : 'Cierre aceptado. La rendición quedó cerrada.';
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', $mensaje);
    }

    public function devolver(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($usuario === null || $empresa === null || $rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        if (($usuario['rol'] ?? '') !== 'administrador') {
            return redirect()->to('/rendiciones/' . $id)->with('error', 'La devolución de fondos la hace quien administra la empresa.');
        }
        $resultado = (new \App\Libraries\DevolucionFondos())->devolver($empresa, $rendicion);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }
        if ($resultado['contenido'] === '') {
            return redirect()->to('/rendiciones/' . $id)->with('ok', 'Los fondos quedaron devueltos y la rendición cerró el flujo.');
        }

        return $this->response
            ->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $resultado['nombre'] . '"')
            ->setBody($resultado['contenido']);
    }

    public function rechazar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($usuario === null || $rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new SaldoRendicion())->rechazar($rendicion, $usuario, (string) $this->request->getPost('detalle'));
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'Cierre rechazado. La rendición volvió a quedar abierta.');
    }

    public function sumar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new SaldoRendicion())->sumar($rendicion, (string) $this->request->getPost('monto'));
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones/' . $id)->with('ok', 'El monto adicional quedó dentro de lo que hay que rendir.');
    }

    public function eliminar(int $id)
    {
        [$usuario, $empresa, $rendicion] = $this->visible($id);
        if ($rendicion === null) {
            return redirect()->to('/rendiciones')->with('error', 'No encontramos esa rendición.');
        }
        $resultado = (new RendicionServicio())->eliminar($rendicion);
        if (! $resultado['ok']) {
            return redirect()->to('/rendiciones/' . $id)->with('errores', $resultado['errores']);
        }

        return redirect()->to('/rendiciones')->with('ok', 'Rendición eliminada. Los gastos volvieron a quedar libres.');
    }

    /**
     * @param array{monto: float, propio: bool} $reglas
     */
    private function tope(array $reglas, string $moneda): string
    {
        if ($reglas['monto'] <= 0) {
            return 'Sin tope';
        }

        return Montos::formato($reglas['monto'], $moneda) . ($reglas['propio'] ? ' · propio' : ' · de la empresa');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cuenta(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $cuenta = model(CuentaModel::class)->find($id);

        return is_array($cuenta) ? $cuenta : null;
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null}
     */
    private function cuentaSesion(): array
    {
        $sesion  = new Sesion();
        $usuario = $sesion->usuario();
        $empresa = $sesion->empresa();

        return [$usuario, $empresa];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: array<string, mixed>|null}
     */
    private function visible(int $id): array
    {
        [$usuario, $empresa] = $this->cuentaSesion();
        if ($usuario === null || $empresa === null) {
            return [null, null, null];
        }
        $rendicion = model(RendicionModel::class)->find($id);
        if (! is_array($rendicion) || (int) $rendicion['empresa_id'] !== (int) $empresa['id']) {
            return [$usuario, $empresa, null];
        }
        $propia = (int) $rendicion['usuario_id'] === (int) $usuario['id'];
        $turno  = (int) ($rendicion['turno_usuario_id'] ?? 0) === (int) $usuario['id'];
        if ($usuario['rol'] === 'rendidor' && ! $propia && ! $turno) {
            return [$usuario, $empresa, null];
        }

        return [$usuario, $empresa, $rendicion];
    }
}
