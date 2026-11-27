<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Informes</h1>
        <p>Estados de cuenta del período.</p>
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
            'tipos'          => 'losa-categorias',
            'pendientes'     => 'losa-rendiciones',
            'remuneraciones' => 'losa-fondos',
            'tarjeta'        => 'losa-empresa',
            'desviaciones'   => 'losa-gastos',
            'caja'           => 'losa-fondos',
            'socios'         => 'losa-personas',
            'recuperar'      => 'losa-parametros',
            'medios'         => 'losa-empresa',
        ];
    ?>
    <?php
        $losas['cuentas'] = 'losa-informes';
        $dePago = ['asientos' => 'preasientos', 'cuentas' => 'cuentas'];
    ?>
    <?php foreach ($piezas as $clave => $pieza) : ?>
        <?php $cerrado = isset($dePago[$clave]) && empty($incluidos[$dePago[$clave]]); ?>
        <a class="losa <?= esc($losas[$clave]) ?>" href="<?= base_url('informes/' . $clave . '?' . $consulta) ?>">
            <strong><?= esc($pieza['titulo']) ?></strong>
            <span><?= $cerrado ? 'Módulo sin contratar.' : esc($pieza['bajada']) ?></span>
        </a>
    <?php endforeach ?>
    <?php if ($esAdmin) : ?>
        <a class="losa losa-informes" href="<?= base_url('informes/preasientos?anio=' . $anio . '&mes=' . $mes) ?>">
            <strong>Preasientos del mes</strong>
            <span><?= empty($incluidos['preasientos']) ? 'Módulo sin contratar.' : 'El mes agrupado, antes del ERP.' ?></span>
        </a>
        <a class="losa losa-parametros" href="<?= base_url('informes/contabilizados?anio=' . $anio . '&mes=' . $mes) ?>">
            <strong>Asientos contabilizados</strong>
            <span><?= empty($incluidos['preasientos']) ? 'Módulo sin contratar.' : 'Lo contabilizado en el ERP por API o a mano.' ?></span>
        </a>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
