<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Módulo sin contratar</h1>
        <p><?= esc($aviso) ?></p>
    </div>
    <a class="boton" href="<?= base_url('informes') ?>">Volver</a>
</header>
<section class="bloque">
    <p><?= esc($detalle) ?></p>
    <?php if ($esAdmin) : ?>
        <p><a href="<?= base_url('modulos') ?>">Contratar el módulo</a></p>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
