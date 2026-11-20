<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Socios</h1>
        <p>Un socio paga varios documentos en el mismo acto. Cada documento usa su forma de pago. El saldo se recupera hacia la entidad.</p>
    </div>
    <a class="boton" href="<?= base_url('panel') ?>">Volver</a>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('socios/entidad') ?>">
        <?= csrf_field() ?>
        <label>Entidad a recuperar
            <input name="nombre" required maxlength="120" placeholder="Filial, cliente, otra sociedad…">
        </label>
        <button class="boton claro" type="submit">Agregar entidad</button>
    </form>
</section>
<?php if ($entidades !== []) : ?>
    <section class="bloque">
        <form method="post" action="<?= base_url('socios') ?>">
            <?= csrf_field() ?>
            <label>Entidad
                <select name="entidad_id" required>
                    <?php foreach ($entidades as $entidad) : ?>
                        <option value="<?= esc((string) $entidad['id']) ?>"><?= esc($entidad['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Nota
                <input name="nota" maxlength="180" placeholder="De qué se trata el acto">
            </label>
            <?php for ($i = 0; $i < 3; $i++) : ?>
                <div class="grid-2">
                    <label>Documento
                        <input name="motivo_linea[]" maxlength="180" placeholder="Factura, boleta…">
                    </label>
                    <label>Monto (<?= esc($moneda) ?>)
                        <input name="monto_linea[]" inputmode="decimal">
                    </label>
                </div>
                <label>Forma de pago
                    <select name="forma_linea[]">
                        <option value="">Selecciona</option>
                        <?php foreach ($formas as $forma) : ?>
                            <option value="<?= esc((string) $forma['id']) ?>"><?= esc($forma['nombre']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            <?php endfor ?>
            <button class="boton" type="submit">Registrar pago</button>
        </form>
    </section>
<?php endif ?>
<?php foreach ($pagos as $pago) : ?>
    <section class="bloque">
        <h2><?= esc($pago['entidad']) ?> · <?= esc(\App\Libraries\Montos::formato((float) $pago['monto'], $moneda)) ?></h2>
        <p><?= (string) $pago['estado'] === 'recuperado' ? 'Recuperado' : 'Por recuperar' ?><?= $pago['nota'] !== '' ? ' · ' . esc($pago['nota']) : '' ?></p>
        <table class="tabla">
            <thead><tr><th>Documento</th><th>Forma de pago</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach ($pago['lineas'] as $linea) : ?>
                    <tr>
                        <td><?= esc($linea['motivo']) ?></td>
                        <td><?= esc($linea['forma']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $linea['monto'], $moneda)) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php if ((string) $pago['estado'] === 'por_recuperar') : ?>
            <form method="post" action="<?= base_url('socios/' . $pago['id'] . '/recuperar') ?>">
                <?= csrf_field() ?>
                <button class="boton" type="submit">Marcar recuperado</button>
            </form>
        <?php endif ?>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
