<?php

require __DIR__ . '/sitio_datos.php';

function web_public($path) {
    $path = trim((string) $path, '/');
    if ($path === '' || str_contains($path, '.php')) {
        return $path;
    }
    return $path . '/';
}

function web_href($path) {
    if (preg_match('#^https?://#', (string) $path)) {
        return $path;
    }
    return url(web_public($path));
}

function web_uri() {
    $uri = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if (!is_string($uri) || $uri === '') {
        $uri = '/';
    }
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = '/' . trim($uri, '/');
    if (str_ends_with($uri, '/index.php')) {
        $uri = substr($uri, 0, -strlen('index.php'));
        $uri = '/' . trim($uri, '/');
    }
    if ($uri === '/index.php' || $uri === '/') {
        return '/';
    }
    if ($uri === '/pais.php') {
        $pais = (string) ($_GET['pais'] ?? '');
        if (isset(web_landings()[$pais])) {
            return '/' . $pais;
        }
    }
    if ($uri === '/tema.php') {
        $tema = (string) ($_GET['tema'] ?? '');
        if (isset(web_temas()[$tema])) {
            return '/' . $tema;
        }
    }
    if ($uri === '/blog.php') {
        $art = (string) ($_GET['articulo'] ?? '');
        if ($art !== '' && isset(web_articulos()[$art])) {
            return '/blog/' . $art;
        }
        return '/blog';
    }
    return $uri;
}

function web_actual($path) {
    $uri = web_uri();
    $path = '/' . trim((string) $path, '/');
    if ($path === '/') {
        return $uri === '/';
    }
    return $uri === $path || str_starts_with($uri, $path . '/');
}

function web_fecha($iso) {
    $meses = [1 => 'ene', 2 => 'feb', 3 => 'mar', 4 => 'abr', 5 => 'may', 6 => 'jun', 7 => 'jul', 8 => 'ago', 9 => 'sep', 10 => 'oct', 11 => 'nov', 12 => 'dic'];
    $time = strtotime($iso . ' 12:00:00');
    if ($time === false) {
        return (string) $iso;
    }
    return (int) date('j', $time) . ' ' . $meses[(int) date('n', $time)] . ' ' . date('Y', $time);
}

function web_inline($parts) {
    if (is_string($parts)) {
        return e($parts);
    }
    $html = '';
    foreach ($parts as $part) {
        $kind = $part[0] ?? '';
        if ($kind === 't' || $kind === 'b') {
            $text = e($part[1] ?? '');
            $html .= $kind === 'b' ? '<strong>' . $text . '</strong>' : $text;
        } elseif ($kind === 'a') {
            $raw = (string) ($part[1] ?? '');
            $href = web_href($raw);
            $extra = preg_match('#^https?://#', $raw) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $html .= '<a href="' . e($href) . '"' . $extra . '>' . e($part[2] ?? '') . '</a>';
        }
    }
    return $html;
}

function web_bloques(array $bloques) {
    $html = '';
    foreach ($bloques as $bloque) {
        $tipo = $bloque[0] ?? '';
        $dato = $bloque[1] ?? '';
        if ($tipo === 'h2') {
            $html .= '<h2>' . e($dato) . '</h2>';
        } elseif ($tipo === 'p') {
            $html .= '<p>' . web_inline($dato) . '</p>';
        } elseif ($tipo === 'ul') {
            $html .= '<ul>';
            foreach ($dato as $item) {
                $html .= '<li>' . web_inline($item) . '</li>';
            }
            $html .= '</ul>';
        } elseif ($tipo === 'nota') {
            $html .= '<aside class="note"><p>' . web_inline($dato) . '</p></aside>';
        } elseif ($tipo === 'tabla' && is_array($dato) && count($dato) > 1) {
            $headers = $dato[0];
            $html .= '<div class="compare">';
            foreach (array_slice($dato, 1) as $fila) {
                $html .= '<article class="compare-card"><strong>' . e($fila[0] ?? '') . '</strong><dl>';
                $max = count($headers);
                for ($i = 1; $i < $max; $i++) {
                    $html .= '<div><dt>' . e($headers[$i] ?? '') . '</dt><dd>' . e($fila[$i] ?? '') . '</dd></div>';
                }
                $html .= '</dl></article>';
            }
            $html .= '</div>';
        }
    }
    return $html;
}

function web_articulos_ordenados() {
    $items = web_articulos();
    uasort($items, function ($a, $b) {
        return strcmp($b['fecha'], $a['fecha']);
    });
    return $items;
}

function web_articulos_recientes($cantidad = 3) {
    return array_slice(web_articulos_ordenados(), 0, $cantidad, true);
}

function web_conceptos() {
    $lista = [];
    foreach (web_temas() as $tema) {
        if (($tema['grupo'] ?? '') === 'concepto') {
            $lista[] = $tema;
        }
    }
    return $lista;
}

function web_org_node() {
    return [
        '@type' => 'Organization',
        'name' => 'PideTuFactura',
        'url' => absolute_url(''),
    ];
}

function web_page_node($name, $description, $path, $lang = 'es') {
    return [
        '@type' => 'WebPage',
        'name' => $name,
        'description' => $description,
        'url' => absolute_url(web_public($path)),
        'inLanguage' => $lang,
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'PideTuFactura',
            'url' => absolute_url(''),
        ],
    ];
}

function web_breadcrumb_ld(array $items) {
    $list = [];
    $i = 1;
    foreach ($items as $item) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $i,
            'name' => $item[0],
        ];
        if (isset($item[1])) {
            $entry['item'] = absolute_url(web_public($item[1]));
        }
        $list[] = $entry;
        $i++;
    }
    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function web_faq_ld(array $faq) {
    $main = [];
    foreach ($faq as $item) {
        $main[] = [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $item['a'],
            ],
        ];
    }
    return [
        '@type' => 'FAQPage',
        'mainEntity' => $main,
    ];
}

function web_ld(array $nodes) {
    return [
        '@context' => 'https://schema.org',
        '@graph' => array_values($nodes),
    ];
}

function web_meta($title, $description, $path, array $extra = []) {
    return [
        'title' => $title,
        'description' => $description,
        'canonical' => absolute_url(web_public($path)),
        'robots' => 'index, follow',
        'lang' => $extra['lang'] ?? 'es',
        'locale' => $extra['locale'] ?? 'es_ES',
        'type' => $extra['type'] ?? 'website',
        'jsonld' => $extra['jsonld'] ?? web_ld([
            web_org_node(),
            web_page_node($title, $description, $path, $extra['lang'] ?? 'es'),
        ]),
    ];
}

function web_home_seo() {
    $title = 'Rendición de gastos, caja menor y reembolsos';
    $description = 'Rendición de gastos, caja menor y reembolsos para empresas en Chile, Colombia, México, Perú, Ecuador y El Salvador, con el comprobante de cada país.';
    $faq = web_home_faq();
    return web_meta($title, $description, '', [
        'jsonld' => web_ld([
            web_org_node(),
            [
                '@type' => 'WebSite',
                'name' => 'PideTuFactura',
                'url' => absolute_url(''),
                'inLanguage' => 'es',
                'description' => $description,
            ],
            [
                '@type' => 'SoftwareApplication',
                'name' => 'PideTuFactura',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'url' => absolute_url(''),
                'inLanguage' => 'es',
                'description' => $description,
            ],
            web_page_node($title, $description, ''),
            web_faq_ld($faq),
        ]),
    ]);
}

function web_tema_seo(array $tema) {
    $crumbs = [
        ['Inicio', ''],
        [$tema['nav'], $tema['slug']],
    ];
    $nodes = [
        web_org_node(),
        web_page_node($tema['h1'], $tema['description'], $tema['slug']),
        web_breadcrumb_ld($crumbs),
    ];
    if (!empty($tema['faq'])) {
        $nodes[] = web_faq_ld($tema['faq']);
    }
    return web_meta($tema['titulo'], $tema['description'], $tema['slug'], [
        'jsonld' => web_ld($nodes),
    ]);
}

function web_landing_seo(array $landing) {
    $crumbs = [
        ['Inicio', ''],
        [$landing['nombre'], $landing['slug']],
    ];
    return web_meta($landing['titulo'], $landing['description'], $landing['slug'], [
        'lang' => $landing['lang'],
        'locale' => $landing['locale'],
        'jsonld' => web_ld([
            web_org_node(),
            web_page_node($landing['h1'], $landing['description'], $landing['slug'], $landing['lang']),
            web_breadcrumb_ld($crumbs),
            web_faq_ld($landing['faq']),
            [
                '@type' => 'SoftwareApplication',
                'name' => 'PideTuFactura',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'url' => absolute_url(''),
                'description' => $landing['description'],
                'inLanguage' => $landing['lang'],
            ],
        ]),
    ]);
}

function web_blog_seo() {
    $title = 'Guías de rendición, caja menor y reembolsos';
    $description = 'Guías para equipos de gastos en Latam: cómo se dice rendición, caja menor, caja chica y reembolso en cada país.';
    return web_meta($title, $description, 'blog', [
        'jsonld' => web_ld([
            web_org_node(),
            [
                '@type' => 'Blog',
                'name' => 'Guías de PideTuFactura',
                'url' => absolute_url(web_public('blog')),
                'inLanguage' => 'es',
                'description' => $description,
            ],
            web_page_node($title, $description, 'blog'),
            web_breadcrumb_ld([
                ['Inicio', ''],
                ['Guías', 'blog'],
            ]),
        ]),
    ]);
}

function web_articulo_seo(array $art) {
    $path = 'blog/' . $art['slug'];
    $crumbs = [
        ['Inicio', ''],
        ['Guías', 'blog'],
        [$art['titulo'], $path],
    ];
    return web_meta($art['titulo'], $art['extracto'], $path, [
        'type' => 'article',
        'jsonld' => web_ld([
            web_org_node(),
            [
                '@type' => 'BlogPosting',
                'headline' => $art['titulo'],
                'description' => $art['extracto'],
                'datePublished' => $art['fecha'],
                'dateModified' => $art['fecha'],
                'inLanguage' => 'es',
                'mainEntityOfPage' => absolute_url(web_public($path)),
                'author' => [
                    '@type' => 'Organization',
                    'name' => 'PideTuFactura',
                    'url' => absolute_url(''),
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'PideTuFactura',
                    'url' => absolute_url(''),
                ],
            ],
            web_breadcrumb_ld($crumbs),
        ]),
    ]);
}

function web_sitemap() {
    $hoy = '2026-09-30';
    $urls = [
        ['', $hoy],
        ['blog', $hoy],
    ];
    foreach (web_temas() as $tema) {
        $urls[] = [$tema['slug'], $hoy];
    }
    foreach (web_landings() as $landing) {
        $urls[] = [$landing['slug'], $hoy];
    }
    foreach (web_articulos() as $art) {
        $urls[] = ['blog/' . $art['slug'], $art['fecha']];
    }
    return $urls;
}

function web_open(array $seo) {
    page_open($seo['title'], ['css/base.css', 'css/web.css'], 'site', $seo);
    render('web/nav');
}

function web_close() {
    render('web/footer');
}

function web_404() {
    http_response_code(404);
    web_open([
        'title' => 'Página no encontrada',
        'description' => 'Esa página no está en PideTuFactura.',
        'robots' => 'noindex',
    ]);
    render('web/noencontrada');
    web_close();
    exit;
}
