<?php

declare(strict_types=1);

use App\Libraries\CajaChica;
use App\Libraries\CargoTarjeta;
use App\Libraries\DocumentoUnico;
use App\Libraries\PagoSocio;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\GastoModel;
use App\Models\TarjetaModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class IniciosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testDocumentoEfectivoAvisaATesoreria(): void
    {
        [$ana, $empresa] = $this->base();
        $forma = $this->formaEfectivo((int) $empresa['id']);
        $servicio = new DocumentoUnico();
        $sinPapel = $servicio->crear((int) $empresa['id'], (int) $ana['id'], $forma, 'Vale de caja', 5000, 'CLP', null);
        $this->assertFalse($sinPapel['ok']);
        $this->assertStringContainsString('comprobante de pago al contado', implode(' ', $sinPapel['errores']));

        $ruta = WRITEPATH . 'contado-prueba.pdf';
        file_put_contents($ruta, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'contado.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);
        $creado = $servicio->crear((int) $empresa['id'], (int) $ana['id'], $forma, 'Vale de caja', 5000, 'CLP', $archivo);
        $this->assertTrue($creado['ok'], implode(' ', $creado['errores']));
        $documento = db_connect()->table('documentos')->where('empresa_id', $empresa['id'])->get()->getRowArray();
        $this->assertSame('informado', $documento['estado']);
        $resuelto = $servicio->resolver((int) $empresa['id'], (int) $documento['id'], 'en_pago');
        $this->assertTrue($resuelto['ok']);
        $mes = (int) date('n');
        $tablero = $servicio->delMes((int) $empresa['id'], (int) date('Y'), $mes);
        $this->assertSame('en_proceso', $tablero[0]['estado']);
        $this->assertSame('Documento Vale de caja', $tablero[0]['motivo']);
        @unlink($ruta);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('documentos');
        $pagina->assertOK();
        $pagina->assertSee('Vale de caja');
        $pagina->assertSee('En pago');
    }

    public function testElCargoValidaLaTarjetaYAbreElFondo(): void
    {
        [$ana, $empresa, $categoria] = $this->baseConCategoria();
        $tarjeta = (int) model(TarjetaModel::class)->insert([
            'empresa_id'     => $empresa['id'],
            'nombre'         => 'Visa empresa',
            'personal'       => 0,
            'monto_aceptado' => 10000,
            'activa'         => 1,
        ], true);
        db_connect()->table('tarjeta_usuarios')->insert(['tarjeta_id' => $tarjeta, 'usuario_id' => $ana['id']]);
        db_connect()->table('tarjeta_categorias')->insert(['tarjeta_id' => $tarjeta, 'categoria_id' => $categoria['id']]);
        $cargos = new CargoTarjeta();
        $fuera = $cargos->crear($ana, (int) $empresa['id'], $tarjeta, (int) $categoria['id'], 'Hotel', 15000, 'CLP', false);
        $this->assertTrue($fuera['ok']);
        $this->assertSame('observado', $fuera['estado']);
        $this->assertSame(0, model(FondoModel::class)->where('origen', 'tarjeta')->countAllResults());

        $dentro = $cargos->crear($ana, (int) $empresa['id'], $tarjeta, (int) $categoria['id'], 'Almuerzo', 4000, 'CLP', true);
        $this->assertSame('validado', $dentro['estado']);
        $fondo = model(FondoModel::class)->where('origen', 'tarjeta')->first();
        $this->assertSame('abierto', $fondo['estado']);
        $this->assertEquals(4000, $fondo['saldo']);
        $anio = (int) date('Y');
        $mes = (int) date('n');
        $estado = $cargos->estado((int) $empresa['id'], $anio, $mes, null);
        $this->assertEquals(-4000, $estado['filas'][1]['facturas']);
        $desvio = $cargos->desviaciones((int) $empresa['id'], $anio, $mes, null);
        $this->assertEquals(4000, $desvio['filas'][0]['diferencia']);

        $cargo = db_connect()->table('cargos_tarjeta')->where('estado', 'validado')->get()->getRowArray();
        model(GastoModel::class)->insert([
            'empresa_id'   => $empresa['id'],
            'usuario_id'   => $ana['id'],
            'rendicion_id' => $cargo['rendicion_id'],
            'categoria_id' => $categoria['id'],
            'concepto'     => 'Almuerzo rendido',
            'proveedor'    => 'Local',
            'fecha'        => date('Y-m-d'),
            'monto'        => 4000,
            'moneda'       => 'CLP',
            'estado'       => 'en_rendicion',
            'creado'       => date('Y-m-d H:i:s'),
        ]);
        $this->assertSame([], $cargos->desviaciones((int) $empresa['id'], $anio, $mes, null)['filas']);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/desviaciones?anio=' . $anio . '&mes=' . $mes);
        $pagina->assertOK();
        $pagina->assertSee('calzan con lo rendido');
    }

    public function testLaCajaEntregaReponeYMarcaElGastoRepetido(): void
    {
        [$ana, $empresa, $categoria] = $this->baseConCategoria();
        $gasto = model(GastoModel::class)->insert([
            'empresa_id'   => $empresa['id'],
            'usuario_id'   => $ana['id'],
            'rendicion_id' => 1,
            'categoria_id' => $categoria['id'],
            'concepto'     => 'Peaje ya rendido',
            'proveedor'    => 'Ruta',
            'fecha'        => date('Y-m-d'),
            'monto'        => 2500,
            'moneda'       => 'CLP',
            'estado'       => 'en_rendicion',
            'creado'       => date('Y-m-d H:i:s'),
        ], true);
        $this->assertGreaterThan(0, $gasto);
        $caja = new CajaChica();
        $entrega = $caja->entregar((int) $empresa['id'], (int) $ana['id'], (int) $categoria['id'], 'Movilización', 3000, 'CLP');
        $this->assertTrue($entrega['ok'], implode(' ', $entrega['errores']));
        $this->assertEquals(-3000, $caja->saldo((int) $empresa['id']));
        $reposicion = $caja->reponer((int) $empresa['id'], 'Reponer peaje', 2500, 'CLP');
        $this->assertTrue($reposicion['ok']);
        $this->assertStringContainsString('Peaje ya rendido', implode(' ', $reposicion['avisos']));
        $marcada = db_connect()->table('caja_movimientos')->where('tipo', 'reposicion')->get()->getRowArray();
        $this->assertSame(1, (int) $marcada['duplicado']);
        $this->assertEquals(-500, $caja->saldo((int) $empresa['id']));

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('caja');
        $pagina->assertOK();
        $pagina->assertSee('Marcado para revisión de caja');
    }

    public function testElSocioRecuperaVariosDocumentosPorEntidad(): void
    {
        [$ana, $empresa] = $this->base();
        $socios = new PagoSocio();
        $this->assertTrue($socios->entidad((int) $empresa['id'], 'Filial Norte')['ok']);
        $entidad = (int) $socios->entidades((int) $empresa['id'])[0]['id'];
        $efectivo = $this->formaEfectivo((int) $empresa['id']);
        $transferencia = (int) db_connect()->table('formas_pago')->where('empresa_id', $empresa['id'])->where('efectivo', 0)->get()->getRowArray()['id'];
        $creado = $socios->crear((int) $empresa['id'], (int) $ana['id'], $entidad, 'Viaje compartido', [
            ['forma_pago_id' => $efectivo, 'motivo' => 'Hotel', 'monto' => 8000],
            ['forma_pago_id' => $transferencia, 'motivo' => 'Pasajes', 'monto' => 2000],
            ['forma_pago_id' => 0, 'motivo' => '', 'monto' => 0],
        ]);
        $this->assertTrue($creado['ok'], implode(' ', $creado['errores']));
        $anio = (int) date('Y');
        $mes = (int) date('n');
        $estado = $socios->estado((int) $empresa['id'], $anio, $mes, true);
        $this->assertEquals(10000, $estado['total']);
        $this->assertCount(2, $estado['filas'][0]['lineas']);
        $this->assertTrue($socios->recuperar((int) $empresa['id'], (int) $estado['filas'][0]['id'])['ok']);
        $this->assertSame([], $socios->estado((int) $empresa['id'], $anio, $mes, true)['filas']);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/socios?anio=' . $anio . '&mes=' . $mes);
        $pagina->assertOK();
        $pagina->assertSee('Filial Norte');
        $pagina->assertSee('Hotel');
        $pagina->assertSee('Recuperado');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
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

        return [$ana, model(EmpresaModel::class)->find($ana['empresa_id'])];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function baseConCategoria(): array
    {
        [$ana, $empresa] = $this->base();
        $categoria = model(CategoriaModel::class)->where('empresa_id', $empresa['id'])->where('nombre', 'Alimentación')->first();

        return [$ana, $empresa, $categoria];
    }

    private function formaEfectivo(int $empresaId): int
    {
        (new \App\Libraries\FormasPago())->asegurar($empresaId);

        return (int) db_connect()->table('formas_pago')->where('empresa_id', $empresaId)->where('efectivo', 1)->get()->getRowArray()['id'];
    }
}
