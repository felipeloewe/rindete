<?php

declare(strict_types=1);

use App\Libraries\Modulos;
use App\Libraries\PersonaServicio;
use App\Libraries\PreAsiento;
use App\Libraries\PreAsientoChile;
use App\Models\CategoriaModel;
use App\Models\CuentaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class PreAsientosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testChileAgrupaPorMesOPorPersonaYNuncaPorRendicion(): void
    {
        [$ana, $empresa, $alimentacion, $transporte] = $this->base();
        $luis = (new PersonaServicio())->crear($empresa, [
            'nombre'    => 'Luis Soto',
            'email'     => 'luis@acme.test',
            'password'  => 'clave-segura',
            'id_fiscal' => '22.222.222-2',
        ]);
        $this->assertTrue($luis['ok'], implode(' ', $luis['errores']));
        $malo = (new PersonaServicio())->guardar($ana, $ana, ['id_fiscal' => '1', 'activo' => '1']);
        $this->assertFalse($malo['ok']);
        $this->assertStringContainsString('RUT', implode(' ', $malo['errores']));
        (new PersonaServicio())->guardar($ana, $ana, ['id_fiscal' => '12.345.678-5', 'activo' => '1']);

        $cuentaAlim = $this->cuenta($empresa, '5101', 'Alimentación');
        $cuentaTrans = $this->cuenta($empresa, '5102', 'Transporte');
        $haber = $this->cuenta($empresa, '1105', 'Fondos por rendir');
        $banco = $this->cuenta($empresa, '1101', 'Banco');
        $db = db_connect();
        $db->table('categoria_cuentas')->insert(['categoria_id' => $alimentacion['id'], 'cuenta_id' => $cuentaAlim]);
        $db->table('categoria_cuentas')->insert(['categoria_id' => $transporte['id'], 'cuenta_id' => $cuentaTrans]);

        $primera = $this->rendir($ana, $alimentacion, 'Almuerzo', 5000, '2026-11-04');
        $segunda = $this->rendir($ana, $transporte, 'Taxi', 3000, '2026-11-18');
        $this->rendir(model(UsuarioModel::class)->find($luis['id']), $alimentacion, 'Cena', 7000, '2026-11-20');
        $suelto = model(GastoModel::class)->insert([
            'empresa_id'   => $empresa['id'],
            'usuario_id'   => $ana['id'],
            'categoria_id' => $alimentacion['id'],
            'concepto'     => 'Gasto suelto',
            'proveedor'    => 'Local',
            'fecha'        => '2026-11-12',
            'monto'        => 9000,
            'moneda'       => 'CLP',
            'estado'       => 'legalizado',
            'creado'       => '2026-11-12 12:00:00',
        ], true);
        PreAsiento::sincronizar(model(GastoModel::class)->find($suelto));
        $sinCuenta = $this->rendir($ana, model(CategoriaModel::class)->where('nombre', 'Alojamiento')->first(), 'Hotel', 4000, '2026-11-21');

        $empresa['preasiento_agrupacion'] = 'mes';
        $empresa['preasiento_auxiliar'] = 0;
        $empresa['preasiento_cuenta_id'] = $haber;
        $empresa['preasiento_salida_id'] = $banco;
        $armada = (new PreAsientoChile())->armar($empresa, 2026, 11);
        $this->assertTrue($armada['ok'], implode(' ', $armada['errores']));
        $this->assertStringContainsString('Hotel', implode(' ', $armada['avisos']));
        $comprobantes = (new PreAsientoChile())->leer((int) $empresa['id'], 2026, 11);
        $this->assertCount(1, $comprobantes);
        $this->assertSame('traspaso', $comprobantes[0]['tipo']);
        $this->assertSame('2026-11-30', $comprobantes[0]['fecha']);
        $this->assertEquals(15000, $comprobantes[0]['debe']);
        $this->assertEquals(15000, $comprobantes[0]['haber']);
        $debe = [];
        foreach ($comprobantes[0]['lineas'] as $linea) {
            if ((float) $linea['debe'] > 0) {
                $debe[$linea['codigo']] = (float) $linea['debe'];
                $this->assertSame('', $linea['auxiliar']);
            }
        }
        $this->assertEquals(12000, $debe['5101']);
        $this->assertEquals(3000, $debe['5102']);
        $this->assertNotSame((int) $primera, (int) $segunda);

        $empresa['preasiento_agrupacion'] = 'usuario';
        $empresa['preasiento_auxiliar'] = 1;
        $porPersona = (new PreAsientoChile())->armar($empresa, 2026, 11);
        $this->assertTrue($porPersona['ok']);
        $vouchers = (new PreAsientoChile())->leer((int) $empresa['id'], 2026, 11);
        $this->assertCount(2, $vouchers);
        $porUsuario = [];
        foreach ($vouchers as $voucher) {
            $porUsuario[(int) $voucher['usuario_id']] = $voucher;
        }
        $this->assertEquals(8000, $porUsuario[(int) $ana['id']]['haber']);
        $this->assertEquals(7000, $porUsuario[(int) $luis['id']]['haber']);
        $auxiliaresAna = array_unique(array_column($porUsuario[(int) $ana['id']]['lineas'], 'auxiliar'));
        $this->assertSame(['12345678-5'], array_values($auxiliaresAna));
        $auxiliaresLuis = array_unique(array_column($porUsuario[(int) $luis['id']]['lineas'], 'auxiliar'));
        $this->assertSame(['22222222-2'], array_values($auxiliaresLuis));

        model(UsuarioModel::class)->update($luis['id'], ['id_fiscal' => null]);
        $empresa['preasiento_agrupacion'] = 'mes';
        $empresa['preasiento_auxiliar'] = 1;
        $conRut = (new PreAsientoChile())->armar($empresa, 2026, 11);
        $this->assertTrue($conRut['ok']);
        $this->assertStringContainsString('Luis Soto', implode(' ', $conRut['avisos']));
        $unico = (new PreAsientoChile())->leer((int) $empresa['id'], 2026, 11);
        $this->assertCount(1, $unico);
        $haberRut = [];
        foreach ($unico[0]['lineas'] as $linea) {
            if ((float) $linea['debe'] > 0) {
                $this->assertSame('', $linea['auxiliar']);
            } else {
                $haberRut[] = $linea['auxiliar'];
            }
        }
        sort($haberRut);
        $this->assertSame(['', '12345678-5'], $haberRut);

        $empresa['pais'] = 'CO';
        $ajena = (new PreAsientoChile())->armar($empresa, 2026, 11);
        $this->assertFalse($ajena['ok']);
        $this->assertStringContainsString('Chile', $ajena['errores'][0]);
    }

    public function testElAdminArmaElPreasientoDesdeInformes(): void
    {
        [$ana, $empresa, $alimentacion] = $this->base();
        $cuenta = $this->cuenta($empresa, '5101', 'Alimentación');
        $haber = $this->cuenta($empresa, '1105', 'Fondos por rendir');
        $banco = $this->cuenta($empresa, '1101', 'Banco');
        db_connect()->table('categoria_cuentas')->insert([
            'categoria_id' => $alimentacion['id'],
            'cuenta_id'    => $cuenta,
        ]);
        $this->rendir($ana, $alimentacion, 'Almuerzo', 5000, '2026-11-04');
        model(EmpresaModel::class)->update($empresa['id'], [
            'preasiento_agrupacion' => 'mes',
            'preasiento_auxiliar'   => 0,
            'preasiento_cuenta_id'  => $haber,
            'preasiento_salida_id'  => $banco,
        ]);

        $this->withSession(['usuario_id' => $ana['id']]);
        $cerrado = $this->get('informes/preasientos?anio=2026&mes=11');
        $cerrado->assertOK();
        $cerrado->assertSee('no está contratado');
        $cerrado->assertDontSee('Armar preasientos');
        $asientos = $this->get('informes/asientos?anio=2026&mes=11');
        $asientos->assertSee('no está contratado');

        \App\Libraries\Modulos::contratar((int) $empresa['id'], 'preasientos');
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/preasientos?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('Armar preasientos');

        $seguridad = service('security');
        $armada = $this->withSession(['usuario_id' => $ana['id']])->post('informes/preasientos', [
            'anio' => '2026',
            'mes'  => '11',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $armada->assertRedirectTo(site_url('informes/preasientos?anio=2026&mes=11'));
        $vista = $this->withSession(['usuario_id' => $ana['id']])->get('informes/preasientos?anio=2026&mes=11');
        $vista->assertOK();
        $vista->assertSee('Cuadra');
        $vista->assertSee('5101');
    }

    public function testLaSalidaNaceElFondoYLaRendicionLoPasaAlGasto(): void
    {
        [$ana, $empresa, $alimentacion] = $this->base();
        $luis = (new PersonaServicio())->crear($empresa, [
            'nombre'   => 'Luis Soto',
            'email'    => 'luis@acme.test',
            'password' => 'clave-segura',
        ]);
        $luis = model(UsuarioModel::class)->find($luis['id']);
        $gasto = $this->cuenta($empresa, '5101', 'Alimentación');
        $fondo = $this->cuenta($empresa, '2101', 'Fondos por rendir');
        $banco = $this->cuenta($empresa, '1101', 'Banco');
        db_connect()->table('categoria_cuentas')->insert([
            'categoria_id' => $alimentacion['id'],
            'cuenta_id'    => $gasto,
        ]);
        $this->fondo($ana, 5000, '2026-11-02 10:00:00');
        $this->fondo($ana, 7000, '2026-11-08 10:00:00');
        $this->fondo($luis, 4000, '2026-11-10 10:00:00');
        $this->fondo($ana, 9000, '2026-10-20 10:00:00');
        $this->rendir($ana, $alimentacion, 'Almuerzo', 6000, '2026-11-12');
        $empresa['preasiento_agrupacion'] = 'mes';
        $empresa['preasiento_auxiliar'] = 0;
        $empresa['preasiento_cuenta_id'] = $fondo;
        $empresa['preasiento_salida_id'] = $banco;

        $armada = (new PreAsientoChile())->armar($empresa, 2026, 11);
        $this->assertTrue($armada['ok'], implode(' ', $armada['errores']));
        $comprobantes = (new PreAsientoChile())->leer((int) $empresa['id'], 2026, 11);
        $this->assertCount(2, $comprobantes);
        $this->assertSame('salida', $comprobantes[0]['tipo']);
        $this->assertSame('traspaso', $comprobantes[1]['tipo']);
        $this->assertEquals(16000, $comprobantes[0]['debe']);
        $this->assertEquals(16000, $comprobantes[0]['haber']);
        $this->assertEquals(6000, $comprobantes[1]['haber']);

        $empresa['preasiento_agrupacion'] = 'usuario';
        (new PreAsientoChile())->armar($empresa, 2026, 11);
        $porPersona = (new PreAsientoChile())->leer((int) $empresa['id'], 2026, 11);
        $salidas = array_values(array_filter($porPersona, static fn (array $fila): bool => $fila['tipo'] === 'salida'));
        $this->assertCount(2, $salidas);
        $montos = [];
        foreach ($salidas as $salida) {
            $montos[(int) $salida['usuario_id']] = (float) $salida['debe'];
        }
        $this->assertEquals(12000, $montos[(int) $ana['id']]);
        $this->assertEquals(4000, $montos[(int) $luis['id']]);

        $empresa['preasiento_agrupacion'] = 'mes';
        (new PreAsientoChile())->armar($empresa, 2026, 11);
        Modulos::contratar((int) $empresa['id'], 'cuentas');
        $mayor = (new PreAsientoChile())->mayor((int) $empresa['id'], 2026, 11);
        $saldos = [];
        foreach ($mayor['cuentas'] as $cuenta) {
            $saldos[$cuenta['codigo']] = $cuenta;
        }
        $this->assertEquals(16000, $saldos['2101']['debe']);
        $this->assertEquals(6000, $saldos['2101']['haber']);
        $this->assertEquals(10000, $saldos['2101']['saldo']);
        $this->assertEquals(16000, $saldos['1101']['haber']);
        $this->assertEquals(6000, $saldos['5101']['debe']);

        $this->withSession(['usuario_id' => $ana['id']]);
        $seguridad = service('security');
        $alta = $this->post('modulos/contratar', [
            'codigo' => 'asientos',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $alta->assertRedirectTo(site_url('modulos'));
        $this->assertTrue(Modulos::activo($empresa, 'asientos'));
        $lista = $this->withSession(['usuario_id' => $ana['id']])->get('modulos');
        $lista->assertOK();
        $lista->assertSee('Contratado');
        $lista->assertSee('Incluidos');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>, 3: array<string, mixed>}
     */
    private function base(): array
    {
        $seguridad = service('security');
        $this->post('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $ana = model(UsuarioModel::class)->where('email', 'ana@acme.test')->first();
        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);

        return [
            $ana,
            $empresa,
            model(CategoriaModel::class)->where('nombre', 'Alimentación')->first(),
            model(CategoriaModel::class)->where('nombre', 'Transporte')->first(),
        ];
    }

    /**
     * @param array<string, mixed> $empresa
     */
    private function cuenta(array $empresa, string $codigo, string $nombre): int
    {
        return (int) model(CuentaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'clase'      => 'generica',
            'codigo'     => $codigo,
            'nombre'     => $nombre,
            'concepto'   => '',
            'usuario_id' => null,
            'activa'     => 1,
        ], true);
    }

    /**
     * @param array<string, mixed> $usuario
     */
    private function fondo(array $usuario, float $monto, string $creado): void
    {
        $solicitud = model(SolicitudModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'categoria_id' => model(CategoriaModel::class)->where('empresa_id', $usuario['empresa_id'])->first()['id'],
            'motivo'       => 'Fondo',
            'monto'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => 'entregada',
            'detalle'      => '',
            'turno_orden'  => 0,
            'creada'       => $creado,
            'resuelta'     => $creado,
            'entregada'    => $creado,
        ], true);
        model(FondoModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'solicitud_id' => $solicitud,
            'motivo'       => 'Fondo',
            'monto'        => $monto,
            'saldo'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => 'abierto',
            'creado'       => $creado,
        ]);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     */
    private function rendir(array $usuario, array $categoria, string $concepto, float $monto, string $fecha): int
    {
        $gastoId = (int) model(GastoModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'categoria_id' => $categoria['id'],
            'concepto'     => $concepto,
            'proveedor'    => 'Local',
            'fecha'        => $fecha,
            'monto'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => 'legalizado',
            'creado'       => $fecha . ' 12:00:00',
        ], true);
        $rendicionId = (int) model(RendicionModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'titulo'       => $concepto,
            'nota'         => '',
            'estado'       => 'enviada',
            'creada'       => $fecha . ' 13:00:00',
            'enviada'      => $fecha . ' 13:00:00',
            'origen'       => 'directa',
            'monto_maximo' => 0,
        ], true);
        model(GastoModel::class)->update($gastoId, [
            'rendicion_id' => $rendicionId,
            'estado'       => 'en_rendicion',
        ]);
        PreAsiento::sincronizar(model(GastoModel::class)->find($gastoId));

        return $rendicionId;
    }
}
