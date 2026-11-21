<?php

declare(strict_types=1);

use App\Libraries\ArchivoHoja;
use App\Libraries\HojaInforme;
use App\Models\EmpresaModel;
use App\Models\UsuarioModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * @internal
 */
final class ExportarRecepcionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = 'App';

    protected function tearDown(): void
    {
        putenv('whatsapp.verifyToken');
        putenv('correo.secreto');
        unset($_ENV['whatsapp.verifyToken'], $_ENV['correo.secreto']);
        parent::tearDown();
    }

    public function testExcelYPdfSalenDelMismoInforme(): void
    {
        $ana = $this->cuenta();
        $pagina = $this->withSession(['usuario_id' => $ana['id']])->get('informes/rendiciones?anio=2026&mes=11');
        $pagina->assertOK();
        $pagina->assertSee('Excel');
        $pagina->assertSee('PDF');

        $excel = $this->withSession(['usuario_id' => $ana['id']])->get('informes/rendiciones/excel?anio=2026&mes=11');
        $excel->assertOK();
        $excel->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $excel->assertHeader('Content-Disposition', 'attachment; filename="rendiciones-2026-11.xlsx"');

        $pdf = $this->withSession(['usuario_id' => $ana['id']])->get('informes/rendiciones/pdf?anio=2026&mes=11');
        $pdf->assertOK();
        $pdf->assertHeader('Content-Type', 'application/pdf');
        $pdf->assertHeader('Content-Disposition', 'attachment; filename="rendiciones-2026-11.pdf"');

        $tablas = HojaInforme::tablas('rendiciones', ['filas' => [], 'por_usuario' => []], 'CLP');
        $this->assertStringStartsWith("PK", ArchivoHoja::excel('Rendiciones', $tablas));
        $this->assertStringStartsWith('%PDF', ArchivoHoja::pdf('Rendiciones', $tablas));
    }

    public function testWhatsappUneEnvioYReceptor(): void
    {
        $ana = $this->cuenta();
        model(EmpresaModel::class)->update($ana['empresa_id'], [
            'whatsapp'       => '+56 9 1111 2222',
            'correo_boletas' => 'boletas@acme.test',
        ]);
        model(UsuarioModel::class)->update($ana['id'], ['telefono' => '9 3333 4444']);

        $empresa = model(EmpresaModel::class)->find($ana['empresa_id']);
        $persona = model(UsuarioModel::class)->find($ana['id']);
        $this->assertSame('56911112222', $empresa['whatsapp']);
        $this->assertSame('56933334444', $persona['telefono']);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry'  => [[
                'changes' => [[
                    'value' => [
                        'metadata' => ['display_phone_number' => '56911112222'],
                        'messages' => [[
                            'from' => '56933334444',
                            'id'   => 'wamid.ana',
                            'type' => 'image',
                            'image' => ['id' => 'media-1', 'mime_type' => 'image/jpeg', 'caption' => 'Almuerzo con cliente'],
                        ]],
                    ],
                ]],
            ]],
        ];
        $this->withBodyFormat('json')->post('webhooks/whatsapp', $payload)->assertOK();
        $this->withBodyFormat('json')->post('webhooks/whatsapp', $payload)->assertOK();

        $filas = db_connect()->table('entradas')->get()->getResultArray();
        $this->assertCount(1, $filas);
        $this->assertSame((int) $ana['id'], (int) $filas[0]['usuario_id']);
        $this->assertSame((int) $ana['empresa_id'], (int) $filas[0]['empresa_id']);
        $this->assertSame('Almuerzo con cliente', $filas[0]['texto']);
        $this->assertSame('whatsapp', $filas[0]['canal']);

        $ajeno = $payload;
        $ajeno['entry'][0]['changes'][0]['value']['messages'][0]['from'] = '56900000000';
        $ajeno['entry'][0]['changes'][0]['value']['messages'][0]['id'] = 'wamid.ajeno';
        $ajeno['entry'][0]['changes'][0]['value']['messages'][0]['image']['caption'] = 'Número desconocido';
        $this->withBodyFormat('json')->post('webhooks/whatsapp', $ajeno)->assertOK();
        $desconocido = db_connect()->table('entradas')->where('mensaje_id', 'wamid.ajeno')->get()->getRowArray();
        $this->assertNull($desconocido['usuario_id']);

        $otroReceptor = $payload;
        $otroReceptor['entry'][0]['changes'][0]['value']['metadata']['display_phone_number'] = '56999999999';
        $otroReceptor['entry'][0]['changes'][0]['value']['messages'][0]['id'] = 'wamid.otro';
        $this->withBodyFormat('json')->post('webhooks/whatsapp', $otroReceptor)->assertOK();
        $this->assertSame(2, db_connect()->table('entradas')->countAllResults());

        $bandeja = $this->withSession(['usuario_id' => $ana['id']])->get('gastos');
        $bandeja->assertOK();
        $bandeja->assertSee('Llegó por WhatsApp desde 56933334444');
        $bandeja->assertSee('Almuerzo con cliente');
        $bandeja->assertSee('El teléfono de origen no está en las personas.');
    }

    public function testCorreoUneEnvioYReceptor(): void
    {
        $ana = $this->cuenta();
        model(EmpresaModel::class)->update($ana['empresa_id'], [
            'correo_boletas' => 'boletas@acme.test',
        ]);
        putenv('correo.secreto=secreto-prueba');
        $_ENV['correo.secreto'] = 'secreto-prueba';

        $cerrado = $this->withBodyFormat('json')->post('webhooks/correo', [
            'hacia' => 'boletas@acme.test',
            'desde' => 'ana@acme.test',
            'texto' => 'Factura del taxi',
        ]);
        $cerrado->assertStatus(401);

        $abierto = $this->withBodyFormat('json')
            ->withHeaders(['X-Rindete-Correo' => 'secreto-prueba'])
            ->post('webhooks/correo', [
                'hacia'       => 'boletas@acme.test',
                'desde'       => 'ana@acme.test',
                'texto'       => 'Factura del taxi',
                'mensaje_id'  => 'correo-1',
                'archivo'     => base64_encode('%PDF-1.4 boleta'),
                'extension'   => 'pdf',
            ]);
        $abierto->assertOK();
        $fila = db_connect()->table('entradas')->where('mensaje_id', 'correo-1')->get()->getRowArray();
        $this->assertSame((int) $ana['id'], (int) $fila['usuario_id']);
        $this->assertSame('correo', $fila['canal']);
        $this->assertNotSame('', $fila['comprobante']);

        putenv('whatsapp.verifyToken=token-prueba');
        $_ENV['whatsapp.verifyToken'] = 'token-prueba';
        $reto = $this->get('webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=token-prueba&hub.challenge=reto-99');
        $reto->assertOK();
        $reto->assertSee('reto-99');
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
}
