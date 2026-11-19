<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Rendiciones</h1>
        <p>Al cerrarla, el saldo decide: si falta dinero pide aceptación, si sobra abre otra rendición y si cuadra se cierra.</p>
    </div>
    <?php if ($autorizada) : ?>
        <a class="boton" href="<?= base_url('rendiciones/nueva') ?>">Nueva rendición</a>
    <?php endif ?>
</header>
<?php if (! $autorizada) : ?>
    <p class="bloque">Para abrir una rendición sin solicitud, la empresa tiene que autorizarte.</p>
<?php endif ?>
<?php if ($items === []) : ?>
    <p class="bloque">Todavía no hay rendiciones.</p>
<?php else : ?>
    <table class="tabla">
        <thead>
            <tr><th>Rendición</th><th>Gastos</th><th>Total</th><th>Saldo</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item) : ?>
                <?php $total = $totales[(int) $item['id']] ?? ['cantidad' => 0, 'monto' => 0, 'moneda' => 'CLP']; ?>
                <tr>
                    <td>
                        <a href="<?= base_url('rendiciones/' . $item['id']) ?>"><?= esc($item['titulo']) ?></a>
                        <small><?= esc($nombres[(int) $item['usuario_id']] ?? '') ?> · <?= esc(date('d/m/Y', strtotime((string) $item['creada']))) ?></small>
                    </td>
                    <td><?= esc((string) $total['cantidad']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $total['monto'], (string) $total['moneda'])) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) ($total['saldo'] ?? 0), (string) $total['moneda'])) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $item['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $item['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
