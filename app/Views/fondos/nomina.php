<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Nómina de fondos</h1>
        <p>Entran las solicitudes aceptadas del mes. Al cerrarlas, Softnet las carga en Remuneraciones y Odoo las toma como novedad del período.</p>
    </div>
    <a class="boton" href="<?= base_url('fondos') ?>">Volver</a>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('fondos/nomina') ?>">
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
    <?php if ($pendientes === []) : ?>
        <p>No hay solicitudes aceptadas de este mes fuera de una nómina.</p>
    <?php else : ?>
        <table class="tabla">
            <thead><tr><th>Persona</th><th>Motivo</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach ($pendientes as $solicitud) : ?>
                    <tr>
                        <td><?= esc($nombres[(int) $solicitud['usuario_id']] ?? '') ?></td>
                        <td><?= esc($solicitud['motivo']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $solicitud['monto'], (string) $solicitud['moneda'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <form method="post" action="<?= base_url('fondos/nomina') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="anio" value="<?= esc((string) $anio) ?>">
            <input type="hidden" name="mes" value="<?= esc((string) $mes) ?>">
            <button class="boton" type="submit">Cerrar nómina</button>
        </form>
    <?php endif ?>
</section>
<?php foreach ($nominas as $nomina) : ?>
    <section class="bloque">
        <div class="cabeza">
            <h2>Nómina <?= esc((string) $nomina['id']) ?></h2>
            <span><?= esc(\App\Libraries\Estados::nomina((string) $nomina['estado'])) ?><?= ($nomina['erp_ref'] ?? '') !== '' ? ' · ' . esc((string) $nomina['erp_ref']) : '' ?></span>
        </div>
        <table class="tabla">
            <thead><tr><th>Persona</th><th>RUT</th><th>Motivo</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach ($nomina['lineas'] as $linea) : ?>
                    <tr>
                        <td><?= esc($linea['nombre']) ?></td>
                        <td><?= esc((string) $linea['rut']) ?></td>
                        <td><?= esc($linea['motivo']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $linea['monto'], (string) $linea['moneda'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
