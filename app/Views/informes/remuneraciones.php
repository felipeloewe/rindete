<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($pieza['titulo']) ?></h1>
        <p><?= esc($periodo) ?>. <?= esc($pieza['bajada']) ?> El monto a liquidación es el que toma el preasiento de liquidación de sueldo.</p>
    </div>
    <a class="boton" href="<?= base_url('informes?' . $consulta) ?>">Volver</a>
</header>
<section class="bloque">
    <?= $this->include('informes/filtros') ?>
</section>
<?php if ($filas === []) : ?>
    <p class="bloque">En este período no hay viático, asignación ni bien rendido.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Viático</th><th>Asignación</th><th>Bien</th><th>A liquidación</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td>
                        <?= esc($fila['nombre']) ?>
                        <?php foreach ($fila['cuentas'] as $cuenta) : ?>
                            <?php if ($cuenta !== '') : ?>
                                <small><?= esc($cuenta) ?></small>
                            <?php endif ?>
                        <?php endforeach ?>
                    </td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['viatico'], $moneda)) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['asignacion'], $moneda)) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['bien'], $moneda)) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['total'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <p class="bloque">A liquidación en el período: <?= esc(\App\Libraries\Montos::formato($total, $moneda)) ?>.</p>
<?php endif ?>
<?= $this->endSection() ?>
