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
    <p>El saldo es el debe menos el haber. Al salir el dinero, el fondo por rendir sube. Al rendir, baja y el gasto sube.</p>
</section>
<?php if ($cuentas === []) : ?>
    <p class="bloque">Este mes no tiene movimientos de cuentas. <?= $puedeArmar ? 'Arma los preasientos del período.' : 'Hace falta el módulo de preasientos para generarlos.' ?></p>
<?php endif ?>
<?php foreach ($cuentas as $cuenta) : ?>
    <section class="bloque">
        <div class="cabeza">
            <h2><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></h2>
            <span>Saldo <?= esc(\App\Libraries\Montos::formato((float) $cuenta['saldo'], $moneda)) ?></span>
        </div>
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Glosa</th><th>Auxiliar</th><th>Debe</th><th>Haber</th></tr></thead>
            <tbody>
                <?php foreach ($cuenta['movimientos'] as $movimiento) : ?>
                    <tr>
                        <td><?= esc(date('d/m/Y', strtotime((string) $movimiento['fecha']))) ?></td>
                        <td><?= esc($movimiento['glosa']) ?></td>
                        <td><?= esc((string) $movimiento['auxiliar']) ?></td>
                        <td><?= (float) $movimiento['debe'] > 0 ? esc(\App\Libraries\Montos::formato((float) $movimiento['debe'], $moneda)) : '' ?></td>
                        <td><?= (float) $movimiento['haber'] > 0 ? esc(\App\Libraries\Montos::formato((float) $movimiento['haber'], $moneda)) : '' ?></td>
                    </tr>
                <?php endforeach ?>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $cuenta['debe'], $moneda)) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $cuenta['haber'], $moneda)) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
