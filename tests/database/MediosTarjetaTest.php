<?php

declare(strict_types=1);

use App\Libraries\Global66Cuenta;
use App\Libraries\MediosConexion;
use App\Libraries\TarjetaIdentidad;
use App\Libraries\TrazadorMedios;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\MedioModel;
use App\Models\TarjetaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class MediosTarjetaTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testLaTarjetaDeLaEmpresaQuedaExclusivaYLaRemesaEntraARendicion(): void
    {
        [$ana, $empresa, $categoria] = $this->base();
        $seguridad = service('security');
        $alta = $this->withSession(['usuario_id' => $ana['id']])->post('parametros/tarjetas', [
            'nombre'         => 'Visa empresa',
            'monto_aceptado' => '20000',
            'ultimos4'       => '4242',
            'emisor'         => 'global66',
            'referencia'     => '2000000584',
            'billetera'      => '123456',
            'titular_id'     => $ana['id'],
            'usuario_id'     => [$ana['id']],
            'categoria_id'   => [$categoria['id']],
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $alta->assertRedirect();
        $alta->assertSessionHas('ok', 'Tarjeta guardada.');
        $tarjeta = model(TarjetaModel::class)->where('nombre', 'Visa empresa')->first();
        $this->assertSame(1, (int) $tarjeta['exclusiva']);
        $this->assertSame('4242', $tarjeta['ultimos4']);

        $personal = (new TarjetaIdentidad())->guardar(
            (int) $empresa['id'],
            'Visa personal',
            0,
            true,
            [(int) $ana['id']],
            [(int) $categoria['id']],
            '1111',
            'global66',
            '999',
            '',
            (int) $ana['id'],
        );
        $this->assertTrue($personal['ok'], implode(' ', $personal['errores']));
        $propia = model(TarjetaModel::class)->where('nombre', 'Visa personal')->first();
        $this->assertSame(0, (int) $propia['exclusiva']);

        $repetida = (new TarjetaIdentidad())->guardar(
            (int) $empresa['id'],
            'Otra',
            0,
            false,
            [(int) $ana['id']],
            [],
            '4242',
            'global66',
            'otra-cuenta',
            '1',
            (int) $ana['id'],
        );
        $this->assertFalse($repetida['ok']);
        $this->assertSame('Esa tarjeta ya está identificada.', $repetida['errores'][0]);

        $seguridad = service('security');
        $conexion = $this->withSession(['usuario_id' => $ana['id']])->post('parametros/medios', [
            'proveedor'      => 'global66',
            'secreto'        => 'clave-global',
            'token_consulta' => '',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $conexion->assertSessionHas('ok', 'Conexión de Global66 guardada.');
        $medio = model(MedioModel::class)->where('proveedor', 'global66')->first();
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('parametros/medios');
        $pagina->assertSee('webhooks/global66/' . $medio['clave']);
        $pagina->assertDontSee('clave-global');

        $aviso = $this->post('webhooks/global66/' . $medio['clave'], [
            'event' => 'WALLET - Founding status',
            'data'  => [
                'transactionId'       => 34567687,
                'status'              => 'COMPLETED',
                'originAmount'        => 150,
                'originCurrencyCode'  => 'CLP',
                'thirdPartyClientName'=> 'Acme S.A.',
                'customerId'          => 2000000584,
                'type'                => 'DEPOSIT',
            ],
        ]);
        $aviso->assertStatus(401);

        $abono = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event' => 'WALLET - Founding status',
                'data'  => [
                    'transactionId'        => 34567687,
                    'status'               => 'COMPLETED',
                    'originAmount'         => 150,
                    'originCurrencyCode'   => 'CLP',
                    'thirdPartyClientName' => 'Acme S.A.',
                    'customerId'           => '2000000584',
                    'type'                 => 'DEPOSIT',
                ],
            ]);
        $abono->assertOK();
        $filaAbono = db_connect()->table('movimientos_medio')->where('externo_id', 'g66-abono-34567687')->get()->getRowArray();
        $this->assertSame('abono', $filaAbono['sentido']);
        $this->assertSame('sin_nomina', $filaAbono['estado']);
        $this->assertSame((int) $tarjeta['id'], (int) $filaAbono['tarjeta_id']);

        $creada = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event'   => 'RMT - Transaction',
                'payload' => [
                    'transactionId'        => 88,
                    'status'               => 'created',
                    'destinyAmount'        => 4990,
                    'destinyCurrencyCode'  => 'CLP',
                    'customerId'           => '2000000584',
                    'purpose'              => 'Viático',
                    'createdAt'            => date('c'),
                ],
            ]);
        $creada->assertOK();
        $this->assertSame('trazado', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-remesa-88')->get()->getRowArray()['estado']);

        $exitosa = $this->withBodyFormat('json')
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
        $exitosa->assertOK();
        $remesa = db_connect()->table('movimientos_medio')->where('externo_id', 'g66-remesa-88')->get()->getRowArray();
        $this->assertSame('en_rendicion', $remesa['estado']);
        $this->assertSame(1, db_connect()->table('movimientos_medio')->where('externo_id', 'g66-remesa-88')->countAllResults());
        $fondo = model(FondoModel::class)->where('origen', 'tarjeta')->first();
        $this->assertSame((int) $ana['id'], (int) $fondo['usuario_id']);

        $anio = (int) date('Y');
        $mes = (int) date('n');
        $informe = $this->withSession(['usuario_id' => $ana['id']])->get('informes/medios?anio=' . $anio . '&mes=' . $mes);
        $informe->assertOK();
        $informe->assertSee('En rendición');
        $informe->assertSee('Llegó sin nómina');
        $informe->assertSee('Viático');
        $informe->assertSee('Acme S.A.');

        $otra = $this->withBodyFormat('json')
            ->withHeaders(['x-api-key' => 'clave-global'])
            ->post('webhooks/global66/' . $medio['clave'], [
                'event'   => 'RMT - Transaction',
                'payload' => [
                    'transactionId'       => 90,
                    'status'              => 'successful',
                    'destinyAmount'       => 1000,
                    'destinyCurrencyCode' => 'CLP',
                    'customerId'          => '999',
                    'purpose'             => 'Gasto personal',
                    'createdAt'           => date('c'),
                ],
            ]);
        $otra->assertOK();
        $this->assertSame('trazado', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-remesa-90')->get()->getRowArray()['estado']);

        $seguridad = service('security');
        $sinToken = $this->withBodyFormat('')->withSession(['usuario_id' => $ana['id']])->post('parametros/tarjetas/' . $tarjeta['id'] . '/sincronizar', [
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $sinToken->assertSessionHas('error', 'Pega el token de consulta de Global66 para traer los movimientos de la cuenta.');
    }

    public function testElMantenedorGuardaElClientSecretSinMostrarlo(): void
    {
        [$ana] = $this->base();
        $secreto = 'secreto-de-prueba-g66';
        $seguridad = service('security');
        $this->withSession(['usuario_id' => $ana['id']])->post('parametros/medios', [
            'proveedor'     => 'global66',
            'secreto'       => 'clave-webhook',
            'credencial'    => 'Nomina de prueba',
            'client_id'     => 'cliente-publico-ada9',
            'client_secret' => $secreto,
            $seguridad->getTokenName() => $seguridad->getHash(),
        ])->assertSessionHas('ok', 'Conexión de Global66 guardada.');

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('parametros/medios');
        $pagina->assertOK();
        $pagina->assertSee('Client ID');
        $pagina->assertSee('Client Secret');
        $pagina->assertSee('cliente-publico-ada9');
        $pagina->assertSee('Nomina de prueba');
        $pagina->assertSee('Client Secret guardado');
        $pagina->assertSee('Probar credencial');
        $pagina->assertDontSee($secreto);

        $medio = model(MedioModel::class)->where('proveedor', 'global66')->first();
        $this->assertNotSame($secreto, (string) $medio['client_secret']);
        $this->assertSame($secreto, (new MediosConexion())->clientSecret($medio));

        $seguridad = service('security');
        $this->withSession(['usuario_id' => $ana['id']])->post('parametros/medios', [
            'proveedor'  => 'global66',
            'secreto'    => 'clave-webhook',
            'credencial' => 'Nomina de prueba',
            'client_id'  => 'cliente-publico-ada9',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ])->assertSessionHas('ok', 'Conexión de Global66 guardada.');
        $otra = model(MedioModel::class)->where('proveedor', 'global66')->first();
        $this->assertSame($secreto, (new MediosConexion())->clientSecret($otra));

        $llamadas = [];
        $cliente = new Global66Cuenta(function (string $metodo, string $ruta, ?string $cuerpo, ?string $token) use (&$llamadas): array {
            $llamadas[] = [$metodo, $ruta, $cuerpo, $token];
            if ($ruta === '/b2b/auth') {
                return ['codigo' => 200, 'json' => ['token' => 'tok-prueba', 'refreshToken' => 'ref-prueba']];
            }

            return ['codigo' => 200, 'json' => ['accounts' => [['walletId' => 164926, 'currency' => 'CLP', 'isPrincipal' => true]]]];
        });
        $prueba = $cliente->probar('cliente-publico-ada9', $secreto);
        $this->assertTrue($prueba['ok'], implode(' ', $prueba['errores']));
        $this->assertSame('POST', $llamadas[0][0]);
        $this->assertSame('/b2b/auth', $llamadas[0][1]);
        $this->assertNull($llamadas[0][3]);
        $this->assertStringContainsString('cliente-publico-ada9', (string) $llamadas[0][2]);
        $this->assertSame('GET', $llamadas[1][0]);
        $this->assertSame('/b2b/accounts', $llamadas[1][1]);
        $this->assertSame('tok-prueba', $llamadas[1][3]);
        $this->assertSame('164926', $prueba['cuentas'][0]['walletId']);
        $rechazo = (new Global66Cuenta(static fn (): array => ['codigo' => 401, 'json' => ['message' => 'Unauthorized']]))
            ->probar('cliente-publico-ada9', $secreto);
        $this->assertSame('Global66 rechazó el Client ID o el Client Secret.', $rechazo['errores'][0]);
    }

    public function testMachSoloTrazaPagosDeComercio(): void
    {
        [$ana] = $this->base();
        $seguridad = service('security');
        $this->withSession(['usuario_id' => $ana['id']])->post('parametros/medios', [
            'proveedor' => 'mach',
            'secreto'   => 'token-mach',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ])->assertSessionHas('ok', 'Conexión de MACH guardada.');
        $medio = model(MedioModel::class)->where('proveedor', 'mach')->first();
        $pago = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer token-mach'])
            ->post('webhooks/mach/' . $medio['clave'], [
                'event_name'        => 'business-payment-completed',
                'event_resource_id' => '12685e38-ef64-4cff-b51a-8a4fe649e3b6',
                'event_upstream_id' => 'pedido-1',
            ]);
        $pago->assertOK();
        $fila = db_connect()->table('movimientos_medio')->where('proveedor', 'mach')->get()->getRowArray();
        $this->assertSame('comercio', $fila['estado']);
        $this->assertSame('Pago de comercio completado', $fila['detalle']);
        $this->assertSame(0, model(FondoModel::class)->where('origen', 'tarjeta')->countAllResults());
        $repetido = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer token-mach'])
            ->post('webhooks/mach/' . $medio['clave'], [
                'event_name'        => 'business-payment-completed',
                'event_resource_id' => '12685e38-ef64-4cff-b51a-8a4fe649e3b6',
            ]);
        $repetido->assertOK();
        $this->assertSame(1, db_connect()->table('movimientos_medio')->where('proveedor', 'mach')->countAllResults());
    }

    public function testElRetiroDeLaCuentaExclusivaQuedaPorRendir(): void
    {
        [$ana, $empresa, $categoria] = $this->base();
        (new TarjetaIdentidad())->guardar(
            (int) $empresa['id'],
            'Cuenta Global',
            0,
            false,
            [(int) $ana['id']],
            [(int) $categoria['id']],
            '',
            'global66',
            '2000000584',
            '55',
            (int) $ana['id'],
        );
        $tarjeta = model(TarjetaModel::class)->where('billetera', '55')->first();
        $nuevos = (new TrazadorMedios())->incorporar((int) $empresa['id'], (int) $tarjeta['id'], [
            [
                'id'           => 7,
                'amount'       => 1500,
                'status'       => 'PAID',
                'type'         => 'BANK_TRANSFER',
                'typeMovement' => 'WITHDRAWAL',
                'comment'      => 'Pago a proveedor',
                'currencyCode' => 'CLP',
                'date'         => date('c'),
            ],
            [
                'id'           => 8,
                'amount'       => 5,
                'status'       => 'PAID',
                'type'         => 'FEE_WITHDRAWAL',
                'typeMovement' => 'WITHDRAWAL',
                'comment'      => 'Comisión',
                'currencyCode' => 'CLP',
                'date'         => date('c'),
            ],
        ]);
        $this->assertSame(2, $nuevos);
        $this->assertSame('en_rendicion', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-mov-7')->get()->getRowArray()['estado']);
        $this->assertSame('trazado', db_connect()->table('movimientos_medio')->where('externo_id', 'g66-mov-8')->get()->getRowArray()['estado']);
        $otra = (new TrazadorMedios())->incorporar((int) $empresa['id'], (int) $tarjeta['id'], [
            ['id' => 7, 'amount' => 1500, 'status' => 'PAID', 'type' => 'BANK_TRANSFER', 'typeMovement' => 'WITHDRAWAL', 'currencyCode' => 'CLP'],
        ]);
        $this->assertSame(0, $otra);
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
            'acepta_politica' => '1',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);
        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('nombre', 'Alimentación')->first();

        return [$ana, $empresa, $categoria];
    }
}
