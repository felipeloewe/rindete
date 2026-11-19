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
<section class="bloque"><?= $this->include('informes/filtros') ?></section>
<?php if ($filas === []) : ?>
    <p class="bloque">No queda nada por recuperar en este período.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Entidad</th><th>Documentos</th><th>Por recuperar</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['entidad']) ?></td>
                    <td><?= esc((string) count($fila['lineas'])) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <p class="bloque">Total por recuperar: <?= esc(\App\Libraries\Montos::formato($total, $moneda)) ?>.</p>
<?php endif ?>
<?= $this->endSection() ?>
