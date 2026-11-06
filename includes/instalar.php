<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/init.php';
$data = store();
echo 'Empresas: ' . count($data['companies']) . PHP_EOL;
echo 'Usuarios: ' . count($data['users']) . PHP_EOL;
echo 'Gastos: ' . count($data['expenses']) . PHP_EOL;
echo 'Rendiciones: ' . count($data['reports']) . PHP_EOL;
