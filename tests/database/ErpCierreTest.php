<?php

declare(strict_types=1);

use App\Libraries\ConexionErp;
use App\Libraries\Modulos;
use App\Libraries\NominaBci;
use App\Libraries\NominaFondos;
use App\Libraries\PreAsientoChile;
use App\Models\CategoriaModel;
use App\Models\ConexionModel;
use App\Models\CuentaModel;
use App\Models\EmpresaModel;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ErpCierreTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testElPreasientoCerradoPasaASerAsientoCuandoElErpLoToma(): void
    {
        [$ana, $empresa] = $this->empresa();
        $fondo = $this->cuenta($empresa, '2101', 'Fondos por rendir');
        $banco = $this->cuenta($empresa, '1101', 'Banco');
        $id = $this->comprobante((int) $empresa['id'], $fondo, $banco, 12000, 12000);
        $descuadrado = $this->comprobante((int) $empresa['id'], $fondo, $banco, 5000, 1000);
        $motor = new PreAsientoChile();
        $this->assertFalse($motor->cerrar((int) $empresa['id'], 2026, 11)['ok']);
        db_connect()->table('preasiento_lineas')->where('comprobante_id', $descuadrado)->delete();
        db_connect()->table('preasiento_comprobantes')->where('id', $descuadrado)->delete();
        $this->assertTrue($motor->cerrar((int) $empresa['id'], 2026, 11)['ok'], 'cierra');
        $empresa['preasiento_cuenta_id'] = $fondo;
        $empresa['preasiento_salida_id'] = $banco;
        $empresa['pais'] = 'CL';
        $rearmar = $motor->armar($empresa, 2026, 11);
        $this->assertFalse($rearmar['ok']);
        $this->assertStringContainsString('cerrado', implode(' ', $rearmar['errores']));

        $odoo = $this->conectar((int) $empresa['id'], 'Odoo producción', 'odoo', 'asientos');
        $apagado = $this->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])->get('api/v1/preasientos');
        $apagado->assertStatus(403);
        Modulos::contratar((int) $empresa['id'], 'preasientos');
        $this->assertTrue($odoo['ok']);
        $guardada = model(ConexionModel::class)->where('empresa_id', $empresa['id'])->first();
        $this->assertSame(hash('sha256', $odoo['token']), $guardada['token_huella']);
        $this->assertStringNotContainsString($odoo['token'], json_encode($guardada, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('token-api-secreto', (string) $guardada['token_api']);
        $this->assertSame('token-api-secreto', service('encrypter')->decrypt(base64_decode((string) $guardada['token_api'])));
        $this->assertSame('https://erp.example.test/api', $guardada['url']);

        $sinToken = $this->withHeaders(['Authorization' => 'Basic no'])->get('api/v1/preasientos');
        $sinToken->assertStatus(401);

        $lista = $this->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])
            ->get('api/v1/preasientos?anio=2026&mes=11');
        $lista->assertOK();
        $cuerpo = json_decode($lista->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('odoo', $cuerpo['sistema']);
        $this->assertSame('/json/2/account.move/create', $cuerpo['preasientos'][0]['proceso']['pasos'][0]['ruta']);
        $this->assertSame('2101', $cuerpo['preasientos'][0]['proceso']['pasos'][0]['cuerpo']['vals_list'][0]['line_ids'][0][2]['account_code']);
        $this->assertEquals(12000, $cuerpo['preasientos'][0]['debe']);

        $pagos = $this->conectar((int) $empresa['id'], 'Odoo nómina', 'odoo', 'pagos');
        $ajena = $this->withHeaders(['Authorization' => 'Bearer ' . $pagos['token']])->get('api/v1/preasientos');
        $ajena->assertStatus(403);

        $toma = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])
            ->post('api/v1/preasientos/' . $id . '/consumir', ['referencia' => 'odoo:15']);
        $toma->assertOK();
        $tomado = json_decode($toma->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('asiento', $tomado['estado']);
        $this->assertSame('odoo:15', $tomado['referencia']);

        $repite = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])
            ->post('api/v1/preasientos/' . $id . '/consumir', ['referencia' => 'odoo:15']);
        $repite->assertOK();
        $otra = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])
            ->post('api/v1/preasientos/' . $id . '/consumir', ['referencia' => 'odoo:99']);
        $otra->assertStatus(409);

        $softnet = $this->conectar((int) $empresa['id'], 'Softnet', 'softnet', 'ambos');
        $vacio = $this->withHeaders(['Authorization' => 'Bearer ' . $softnet['token']])->get('api/v1/preasientos');
        $vacio->assertOK();
        $sinPendientes = json_decode($vacio->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $sinPendientes['preasientos']);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/preasientos?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('Contabilizado');
        $pagina->assertSee('odoo:15');
        $pagina->assertDontSee('Armar preasientos');
        $pagina->assertDontSee('Contabilizado en el ERP');
    }

    public function testLaNominaCierraSoloSolicitudesAceptadasDelMes(): void
    {
        [$ana, $empresa] = $this->empresa();
        model(UsuarioModel::class)->update($ana['id'], ['id_fiscal' => '12345678-5']);
        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->first();
        $aceptada = $this->solicitud($ana, $categoria, 'Pasajes', 8000, 'aceptada', '2026-11-04 09:00:00');
        $this->solicitud($ana, $categoria, 'Octubre', 1000, 'aceptada', '2026-10-20 09:00:00');
        $this->solicitud($ana, $categoria, 'Ya entregada', 2000, 'entregada', '2026-11-08 09:00:00');
        $this->solicitud($ana, $categoria, 'En curso', 3000, 'en_aceptacion', '2026-11-09 09:00:00');

        $servicio = new NominaFondos();
        $sinCuenta = $servicio->cerrar((int) $empresa['id'], 2026, 11, '12345678');
        $this->assertFalse($sinCuenta['ok']);
        $this->assertStringContainsString('cuenta del banco', implode(' ', $sinCuenta['errores']));
        model(UsuarioModel::class)->update($ana['id'], ['cuenta_banco' => '12345678']);
        $sinBanco = $servicio->cerrar((int) $empresa['id'], 2026, 11, '12345678');
        $this->assertFalse($sinBanco['ok']);
        $this->assertStringContainsString('banco de destino', implode(' ', $sinBanco['errores']));
        model(UsuarioModel::class)->update($ana['id'], ['banco_destino' => '016']);
        $sinCargo = $servicio->cerrar((int) $empresa['id'], 2026, 11, '1234567890123');
        $this->assertFalse($sinCargo['ok']);
        $this->assertStringContainsString('cuenta de cargo', implode(' ', $sinCargo['errores']));
        $cierre = $servicio->cerrar((int) $empresa['id'], 2026, 11, '12.345.678');
        $this->assertTrue($cierre['ok'], implode(' ', $cierre['errores']));
        $fondo = model(\App\Models\FondoModel::class)->where('solicitud_id', $aceptada)->first();
        $this->assertSame('abierto', $fondo['estado']);
        $this->assertEquals(8000, $fondo['saldo']);
        $otra = $servicio->cerrar((int) $empresa['id'], 2026, 11);
        $this->assertFalse($otra['ok']);

        $nomina = $servicio->una((int) $empresa['id'], (int) $cierre['id']);
        $this->assertCount(1, $nomina['lineas']);
        $this->assertSame($aceptada, (int) $nomina['lineas'][0]['solicitud_id']);
        $this->assertSame('12345678-5', $nomina['lineas'][0]['rut']);
        $this->assertSame('12345678', $nomina['lineas'][0]['cuenta_banco']);
        $this->assertSame('016', $nomina['lineas'][0]['banco_destino']);
        $this->assertSame('12345678', $nomina['cuenta_cargo']);
        $this->assertSame('en_banco', $nomina['estado']);
        $archivo = $servicio->archivo((int) $empresa['id'], (int) $cierre['id']);
        $this->assertTrue($archivo['ok'], implode(' ', $archivo['errores']));
        $this->assertSame('nomina-fondos-2026-11.txt', $archivo['nombre']);
        $this->assertSame(
            "000012345678;000000000012345678;016;12345678;5;Ana Pérez;8000;;;OTR;Pasajes;ana@acme.test;Ana Pérez\r\n",
            $archivo['contenido'],
        );
        $ejemplo = NominaBci::texto([
            'anio' => 2026,
            'mes' => 11,
            'cuenta_cargo' => '12345678',
            'lineas' => [[
                'usuario_id' => 7,
                'nombre' => 'Nombre Prueba',
                'rut' => '123455678-3',
                'cuenta_banco' => '12345678',
                'banco_destino' => '016',
                'motivo' => 'Prueba Pago',
                'monto' => 3,
            ]],
        ], [7 => 'prueba@bci.cl']);
        $this->assertSame(
            "000012345678;000000000012345678;016;123455678;3;Nombre Prueba;3;;;OTR;Prueba Pago;prueba@bci.cl;Nombre Prueba\r\n",
            $ejemplo['contenido'],
        );
        $conCentavos = NominaBci::texto([
            'anio' => 2026,
            'mes' => 11,
            'cuenta_cargo' => '12345678',
            'lineas' => [[
                'nombre' => 'Nombre Prueba',
                'rut' => '12345678-5',
                'cuenta_banco' => '12345678',
                'banco_destino' => '016',
                'motivo' => 'Fondo',
                'monto' => 10.5,
            ]],
        ]);
        $this->assertFalse($conCentavos['ok']);
        $this->assertSame('Pasajes', $nomina['lineas'][0]['motivo']);

        $softnet = $this->conectar((int) $empresa['id'], 'Softnet tesorería', 'softnet', 'pagos');
        $lista = $this->withHeaders(['Authorization' => 'Bearer ' . $softnet['token']])
            ->get('api/v1/nominas?anio=2026&mes=11');
        $lista->assertOK();
        $cuerpo = json_decode($lista->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Tesorería', $cuerpo['nominas'][0]['proceso']['modulo']);
        $this->assertSame('fondos_por_rendir', $cuerpo['nominas'][0]['tipo']);
        $this->assertSame(8000, $cuerpo['nominas'][0]['lineas'][0]['monto']);

        $odoo = $this->conectar((int) $empresa['id'], 'Odoo nómina', 'odoo', 'ambos');
        $toma = $this->withBodyFormat('json')
            ->withHeaders(['Authorization' => 'Bearer ' . $odoo['token']])
            ->post('api/v1/nominas/' . $cierre['id'] . '/consumir', ['referencia' => 'odoo:novedad:11']);
        $toma->assertOK();
        $tomada = json_decode($toma->getJSON(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('account.payment', $tomada['proceso']['modelo']);
        $this->assertSame('outbound', $tomada['proceso']['pagos'][0]['payment_type']);
        $this->assertSame('confirmada', $tomada['estado']);
        $queda = $this->withHeaders(['Authorization' => 'Bearer ' . $softnet['token']])->get('api/v1/nominas');
        $this->assertSame([], json_decode($queda->getJSON(), true, 512, JSON_THROW_ON_ERROR)['nominas']);

        $seguridad = service('security');
        $alta = $this->withSession(['usuario_id' => $ana['id']])->post('parametros/conexiones', [
            'nombre'    => 'Odoo de prueba',
            'sistema'   => 'odoo',
            'url'       => 'https://acme.odoo.com',
            'token_api' => 'odoo-token-de-prueba',
            'destino'   => 'ambos',
            'vigente'   => '1',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $alta->assertRedirectTo(site_url('parametros/conexiones'));
        $alta->assertSessionHas('token');
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('fondos/nomina?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('El banco la registró');
        $pagina->assertSee('odoo:novedad:11');
        $pagina->assertSee('Descargar archivo');
        $pagina->assertDontSee('Crear en el banco');
        $pagina->assertDontSee('Remuneraciones');
        $descarga = $this->withSession(['usuario_id' => $ana['id']])
            ->get('fondos/nomina/' . $cierre['id'] . '/archivo');
        $descarga->assertOK();
        $descarga->assertHeader('Content-Type', 'text/plain; charset=Windows-1252');
        $this->assertStringContainsString('8000;;;OTR;Pasajes;ana@acme.test', $descarga->getBody());
    }

    public function testSinApiElCheckboxDejaElPreasientoContabilizado(): void
    {
        [$ana, $empresa] = $this->empresa();
        Modulos::contratar((int) $empresa['id'], 'preasientos');
        $fondo = $this->cuenta($empresa, '2101', 'Fondos por rendir');
        $banco = $this->cuenta($empresa, '1101', 'Banco');
        $id = $this->comprobante((int) $empresa['id'], $fondo, $banco, 12000, 12000);
        $motor = new PreAsientoChile();
        $abierto = $motor->contabilizar((int) $empresa['id'], [$id]);
        $this->assertFalse($abierto['ok']);
        $this->assertStringContainsString('mes está cerrado', implode(' ', $abierto['errores']));
        $this->assertTrue($motor->cerrar((int) $empresa['id'], 2026, 11)['ok']);
        model(EmpresaModel::class)->update($empresa['id'], [
            'preasiento_cuenta_id' => $fondo,
            'preasiento_salida_id' => $banco,
        ]);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/preasientos?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('Contabilizado en el ERP');
        $pagina->assertSee('no hay una API vigente');

        $seguridad = service('security');
        $vacio = $this->withSession(['usuario_id' => $ana['id']])->post('informes/preasientos/contabilizar', [
            'anio' => '2026',
            'mes'  => '11',
            $seguridad->getTokenName() => $seguridad->getHash(),
        ]);
        $vacio->assertRedirectTo(site_url('informes/preasientos?anio=2026&mes=11'));
        $vacio->assertSessionHas('errores');

        $marca = $this->withSession(['usuario_id' => $ana['id']])->post('informes/preasientos/contabilizar', [
            'anio' => '2026',
            'mes'  => '11',
            'ids'  => [$id],
            $seguridad->getTokenName() => service('security')->getHash(),
        ]);
        $marca->assertRedirectTo(site_url('informes/contabilizados?anio=2026&mes=11'));
        $dash = $this->withSession(['usuario_id' => $ana['id']])->get('informes/contabilizados?anio=2026&mes=11');
        $dash->assertOK();
        $dash->assertSee('Manual');
        $dash->assertSee('2101');
        $dash->assertSee('CLP 12.000');
        $dash->assertSee('Traspaso de noviembre');
        $guardado = db_connect()->table('preasiento_comprobantes')->where('id', $id)->get()->getRowArray();
        $this->assertSame('asiento', $guardado['estado']);
        $this->assertSame('manual', $guardado['origen']);

        $otro = $this->comprobante((int) $empresa['id'], $fondo, $banco, 4000, 4000);
        db_connect()->table('preasiento_comprobantes')->where('id', $otro)->update(['estado' => 'cerrado']);
        $this->conectar((int) $empresa['id'], 'Odoo', 'odoo', 'asientos');
        $bloqueado = $motor->contabilizar((int) $empresa['id'], [$otro]);
        $this->assertFalse($bloqueado['ok']);
        $this->assertStringContainsString('API vigente', implode(' ', $bloqueado['errores']));
        $sigue = db_connect()->table('preasiento_comprobantes')->where('id', $otro)->get()->getRowArray();
        $this->assertSame('cerrado', $sigue['estado']);
    }

    /**
     * @return array{ok: bool, errores: list<string>, token: string}
     */
    private function conectar(int $empresaId, string $nombre, string $sistema, string $destino): array
    {
        return (new ConexionErp())->crear(
            $empresaId,
            $nombre,
            $sistema,
            $destino,
            true,
            'https://erp.example.test/api',
            'token-api-secreto',
        );
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function empresa(): array
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

        return [$ana, model(EmpresaModel::class)->find($ana['empresa_id'])];
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

    private function comprobante(int $empresaId, int $debeId, int $haberId, float $debe, float $haber): int
    {
        $db = db_connect();
        $db->table('preasiento_comprobantes')->insert([
            'empresa_id' => $empresaId,
            'anio'       => 2026,
            'mes'        => 11,
            'agrupacion' => 'mes',
            'usuario_id' => null,
            'tipo'       => 'traspaso',
            'glosa'      => 'Traspaso de noviembre',
            'fecha'      => '2026-11-30',
            'moneda'     => 'CLP',
            'estado'     => 'propuesto',
            'creado'     => '2026-11-30 12:00:00',
        ]);
        $id = (int) $db->insertID();
        $db->table('preasiento_lineas')->insert([
            'comprobante_id' => $id,
            'orden'          => 1,
            'cuenta_id'      => $debeId,
            'glosa'          => 'Gasto',
            'debe'           => $debe,
            'haber'          => 0,
            'auxiliar'       => '12345678-5',
        ]);
        $db->table('preasiento_lineas')->insert([
            'comprobante_id' => $id,
            'orden'          => 2,
            'cuenta_id'      => $haberId,
            'glosa'          => 'Fondos por rendir',
            'debe'           => 0,
            'haber'          => $haber,
            'auxiliar'       => '',
        ]);

        return $id;
    }

    /**
     * @param array<string, mixed> $usuario
     * @param array<string, mixed> $categoria
     */
    public function testUnaSolicitudRepetidaNoEntraALaPlanilla(): void
    {
        [$ana, $empresa] = $this->empresa();
        model(UsuarioModel::class)->update($ana['id'], [
            'id_fiscal'     => '12345678-5',
            'cuenta_banco'  => '12345678',
            'banco_destino' => '016',
        ]);
        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->first();
        $primera = $this->solicitud($ana, $categoria, 'Pasajes', 8000, 'aceptada', '2026-11-04 09:00:00');
        $this->solicitud($ana, $categoria, 'Pasajes', 8000, 'aceptada', '2026-11-05 09:00:00');
        $this->solicitud($ana, $categoria, 'Movilización', 8000, 'aceptada', '2026-11-06 09:00:00');

        $servicio = new NominaFondos();
        $this->assertCount(2, $servicio->pendientes((int) $empresa['id'], 2026, 11));
        $this->assertCount(1, $servicio->repetidas((int) $empresa['id'], 2026, 11));
        $cierre = $servicio->cerrar((int) $empresa['id'], 2026, 11, '12345678');
        $this->assertTrue($cierre['ok'], implode(' ', $cierre['errores']));
        $this->assertStringContainsString('está repetida', implode(' ', $cierre['avisos']));
        $nomina = $servicio->una((int) $empresa['id'], (int) $cierre['id']);
        $this->assertCount(2, $nomina['lineas']);
        $this->assertSame($primera, (int) $nomina['lineas'][0]['solicitud_id']);

        $tablero = $servicio->tablero((int) $empresa['id'], 2026, 11);
        $estados = [];
        foreach ($tablero as $fila) {
            $estados[$fila['motivo'] . '#' . $fila['id']] = $fila['estado'];
        }
        $this->assertSame('en_proceso', $estados['Pasajes#' . $primera]);
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('fondos/pagos?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('En proceso');
        $pagina->assertSee('Pendiente');
    }

    private function solicitud(array $usuario, array $categoria, string $motivo, float $monto, string $estado, string $creada): int
    {
        return (int) model(SolicitudModel::class)->insert([
            'empresa_id'   => $usuario['empresa_id'],
            'usuario_id'   => $usuario['id'],
            'categoria_id' => $categoria['id'],
            'motivo'       => $motivo,
            'monto'        => $monto,
            'moneda'       => 'CLP',
            'estado'       => $estado,
            'detalle'      => '',
            'turno_orden'  => 0,
            'creada'       => $creada,
            'resuelta'     => $creada,
            'entregada'    => $estado === 'entregada' ? $creada : null,
        ], true);
    }
}
