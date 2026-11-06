<?php
require __DIR__ . '/includes/init.php';

header('Content-Type: text/plain; charset=UTF-8');
$base = base_path();
$prefix = $base === '' ? '' : $base;
echo "User-agent: *\n";
echo "Allow: {$prefix}/\n";
$bloqueadas = [
    'panel.php',
    'login.php',
    'registro.php',
    'logout.php',
    'recuperar.php',
    'restablecer.php',
    'gastos.php',
    'gasto.php',
    'informes.php',
    'informe.php',
    'usuarios.php',
    'categorias.php',
    'politicas.php',
    'perfil.php',
    'uploads/',
    'data/',
];
foreach ($bloqueadas as $ruta) {
    echo 'Disallow: ' . $prefix . '/' . $ruta . "\n";
}
echo 'Sitemap: ' . absolute_url('sitemap.xml') . "\n";
