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
</section>
<?php if ($por_usuario !== []) : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Gastos</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($por_usuario as $grupo) : ?>
                <tr>
                    <td><?= esc($grupo['nombre']) ?></td>
                    <td><?= esc((string) $grupo['cantidad']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $grupo['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?php if ($por_cuenta !== []) : ?>
    <table class="tabla">
        <thead><tr><th>Cuenta</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($por_cuenta as $grupo) : ?>
                <tr>
                    <td><?= esc($grupo['cuenta']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $grupo['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?php if ($filas === []) : ?>
    <p class="bloque">No hay gastos rendidos en este período.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Gasto</th><th>Tipo</th><th>Cuenta</th><th>Monto</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['concepto']) ?><small><?= esc($fila['nombre']) ?> · <?= esc(date('d/m/Y', strtotime((string) $fila['fecha']))) ?></small></td>
                    <td><?= esc($fila['categoria']) ?></td>
                    <td><?= esc($fila['cuenta']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
