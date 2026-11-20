<?php

declare(strict_types=1);

use App\Libraries\Comprobante;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class ComprobanteTest extends CIUnitTestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = WRITEPATH . 'uploads-test-' . bin2hex(random_bytes(4));
        mkdir($this->tmpDir, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($this->tmpDir);

        $uploads = WRITEPATH . 'uploads';
        foreach (glob($uploads . DIRECTORY_SEPARATOR . '1_*') ?: [] as $archivo) {
            @unlink($archivo);
        }

        parent::tearDown();
    }

    public function testAceptaPdfConMagicBytes(): void
    {
        $ruta = $this->archivoTemporal('nota.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
        $archivo = new UploadedFile($ruta, 'nota.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new Comprobante())->guardar($archivo, 1);

        $this->assertTrue($resultado['ok'], $resultado['error']);
        $this->assertMatchesRegularExpression('/^1_[a-f0-9]{16}\.pdf$/', $resultado['nombre']);
        $this->assertSame('application/pdf', (new Comprobante())->mime($resultado['nombre']));
        (new Comprobante())->borrar($resultado['nombre']);
    }

    public function testAceptaPngConMagicBytes(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($png);
        $ruta = $this->archivoTemporal('foto.png', $png);
        $archivo = new UploadedFile($ruta, 'foto.png', 'image/png', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new Comprobante())->guardar($archivo, 1);

        $this->assertTrue($resultado['ok'], $resultado['error']);
        $this->assertStringEndsWith('.png', $resultado['nombre']);
        (new Comprobante())->borrar($resultado['nombre']);
    }

    public function testRechazaOctetStreamSinMagicValido(): void
    {
        $ruta = $this->archivoTemporal('payload.pdf', "MZ\0\0not-a-pdf-or-image");
        $archivo = new UploadedFile($ruta, 'payload.pdf', 'application/octet-stream', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new Comprobante())->guardar($archivo, 1);

        $this->assertFalse($resultado['ok']);
        $this->assertSame('', $resultado['nombre']);
        $this->assertSame('El archivo no coincide con su tipo.', $resultado['error']);
    }

    public function testRechazaExtensionPdfConContenidoPng(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
        $this->assertNotFalse($png);
        $ruta = $this->archivoTemporal('mentira.pdf', $png);
        $archivo = new UploadedFile($ruta, 'mentira.pdf', 'application/pdf', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new Comprobante())->guardar($archivo, 1);

        $this->assertFalse($resultado['ok']);
        $this->assertSame('El archivo no coincide con su tipo.', $resultado['error']);
    }

    public function testRechazaExtensionNoPermitida(): void
    {
        $ruta = $this->archivoTemporal('script.exe', "MZ\0\0");
        $archivo = new UploadedFile($ruta, 'script.exe', 'application/octet-stream', (int) filesize($ruta), UPLOAD_ERR_OK);

        $resultado = (new Comprobante())->guardar($archivo, 1);

        $this->assertFalse($resultado['ok']);
        $this->assertSame('Usa JPG, PNG, WEBP o PDF.', $resultado['error']);
    }

    private function archivoTemporal(string $nombre, string $contenido): string
    {
        $ruta = $this->tmpDir . DIRECTORY_SEPARATOR . $nombre;
        file_put_contents($ruta, $contenido);

        return $ruta;
    }
}
