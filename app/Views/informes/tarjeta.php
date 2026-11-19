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
    <p class="bloque">Este período no tiene cargos de tarjeta.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Tarjeta</th><th>Cargo</th><th>Facturas por pagar</th><th>Validación</th><th>Rendido</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc($fila['tarjeta']) ?></td>
                    <td><?= esc($fila['motivo']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['facturas'], (string) $fila['moneda'])) ?></td>
                    <td><?= (string) $fila['estado'] === 'validado' ? 'Validado' : 'Observado' ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['rendido'], (string) $fila['moneda'])) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <p class="bloque">Cargos del período: <?= esc(\App\Libraries\Montos::formato($total, $moneda)) ?>.</p>
<?php endif ?>
<?= $this->endSection() ?>
