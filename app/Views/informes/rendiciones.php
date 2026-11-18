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
        <thead><tr><th>Persona</th><th>Rendiciones</th><th>Total</th></tr></thead>
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
<?php if ($filas === []) : ?>
    <p class="bloque">No hay rendiciones en este período.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Rendición</th><th>Persona</th><th>Gastos</th><th>Total</th><th>Estado</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><a href="<?= base_url('rendiciones/' . $fila['id']) ?>"><?= esc($fila['titulo']) ?></a></td>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc((string) $fila['cantidad']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], $moneda)) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $fila['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $fila['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
