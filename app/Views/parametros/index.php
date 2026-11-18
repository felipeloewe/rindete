<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Parametrización</h1>
        <p>Elige la regla que quieres dejar vigente. La carga lee cuentas, clasificación, gastos aceptados y tipos de documento.</p>
    </div>
</header>
<div class="mosaico">
    <a class="losa losa-gastos" href="<?= base_url('parametros/cuentas') ?>">
        <strong>Cuentas</strong>
        <span>Genéricas y de remuneración.</span>
    </a>
    <a class="losa losa-rendiciones" href="<?= base_url('parametros/clasificacion') ?>">
        <strong>Clasificación</strong>
        <span>La cuenta de cada categoría.</span>
    </a>
    <a class="losa losa-categorias" href="<?= base_url('parametros/conexiones') ?>">
        <strong>Conexiones</strong>
        <span>Sistemas para asientos y pagos.</span>
    </a>
    <a class="losa losa-parametros" href="<?= base_url('parametros/flujo') ?>">
        <strong>Flujo</strong>
        <span>Quién acepta y en qué orden.</span>
    </a>
    <a class="losa losa-empresa" href="<?= base_url('parametros/tarjetas') ?>">
        <strong>Tarjetas</strong>
        <span>Personas, gastos y monto.</span>
    </a>
    <a class="losa losa-aceptados" href="<?= base_url('parametros/aceptados') ?>">
        <strong>Gastos aceptados</strong>
        <span>Lo que cada persona puede cargar.</span>
    </a>
    <a class="losa losa-tipos" href="<?= base_url('parametros/tipos') ?>">
        <strong>Tipos de documento</strong>
        <span>Montos y comprobante.</span>
    </a>
    <a class="losa losa-informes" href="<?= base_url('parametros/preasientos') ?>">
        <strong>Preasientos</strong>
        <span><?= ! empty($preasientos) ? 'Salida de fondos y traspaso al gasto.' : 'Módulo sin contratar.' ?></span>
    </a>
</div>
<?= $this->endSection() ?>
