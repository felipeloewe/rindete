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
    <?php if ($sin_cuenta > 0) : ?>
        <p>Hay gastos rendidos sin cuenta por <?= esc(\App\Libraries\Montos::formato((float) $sin_cuenta, $moneda)) ?>. Clasifica el tipo de gasto para que entren al preasiento.</p>
    <?php endif ?>
</section>
<?php if ($por_usuario !== []) : ?>
    <h2 class="seccion">Por persona</h2>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($por_usuario as $grupo) : ?>
                <tr>
                    <td><?= esc($grupo['nombre']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $grupo['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?php foreach ($por_mes as $grupo) : ?>
    <h2 class="seccion"><?= esc($grupo['mes']) ?> · todas las personas</h2>
    <table class="tabla">
        <thead><tr><th>Cuenta</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($grupo['lineas'] as $linea) : ?>
                <tr>
                    <td><?= esc($linea['cuenta']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $linea['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
            <tr>
                <td>Total del mes</td>
                <td><?= esc(\App\Libraries\Montos::formato((float) $grupo['total'], $moneda)) ?></td>
            </tr>
        </tbody>
    </table>
<?php endforeach ?>
<?php if ($filas === []) : ?>
    <p class="bloque">No hay preasientos en este período.</p>
<?php else : ?>
    <h2 class="seccion">Detalle</h2>
    <table class="tabla">
        <thead><tr><th>Fecha</th><th>Persona</th><th>Cuenta</th><th>Gasto</th><th>Monto</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc(date('d/m/Y', strtotime((string) $fila['fecha']))) ?></td>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc($fila['cuenta']) ?></td>
                    <td><?= esc($fila['concepto']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
