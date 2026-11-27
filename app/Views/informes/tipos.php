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
        <div><dt>Total</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $total, $moneda)) ?></dd></div>
    </dl>
</section>
<?php if ($filas === []) : ?>
    <p class="bloque">No hay gastos rendidos en este período.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Tipo de gasto</th><th>Cantidad</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['categoria']) ?></td>
                    <td><?= esc((string) $fila['cantidad']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
