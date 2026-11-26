<?php

declare(strict_types=1);

use App\Libraries\SolicitudServicio;
use App\Models\AceptadorModel;
use App\Models\CategoriaModel;
use App\Models\EmpresaModel;
use App\Models\FondoModel;
use App\Models\SolicitudModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class FondosTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testSinFlujoLaSolicitudQuedaAceptada(): void
    {
        [$ana, $empresa] = $this->empresa();
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $pedido = (new SolicitudServicio())->pedir($ana, $empresa, 'Viaje corto', '8000', (int) $categoria['id']);

        $this->assertTrue($pedido['ok'], implode(' ', $pedido['errores']));
        $this->assertSame('aceptada', $pedido['solicitud']['estado']);
        $this->assertSame(0, model(FondoModel::class)->countAllResults());
    }

    public function testElMontoYElGastoDetienenLaSolicitud(): void
    {
        [$ana, $empresa] = $this->empresa();
        model(EmpresaModel::class)->update($empresa['id'], ['monto_maximo_rendicion' => 10000]);
        $empresa = model(EmpresaModel::class)->find($empresa['id']);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $servicio = new SolicitudServicio();

        $sobre = $servicio->pedir($ana, $empresa, 'Viaje largo', '25000', (int) $categoria['id']);
        $this->assertSame('detenida', $sobre['solicitud']['estado']);
        $this->assertSame('Supera el monto máximo de una rendición.', $sobre['solicitud']['detalle']);

        db_connect()->table('gastos_aceptados')->where('categoria_id', $categoria['id'])->delete();
        $ajena = $servicio->pedir($ana, $empresa, 'Otra cosa', '3000', (int) $categoria['id']);
        $this->assertSame('detenida', $ajena['solicitud']['estado']);
        $this->assertSame('Ese gasto no está permitido para esta persona.', $ajena['solicitud']['detalle']);
    }

    public function testElFlujoAceptaYTesoreriaEntregaElFondo(): void
    {
        [$ana, $empresa] = $this->empresa();
        $luis = $this->rendidor((int) $empresa['id']);
        model(AceptadorModel::class)->insert(['empresa_id' => $empresa['id'], 'usuario_id' => $ana['id'], 'orden' => 1]);
        model(AceptadorModel::class)->insert(['empresa_id' => $empresa['id'], 'usuario_id' => $luis['id'], 'orden' => 2]);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Transporte')->first();
        $servicio = new SolicitudServicio();
        $pedido = $servicio->pedir($ana, $empresa, 'Pasajes', '12000', (int) $categoria['id']);

        $this->assertSame('en_aceptacion', $pedido['solicitud']['estado']);
        $this->assertSame((int) $ana['id'], (int) $pedido['solicitud']['turno_usuario_id']);
        $ajeno = $servicio->aceptar($pedido['solicitud'], $luis);
        $this->assertFalse($ajeno['ok']);

        $primera = $servicio->aceptar($pedido['solicitud'], $ana);
        $this->assertSame('en_aceptacion', $primera['solicitud']['estado']);
        $this->assertSame((int) $luis['id'], (int) $primera['solicitud']['turno_usuario_id']);
        $segunda = $servicio->aceptar($primera['solicitud'], $luis);
        $this->assertSame('aceptada', $segunda['solicitud']['estado']);
        $this->assertFalse($servicio->entregar($segunda['solicitud'], $luis)['ok']);

        $entrega = $servicio->entregar($segunda['solicitud'], $ana);
        $this->assertSame('entregada', $entrega['solicitud']['estado']);
        $fondo = model(FondoModel::class)->where('solicitud_id', $entrega['solicitud']['id'])->first();
        $this->assertSame(12000.0, (float) $fondo['saldo']);
        $this->assertSame(12000.0, (float) $fondo['monto']);
        $this->assertSame('abierto', $fondo['estado']);
    }

    public function testLaPantallaMuestraElPedido(): void
    {
        [$ana] = $this->empresa();
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('fondos');
        $pagina->assertOK();
        $pagina->assertSee('Pedir fondo');

        $seguridad = service('security');
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        $envio = $this->withSession(['usuario_id' => $ana['id']])->post('fondos', [
            'motivo'                       => 'Almuerzo de trabajo',
            'monto'                        => '4500',
            'categoria_id'                 => (string) $categoria['id'],
            $seguridad->getTokenName()     => $seguridad->getHash(),
        ]);
        $envio->assertRedirect();
        $this->assertSame('aceptada', model(SolicitudModel::class)->first()['estado']);
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
        model(UsuarioModel::class)->update($ana['id'], ['rinde' => 1]);
        $ana = model(UsuarioModel::class)->find($ana['id']);

        return [$ana, model(EmpresaModel::class)->find($ana['empresa_id'])];
    }

    /**
     * @return array<string, mixed>
     */
    private function rendidor(int $empresaId): array
    {
        $id = model(UsuarioModel::class)->insert([
            'empresa_id' => $empresaId,
            'nombre'     => 'Luis',
            'email'      => 'luis@acme.test',
            'password'   => password_hash('clave-segura', PASSWORD_DEFAULT),
            'rol'        => 'rendidor',
            'activo'     => 1,
            'creado'     => date('Y-m-d H:i:s'),
        ] + \App\Libraries\PoliticaDatos::marca(), true);

        return model(UsuarioModel::class)->find($id);
    }
}
