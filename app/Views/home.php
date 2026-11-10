<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="hero">
    <h1>Rendición de gastos para la empresa</h1>
    <p>Crea la cuenta de tu empresa. Después vas a cargar gastos, rendiciones y el pedido de factura.</p>
    <p><a class="boton" href="<?= base_url('registro') ?>">Crear empresa</a></p>
</section>
<div class="mosaico">
    <article class="losa losa-gastos">
        <h2>Gastos</h2>
        <span>El comprobante y la categoría.</span>
    </article>
    <article class="losa losa-categorias">
        <h2>Rendiciones</h2>
        <span>El paquete que se aprueba.</span>
    </article>
    <article class="losa losa-empresa">
        <h2>Factura</h2>
        <span>El pedido cuando cierra el mes.</span>
    </article>
</div>
<?= $this->endSection() ?>
