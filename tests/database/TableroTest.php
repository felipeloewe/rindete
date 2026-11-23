<?php

declare(strict_types=1);

use App\Libraries\SaldoRendicion;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\GastoModel;
use App\Models\RendicionModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class TableroTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    public function testElInicioEmpujaACerrarYPermiteDevolver(): void
    {
        $ana = $this->cuenta();
        $alimentacion = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $abierto = $this->rendicion($ana, $alimentacion, 'Viaje abierto', 4000, '2026-11-02');
        $deuda = $this->rendicion($ana, $alimentacion, 'Taxi por devolver', 8000, '2026-11-03');
        $gasto = $this->gasto($ana, $alimentacion, 'Almuerzo repetido', 3000, '2026-11-04');
        model(GastoModel::class)->update($gasto['id'], ['duplicado' => 1, 'estado' => 'observado']);

        $panel = $this->withSession(['usuario_id' => $ana['id']])->get('panel');
        $panel->assertOK();
        $panel->assertSee('Acciones pendientes');
        $panel->assertSee('Viaje abierto');
        $panel->assertSee('Sigue abierta. Ciérrala para terminar el flujo.');
        $panel->assertSee('Almuerzo repetido');

        $rendicion = model(RendicionModel::class)->find($deuda);
        $cierre = (new SaldoRendicion())->cerrar($rendicion);
        $this->assertTrue($cierre['ok']);
        $aceptar = (new SaldoRendicion())->aceptar(model(RendicionModel::class)->find($deuda), $ana);
        $this->assertTrue($aceptar['ok']);
        $cerrada = model(RendicionModel::class)->find($deuda);
        $this->assertSame('por_devolver', $cerrada['reembolso']);

        $espera = $this->withSession(['usuario_id' => $ana['id']])->get('panel');
        $espera->assertSee('Está rendida y espera los fondos de vuelta.');
        $ficha = $this->withSession(['usuario_id' => $ana['id']])->get('rendiciones/' . $deuda);
        $ficha->assertSee('Devolver los fondos');

        model(UsuarioModel::class)->update($ana['id'], [
            'id_fiscal'     => '11111111-1',
            'cuenta_banco'  => '12345678',
            'banco_destino' => '016',
        ]);
        model(EmpresaModel::class)->update($ana['empresa_id'], ['cuenta_cargo' => '99887766']);
        $seguridad = service('security');
        $archivo = $this->withSession(['usuario_id' => $ana['id']])->post('rendiciones/' . $deuda . '/devolver', [
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $archivo->assertOK();
        $archivo->assertHeader('Content-Disposition', 'attachment; filename="devolucion-' . $deuda . '.txt"');
        $devuelta = model(RendicionModel::class)->find($deuda);
        $this->assertSame('devuelto', $devuelta['reembolso']);

        $final = $this->withSession(['usuario_id' => $ana['id']])->get('panel');
        $final->assertSee('Ya recibió los fondos de vuelta.');
        $final->assertDontSee('Está rendida y espera los fondos de vuelta.');
    }

    public function testLaBoletaRecibidaDejaLaLecturaEnElGasto(): void
    {
        $ana = $this->cuenta();
        $id = db_connect()->table('entradas')->insert([
            'empresa_id'  => $ana['empresa_id'],
            'usuario_id'  => $ana['id'],
            'canal'       => 'whatsapp',
            'emisor'      => '56933334444',
            'receptor'    => '56911112222',
            'mensaje_id'  => 'wamid.lectura',
            'texto'       => 'Taxi al aeropuerto',
            'comprobante' => '',
            'lectura'     => json_encode(['monto' => '15000', 'fecha' => '2026-11-21', 'id_fiscal' => '11111111-1'], JSON_UNESCAPED_UNICODE),
            'estado'      => 'pendiente',
            'creado'      => '2026-11-21 12:00:00',
        ]);
        $this->assertTrue($id !== false);
        $entrada = db_connect()->table('entradas')->where('mensaje_id', 'wamid.lectura')->get()->getRowArray();
        $formulario = $this->withSession(['usuario_id' => $ana['id']])->get('gastos/nuevo?entrada=' . $entrada['id']);
        $formulario->assertOK();
        $formulario->assertSee('Leímos monto, fecha, RUT o folio de la boleta.');
        $formulario->assertSee('15000');
        $formulario->assertSee('11111111-1');
    }

    /**
     * @return array<string, mixed>
     */
    private function cuenta(): array
    {
        $seguridad = service('security');
        $this->post('registro', [
            'pais'            => 'CL',
            'empresa'         => 'Acme SpA',
            'id_fiscal'       => '12.345.678-5',
            'nombre'          => 'Ana Pérez',
            'email'           => 'ana@acme.test',
            'password'        => 'clave-segura',
            'acepta_politica' => '1',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);

        return model(UsuarioModel::class)->porEmail('ana@acme.test');
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     *
     * @return array<string, mixed>
     */
    private function gasto(array $usuario, array $categoria, string $concepto, float $monto, string $fecha): array
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
            'estado'       => 'legalizado',
            'creado'       => $fecha . ' 12:00:00',
        ], true);

        return model(GastoModel::class)->find($id);
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     */
    private function rendicion(array $usuario, array $categoria, string $titulo, float $monto, string $fecha): int
    {
        $gasto = $this->gasto($usuario, $categoria, $titulo, $monto, $fecha);
        $id = (int) model(RendicionModel::class)->insert([
            'empresa_id'       => $usuario['empresa_id'],
            'usuario_id'       => $usuario['id'],
            'titulo'           => $titulo,
            'nota'             => '',
            'estado'           => 'abierta',
            'creada'           => $fecha . ' 12:00:00',
            'enviada'          => null,
            'origen'           => 'directa',
            'monto_por_rendir' => 0,
        ], true);
        model(GastoModel::class)->update($gasto['id'], [
            'rendicion_id' => $id,
            'estado'       => 'en_rendicion',
        ]);

        return $id;
    }
}
