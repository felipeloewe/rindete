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
    <p class="bloque">Después del pago, los cargos de este período calzan con lo rendido.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Cargo</th><th>Pagado</th><th>Rendido</th><th>Diferencia</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc($fila['motivo']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], (string) $fila['moneda'])) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['rendido'], (string) $fila['moneda'])) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['diferencia'], (string) $fila['moneda'])) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
