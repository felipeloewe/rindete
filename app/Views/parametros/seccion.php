<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($titulo) ?></h1>
        <p><?= esc($bajada) ?></p>
    </div>
    <a class="boton claro" href="<?= base_url('parametros') ?>">Volver</a>
</header>
<section class="bloque">
    <?= $this->include('parametros/piezas/' . $pieza) ?>
</section>
<?= $this->endSection() ?>
