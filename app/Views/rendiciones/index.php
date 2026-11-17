<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Rendiciones</h1>
        <p>Agrupa gastos legalizados y envíalos juntos.</p>
    </div>
    <a class="boton" href="<?= base_url('rendiciones/nueva') ?>">Nueva rendición</a>
</header>
<?php if ($items === []) : ?>
    <p class="bloque">Todavía no hay rendiciones.</p>
<?php else : ?>
    <table class="tabla">
        <thead>
            <tr><th>Rendición</th><th>Gastos</th><th>Total</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item) : ?>
                <?php $total = $totales[(int) $item['id']] ?? ['cantidad' => 0, 'monto' => 0, 'moneda' => 'CLP']; ?>
                <tr>
                    <td>
                        <a href="<?= base_url('rendiciones/' . $item['id']) ?>"><?= esc($item['titulo']) ?></a>
                        <small><?= esc(date('d/m/Y', strtotime((string) $item['creada']))) ?></small>
                    </td>
                    <td><?= esc((string) $total['cantidad']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $total['monto'], (string) $total['moneda'])) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $item['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $item['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
