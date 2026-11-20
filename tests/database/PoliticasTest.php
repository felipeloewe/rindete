<?php

declare(strict_types=1);

use App\Libraries\GastoEvaluador;
use App\Models\EmpresaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * M3 — políticas de empresa editables por administrador.
 *
 * @internal
 */
final class PoliticasTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testAdminActualizaPoliticasYAllowedFields(): void
    {
        $admin = $this->registrar();

        $resultado = $this->withSession(['usuario_id' => $admin['id']])->postForm('empresa', [
            'nombre'              => 'Acme SpA',
            'pais'                => 'CL',
            'id_fiscal'           => '12.345.678-5',
            'detectar_duplicados' => '0',
            'dias_duplicado'      => '7',
            'comprobante_desde'   => '25000',
            'timezone'            => 'America/Bogota',
        ]);

        $resultado->assertRedirectTo(site_url('empresa'));
        $empresa = model(EmpresaModel::class)->find($admin['empresa_id']);
        $this->assertIsArray($empresa);
        $this->assertSame(0, (int) $empresa['detectar_duplicados']);
        $this->assertSame(7, (int) $empresa['dias_duplicado']);
        $this->assertSame(25000.0, (float) $empresa['comprobante_desde']);
        $this->assertSame('America/Bogota', $empresa['timezone']);
    }

    public function testPoliticaComprobanteDesdeSeAplicaAlEvaluar(): void
    {
        $admin = $this->registrar();
        $this->withSession(['usuario_id' => $admin['id']])->postForm('empresa', [
            'nombre'              => 'Acme SpA',
            'pais'                => 'CL',
            'id_fiscal'           => '12.345.678-5',
            'detectar_duplicados' => '1',
            'dias_duplicado'      => '3',
            'comprobante_desde'   => '50000',
            'timezone'            => 'America/Santiago',
        ]);
        $empresa = model(EmpresaModel::class)->find($admin['empresa_id']);

        $bajo = (new GastoEvaluador())->evaluar([
            'monto'       => 10000,
            'comprobante' => '',
            'id_fiscal'   => '1',
            'num_doc'     => '1',
            'tipo_doc'    => 'Boleta',
            'proveedor'   => 'X',
            'fecha'       => '2026-11-09',
            'estado'      => 'legalizado',
        ], $empresa, ['tope' => 0], []);

        $this->assertSame(0, $bajo['sin_comprobante']);
        $this->assertSame('legalizado', $bajo['estado']);
    }

    public function testRendidorNoPuedeEditarPoliticas(): void
    {
        $admin   = $this->registrar();
        $empresa = model(EmpresaModel::class)->find($admin['empresa_id']);
        $rendidorId = model(UsuarioModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'nombre'     => 'Luis',
            'email'      => 'luis@acme.test',
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ], true);

        $this->withSession(['usuario_id' => $rendidorId])->postForm('empresa', [
            'nombre'              => 'Hackeada',
            'pais'                => 'CL',
            'id_fiscal'           => '12.345.678-5',
            'detectar_duplicados' => '0',
            'dias_duplicado'      => '90',
            'comprobante_desde'   => '1',
            'timezone'            => 'UTC',
        ])->assertRedirectTo(site_url('empresa'));

        $actual = model(EmpresaModel::class)->find($empresa['id']);
        $this->assertSame('Acme SpA', $actual['nombre']);
        $this->assertSame(1, (int) $actual['detectar_duplicados']);
    }

    /**
     * @return array<string, mixed>
     */
    private function registrar(): array
    {
        $this->postForm('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ])->assertRedirectTo(site_url('panel'));

        $usuario = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $this->assertIsArray($usuario);

        return $usuario;
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
