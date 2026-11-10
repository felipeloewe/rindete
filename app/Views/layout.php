<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'Rindete') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600&family=Urbanist:wght@500;600;700&display=swap">
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<?php
    $etapa = trim($this->renderSection('etapa'));
    $ruta  = trim(uri_string(), '/');
    $seccion = 'entrada';
    if ($ruta === 'panel') {
        $seccion = 'panel';
    } elseif ($ruta === 'gastos' || str_starts_with($ruta, 'gastos/')) {
        $seccion = 'gastos';
    } elseif ($ruta === 'categorias' || str_starts_with($ruta, 'categorias/')) {
        $seccion = 'categorias';
    } elseif ($ruta === 'empresa' || str_starts_with($ruta, 'empresa/')) {
        $seccion = 'empresa';
    } elseif ($ruta === 'cuenta' || str_starts_with($ruta, 'cuenta/')) {
        $seccion = 'cuenta';
    }
    $marca = static function (string $prefijo) use ($ruta): string {
        $activa = $prefijo === 'panel'
            ? $ruta === 'panel'
            : ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/'));

        return $activa ? ' class="activa"' : '';
    };
?>
<body class="etapa-<?= esc($etapa !== '' ? $etapa : 'trabajo') ?> sec-<?= esc($seccion) ?>">
    <div class="franja"></div>
    <header class="top">
        <a class="marca" href="<?= base_url(session('usuario_id') ? 'panel' : '/') ?>">
            <svg class="logo" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true">
                <rect x="3" y="8" width="16" height="18" rx="4" fill="#8fd6cf"/>
                <rect x="13" y="4" width="16" height="18" rx="4" fill="#0e5c62"/>
            </svg>
            Rindete
        </a>
        <nav>
            <?php if (session('usuario_id')) : ?>
                <a<?= $marca('panel') ?> href="<?= base_url('panel') ?>">Inicio</a>
                <a<?= $marca('gastos') ?> href="<?= base_url('gastos') ?>">Gastos</a>
                <a<?= $marca('categorias') ?> href="<?= base_url('categorias') ?>">Categorías</a>
                <a<?= $marca('empresa') ?> href="<?= base_url('empresa') ?>">Empresa</a>
                <a<?= $marca('cuenta') ?> href="<?= base_url('cuenta') ?>">Cuenta</a>
            <?php endif ?>
        </nav>
        <div class="lado">
            <?php if (session('usuario_id')) : ?>
                <a class="enlace" href="<?= base_url('logout') ?>">Salir</a>
                <a class="boton" href="<?= base_url('gastos/nuevo') ?>">Nuevo gasto</a>
            <?php else : ?>
                <a class="enlace" href="<?= base_url('login') ?>">Entrar</a>
                <a class="boton" href="<?= base_url('registro') ?>">Crear empresa</a>
            <?php endif ?>
        </div>
    </header>
    <main>
        <?php if (session('ok')) : ?>
            <p class="aviso ok"><?= esc(session('ok')) ?></p>
        <?php endif ?>
        <?php if (session('error')) : ?>
            <p class="aviso mal"><?= esc(session('error')) ?></p>
        <?php endif ?>
        <?php foreach (session('errores') ?? [] as $mensaje) : ?>
            <p class="aviso mal"><?= esc($mensaje) ?></p>
        <?php endforeach ?>
        <?= $this->renderSection('content') ?>
    </main>
</body>
</html>
