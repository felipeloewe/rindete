<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'Rindete') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<?php
    $etapa = trim($this->renderSection('etapa'));
    $ruta  = trim(uri_string(), '/');
    $marca = static function (string $prefijo) use ($ruta): string {
        $activa = $prefijo === 'panel'
            ? $ruta === 'panel'
            : ($ruta === $prefijo || str_starts_with($ruta, $prefijo . '/'));

        return $activa ? ' class="activa"' : '';
    };
?>
<body class="etapa-<?= esc($etapa !== '' ? $etapa : 'trabajo') ?>">
    <header class="top">
        <a class="marca" href="<?= base_url(session('usuario_id') ? 'panel' : '/') ?>">Rindete</a>
        <nav>
            <?php if (session('usuario_id')) : ?>
                <a<?= $marca('panel') ?> href="<?= base_url('panel') ?>">Inicio</a>
                <a<?= $marca('gastos') ?> href="<?= base_url('gastos') ?>">Gastos</a>
                <a<?= $marca('categorias') ?> href="<?= base_url('categorias') ?>">Categorías</a>
                <a<?= $marca('empresa') ?> href="<?= base_url('empresa') ?>">Empresa</a>
                <a<?= $marca('cuenta') ?> href="<?= base_url('cuenta') ?>">Cuenta</a>
                <a href="<?= base_url('logout') ?>">Salir</a>
            <?php else : ?>
                <a href="<?= base_url('login') ?>">Entrar</a>
                <a href="<?= base_url('registro') ?>">Crear empresa</a>
            <?php endif ?>
        </nav>
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
