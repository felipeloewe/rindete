<?php

declare(strict_types=1);

use App\Libraries\PersonaServicio;
use App\Libraries\RendicionServicio;
use App\Models\CategoriaModel;
use App\Models\CuentaModel;
use App\Models\EmpresaModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PersonasTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testElAdministradorDaDeAltaHastaElCupo(): void
    {
        [$ana, $empresa] = $this->empresa();
        $this->withSession(['usuario_id' => $ana['id']]);
        $pagina = $this->get('personas');
        $pagina->assertOK();
        $pagina->assertSee('Dar de alta');
        $pagina->assertDontSee('Aceptación de políticas');
        $pagina->assertDontSee('Falta aceptar las condiciones de uso.');
        $pagina->assertSee('0 de 4');
        $pagina->assertSee('Mínimo 8 caracteres.');
        $pagina->assertSee('Integraciones');
        $pagina->assertDontSee('Identificador Global66');
        $pagina->assertSee('Elige el banco o el TPPF');
        $pagina->assertSee('label="Bancos"');
        $pagina->assertSee('label="TPPF"');
        $pagina->assertSee('Global Card');
        $pagina->assertSee('738');

        $servicio = new PersonaServicio();
        foreach (['luis@acme.test', 'marta@acme.test', 'nora@acme.test', 'quinta@acme.test'] as $correo) {
            $datos = [
                'nombre'   => 'Persona ' . $correo,
                'email'    => $correo,
                'password' => 'clave-segura',
            ];
            if ($correo === 'luis@acme.test') {
                $datos['banco_destino'] = '738';
                $datos['cuenta_banco']  = '11223344';
            }
            $alta = $servicio->crear($empresa, $datos);
            $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        }
        $lleno = $servicio->crear($empresa, [
            'nombre'   => 'Sexta',
            'email'    => 'sexta@acme.test',
            'password' => 'clave-segura',
            'acepta_politica' => '1',
        ]);
        $this->assertFalse($lleno['ok']);
        $this->assertSame('El plan Free admite 4 usuarios.', $lleno['errores'][0]);

        $luis = model(UsuarioModel::class)->porEmail('luis@acme.test');
        $this->assertSame(0, (int) $luis['puede_rendir_sin_solicitud']);
        $this->assertSame(\App\Libraries\PoliticaDatos::VERSION, $luis['politica_version']);
        $this->assertSame('738', $luis['banco_destino']);
        $this->assertTrue(\App\Libraries\BancosChile::existe('738'));
        $this->assertTrue(\App\Libraries\BancosChile::existe('016'));
        $categorias = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->countAllResults();
        $aceptados = db_connect()->table('gastos_aceptados')->where('usuario_id', $luis['id'])->countAllResults();
        $this->assertSame($categorias, $aceptados);

        $ajeno = $this->withSession(['usuario_id' => $luis['id']])->get('personas');
        $ajeno->assertRedirectTo(site_url('panel'));

        $propia = $servicio->guardar($ana, $ana, ['activo' => '0', 'monto_maximo' => '']);
        $this->assertTrue($propia['ok'], implode(' ', $propia['errores']));
        $this->assertSame(1, (int) model(UsuarioModel::class)->find($ana['id'])['activo']);
    }

    public function testLaCuentaGlobalApareceAlHabilitarLaIntegracion(): void
    {
        [$ana, $empresa] = $this->empresa();
        model(\App\Models\MedioModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'proveedor'  => 'global66',
            'clave'      => 'clave-prueba',
            'secreto'    => 'cifrado',
            'vigente'    => 1,
        ]);
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('personas');
        $pagina->assertOK();
        $pagina->assertSee('Identificador Global66');
        $pagina->assertSee('Opcional. accountID');

        $cuenta = (new \App\Libraries\CuentaGlobal())->revisar((int) $empresa['id'], 0, [
            'global_cliente'   => '',
            'global_billetera' => '445566',
        ]);
        $this->assertTrue($cuenta['ok'], implode(' ', $cuenta['errores']));
        $this->assertTrue($cuenta['aplica']);
        $this->assertSame('445566', $cuenta['billetera']);
    }

    public function testLaRendicionDirectaUsaElTopeYElConceptoDeLaPersona(): void
    {
        [$ana, $empresa] = $this->empresa();
        model(EmpresaModel::class)->update($empresa['id'], ['monto_maximo_rendicion' => 20000]);
        $empresa = model(EmpresaModel::class)->find($empresa['id']);
        $alta = (new PersonaServicio())->crear($empresa, [
            'nombre'   => 'Luis Soto',
            'email'    => 'luis@acme.test',
            'password' => 'clave-segura',
            'acepta_politica' => '1',
        ]);
        $luis = model(UsuarioModel::class)->find($alta['id']);
        $servicio = new RendicionServicio();
        $negada = $servicio->crear($luis, 'Viáticos de marzo', '');
        $this->assertFalse($negada['ok']);
        $this->assertSame('Esta persona no está autorizada a rendir sin solicitud.', $negada['errores'][0]);

        $general = $this->cuenta($empresa, 'viatico', null, '6101', 'Viático general');
        $this->cuenta($empresa, 'asignacion', null, '6102', 'Asignación general');
        $propia = $this->cuenta($empresa, 'viatico', (int) $luis['id'], '6103', 'Viático de Luis');

        (new PersonaServicio())->guardar($ana, $luis, [
            'puede_rendir_sin_solicitud' => '1',
            'monto_maximo'               => '8000',
            'activo'                     => '1',
        ]);
        $luis = model(UsuarioModel::class)->find($luis['id']);
        $creada = $servicio->crear($luis, 'Viáticos de marzo', 'Visita');
        $this->assertTrue($creada['ok'], implode(' ', $creada['errores']));
        $rendicion = model(RendicionModel::class)->find($creada['id']);
        $this->assertSame('directa', $rendicion['origen']);
        $this->assertEquals(8000, (float) $rendicion['monto_maximo']);
        $this->assertSame((int) $propia, (int) $rendicion['cuenta_viatico_id']);
        $this->assertNotSame((int) $general, (int) $rendicion['cuenta_viatico_id']);

        $asignacion = model(CuentaModel::class)->find($rendicion['cuenta_asignacion_id']);
        $this->assertSame('6102', $asignacion['codigo']);
        $this->assertNull($asignacion['usuario_id']);

        $this->withSession(['usuario_id' => $luis['id']]);
        $ficha = $this->get('rendiciones/' . $rendicion['id']);
        $ficha->assertOK();
        $ficha->assertSee('Sin solicitud');
        $ficha->assertSee('Viático de Luis');
        $ficha->assertSee('propio');
        $ficha->assertSee('CLP 8.000');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function empresa(): array
    {
        $security = service('security');
        $this->post('registro', [
            'pais'                          => 'CL',
            'empresa'                       => 'Acme SpA',
            'id_fiscal'                     => '12.345.678-5',
            'nombre'                        => 'Ana Pérez',
            'email'                         => 'ana@acme.test',
            'password'                      => 'clave-segura',
            'acepta_politica' => '1',
            $security->getTokenName()       => $security->getHash(),
        ]);
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');

        return [$ana, model(EmpresaModel::class)->find($ana['empresa_id'])];
    }

    private function cuenta(array $empresa, string $concepto, ?int $usuarioId, string $codigo, string $nombre): int
    {
        return (int) model(CuentaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'clase'      => 'remuneracion',
            'codigo'     => $codigo,
            'nombre'     => $nombre,
            'concepto'   => $concepto,
            'usuario_id' => $usuarioId,
            'activa'     => 1,
        ], true);
    }
}
