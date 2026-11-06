<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/sitio.php';

$slug = (string) ($_GET['pais'] ?? '');
$landing = web_landing($slug);
if (!$landing) {
    web_404();
}

web_open(web_landing_seo($landing));
render('web/pais', [
    'landing' => $landing,
    'fiscal' => pais($landing['codigo']),
]);
web_close();
