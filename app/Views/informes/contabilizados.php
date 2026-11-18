<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Asientos contabilizados</h1>
        <p>Movimientos de preasientos que ya se contabilizaron en el ERP. Llegan por la API o, si no hay una conexión vigente, porque se marcaron a mano. Es solo el cierre en el sistema externo.</p>
    </div>
    <a class="boton" href="<?= base_url('informes/preasientos?anio=' . $anio . '&mes=' . $mes) ?>">Volver</a>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('informes/contabilizados') ?>">
        <label>Año
            <select name="anio">
                <?php foreach ($anios as $opcion) : ?>
                    <option value="<?= esc((string) $opcion) ?>" <?= (int) $anio === (int) $opcion ? 'selected' : '' ?>><?= esc((string) $opcion) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Mes
            <select name="mes">
                <?php foreach ($meses as $numero => $texto) : ?>
                    <option value="<?= esc((string) $numero) ?>" <?= (int) $mes === (int) $numero ? 'selected' : '' ?>><?= esc($texto) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <button class="boton" type="submit">Ver</button>
    </form>
    <?php if ($manual) : ?>
        <p>No hay una API vigente para los preasientos. Los cerrados se marcan en el mes y aquí aparecen como asientos contabilizados.</p>
    <?php else : ?>
        <p>Hay una API vigente. El ERP los toma y aquí quedan sus movimientos.</p>
    <?php endif ?>
</section>
<?php if ($movimientos === []) : ?>
    <p class="bloque">Todavía no hay asientos contabilizados en este mes.</p>
<?php else : ?>
    <section class="bloque">
        <table class="tabla">
            <thead><tr><th>Fecha</th><th>Preasiento</th><th>Cuenta</th><th>Glosa</th><th>Auxiliar</th><th>Debe</th><th>Haber</th><th>Cómo</th></tr></thead>
            <tbody>
                <?php $debe = 0.0; $haber = 0.0; ?>
                <?php foreach ($movimientos as $linea) : ?>
                    <?php $debe += (float) $linea['debe']; $haber += (float) $linea['haber']; ?>
                    <tr>
                        <td><?= esc(date('d/m/Y', strtotime((string) $linea['fecha']))) ?></td>
                        <td><?= esc((string) $linea['comprobante']) ?></td>
                        <td><?= esc((string) $linea['codigo']) ?> <?= esc((string) $linea['nombre']) ?></td>
                        <td><?= esc((string) $linea['glosa']) ?></td>
                        <td><?= esc((string) $linea['auxiliar']) ?></td>
                        <td><?= (float) $linea['debe'] > 0 ? esc(\App\Libraries\Montos::formato((float) $linea['debe'], (string) $linea['moneda'])) : '' ?></td>
                        <td><?= (float) $linea['haber'] > 0 ? esc(\App\Libraries\Montos::formato((float) $linea['haber'], (string) $linea['moneda'])) : '' ?></td>
                        <td><?= esc(\App\Libraries\Estados::contabilizacion((string) $linea['origen'], (string) ($linea['erp_ref'] ?? ''))) ?></td>
                    </tr>
                <?php endforeach ?>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><?= abs($debe - $haber) < 0.001 ? 'Cuadra' : 'No cuadra' ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato($debe, (string) ($movimientos[0]['moneda'] ?? 'CLP'))) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato($haber, (string) ($movimientos[0]['moneda'] ?? 'CLP'))) ?></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
