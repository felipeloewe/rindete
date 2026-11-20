<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;

/**
 * Sets HSTS on responses when HTTPS is forced.
 *
 * CI4's forcehttps filter only attaches Strict-Transport-Security on the
 * HTTP→HTTPS redirect; this covers normal HTTPS responses. No preload.
 */
class HstsFilter implements FilterInterface
{
    private const MAX_AGE = 31_536_000; // 1 year

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $config = config(App::class);

        if ($config->forceGlobalSecureRequests !== true) {
            return null;
        }

        $response->setHeader(
            'Strict-Transport-Security',
            'max-age=' . self::MAX_AGE,
        );

        return $response;
    }
}
