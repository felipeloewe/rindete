<?php

declare(strict_types=1);

use App\Libraries\GastoServicio;
use App\Libraries\InformeServicio;
use App\Libraries\RendicionServicio;
use App\Libraries\SaldoRendicion;
use App\Models\AceptadorModel;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class RendicionesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testSoloEntraUnGastoLegalizadoYEnviarLoCierra(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $servicio  = new RendicionServicio();
        $creada    = $servicio->crear($usuario, 'Viaje de noviembre', 'Cliente en Santiago');
        $rendicion = model(RendicionModel::class)->find($creada['id']);

        $observado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Cena',
            'proveedor'    => 'Local Norte',
            'fecha'        => '2026-11-09',
            'monto'        => '80000',
            'categoria_id' => $categoria['id'],
        ], null);
        $legal = $this->legalizado($usuario, $empresa, $categoria);

        $this->assertFalse($servicio->agregar($rendicion, $observado['gasto'])['ok']);
        $this->assertTrue($servicio->agregar($rendicion, $legal['gasto'])['ok']);
        $gasto = model(GastoModel::class)->find($legal['gasto']['id']);
        $this->assertSame('en_rendicion', $gasto['estado']);
        $this->assertSame((int) $rendicion['id'], (int) $gasto['rendicion_id']);

        $this->withSession(['usuario_id' => $usuario['id']]);
        $pedido = $servicio->enviar(model(RendicionModel::class)->find($rendicion['id']));
        $this->assertTrue($pedido['ok']);
        $this->assertSame('negativo', $pedido['caso']);
        $this->assertSame('cierre_pedido', model(RendicionModel::class)->find($rendicion['id'])['estado']);
        $this->assertFalse($servicio->agregar(model(RendicionModel::class)->find($rendicion['id']), $legal['gasto'])['ok']);

        $pagina = $this->get('rendiciones/' . $rendicion['id']);
        $pagina->assertOK();
        $pagina->assertSee('Viaje de noviembre');
        $pagina->assertSee('Cierre pedido');
        $pagina->assertSee('Aceptar cierre');

        $this->postForm('rendiciones/' . $rendicion['id'] . '/aceptar', []);
        $cerrada = $this->get('rendiciones/' . $rendicion['id']);
        $cerrada->assertOK();
        $cerrada->assertSee('Cerrada');
        $cerrada->assertDontSee('Aceptar cierre');
        $this->assertSame('cerrada', model(RendicionModel::class)->find($rendicion['id'])['estado']);
    }

    public function testElSaldoPositivoAbreOtraYElCeroCierra(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario  = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa  = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $servicio = new RendicionServicio();
        $fondo    = $this->fondo($usuario, 20000);
        $creada   = $servicio->crear($usuario, 'Viaje con fondo', '', (int) $fondo['id']);
        $this->assertTrue($creada['ok']);
        $rendicion = model(RendicionModel::class)->find($creada['id']);
        $this->assertEquals(20000, (float) $rendicion['monto_por_rendir']);
        $legal = $this->legalizado($usuario, $empresa, model(CategoriaModel::class)->find($this->categoria($usuario)));
        $this->assertTrue($servicio->agregar($rendicion, $legal['gasto'])['ok']);

        $cerrado = $servicio->enviar($rendicion, '1500');
        $this->assertTrue($cerrado['ok']);
        $this->assertSame('positivo', $cerrado['caso']);
        $this->assertSame('cerrada', model(RendicionModel::class)->find($rendicion['id'])['estado']);
        $this->assertEquals(20000, (float) model(FondoModel::class)->find($fondo['id'])['saldo']);
        $nueva = model(RendicionModel::class)->find($cerrado['nueva']);
        $this->assertSame('traspaso', $nueva['origen']);
        $this->assertSame('abierta', $nueva['estado']);
        $this->assertEquals(9500, (float) $nueva['monto_por_rendir']);
        $this->assertEquals(1500, (float) $nueva['monto_adicional']);
        $this->assertSame((int) $rendicion['id'], (int) $nueva['rendicion_previa_id']);

        $this->assertTrue((new SaldoRendicion())->sumar($nueva, '500')['ok']);
        $nueva = model(RendicionModel::class)->find($nueva['id']);
        $this->assertEquals(10000, (float) $nueva['monto_por_rendir']);

        $cuadrado = $this->fondo($usuario, 12000);
        $directa  = $servicio->crear($usuario, 'Cuadrada', '', (int) $cuadrado['id']);
        $segunda  = model(RendicionModel::class)->find($directa['id']);
        $otro     = $this->legalizado($usuario, $empresa, model(CategoriaModel::class)->find($this->categoria($usuario)), '101', '2026-11-20');
        $this->assertTrue($servicio->agregar($segunda, $otro['gasto'])['ok']);
        $cierre = $servicio->enviar($segunda);
        $this->assertSame('cero', $cierre['caso']);
        $this->assertSame(0, $cierre['nueva']);
        $this->assertSame('cerrada', model(RendicionModel::class)->find($segunda['id'])['estado']);
        $this->assertNull(model(RendicionModel::class)->where('rendicion_previa_id', $segunda['id'])->first());

        $cartola = (new InformeServicio())->cartola((int) $empresa['id'], 2026, 11, null);
        $this->assertEquals(32000, $cartola['por_rendir']);
        $this->assertEquals(10000, $cartola['por_rendir_abiertas']);

        $this->withSession(['usuario_id' => $usuario['id']]);
        $pagina = $this->get('rendiciones/' . $nueva['id']);
        $pagina->assertOK();
        $pagina->assertSee('Saldo de Viaje con fondo');
        $pagina->assertSee('CLP 10.000');
        $pagina->assertSee('Abierta');
    }

    public function testElCierreNegativoRecorreElFlujoYSePuedeRechazar(): void
    {
        $this->postForm('registro', $this->cuenta());
        $ana     = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);
        $luis    = model(UsuarioModel::class)->insert([
            'empresa_id' => $ana['empresa_id'],
            'nombre'     => 'Luis Soto',
            'email'      => 'luis@acme.test',
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ], true);
        $luis = model(UsuarioModel::class)->find($luis);
        model(AceptadorModel::class)->insert([
            'empresa_id' => $ana['empresa_id'],
            'orden'      => 1,
            'usuario_id' => $luis['id'],
        ]);
        model(AceptadorModel::class)->insert([
            'empresa_id' => $ana['empresa_id'],
            'orden'      => 2,
            'usuario_id' => $ana['id'],
        ]);

        $servicio  = new RendicionServicio();
        $saldos    = new SaldoRendicion();
        $creada    = $servicio->crear($ana, 'Con exceso', '');
        $rendicion = model(RendicionModel::class)->find($creada['id']);
        $legal     = $this->legalizado($ana, $empresa, model(CategoriaModel::class)->find($this->categoria($ana)));
        $servicio->agregar($rendicion, $legal['gasto']);
        $this->assertSame('negativo', $servicio->enviar($rendicion)['caso']);
        $rendicion = model(RendicionModel::class)->find($rendicion['id']);
        $this->assertSame((int) $luis['id'], (int) $rendicion['turno_usuario_id']);
        $this->assertFalse($saldos->aceptar($rendicion, $ana)['ok']);
        $this->assertTrue($saldos->aceptar($rendicion, $luis)['ok']);
        $rendicion = model(RendicionModel::class)->find($rendicion['id']);
        $this->assertSame('cierre_pedido', $rendicion['estado']);
        $this->assertSame((int) $ana['id'], (int) $rendicion['turno_usuario_id']);
        $this->assertTrue($saldos->aceptar($rendicion, $ana)['ok']);
        $this->assertSame('cerrada', model(RendicionModel::class)->find($rendicion['id'])['estado']);

        $otra    = model(RendicionModel::class)->find($servicio->crear($ana, 'Para rechazar', '')['id']);
        $gasto   = $this->legalizado($ana, $empresa, model(CategoriaModel::class)->find($this->categoria($ana)), '202', '2026-11-20');
        $servicio->agregar($otra, $gasto['gasto']);
        $servicio->enviar($otra);
        $otra = model(RendicionModel::class)->find($otra['id']);
        $this->assertTrue($saldos->rechazar($otra, $luis, 'Falta la boleta')['ok']);
        $otra = model(RendicionModel::class)->find($otra['id']);
        $this->assertSame('abierta', $otra['estado']);
        $this->assertNull($otra['turno_usuario_id']);
    }

    public function testQuitarDevuelveElGastoALegalizado(): void
    {
        $this->postForm('registro', $this->cuenta());
        $usuario   = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa   = model(EmpresaModel::class)->find($usuario['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $servicio  = new RendicionServicio();
        $creada    = $servicio->crear($usuario, 'Corta', '');
        $rendicion = model(RendicionModel::class)->find($creada['id']);
        $legal     = $this->legalizado($usuario, $empresa, $categoria);
        $servicio->agregar($rendicion, $legal['gasto']);

        $this->assertTrue($servicio->quitar($rendicion, model(GastoModel::class)->find($legal['gasto']['id']))['ok']);
        $gasto = model(GastoModel::class)->find($legal['gasto']['id']);
        $this->assertSame('legalizado', $gasto['estado']);
        $this->assertNull($gasto['rendicion_id']);
        $this->assertFalse($servicio->enviar($rendicion)['ok']);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $empresa
     * @param array<string, mixed> $categoria
     *
     * @return array{ok: bool, errores: list<string>, gasto?: array<string, mixed>}
     */
    private function legalizado(array $usuario, array $empresa, array $categoria, string $numero = '100', string $fecha = '2026-11-10'): array
    {
        $ruta = WRITEPATH . 'rendicion-prueba.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
        $resultado = (new GastoServicio())->crear($usuario, $empresa, [
            'concepto'     => 'Taxi',
            'proveedor'    => 'Transporte Sur',
            'fecha'        => $fecha,
            'monto'        => '12000',
            'categoria_id' => $categoria['id'],
            'tipo_doc'     => 'Boleta',
            'id_fiscal'    => '76.111.222-3',
            'num_doc'      => $numero,
        ], $archivo);
        @unlink($ruta);

        return $resultado;
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

    /**
     * @return array<string, string>
     */
    /**
     * @param array<string, mixed> $usuario
     */
    private function categoria(array $usuario): int
    {
        return (int) model(CategoriaModel::class)->where('empresa_id', $usuario['empresa_id'])->where('nombre', 'Alimentación')->first()['id'];
    }

    /**
     * @param array<string, mixed> $usuario
     *
     * @return array<string, mixed>
     */
    private function fondo(array $usuario, float $monto): array
    {
        $db = db_connect();
        $db->table('solicitudes')->insert([
            'empresa_id'       => $usuario['empresa_id'],
            'usuario_id'       => $usuario['id'],
            'categoria_id'     => $this->categoria($usuario),
            'motivo'           => 'Viaje',
            'monto'            => $monto,
            'moneda'           => 'CLP',
            'estado'           => 'entregada',
            'detalle'          => '',
            'turno_usuario_id' => null,
            'turno_orden'      => 0,
            'creada'           => date('Y-m-d H:i:s'),
            'resuelta'         => date('Y-m-d H:i:s'),
            'entregada'        => date('Y-m-d H:i:s'),
        ]);
        $id = model(FondoModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'solicitud_id' => (int) $db->insertID(),
            'motivo'       => 'Viaje',
            'monto'        => $monto,
            'saldo'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => 'abierto',
            'creado'       => date('Y-m-d H:i:s'),
        ], true);

        return model(FondoModel::class)->find($id);
    }

    private function cuenta(): array
    {
        return [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
        ];
    }
}
