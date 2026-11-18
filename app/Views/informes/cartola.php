<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($pieza['titulo']) ?></h1>
        <p><?= esc($periodo) ?>. <?= esc($pieza['bajada']) ?></p>
    </div>
    <a class="boton" href="<?= base_url('informes?' . $consulta) ?>">Volver</a>
</header>
<section class="bloque">
    <?= $this->include('informes/filtros') ?>
    <dl class="ficha">
        <div><dt>Entregado</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $entregado, $moneda)) ?></dd></div>
        <div><dt>Rendido</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $rendido, $moneda)) ?></dd></div>
        <div><dt>Saldo del período</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $saldo, $moneda)) ?></dd></div>
        <div><dt>Por rendir, fondos abiertos</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $por_rendir, $moneda)) ?></dd></div>
    </dl>
</section>
<?php if ($movimientos === []) : ?>
    <p class="bloque">No hay movimientos en este período.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Detalle</th><th>Entra</th><th>Sale</th><th>Saldo</th></tr></thead>
        <tbody>
            <?php foreach ($movimientos as $movimiento) : ?>
                <tr>
                    <td><?= esc(date('d/m/Y', strtotime((string) $movimiento['fecha']))) ?><small><?= esc($movimiento['nombre']) ?></small></td>
                    <td><?= esc($movimiento['detalle']) ?></td>
                    <td><?= $movimiento['entrada'] > 0 ? esc(\App\Libraries\Montos::formato((float) $movimiento['entrada'], $moneda)) : '' ?></td>
                    <td><?= $movimiento['salida'] > 0 ? esc(\App\Libraries\Montos::formato((float) $movimiento['salida'], $moneda)) : '' ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $movimiento['saldo'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
