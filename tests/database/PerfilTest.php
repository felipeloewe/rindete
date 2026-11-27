<?php

declare(strict_types=1);

use App\Models\CategoriaModel;
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
final class PerfilTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $namespace = 'App';
    protected $refresh   = true;

    public function testMiPerfilReuneDatosEstadoYAlertas(): void
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
        $ana = model(UsuarioModel::class)->porEmail('ana@acme.test');
        model(UsuarioModel::class)->update($ana['id'], ['rinde' => 1]);
        $categoria = model(CategoriaModel::class)->where('nombre', 'Alimentación')->first();
        model(GastoModel::class)->insert([
            'empresa_id'   => $ana['empresa_id'],
            'usuario_id'   => $ana['id'],
            'categoria_id' => $categoria['id'],
            'concepto'     => 'Taxi del centro',
            'proveedor'    => 'Local',
            'fecha'        => '2026-11-04',
            'monto'        => 3000,
            'moneda'       => 'CLP',
            'estado'       => 'observado',
            'creado'       => '2026-11-04 12:00:00',
        ]);
        $ahora = '2026-11-04 12:00:00';
        $solicitud = model(SolicitudModel::class)->insert([
            'empresa_id'   => $ana['empresa_id'],
            'usuario_id'   => $ana['id'],
            'categoria_id' => $categoria['id'],
            'motivo'       => 'Pasajes',
            'monto'        => 8000,
            'moneda'       => 'CLP',
            'estado'       => 'en_aceptacion',
            'detalle'      => '',
            'turno_orden'  => 0,
            'creada'       => $ahora,
        ], true);
        model(FondoModel::class)->insert([
            'empresa_id'   => $ana['empresa_id'],
            'usuario_id'   => $ana['id'],
            'solicitud_id' => $solicitud,
            'motivo'       => 'Caja de viaje',
            'monto'        => 8000,
            'saldo'        => 8000,
            'moneda'       => 'CLP',
            'estado'       => 'abierto',
            'creado'       => $ahora,
        ]);
        $rendicion = (int) model(RendicionModel::class)->insert([
            'empresa_id'       => $ana['empresa_id'],
            'usuario_id'       => $ana['id'],
            'titulo'           => 'Viaje abierto',
            'nota'             => '',
            'estado'           => 'abierta',
            'creada'           => $ahora,
            'enviada'          => null,
            'origen'           => 'directa',
            'monto_por_rendir' => 0,
        ], true);
        db_connect()->table('rendicion_pasos')->insert([
            'rendicion_id' => $rendicion,
            'usuario_id'   => $ana['id'],
            'orden'        => 1,
            'decision'     => 'rechazada',
            'detalle'      => 'Falta el respaldo.',
            'creada'       => $ahora,
        ]);

        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('cuenta');
        $pagina->assertOK();
        $pagina->assertSee('Mi perfil');
        $pagina->assertSee('Mis datos');
        $pagina->assertSee('Acme SpA');
        $pagina->assertSee('Administrador y usuario');
        $pagina->assertSee('On work');
        $pagina->assertDontSee('Lo que tienes que hacer');
        $pagina->assertSee('Corrige el gasto Taxi del centro.');
        $pagina->assertSee('Retoma la rendición Viaje abierto.');
        $pagina->assertSee('Fondos a rendir');
        $pagina->assertSee('Caja de viaje');
        $pagina->assertSee('Viaje abierto');
        $pagina->assertSee('Solicitaste el fondo Pasajes.');
        $pagina->assertSee('El gasto Taxi del centro está objetado.');
        $pagina->assertSee('La rendición Viaje abierto fue objetada.');
        $pagina->assertSee('Salir');
        $pagina->assertDontSee('Nuevo gasto');
        $pagina->assertDontSee('>Inicio<');
    }
}
