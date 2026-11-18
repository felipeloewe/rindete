<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="bloque">
    <div class="cabeza">
        <h1><?= esc($solicitud['motivo']) ?></h1>
        <span class="<?= esc(\App\Libraries\Estados::claseSolicitud((string) $solicitud['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::solicitud((string) $solicitud['estado'])) ?></span>
    </div>
    <dl class="ficha">
        <div><dt>Persona</dt><dd><?= esc($nombres[(int) $solicitud['usuario_id']] ?? '') ?></dd></div>
        <div><dt>Gasto</dt><dd><?= esc($categoria['nombre'] ?? '') ?></dd></div>
        <div><dt>Monto</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $solicitud['monto'], (string) $solicitud['moneda'])) ?></dd></div>
        <?php if ($fondo !== null) : ?>
            <div><dt>Saldo por rendir</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $fondo['saldo'], (string) $fondo['moneda'])) ?></dd></div>
        <?php endif ?>
        <?php if ((int) ($solicitud['turno_usuario_id'] ?? 0) > 0) : ?>
            <div><dt>Turno</dt><dd><?= esc($nombres[(int) $solicitud['turno_usuario_id']] ?? '') ?></dd></div>
        <?php endif ?>
    </dl>
    <?php if ($solicitud['detalle'] !== '') : ?>
        <p><?= esc($solicitud['detalle']) ?></p>
    <?php endif ?>
    <?php if ($pasos !== []) : ?>
        <ul>
            <?php foreach ($pasos as $paso) : ?>
                <li><?= esc($nombres[(int) $paso['usuario_id']] ?? '') ?> <?= $paso['decision'] === 'aceptada' ? 'aceptó' : 'rechazó' ?><?= $paso['detalle'] !== '' ? ': ' . esc($paso['detalle']) : '' ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <div class="acciones">
        <?php if ($turno && $solicitud['estado'] === 'en_aceptacion') : ?>
            <form class="plano" method="post" action="<?= base_url('fondos/' . $solicitud['id'] . '/aceptar') ?>">
                <?= csrf_field() ?>
                <button class="boton" type="submit">Aceptar</button>
            </form>
            <form class="plano" method="post" action="<?= base_url('fondos/' . $solicitud['id'] . '/rechazar') ?>">
                <?= csrf_field() ?>
                <input name="detalle" maxlength="180" placeholder="Por qué la rechazas">
                <button class="boton claro" type="submit">Rechazar</button>
            </form>
        <?php endif ?>
        <?php if ($esAdmin && $solicitud['estado'] === 'aceptada') : ?>
            <form class="plano" method="post" action="<?= base_url('fondos/' . $solicitud['id'] . '/entregar') ?>">
                <?= csrf_field() ?>
                <button class="boton" type="submit">Entregar fondo</button>
            </form>
        <?php endif ?>
        <a class="boton claro" href="<?= base_url('fondos') ?>">Volver</a>
    </div>
</section>
<?= $this->endSection() ?>
