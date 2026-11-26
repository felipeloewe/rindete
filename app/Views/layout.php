<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'Rindete') ?></title>
    <link rel="icon" href="<?= base_url('favicon.svg') ?>" type="image/svg+xml">
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= base_url('apple-touch-icon.png') ?>">
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
    } elseif ($ruta === 'rendiciones' || str_starts_with($ruta, 'rendiciones/')) {
        $seccion = 'rendiciones';
    } elseif ($ruta === 'fondos' || str_starts_with($ruta, 'fondos/') || in_array($ruta, ['documentos', 'cargos', 'caja', 'socios'], true) || str_starts_with($ruta, 'documentos/') || str_starts_with($ruta, 'socios/')) {
        $seccion = 'fondos';
    } elseif ($ruta === 'modulos' || str_starts_with($ruta, 'modulos/')) {
        $seccion = 'parametros';
    } elseif ($ruta === 'informes' || str_starts_with($ruta, 'informes/')) {
        $seccion = 'informes';
    } elseif ($ruta === 'categorias' || str_starts_with($ruta, 'categorias/')) {
        $seccion = 'categorias';
    } elseif ($ruta === 'parametros' || str_starts_with($ruta, 'parametros/')) {
        $seccion = [
            'parametros/cuentas'       => 'gastos',
            'parametros/clasificacion' => 'rendiciones',
            'parametros/conexiones'    => 'categorias',
            'parametros/tarjetas'      => 'empresa',
            'parametros/medios'        => 'empresa',
            'parametros/aceptados'     => 'cuenta',
            'parametros/tipos'         => 'param-tipos',
            'parametros/preasientos'   => 'informes',
        ][$ruta] ?? 'parametros';
    } elseif ($ruta === 'personas' || str_starts_with($ruta, 'personas/')) {
        $seccion = 'personas';
    } elseif ($ruta === 'empresa' || str_starts_with($ruta, 'empresa/')) {
        $seccion = 'empresa';
    } elseif ($ruta === 'politicas' || str_starts_with($ruta, 'politicas/') || $ruta === 'condiciones') {
        $seccion = 'cuenta';
    } elseif ($ruta === 'cuenta' || str_starts_with($ruta, 'cuenta/')) {
        $seccion = 'cuenta';
    } elseif ($ruta === 'master' || str_starts_with($ruta, 'master/')) {
        $seccion = 'cuenta';
    }
    $esMaestro = (bool) session('maestro_id') && ($ruta === 'master' || str_starts_with($ruta, 'master/'));
    $esAdmin = false;
    if (session('usuario_id')) {
        $personaNav = (new \App\Libraries\Sesion())->usuario();
        $esAdmin = is_array($personaNav) && ($personaNav['rol'] ?? '') === 'administrador';
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
        <a class="marca" href="<?= base_url($esMaestro ? 'master' : (session('usuario_id') ? 'panel' : '/')) ?>">
            <svg class="logo" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true">
                <rect x="3" y="8" width="16" height="18" rx="4" fill="#8fd6cf"/>
                <rect x="13" y="4" width="16" height="18" rx="4" fill="#0e5c62"/>
            </svg>
            Rindete
        </a>
        <nav>
            <?php if ($esMaestro) : ?>
                <a class="activa" href="<?= base_url('master') ?>">Empresas</a>
            <?php elseif (session('usuario_id')) : ?>
                <a<?= $marca('panel') ?> href="<?= base_url('panel') ?>">Inicio</a>
                <a<?= $marca('gastos') ?> href="<?= base_url('gastos') ?>">Gastos</a>
                <a<?= $marca('rendiciones') ?> href="<?= base_url('rendiciones') ?>">Rendiciones</a>
                <a<?= $marca('fondos') ?> href="<?= base_url('fondos') ?>">Fondos</a>
                <a<?= $marca('informes') ?> href="<?= base_url('informes') ?>">Informes</a>
                <a<?= $marca('categorias') ?> href="<?= base_url('categorias') ?>">Categorías</a>
                <?php if ($esAdmin) : ?>
                    <?php $esIntegracion = $ruta === 'parametros/medios' || str_starts_with($ruta, 'parametros/medios/'); ?>
                    <a<?= ! $esIntegracion && ($ruta === 'parametros' || str_starts_with($ruta, 'parametros/')) ? ' class="activa"' : '' ?> href="<?= base_url('parametros') ?>">Parámetros</a>
                    <a<?= $esIntegracion ? ' class="activa"' : '' ?> href="<?= base_url('parametros/medios') ?>">Integraciones</a>
                    <a<?= $marca('modulos') ?> href="<?= base_url('modulos') ?>">Módulos</a>
                    <a<?= $marca('personas') ?> href="<?= base_url('personas') ?>">Personas</a>
                <?php endif ?>
                <a<?= $marca('empresa') ?> href="<?= base_url('empresa') ?>">Empresa</a>
                <a<?= $marca('cuenta') ?> href="<?= base_url('cuenta') ?>">Cuenta</a>
            <?php endif ?>
        </nav>
        <div class="lado">
            <?php if ($esMaestro) : ?>
                <a class="enlace" href="<?= base_url('master/salir') ?>">Salir</a>
            <?php elseif (session('usuario_id')) : ?>
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
        <?php foreach (session('avisos') ?? [] as $mensaje) : ?>
            <p class="aviso nota"><?= esc($mensaje) ?></p>
        <?php endforeach ?>
        <?= $this->renderSection('content') ?>
    </main>
    <dialog id="aviso-campos" class="popup">
        <h2>Falta información</h2>
        <p>Debe completar toda la información solicitada.</p>
        <ul></ul>
        <form method="dialog">
            <button class="boton" type="submit">Entendido</button>
        </form>
    </dialog>
    <script src="<?= base_url('js/validacion.js') ?>"></script>
</body>
</html>
