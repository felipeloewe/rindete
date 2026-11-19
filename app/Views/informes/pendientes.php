<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($pieza['titulo']) ?></h1>
        <p><?= esc($periodo) ?>. <?= esc($pieza['bajada']) ?> Los saldos que siguen abiertos también se ven en montos por rendir y en el estado de cuenta.</p>
    </div>
    <a class="boton" href="<?= base_url('informes?' . $consulta) ?>">Volver</a>
</header>
<section class="bloque">
    <?= $this->include('informes/filtros') ?>
</section>
<?php if ($filas === []) : ?>
    <p class="bloque">Este mes no deja rendiciones abiertas.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Rendición</th><th>Persona</th><th>Estado</th><th>Saldo</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><a href="<?= base_url('rendiciones/' . $fila['id']) ?>"><?= esc($fila['titulo']) ?></a></td>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $fila['estado'])) ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $fila['estado'])) ?></span></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['saldo'], $moneda)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <p class="bloque">Por rendir, en rendiciones que siguen abiertas: <?= esc(\App\Libraries\Montos::formato($total, $moneda)) ?>.</p>
<?php endif ?>
<?= $this->endSection() ?>
