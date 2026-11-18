<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Módulos</h1>
        <p>El plan Free deja activos los módulos de la operación. Cada módulo de contabilidad se contrata solo, cuando la empresa lo necesita. El precio todavía no está definido: contratar lo activa aquí y se puede dejar después.</p>
    </div>
</header>
<section class="bloque">
    <h2>Incluidos</h2>
    <ul class="lista">
        <?php foreach ($gratis as $nombre) : ?>
            <li><?= esc($nombre) ?></li>
        <?php endforeach ?>
    </ul>
</section>
<?php foreach ($catalogo as $codigo => $modulo) : ?>
    <?php $activo = in_array($codigo, $contratados, true); ?>
    <section class="bloque">
        <div class="cabeza">
            <h2><?= esc($modulo['nombre']) ?></h2>
            <span><?= $activo ? 'Contratado' : 'Sin contratar' ?></span>
        </div>
        <p><?= esc($modulo['detalle']) ?></p>
        <form method="post" action="<?= base_url($activo ? 'modulos/dejar' : 'modulos/contratar') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="codigo" value="<?= esc($codigo) ?>">
            <button class="boton<?= $activo ? ' claro' : '' ?>" type="submit"><?= $activo ? 'Dejar' : 'Contratar' ?></button>
        </form>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
