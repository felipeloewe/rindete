<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Informes</h1>
        <p>Estados de cuenta del período. El administrador ve a todas las personas.</p>
    </div>
</header>
<section class="bloque">
    <?= $this->include('informes/filtros') ?>
</section>
<div class="mosaico">
    <?php
        $losas = [
            'rendiciones' => 'losa-rendiciones',
            'cartola'     => 'losa-fondos',
            'gastos'      => 'losa-gastos',
            'asientos'    => 'losa-parametros',
            'tipos'       => 'losa-categorias',
        ];
    ?>
    <?php foreach ($piezas as $clave => $pieza) : ?>
        <a class="losa <?= esc($losas[$clave]) ?>" href="<?= base_url('informes/' . $clave . '?' . $consulta) ?>">
            <strong><?= esc($pieza['titulo']) ?></strong>
            <span><?= esc($pieza['bajada']) ?></span>
        </a>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
