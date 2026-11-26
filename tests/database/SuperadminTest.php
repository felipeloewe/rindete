<?php

declare(strict_types=1);

use App\Libraries\Incorporacion;
use App\Libraries\PersonaServicio;
use App\Libraries\Planes;
use App\Libraries\TableroMaestro;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class SuperadminTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testLaClaveInicialSeCambiaAntesDeVerLasEmpresas(): void
    {
        $pagina = $this->get('master');
        $pagina->assertOK();
        $pagina->assertSee('Superadmin');
        $pagina->assertSee('Usuario');

        $mal = $this->postForm('master', ['usuario' => 'Master', 'password' => 'otra']);
        $mal->assertRedirectTo(site_url('master'));
        $mal->assertSessionHas('error', 'Usuario o contraseña incorrectos.');

        $entra = $this->postForm('master', ['usuario' => 'Master', 'password' => 'demo']);
        $entra->assertRedirectTo(site_url('master/clave'));
        $maestro = db_connect()->table('maestros')->where('usuario', 'Master')->get()->getRowArray();
        $this->withSession(['maestro_id' => $maestro['id']]);
        $corto = $this->postForm('master/clave', ['actual' => 'demo', 'password' => 'demo', 'password2' => 'demo']);
        $corto->assertSessionHas('errores');

        $cambio = $this->postForm('master/clave', [
            'actual'    => 'demo',
            'password'  => 'clave-operacion',
            'password2' => 'clave-operacion',
        ]);
        $cambio->assertRedirectTo(site_url('master'));
        $fila = db_connect()->table('maestros')->where('usuario', 'Master')->get()->getRowArray();
        $this->assertSame(0, (int) $fila['debe_cambiar']);
        $this->assertTrue(password_verify('clave-operacion', (string) $fila['password']));

        $panel = $this->get('master');
        $panel->assertOK();
        $panel->assertSee('Activas');
        $panel->assertSee('Plan Free');
        $panel->assertSee('Contratación mensual');
        $panel->assertSee('Contratación anual');
        $panel->assertSee('CLP 0');
    }

    public function testCreaElPlanFreeYElDeCobroYSoloCuentaLasActivas(): void
    {
        $maestro = db_connect()->table('maestros')->where('usuario', 'Master')->get()->getRowArray();
        db_connect()->table('maestros')->where('id', $maestro['id'])->update(['debe_cambiar' => 0]);
        $this->withSession(['maestro_id' => $maestro['id']]);

        $libre = $this->postForm('master/empresas', $this->empresa('free', [
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'email'     => 'ana@acme.test',
        ]));
        $libre->assertSessionHas('ok', 'Empresa creada.');
        $avisos = session()->getFlashdata('avisos');
        $this->assertIsArray($avisos);
        $this->assertStringContainsString('ana@acme.test', $avisos[0]);
        $this->assertStringContainsString('Clave inicial', $avisos[0]);

        $mes = $this->postForm('master/empresas', $this->empresa('cobro', [
            'empresa'            => 'Norte Ltda',
            'id_fiscal'          => '11.111.111-1',
            'email'              => 'luz@norte.test',
            'cobro_plan'         => 'Plan Empresa',
            'cobro_monto'        => '50000',
            'cobro_periodicidad' => 'mensual',
            'cupo_usuarios'      => '8',
        ]));
        $mes->assertSessionHas('ok', 'Empresa creada.');
        $anio = $this->postForm('master/empresas', $this->empresa('cobro', [
            'empresa'            => 'Sur SpA',
            'id_fiscal'          => '22.222.222-2',
            'email'              => 'leo@sur.test',
            'cobro_plan'         => 'Plan Anual',
            'cobro_monto'        => '600000',
            'cobro_periodicidad' => 'anual',
            'cupo_usuarios'      => '20',
        ]));
        $anio->assertSessionHas('ok', 'Empresa creada.');

        $panel = $this->get('master');
        $panel->assertOK();
        $panel->assertSee('Acme SpA');
        $panel->assertSee('Norte Ltda');
        $panel->assertSee('Plan Empresa');
        $panel->assertSee('Plan Anual');
        $panel->assertSee('CLP 50.000');
        $panel->assertSee('CLP 600.000');
        $panel->assertSee('Mensual');
        $panel->assertSee('Anual');
        $panel->assertSee('ana@acme.test');
        $panel->assertSee('Usuarios registrados');
        $panel->assertSee('Solo administrador');
        $panel->assertSee('Marcar como usuario rendidor');
        $panel->assertSee('0 / 4');

        $norte = model(EmpresaModel::class)->porFiscal('CL', '11111111-1');
        $this->assertSame('cobro', $norte['plan']);
        $this->assertSame(8, (int) $norte['cupo_usuarios']);
        $pausa = $this->postForm('master/empresas/' . $norte['id'] . '/vigencia', ['vigente' => '0']);
        $pausa->assertSessionHas('ok', 'Empresa en pausa. Sale del tablero de activas.');
        $luego = $this->get('master');
        $luego->assertSee('En pausa');
        $luego->assertSee('CLP 600.000');
        $tablero = (new \App\Libraries\TableroMaestro())->armar();
        $this->assertSame(2, $tablero['activas']);
        $this->assertSame(0.0, $tablero['mensual'][0]['monto']);
        $this->assertSame(600000.0, $tablero['anual'][0]['monto']);

        model(EmpresaModel::class)->update($norte['id'], ['cupo_usuarios' => 1]);
        $otra = (new Incorporacion())->administrador((int) $norte['id'], 'Otra', 'otra@norte.test');
        $this->assertTrue($otra['ok'], implode(' ', $otra['errores']));
        $luz = model(UsuarioModel::class)->porEmail('luz@norte.test');
        $marca = (new Incorporacion())->marcarRendidor((int) $luz['id'], true);
        $this->assertTrue($marca['ok'], implode(' ', $marca['errores']));
        $segunda = model(UsuarioModel::class)->porEmail('otra@norte.test');
        $llena = (new Incorporacion())->marcarRendidor((int) $segunda['id'], true);
        $this->assertFalse($llena['ok']);
        $this->assertSame('El plan admite 1 usuario.', $llena['errores'][0]);
        $admin = model(UsuarioModel::class)->porEmail('luz@norte.test');
        $this->assertSame(1, (int) $admin['clave_inicial']);
        $this->assertSame(1, (int) $admin['rinde']);
        $this->assertSame('', (string) ($admin['politica_version'] ?? ''));
    }

    public function testDistingueAlAdminQueTambienRinde(): void
    {
        $alta = (new Incorporacion())->abrir([
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'plan'      => 'free',
        ]);
        $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa = model(EmpresaModel::class)->find((int) $ana['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('empresa_id', (int) $empresa['id'])->first();
        $solo = (new TableroMaestro())->armar();
        $this->assertSame('Solo administrador', $solo['personas'][0]['papel']);
        $this->assertSame('0 / 4', $solo['empresas'][0]['cupo']);

        model(GastoModel::class)->insert([
            'empresa_id'   => (int) $empresa['id'],
            'usuario_id'   => (int) $ana['id'],
            'categoria_id' => (int) $categoria['id'],
            'concepto'     => 'Taxi',
            'proveedor'    => 'Uber',
            'fecha'        => '2026-11-01',
            'monto'        => 1000,
            'moneda'       => 'CLP',
            'estado'       => 'borrador',
            'creado'       => date('Y-m-d H:i:s'),
        ]);
        model(UsuarioModel::class)->insert([
            'empresa_id' => (int) $empresa['id'],
            'nombre'     => 'Luis Soto',
            'email'      => 'luis@acme.test',
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ]);

        $papeles = [];
        foreach ((new TableroMaestro())->armar()['personas'] as $persona) {
            $papeles[$persona['email']] = $persona['papel'];
        }
        $this->assertSame('Solo administrador', $papeles['ana@acme.test']);
        $this->assertSame('Usuario', $papeles['luis@acme.test']);
        $marcado = (new Incorporacion())->marcarRendidor((int) $ana['id'], true);
        $this->assertTrue($marcado['ok'], implode(' ', $marcado['errores']));
        $papeles = [];
        $luego = (new TableroMaestro())->armar();
        foreach ($luego['personas'] as $persona) {
            $papeles[$persona['email']] = $persona['papel'];
        }
        $this->assertSame('Administrador y usuario', $papeles['ana@acme.test']);
        $this->assertSame('2 / 4', $luego['empresas'][0]['cupo']);
        $this->assertSame('Acme SpA', $luego['personas'][1]['empresa']);
    }

    public function testElPlanFreeAdmiteCuatroRendidoresAdemasDelAdmin(): void
    {
        $alta = (new Incorporacion())->abrir([
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'plan'      => 'free',
        ]);
        $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $this->assertSame(0, (int) $ana['rinde']);
        $empresa = model(EmpresaModel::class)->find((int) $ana['empresa_id']);
        $servicio = new PersonaServicio();
        foreach (['luis@acme.test', 'marta@acme.test', 'nora@acme.test', 'quinta@acme.test'] as $correo) {
            $creada = $servicio->crear($empresa, [
                'nombre'          => 'Persona ' . $correo,
                'email'           => $correo,
                'password'        => 'clave-segura',
                'acepta_politica' => '1',
            ]);
            $this->assertTrue($creada['ok'], implode(' ', $creada['errores']));
        }
        $this->assertSame(4, Planes::rendidores((int) $empresa['id']));
        $llena = (new Incorporacion())->marcarRendidor((int) $ana['id'], true);
        $this->assertFalse($llena['ok']);
        $this->assertSame('El plan Free admite 4 usuarios.', $llena['errores'][0]);
        $gasto = (new \App\Libraries\GastoServicio())->crear($ana, $empresa, [
            'concepto' => 'Taxi',
            'monto'    => '1000',
            'fecha'    => '2026-11-01',
        ], null);
        $this->assertFalse($gasto['ok']);
        $this->assertSame(Planes::SIN_MARCA, $gasto['errores'][0]);
    }

    public function testElAdministradorCambiaLaClaveInicialAntesDeEntrar(): void
    {
        $alta = (new Incorporacion())->abrir([
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'plan'      => 'free',
        ]);
        $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        $login = $this->postForm('login', ['email' => 'ana@acme.test', 'password' => $alta['clave']]);
        $login->assertRedirectTo(site_url('cuenta'));
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $this->withSession(['usuario_id' => $ana['id']]);
        $panel = $this->get('panel');
        $panel->assertRedirectTo(site_url('cuenta'));
        $cuenta = $this->get('cuenta');
        $cuenta->assertOK();
        $cuenta->assertSee('Esta es la contraseña inicial.');
        $guarda = $this->postForm('cuenta', [
            'nombre'   => 'Ana Pérez',
            'email'    => 'ana@acme.test',
            'password' => 'clave-segura',
        ]);
        $guarda->assertRedirectTo(site_url('cuenta'));
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $this->assertSame(0, (int) $ana['clave_inicial']);
        $this->assertTrue(password_verify('clave-segura', (string) $ana['password']));
        $politica = $this->get('panel');
        $politica->assertRedirectTo(site_url('politicas/aceptar'));
    }

    /**
     * @param array<string, string> $extra
     *
     * @return array<string, string>
     */
    private function empresa(string $plan, array $extra): array
    {
        return array_merge([
            'pais'               => 'CL',
            'empresa'            => 'Acme SpA',
            'id_fiscal'          => '12.345.678-5',
            'plan'               => $plan,
            'nombre'             => 'Ana Pérez',
            'email'              => 'ana@acme.test',
            'cobro_plan'         => '',
            'cobro_monto'        => '',
            'cobro_periodicidad' => '',
            'cupo_usuarios'      => '',
        ], $extra);
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
