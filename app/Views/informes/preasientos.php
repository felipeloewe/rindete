<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Preasientos</h1>
        <p>Preasientos del mes, antes de ingresarlos al ERP. Al entregar un fondo sale el dinero y nace el fondo por rendir. Al rendir, ese fondo pasa al gasto. El mes queda por persona o en un solo comprobante. No hay uno por rendición ni por fondo.</p>
    </div>
    <a class="boton" href="<?= base_url('informes?anio=' . $anio . '&mes=' . $mes) ?>">Volver</a>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('informes/preasientos') ?>">
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
    <?php if (! $chile) : ?>
        <p>El preasiento normativo está definido para Chile.</p>
    <?php elseif ($cuenta === null || $salida === null) : ?>
        <p>Elige en <a href="<?= base_url('parametros/preasientos') ?>">Parámetros</a> la cuenta de donde sale el dinero y el fondo por rendir.</p>
    <?php else : ?>
        <p>
            <?= $agrupacion === 'usuario' ? 'Un preasiento por persona.' : 'Un preasiento del mes.' ?>
            <?= $auxiliar ? ' Con auxiliar del RUT.' : ' Sin auxiliar.' ?>
            Sale de <?= esc($salida['codigo']) ?> <?= esc($salida['nombre']) ?>
            y el fondo es <?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?>.
        </p>
        <form method="post" action="<?= base_url('informes/preasientos') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="anio" value="<?= esc((string) $anio) ?>">
            <input type="hidden" name="mes" value="<?= esc((string) $mes) ?>">
            <button class="boton" type="submit">Armar preasientos</button>
        </form>
    <?php endif ?>
</section>
<?php if ($comprobantes === []) : ?>
    <p class="bloque">Todavía no hay preasientos de este mes.</p>
<?php endif ?>
<?php foreach ($comprobantes as $comprobante) : ?>
    <section class="bloque">
        <div class="cabeza">
            <h2><?= esc($comprobante['glosa']) ?></h2>
            <span><?= ($comprobante['tipo'] ?? '') === 'salida' ? 'Salida' : 'Traspaso' ?> · <?= esc(date('d/m/Y', strtotime((string) $comprobante['fecha']))) ?></span>
        </div>
        <table class="tabla">
            <thead><tr><th>Cuenta</th><th>Glosa</th><th>Auxiliar</th><th>Debe</th><th>Haber</th></tr></thead>
            <tbody>
                <?php foreach ($comprobante['lineas'] as $linea) : ?>
                    <tr>
                        <td><?= esc($linea['codigo']) ?> <?= esc($linea['nombre']) ?></td>
                        <td><?= esc($linea['glosa']) ?></td>
                        <td><?= esc((string) $linea['auxiliar']) ?></td>
                        <td><?= (float) $linea['debe'] > 0 ? esc(\App\Libraries\Montos::formato((float) $linea['debe'], (string) $comprobante['moneda'])) : '' ?></td>
                        <td><?= (float) $linea['haber'] > 0 ? esc(\App\Libraries\Montos::formato((float) $linea['haber'], (string) $comprobante['moneda'])) : '' ?></td>
                    </tr>
                <?php endforeach ?>
                <tr>
                    <td>Total</td>
                    <td></td>
                    <td><?= abs((float) $comprobante['debe'] - (float) $comprobante['haber']) < 0.001 ? 'Cuadra' : 'No cuadra' ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $comprobante['debe'], (string) $comprobante['moneda'])) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $comprobante['haber'], (string) $comprobante['moneda'])) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
