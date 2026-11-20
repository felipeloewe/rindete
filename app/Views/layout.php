<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'Rindete') ?></title>
    <link rel="stylesheet" href="<?= base_url('css/app.css') ?>">
</head>
<body>
    <header class="top">
        <a class="marca" href="<?= base_url(session('usuario_id') ? 'panel' : '/') ?>">Rindete</a>
        <nav>
            <?php if (session('usuario_id')) : ?>
                <a href="<?= base_url('panel') ?>">Inicio</a>
                <a href="<?= base_url('gastos') ?>">Gastos</a>
                <?php if (session('rol') === 'administrador') : ?>
                    <a href="<?= base_url('categorias') ?>">Categorías</a>
                <?php endif ?>
                <a href="<?= base_url('empresa') ?>">Empresa</a>
                <a href="<?= base_url('cuenta') ?>">Cuenta</a>
                <form class="plano salir" method="post" action="<?= base_url('logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit">Salir</button>
                </form>
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
