<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Libraries\ConexionErp;
use App\Libraries\Modulos;
use App\Libraries\NominaFondos;
use App\Libraries\PreAsientoChile;
use App\Libraries\SistemasErp;
use App\Models\EmpresaModel;
use CodeIgniter\HTTP\ResponseInterface;

class Erp extends BaseController
{
    public function preasientos(): ResponseInterface
    {
        $conexion = $this->conexion('asientos');
        if ($conexion instanceof ResponseInterface) {
            return $conexion;
        }
        $empresa = model(EmpresaModel::class)->find((int) $conexion['empresa_id']);
        if (! is_array($empresa) || ! Modulos::activo($empresa, 'preasientos')) {
            return $this->falla(403, Modulos::aviso('preasientos'));
        }
        $anio = (int) $this->request->getGet('anio');
        $mes = (int) $this->request->getGet('mes');
        $filtrar = $anio >= 2000 && $mes >= 1 && $mes <= 12;
        $comprobantes = (new PreAsientoChile())->porEstado(
            (int) $conexion['empresa_id'],
            'cerrado',
            $filtrar ? $anio : null,
            $filtrar ? $mes : null,
        );
        $sistema = (string) $conexion['sistema'];

        return $this->response->setJSON([
            'sistema'     => $sistema,
            'preasientos' => array_map(
                static fn (array $comprobante): array => SistemasErp::preasiento($sistema, $comprobante),
                $comprobantes,
            ),
        ]);
    }

    public function consumirPreasiento(int $id): ResponseInterface
    {
        $conexion = $this->conexion('asientos');
        if ($conexion instanceof ResponseInterface) {
            return $conexion;
        }
        $empresa = model(EmpresaModel::class)->find((int) $conexion['empresa_id']);
        if (! is_array($empresa) || ! Modulos::activo($empresa, 'preasientos')) {
            return $this->falla(403, Modulos::aviso('preasientos'));
        }
        $resultado = (new PreAsientoChile())->consumir((int) $conexion['empresa_id'], $id, $this->referencia());
        if (! $resultado['ok']) {
            $mensaje = $resultado['errores'][0];
            $codigo = str_contains($mensaje, 'No encontramos') ? 404 : (str_contains($mensaje, 'ya es un asiento') ? 409 : 422);

            return $this->falla($codigo, $mensaje);
        }

        return $this->response->setJSON(
            SistemasErp::preasiento((string) $conexion['sistema'], $resultado['comprobante']),
        );
    }

    public function nominas(): ResponseInterface
    {
        $conexion = $this->conexion('pagos');
        if ($conexion instanceof ResponseInterface) {
            return $conexion;
        }
        $anio = (int) $this->request->getGet('anio');
        $mes = (int) $this->request->getGet('mes');
        $servicio = new NominaFondos();
        $nominas = $anio >= 2000 && $mes >= 1 && $mes <= 12
            ? $servicio->delMes((int) $conexion['empresa_id'], $anio, $mes)
            : $servicio->cerradas((int) $conexion['empresa_id']);
        $nominas = array_values(array_filter(
            $nominas,
            static fn (array $nomina): bool => in_array((string) $nomina['estado'], ['en_banco', 'cerrada'], true),
        ));
        $sistema = (string) $conexion['sistema'];

        return $this->response->setJSON([
            'sistema' => $sistema,
            'nominas' => array_map(
                static fn (array $nomina): array => SistemasErp::nomina($sistema, $nomina),
                $nominas,
            ),
        ]);
    }

    public function consumirNomina(int $id): ResponseInterface
    {
        $conexion = $this->conexion('pagos');
        if ($conexion instanceof ResponseInterface) {
            return $conexion;
        }
        $resultado = (new NominaFondos())->consumir((int) $conexion['empresa_id'], $id, $this->referencia());
        if (! $resultado['ok']) {
            $mensaje = $resultado['errores'][0];
            $codigo = str_contains($mensaje, 'No encontramos') ? 404 : (str_contains($mensaje, 'ya fue registrada') ? 409 : 422);

            return $this->falla($codigo, $mensaje);
        }

        return $this->response->setJSON(
            SistemasErp::nomina((string) $conexion['sistema'], $resultado['nomina']),
        );
    }

    /**
     * @return array<string, mixed>|ResponseInterface
     */
    private function conexion(string $destino): array|ResponseInterface
    {
        $encabezado = $this->request->getHeaderLine('Authorization');
        if (preg_match('/^Bearer\s+(\S+)$/i', $encabezado, $coincidencia) !== 1) {
            return $this->falla(401, 'Falta el token de la conexión.');
        }
        $conexion = (new ConexionErp())->porToken($coincidencia[1]);
        if ($conexion === null) {
            return $this->falla(401, 'Esa conexión no está vigente.');
        }
        if (! (new ConexionErp())->sirve($conexion, $destino)) {
            $para = $destino === 'asientos' ? 'los preasientos' : 'la nómina';

            return $this->falla(403, 'Esta conexión no está habilitada para ' . $para . '.');
        }

        return $conexion;
    }

    private function referencia(): string
    {
        $json = $this->request->getJSON(true);

        return trim((string) (is_array($json) ? ($json['referencia'] ?? '') : $this->request->getPost('referencia')));
    }

    private function falla(int $codigo, string $mensaje): ResponseInterface
    {
        return $this->response->setStatusCode($codigo)->setJSON(['error' => $mensaje]);
    }
}
