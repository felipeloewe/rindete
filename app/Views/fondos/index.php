<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Fondos</h1>
        <p>Pide el dinero por rendir. Tesorería lo entrega cuando el flujo lo acepta.</p>
    </div>
    <a class="boton" href="<?= base_url('fondos/nueva') ?>">Pedir fondo</a>
</header>
<?php if ($fondos !== []) : ?>
    <h2>Por rendir</h2>
    <table class="tabla">
        <thead><tr><th>Fondo</th><th>Saldo</th><th>Estado</th></tr></thead>
        <tbody>
            <?php foreach ($fondos as $fondo) : ?>
                <tr>
                    <td>
                        <a href="<?= base_url('fondos/' . $fondo['solicitud_id']) ?>"><?= esc($fondo['motivo']) ?></a>
                        <?php if ($esAdmin) : ?>
                            <small><?= esc($nombres[(int) $fondo['usuario_id']] ?? '') ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fondo['saldo'], (string) $fondo['moneda'])) ?></td>
                    <td><span class="chip chip-legalizado">Abierto</span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?php if ($solicitudes === []) : ?>
    <p class="bloque">Todavía no hay solicitudes de fondo.</p>
<?php else : ?>
    <h2>Solicitudes</h2>
    <table class="tabla">
        <thead><tr><th>Solicitud</th><th>Monto</th><th>Estado</th></tr></thead>
        <tbody>
            <?php foreach ($solicitudes as $item) : ?>
                <tr>
                    <td>
                        <a href="<?= base_url('fondos/' . $item['id']) ?>"><?= esc($item['motivo']) ?></a>
                        <small><?= esc($nombres[(int) $item['usuario_id']] ?? '') ?> · <?= esc(date('d/m/Y', strtotime((string) $item['creada']))) ?></small>
                    </td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $item['monto'], (string) $item['moneda'])) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::claseSolicitud((string) $item['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::solicitud((string) $item['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
