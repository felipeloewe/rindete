<?php
require dirname(__DIR__) . '/includes/init.php';
require dirname(__DIR__) . '/includes/sitio.php';

$slug = (string) ($_GET['articulo'] ?? '');
if ($slug === '') {
    web_open(web_blog_seo());
    render('web/blog_index', ['articulos' => web_articulos_ordenados()]);
    web_close();
    exit;
}

$art = web_articulo($slug);
if (!$art) {
    web_404();
}

web_open(web_articulo_seo($art));
render('web/articulo', ['art' => $art]);
web_close();
