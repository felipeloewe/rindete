<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Rendición de gastos para la empresa</h1>
    <p>Crea la cuenta de tu empresa. Después vas a cargar gastos, rendiciones y el pedido de factura.</p>
    <p><a class="boton" href="<?= base_url('registro') ?>">Crear empresa</a></p>
</section>
<?= $this->endSection() ?>
