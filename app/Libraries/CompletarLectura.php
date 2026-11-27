<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GastoModel;

/**
 * Completa en gastos ya guardados los datos del documento que quedaron vacíos.
 * No pisa lo que la persona escribió.
 */
class CompletarLectura
{
    /** @var list<string> */
    private const CAMPOS = ['proveedor', 'tipo_doc', 'id_fiscal', 'num_doc', 'codigo_fiscal'];

    /**
     * @return array{revisados: int, actualizados: int, sin_lectura: int, detalle: list<array{id: int, campos: list<string>}>}
     */
    public function aplicar(): array
    {
        $informe = ['revisados' => 0, 'actualizados' => 0, 'sin_lectura' => 0, 'detalle' => []];
        $empresas = [];
        foreach (model(GastoModel::class)->orderBy('id', 'ASC')->findAll() as $gasto) {
            if ((string) ($gasto['comprobante'] ?? '') === '') {
                continue;
            }
            $vacios = $this->vacios($gasto);
            if ($vacios === []) {
                continue;
            }
            $informe['revisados']++;
            $leido = $this->propuesta($gasto);
            $cambios = [];
            foreach ($vacios as $campo) {
                $valor = trim((string) ($leido[$campo] ?? ''));
                if ($valor !== '') {
                    $cambios[$campo] = $valor;
                }
            }
            if ($cambios === []) {
                $informe['sin_lectura']++;
                continue;
            }
            model(GastoModel::class)->update((int) $gasto['id'], $cambios);
            $empresas[(int) $gasto['empresa_id']] = true;
            $informe['actualizados']++;
            $informe['detalle'][] = ['id' => (int) $gasto['id'], 'campos' => array_keys($cambios)];
        }
        foreach (array_keys($empresas) as $empresaId) {
            (new GastoServicio())->reevaluar($empresaId);
        }

        return $informe;
    }

    /**
     * @param array<string, mixed> $gasto
     *
     * @return list<string>
     */
    private function vacios(array $gasto): array
    {
        $vacios = [];
        foreach (self::CAMPOS as $campo) {
            if (trim((string) ($gasto[$campo] ?? '')) === '') {
                $vacios[] = $campo;
            }
        }

        return $vacios;
    }

    /**
     * @param array<string, mixed> $gasto
     *
     * @return array<string, string>
     */
    private function propuesta(array $gasto): array
    {
        $propuesta = [];
        $ruta = (new Comprobante())->ruta((string) $gasto['comprobante']);
        if ($ruta !== null) {
            $propuesta = (new LectorComprobante())->leerRuta($ruta);
        }
        foreach ($this->lecturaGuardada($gasto) as $campo => $valor) {
            if (trim((string) ($propuesta[$campo] ?? '')) === '' && trim((string) $valor) !== '') {
                $propuesta[$campo] = (string) $valor;
            }
        }

        return $propuesta;
    }

    /**
     * @param array<string, mixed> $gasto
     *
     * @return array<string, string>
     */
    private function lecturaGuardada(array $gasto): array
    {
        $db = db_connect();
        if (! $db->tableExists('entradas') || ! $db->fieldExists('lectura', 'entradas')) {
            return [];
        }
        $fila = $db->table('entradas')
            ->where('empresa_id', (int) $gasto['empresa_id'])
            ->where('comprobante', (string) $gasto['comprobante'])
            ->where('lectura !=', null)
            ->where('lectura !=', '')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();
        if (! is_array($fila)) {
            return [];
        }
        $json = json_decode((string) ($fila['lectura'] ?? ''), true);

        return is_array($json) ? $json : [];
    }
}
