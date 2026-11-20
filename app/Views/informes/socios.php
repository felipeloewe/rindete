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
    <p class="bloque">Este período no tiene pagos de socios.</p>
<?php else : ?>
    <?php foreach ($filas as $fila) : ?>
        <section class="bloque">
            <h2><?= esc($fila['entidad']) ?> · <?= (string) $fila['estado'] === 'recuperado' ? 'Recuperado' : 'Por recuperar' ?></h2>
            <table class="tabla">
                <thead><tr><th>Documento</th><th>Forma de pago</th><th>Monto</th></tr></thead>
                <tbody>
                    <?php foreach ($fila['lineas'] as $linea) : ?>
                        <tr>
                            <td><?= esc($linea['motivo']) ?></td>
                            <td><?= esc($linea['forma']) ?></td>
                            <td><?= esc(\App\Libraries\Montos::formato((float) $linea['monto'], $moneda)) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </section>
    <?php endforeach ?>
    <p class="bloque">Por recuperar en el período: <?= esc(\App\Libraries\Montos::formato($total, $moneda)) ?>.</p>
<?php endif ?>
<?= $this->endSection() ?>
