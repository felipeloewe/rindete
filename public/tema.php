<?php
require dirname(__DIR__) . '/includes/init.php';
require dirname(__DIR__) . '/includes/sitio.php';

$slug = (string) ($_GET['tema'] ?? '');
$tema = web_tema($slug);
if (!$tema) {
    web_404();
}

web_open(web_tema_seo($tema));
render('web/tema', ['tema' => $tema]);
web_close();
