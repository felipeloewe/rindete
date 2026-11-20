<?php

declare(strict_types=1);

use App\Libraries\InformeServicio;
use App\Libraries\PersonaServicio;
use App\Libraries\PreAsiento;
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
final class InformesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testLaCartolaRestaLoRendidoYElAdminVeATodos(): void
    {
        [$ana, $empresa, $alimentacion] = $this->base();
        $luis = $this->persona($empresa, 'Luis Soto', 'luis@acme.test');
        $this->fondo($ana, $alimentacion, 'Pasajes', 12000, '2026-11-02 10:00:00');
        $cuenta = (int) model(CuentaModel::class)->insert([
            'empresa_id' => $empresa['id'],
            'clase'      => 'generica',
            'codigo'     => '5101',
            'nombre'     => 'Alimentación',
            'concepto'   => '',
            'usuario_id' => null,
            'activa'     => 1,
        ], true);
        db_connect()->table('categoria_cuentas')->insert([
            'categoria_id' => $alimentacion['id'],
            'cuenta_id'    => $cuenta,
        ]);
        $legal = $this->gasto($ana, $alimentacion, 'Taxi', 5000, '2026-11-10', 'legalizado');
        PreAsiento::sincronizar($legal);
        $this->gasto($ana, $alimentacion, 'Cena observada', 9000, '2026-11-11', 'observado');
        $this->gasto($luis, $alimentacion, 'Almuerzo', 3000, '2026-12-04', 'legalizado');
        $this->rendicion($ana, 'Viaje de Ana', '2026-11-12 09:00:00', (int) $legal['id']);
        $rendicionLuis = $this->rendicion($luis, 'Viaje de Luis', '2026-11-15 09:00:00', 0);

        $informe = new InformeServicio();
        $cartola = $informe->cartola((int) $empresa['id'], 2026, 11, null);
        $this->assertEquals(12000, $cartola['entregado']);
        $this->assertEquals(5000, $cartola['rendido']);
        $this->assertEquals(7000, $cartola['saldo']);
        $this->assertEquals(12000, $cartola['por_rendir']);
        $this->assertSame('Fondo Pasajes', $cartola['movimientos'][0]['detalle']);
        $this->assertEquals(7000, $cartola['movimientos'][1]['saldo']);

        $rendiciones = $informe->rendiciones((int) $empresa['id'], 2026, 11, null);
        $this->assertCount(2, $rendiciones['filas']);
        $this->assertSame('Ana Pérez', $rendiciones['por_usuario'][0]['nombre']);
        $this->assertEquals(5000, $rendiciones['por_usuario'][0]['monto']);

        $propias = $informe->rendiciones((int) $empresa['id'], 2026, 11, (int) $luis['id']);
        $this->assertCount(1, $propias['filas']);
        $this->assertSame('Viaje de Luis', $propias['filas'][0]['titulo']);
        $this->assertSame((int) $rendicionLuis, $propias['filas'][0]['id']);

        $tipos = $informe->tipos((int) $empresa['id'], 2026, 11, null);
        $this->assertEquals(5000, $tipos['total']);
        $this->assertSame('Alimentación', $tipos['filas'][0]['categoria']);

        $anio = $informe->asientos((int) $empresa['id'], 2026, 0, null);
        $this->assertCount(1, $anio['por_mes']);
        $this->assertSame('2026-11', $anio['por_mes'][0]['mes']);
        $this->assertEquals(5000, $anio['por_mes'][0]['total']);
        $this->assertSame('5101 Alimentación', $anio['filas'][0]['cuenta']);
        $this->assertEquals(3000, $anio['sin_cuenta']);

        $this->withSession(['usuario_id' => $ana['id']]);
        $indice = $this->get('informes?anio=2026&mes=11');
        $indice->assertOK();
        $indice->assertSee('Cartola');
        $pagina = $this->get('informes/cartola?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('CLP 12.000');
        $pagina->assertSee('CLP 7.000');
        $lista = $this->get('rendiciones');
        $lista->assertSee('Ana Pérez');
        $lista->assertSee('Luis Soto');

        $ajena = $this->withSession(['usuario_id' => $luis['id']])->get('informes/rendiciones?anio=2026&mes=11');
        $ajena->assertOK();
        $ajena->assertSee('Viaje de Luis');
        $ajena->assertDontSee('Viaje de Ana');
    }

    public function testPendientesYRemuneracionesDelMes(): void
    {
        [$ana, $empresa, $alimentacion] = $this->base();
        model(CategoriaModel::class)->update($alimentacion['id'], ['concepto' => 'viatico']);
        $abierta = (int) model(RendicionModel::class)->insert([
            'empresa_id'      => $ana['empresa_id'],
            'usuario_id'      => $ana['id'],
            'titulo'          => 'Noviembre abierto',
            'nota'            => '',
            'estado'          => 'abierta',
            'creada'          => '2026-11-03 09:00:00',
            'enviada'         => null,
            'origen'          => 'directa',
            'monto_maximo'    => 0,
            'monto_por_rendir'=> 10000,
        ], true);
        model(RendicionModel::class)->insert([
            'empresa_id'   => $ana['empresa_id'],
            'usuario_id'   => $ana['id'],
            'titulo'       => 'Noviembre cerrado',
            'nota'         => '',
            'estado'       => 'cerrada',
            'creada'       => '2026-11-04 09:00:00',
            'enviada'      => '2026-11-20 09:00:00',
            'origen'       => 'directa',
            'monto_maximo' => 0,
        ]);
        $gasto = $this->gasto($ana, $alimentacion, 'Viático de noviembre', 4000, '2026-11-08', 'en_rendicion');
        model(GastoModel::class)->update($gasto['id'], ['rendicion_id' => $abierta]);

        $informe = new InformeServicio();
        $pendientes = $informe->pendientes((int) $empresa['id'], 2026, 11, null);
        $this->assertCount(1, $pendientes['filas']);
        $this->assertSame('Noviembre abierto', $pendientes['filas'][0]['titulo']);
        $this->assertEquals(6000, $pendientes['filas'][0]['saldo']);
        $this->assertEquals(6000, $pendientes['total']);

        $remuneraciones = $informe->remuneraciones((int) $empresa['id'], 2026, 11, null);
        $this->assertEquals(4000, $remuneraciones['total']);
        $this->assertEquals(4000, $remuneraciones['filas'][0]['viatico']);
        $this->assertSame('Ana Pérez', $remuneraciones['filas'][0]['nombre']);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/pendientes?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('Noviembre abierto');
        $pagina->assertDontSee('Noviembre cerrado');
        $sueldo = $this->withSession(['usuario_id' => $ana['id']])->get('informes/remuneraciones?anio=2026&mes=11');
        $sueldo->assertOK();
        $sueldo->assertSee('A liquidación');
        $sueldo->assertSee('Ana Pérez');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
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
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);
        $alimentacion = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();

        return [$ana, $empresa, $alimentacion];
    }

    /**
     * @param array<string, mixed> $empresa
     *
     * @return array<string, mixed>
     */
    private function persona(array $empresa, string $nombre, string $email): array
    {
        $alta = (new PersonaServicio())->crear($empresa, [
            'nombre'   => $nombre,
            'email'    => $email,
            'password' => 'clave-segura',
        ]);

        return model(UsuarioModel::class)->find($alta['id']);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     */
    private function fondo(array $usuario, array $categoria, string $motivo, float $monto, string $creado): void
    {
        $solicitud = model(SolicitudModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'categoria_id' => $categoria['id'],
            'motivo'       => $motivo,
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
            'motivo'       => $motivo,
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
     *
     * @return array<string, mixed>
     */
    private function gasto(array $usuario, array $categoria, string $concepto, float $monto, string $fecha, string $estado): array
    {
        $id = model(GastoModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'categoria_id' => $categoria['id'],
            'concepto'     => $concepto,
            'proveedor'    => 'Local',
            'fecha'        => $fecha,
            'monto'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => $estado,
            'creado'       => $fecha . ' 12:00:00',
        ], true);

        return model(GastoModel::class)->find($id);
    }

    /**
     * @param array<string, mixed> $usuario
     */
    private function rendicion(array $usuario, string $titulo, string $creada, int $gastoId): int
    {
        $id = (int) model(RendicionModel::class)->insert([
            'empresa_id' => $usuario['empresa_id'],
            'usuario_id' => $usuario['id'],
            'titulo'     => $titulo,
            'nota'       => '',
            'estado'     => 'abierta',
            'creada'     => $creada,
            'enviada'    => null,
            'origen'     => 'directa',
            'monto_maximo' => 0,
        ], true);
        if ($gastoId > 0) {
            model(GastoModel::class)->update($gastoId, [
                'rendicion_id' => $id,
                'estado'       => 'en_rendicion',
            ]);
            PreAsiento::sincronizar(model(GastoModel::class)->find($gastoId));
        }

        return $id;
    }
}
