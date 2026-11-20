<?php

declare(strict_types=1);

namespace App\Libraries;

class PagoSocio
{
    /**
     * @param list<array{forma_pago_id: int, motivo: string, monto: float}> $lineas
     *
     * @return array{ok: bool, errores: list<string>}
     */
    public function crear(int $empresaId, int $usuarioId, int $entidadId, string $nota, array $lineas): array
    {
        $entidad = db_connect()->table('entidades')->where('empresa_id', $empresaId)->where('id', $entidadId)->where('activo', 1)->get()->getRowArray();
        if (! is_array($entidad)) {
            return ['ok' => false, 'errores' => ['Elige la entidad a la que se recupera.']];
        }
        $formas = new FormasPago();
        $validas = [];
        foreach ($lineas as $linea) {
            $motivo = mb_substr(trim($linea['motivo']), 0, 180);
            $monto = $linea['monto'];
            if ($motivo === '' && $monto <= 0) {
                continue;
            }
            if ($motivo === '' || $monto <= 0) {
                return ['ok' => false, 'errores' => ['Cada documento lleva motivo y un monto mayor que cero.']];
            }
            $forma = $formas->una($empresaId, $linea['forma_pago_id']);
            if ($forma === null || (int) $forma['activo'] !== 1) {
                return ['ok' => false, 'errores' => ['Elige la forma de pago de cada documento.']];
            }
            $validas[] = ['forma_pago_id' => (int) $forma['id'], 'motivo' => $motivo, 'monto' => $monto];
        }
        if ($validas === []) {
            return ['ok' => false, 'errores' => ['Agrega al menos un documento.']];
        }
        $db = db_connect();
        $db->table('pagos_socio')->insert([
            'empresa_id' => $empresaId,
            'usuario_id' => $usuarioId,
            'entidad_id' => $entidadId,
            'nota'       => mb_substr(trim($nota), 0, 180),
            'estado'     => 'por_recuperar',
            'creada'     => date('Y-m-d H:i:s'),
        ]);
        $pagoId = (int) $db->insertID();
        foreach ($validas as $linea) {
            $db->table('pago_socio_lineas')->insert($linea + ['pago_id' => $pagoId]);
        }

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function entidad(int $empresaId, string $nombre): array
    {
        $nombre = mb_substr(trim($nombre), 0, 120);
        if ($nombre === '') {
            return ['ok' => false, 'errores' => ['Escribe la entidad.']];
        }
        $existe = db_connect()->table('entidades')->where('empresa_id', $empresaId)->where('nombre', $nombre)->countAllResults();
        if ($existe > 0) {
            return ['ok' => false, 'errores' => ['Esa entidad ya está.']];
        }
        db_connect()->table('entidades')->insert([
            'empresa_id' => $empresaId,
            'nombre'     => $nombre,
            'activo'     => 1,
        ]);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return array{ok: bool, errores: list<string>}
     */
    public function recuperar(int $empresaId, int $id): array
    {
        $pago = db_connect()->table('pagos_socio')->where('empresa_id', $empresaId)->where('id', $id)->get()->getRowArray();
        if (! is_array($pago)) {
            return ['ok' => false, 'errores' => ['No encontramos ese pago de socio.']];
        }
        if ((string) $pago['estado'] === 'recuperado') {
            return ['ok' => true, 'errores' => []];
        }
        db_connect()->table('pagos_socio')->where('id', $id)->update(['estado' => 'recuperado']);

        return ['ok' => true, 'errores' => []];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function entidades(int $empresaId): array
    {
        return db_connect()->table('entidades')->where('empresa_id', $empresaId)->where('activo', 1)->orderBy('nombre', 'ASC')->get()->getResultArray();
    }

    /**
     * @return array{filas: list<array<string, mixed>>, total: float}
     */
    public function estado(int $empresaId, int $anio, int $mes, bool $soloPendiente): array
    {
        [$desde, $hasta] = InformeServicio::limites($anio, $mes);
        $entidades = [];
        foreach ($this->entidades($empresaId) as $entidad) {
            $entidades[(int) $entidad['id']] = (string) $entidad['nombre'];
        }
        $formas = [];
        foreach ((new FormasPago())->lista($empresaId) as $forma) {
            $formas[(int) $forma['id']] = (string) $forma['nombre'];
        }
        $consulta = db_connect()->table('pagos_socio')
            ->where('empresa_id', $empresaId)
            ->where('creada >=', $desde)
            ->where('creada <', $hasta)
            ->orderBy('id', 'ASC');
        if ($soloPendiente) {
            $consulta->where('estado', 'por_recuperar');
        }
        $filas = [];
        $total = 0.0;
        foreach ($consulta->get()->getResultArray() as $pago) {
            $lineas = db_connect()->table('pago_socio_lineas')->where('pago_id', $pago['id'])->orderBy('id', 'ASC')->get()->getResultArray();
            $monto = 0.0;
            foreach ($lineas as &$linea) {
                $linea['forma'] = $formas[(int) $linea['forma_pago_id']] ?? '';
                $monto += (float) $linea['monto'];
            }
            unset($linea);
            $filas[] = $pago + [
                'entidad' => $entidades[(int) $pago['entidad_id']] ?? '',
                'lineas'  => $lineas,
                'monto'   => $monto,
            ];
            if ((string) $pago['estado'] === 'por_recuperar') {
                $total += $monto;
            }
        }

        return ['filas' => $filas, 'total' => $total];
    }
}
