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
<p class="bloque">Saldo del período: <?= esc(\App\Libraries\Montos::formato($saldo, $moneda)) ?>. Entregado <?= esc(\App\Libraries\Montos::formato($entregado, $moneda)) ?>. Reposición <?= esc(\App\Libraries\Montos::formato($reponer, $moneda)) ?>.</p>
<?php if ($filas === []) : ?>
    <p class="bloque">Este período no mueve la caja.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Movimiento</th><th>Persona</th><th>Monto</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= (string) $fila['tipo'] === 'reposicion' ? 'Reposición' : 'Entrega' ?> · <?= esc($fila['motivo']) ?></td>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], (string) $fila['moneda'])) ?></td>
                    <td><?= (int) $fila['duplicado'] === 1 ? 'Marcado para revisión de caja' : '' ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
