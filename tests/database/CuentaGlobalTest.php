<?php

declare(strict_types=1);

use App\Libraries\NominaFondos;
use App\Libraries\PersonaServicio;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\MedioModel;
use App\Models\RendicionModel;
use App\Models\SolicitudModel;
use App\Models\TarjetaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class CuentaGlobalTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testCadaRendidorTieneSuCuentaYElAbonoConfirmaLaNomina(): void
    {
        $seguridad = service('security');
        $this->post('registro', [
            'pais'      => 'CL',
            'empresa'   => 'Acme SpA',
            'id_fiscal' => '12.345.678-5',
            'nombre'    => 'Ana Pérez',
            'email'     => 'ana@acme.test',
            'password'  => 'clave-segura',
            'acepta_politica' => '1',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);
        $alta = (new PersonaServicio())->crear($empresa, [
            'nombre'          => 'Luis Soto',
            'email'           => 'luis@acme.test',
            'password'        => 'clave-segura',
            'acepta_politica' => '1',
            'id_fiscal'       => '11.111.111-1',
            'cuenta_banco'    => '12345678',
            'banco_destino'   => '016',
        ]);
        $this->assertTrue($alta['ok'], implode(' ', $alta['errores']));
        $luis = model(UsuarioModel::class)->porEmail('luis@acme.test');

        $this->withSession(['usuario_id' => $ana['id']]);
        $seguridad = service('security');
        $this->post('personas/' . $ana['id'], [
            'id_fiscal'        => '12.345.678-5',
            'cuenta_banco'     => '99887766',
            'banco_destino'    => '016',
            'global_cliente'   => '2000000584',
            'global_billetera' => '111',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ])->assertSessionHas('ok', 'Datos de la persona guardados.');
        $seguridad = service('security');
        $this->post('personas/' . $luis['id'], [
            'id_fiscal'        => '11.111.111-1',
            'cuenta_banco'     => '12345678',
            'banco_destino'    => '016',
            'activo'           => '1',
            'global_cliente'   => '2000000999',
            'global_billetera' => '222',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ])->assertSessionHas('ok', 'Datos de la persona guardados.');

        $seguridad = service('security');
        $repetida = $this->post('personas/' . $luis['id'], [
            'id_fiscal'        => '11.111.111-1',
            'cuenta_banco'     => '12345678',
            'banco_destino'    => '016',
            'activo'           => '1',
            'global_cliente'   => '2000000584',
            'global_billetera' => '333',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $repetida->assertSessionHas('errores', ['Esa cuenta Global66 ya es de otra persona.']);
        $this->assertSame(2, model(TarjetaModel::class)->where('emisor', 'global66')->countAllResults());

        $pagina = $this->get('personas');
        $pagina->assertSee('Cuenta Global66 2000000584');
        $pagina->assertSee('Cuenta Global66 2000000999');

        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->first();
        $ahora = date('Y-m-d H:i:s');
        model(SolicitudModel::class)->insert([
            'empresa_id'   => $empresa['id'],
            'usuario_id'   => $ana['id'],
            'categoria_id' => $categoria['id'],
            'motivo'       => 'Viático de noviembre',
            'monto'        => 15000,
            'moneda'       => 'CLP',
            'estado'       => 'aceptada',
            'detalle'      => '',
            'turno_orden'  => 0,
            'creada'       => $ahora,
            'resuelta'     => $ahora,
        ]);
        $nomina = (new NominaFondos())->cerrar((int) $empresa['id'], (int) date('Y'), (int) date('n'), '99887766');
        $this->assertTrue($nomina['ok'], implode(' ', $nomina['errores']));
        $fondo = model(FondoModel::class)->where('usuario_id', $ana['id'])->where('origen', '')->first();
        $this->assertSame(0, (int) $fondo['en_cuenta']);

        $seguridad = service('security');
        $this->post('parametros/medios', [
            'proveedor' => 'global66',
            'secreto'   => 'clave-global',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $medio = model(MedioModel::class)->where('proveedor', 'global66')->first();

        $abono = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event' => 'WALLET - Founding status',
                'data'  => [
                    'transactionId'        => 34567687,
                    'status'               => 'COMPLETED',
                    'originAmount'         => 15000,
                    'originCurrencyCode'   => 'CLP',
                    'thirdPartyClientName' => 'Acme S.A.',
                    'customerId'           => '2000000584',
                    'type'                 => 'DEPOSIT',
                ],
            ]);
        $abono->assertOK();
        $this->assertSame(1, (int) model(FondoModel::class)->find($fondo['id'])['en_cuenta']);
        $this->assertSame('en_cuenta', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-abono-34567687')->get()->getRowArray()['estado']);

        $ajeno = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event' => 'WALLET - Founding status',
                'data'  => [
                    'transactionId'        => 34567688,
                    'status'               => 'COMPLETED',
                    'originAmount'         => 8000,
                    'originCurrencyCode'   => 'CLP',
                    'thirdPartyClientName' => 'Otra',
                    'customerId'           => '2000000999',
                    'type'                 => 'DEPOSIT',
                ],
            ]);
        $ajeno->assertOK();
        $this->assertSame('sin_nomina', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-abono-34567688')->get()->getRowArray()['estado']);
        $this->assertSame(0, model(FondoModel::class)->where('usuario_id', $luis['id'])->where('en_cuenta', 1)->countAllResults());

        $egreso = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event'   => 'RMT - Transaction',
                'payload' => [
                    'transactionId'       => 88,
                    'status'              => 'successful',
                    'destinyAmount'       => 4990,
                    'destinyCurrencyCode' => 'CLP',
                    'customerId'          => '2000000584',
                    'purpose'             => 'Viático',
                    'createdAt'           => date('c'),
                ],
            ]);
        $egreso->assertOK();
        $this->assertSame('en_rendicion', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-remesa-88')->get()->getRowArray()['estado']);
        $rendicion = model(RendicionModel::class)->where('usuario_id', $ana['id'])->where('origen', 'tarjeta')->first();
        $this->assertSame(4990.0, (float) $rendicion['monto_por_rendir']);

        $otro = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event'   => 'RMT - Transaction',
                'payload' => [
                    'transactionId'       => 90,
                    'status'              => 'successful',
                    'destinyAmount'       => 1000,
                    'destinyCurrencyCode' => 'CLP',
                    'customerId'          => '2000000999',
                    'purpose'             => 'Taxi',
                    'createdAt'           => date('c'),
                ],
            ]);
        $otro->assertOK();
        $deLuis = model(RendicionModel::class)->where('usuario_id', $luis['id'])->where('origen', 'tarjeta')->first();
        $this->assertSame(1000.0, (float) $deLuis['monto_por_rendir']);
        $this->assertSame(1, model(RendicionModel::class)->where('usuario_id', $ana['id'])->where('origen', 'tarjeta')->countAllResults());
    }
}
