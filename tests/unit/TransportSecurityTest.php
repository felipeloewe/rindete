<?php

declare(strict_types=1);

use App\Filters\HstsFilter;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Cookie;
use Config\Filters;

/**
 * @internal
 */
final class TransportSecurityTest extends CIUnitTestCase
{
    public function testForceHttpsIsRequiredAndSecureHeadersEnabled(): void
    {
        $filters = config(Filters::class);

        $this->assertContains('forcehttps', $filters->required['before']);
        $this->assertContains('secureheaders', $filters->globals['after']);
        $this->assertContains('hsts', $filters->globals['after']);
        $this->assertSame(HstsFilter::class, $filters->aliases['hsts']);
    }

    public function testCookieKeepsHttpOnlyAndSameSiteLax(): void
    {
        $cookie = config(Cookie::class);

        $this->assertTrue($cookie->httponly);
        $this->assertSame('Lax', $cookie->samesite);
        // PHPUnit runs with ENVIRONMENT=testing → Secure off so feature tests work.
        $this->assertFalse($cookie->secure);
        $this->assertFalse(config(App::class)->forceGlobalSecureRequests);
    }

    public function testHstsHeaderWhenHttpsForced(): void
    {
        $app = config(App::class);
        $previous = $app->forceGlobalSecureRequests;
        $app->forceGlobalSecureRequests = true;

        try {
            $request  = $this->makeRequest();
            $response = new Response(config(App::class));
            $filter   = new HstsFilter();

            $result = $filter->after($request, $response);

            $this->assertInstanceOf(Response::class, $result);
            $this->assertSame(
                'max-age=31536000',
                $result->getHeaderLine('Strict-Transport-Security'),
            );
        } finally {
            $app->forceGlobalSecureRequests = $previous;
        }
    }

    public function testHstsSkippedWhenHttpsNotForced(): void
    {
        $app = config(App::class);
        $previous = $app->forceGlobalSecureRequests;
        $app->forceGlobalSecureRequests = false;

        try {
            $request  = $this->makeRequest();
            $response = new Response(config(App::class));
            $filter   = new HstsFilter();

            $this->assertNull($filter->after($request, $response));
            $this->assertSame('', $response->getHeaderLine('Strict-Transport-Security'));
        } finally {
            $app->forceGlobalSecureRequests = $previous;
        }
    }

    public function testHtaccessRedirectsHttpToHttps(): void
    {
        $htaccess = file_get_contents(ROOTPATH . 'public/.htaccess');

        $this->assertNotFalse($htaccess);
        $this->assertStringContainsString('RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]', $htaccess);
        $this->assertStringContainsString('Strict-Transport-Security "max-age=31536000"', $htaccess);
        $this->assertStringContainsString('localhost', $htaccess);
    }

    private function makeRequest(): IncomingRequest
    {
        return new IncomingRequest(
            config(App::class),
            new URI('http://example.com/'),
            null,
            new UserAgent(),
        );
    }
}
