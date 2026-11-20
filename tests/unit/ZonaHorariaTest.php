<?php

declare(strict_types=1);

use App\Libraries\GastoServicio;
use App\Libraries\ZonaHoraria;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * M8 — timezone por empresa / default America/Santiago y borde de mes en cupo.
 *
 * @internal
 */
final class ZonaHorariaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testDefaultYValidacion(): void
    {
        $this->assertSame('America/Santiago', ZonaHoraria::DEFAULT);
        $this->assertSame('America/Santiago', ZonaHoraria::deEmpresa(null));
        $this->assertSame('America/Bogota', ZonaHoraria::deEmpresa(['timezone' => 'America/Bogota']));
        $this->assertSame('America/Santiago', ZonaHoraria::deEmpresa(['timezone' => 'Invalid/Zone']));
        $this->assertTrue(ZonaHoraria::valida('America/Santiago'));
        $this->assertFalse(ZonaHoraria::valida('Marte/Base'));
        $this->assertSame('America/Santiago', config('App')->appTimezone);
    }

    public function testInicioMesEnBordeUtcVsSantiago(): void
    {
        // 2026-12-01 02:30 UTC ≈ 2026-11-30 23:30 America/Santiago (UTC-3).
        $enSantiago = new DateTimeImmutable('2026-12-01 02:30:00', new DateTimeZone('UTC'));
        $enSantiago = $enSantiago->setTimezone(new DateTimeZone('America/Santiago'));
        $enUtc      = new DateTimeImmutable('2026-12-01 02:30:00', new DateTimeZone('UTC'));

        $this->assertSame(
            '2026-11-01 00:00:00',
            ZonaHoraria::inicioMesCupo(['timezone' => 'America/Santiago'], $enSantiago),
        );
        $this->assertSame(
            '2026-12-01 00:00:00',
            ZonaHoraria::inicioMesCupo(['timezone' => 'UTC'], $enUtc),
        );
    }

    public function testCupoExcluyeGastosDelMesAnterior(): void
    {
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ])->assertRedirectTo(site_url('panel'));

        $usuario   = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->first();
        $this->assertSame('America/Santiago', $empresa['timezone']);

        $inicioMes = ZonaHoraria::inicioMesCupo($empresa);
        $anterior  = (new DateTimeImmutable($inicioMes))->modify('-1 day')->format('Y-m-d H:i:s');
        $actual    = (new DateTimeImmutable($inicioMes))->modify('+1 day')->format('Y-m-d H:i:s');

        $this->insertarGasto($empresa, $usuario, $categoria, 'Anterior', $anterior);
        $this->insertarGasto($empresa, $usuario, $categoria, 'Actual', $actual);

        $cupo = (new GastoServicio())->cupoUsado($empresa);
        $this->assertSame(1, $cupo);
    }

    /**
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     */
    private function insertarGasto(array $empresa, array $usuario, array $categoria, string $concepto, string $creado): void
    {
        model(GastoModel::class)->insert([
            'empresa_id'        => $empresa['id'],
            'usuario_id'        => $usuario['id'],
            'categoria_id'      => $categoria['id'],
            'concepto'          => $concepto,
            'proveedor'         => 'X',
            'fecha'             => substr($creado, 0, 10),
            'monto'             => 1000,
            'moneda'            => 'CLP',
            'tipo_doc'          => '',
            'id_fiscal'         => '',
            'num_doc'           => '',
            'codigo_fiscal'     => '',
            'nota'              => '',
            'comprobante'       => '',
            'estado'            => 'legalizado',
            'duplicado'         => 0,
            'fuera_politica'    => 0,
            'sin_comprobante'   => 0,
            'fiscal_incompleto' => 1,
            'creado'            => $creado,
        ]);
    }

    /**
     * @param array<string, string> $data
     */
    private function postForm(string $path, array $data)
    {
        $security = service('security');
        $data[$security->getTokenName()] = $security->getHash();

        return $this->post($path, $data);
    }
}
