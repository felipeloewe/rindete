<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Documento único</h1>
        <p>Un documento, una forma de pago, y el aviso a tesorería. Si es efectivo, el comprobante de pago al contado va antes de seguir.</p>
    </div>
    <a class="boton" href="<?= base_url('panel') ?>">Volver</a>
</header>
<section class="bloque">
    <form method="post" enctype="multipart/form-data" action="<?= base_url('documentos') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Forma de pago
                <select name="forma_pago_id" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($formas as $forma) : ?>
                        <option value="<?= esc((string) $forma['id']) ?>"><?= esc($forma['nombre']) ?><?= (int) $forma['efectivo'] === 1 ? ' · exige comprobante' : '' ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Monto (<?= esc($moneda) ?>)
                <input name="monto" inputmode="decimal" required placeholder="0">
            </label>
        </div>
        <label>Motivo
            <input name="motivo" required maxlength="180" placeholder="Pago de un proveedor, un vale…">
        </label>
        <label>Comprobante de pago al contado
            <input type="file" name="comprobante" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            <small>Obligatorio cuando la forma de pago es efectivo.</small>
        </label>
        <button class="boton" type="submit">Informar a tesorería</button>
    </form>
</section>
<?php if ($documentos === []) : ?>
    <p class="bloque">Todavía no hay documentos informados.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Documento</th><th>Forma</th><th>Monto</th><th>Estado</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($documentos as $documento) : ?>
                <tr>
                    <td><?= esc($documento['motivo']) ?></td>
                    <td><?= esc($nombres[(int) $documento['forma_pago_id']] ?? '') ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $documento['monto'], (string) $documento['moneda'])) ?></td>
                    <td><span class="chip <?= (string) $documento['estado'] === 'pagado' ? 'chip-aprobado' : 'chip-en_rendicion' ?>"><?= esc((string) $documento['estado'] === 'informado' ? 'Informado' : ((string) $documento['estado'] === 'en_pago' ? 'En pago' : 'Pagado')) ?></span></td>
                    <td>
                        <?php if ((string) $documento['estado'] === 'informado') : ?>
                            <form method="post" action="<?= base_url('documentos/' . $documento['id'] . '/resolver') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="en_pago">
                                <button class="boton claro" type="submit">Al flujo de pagos</button>
                            </form>
                            <form method="post" action="<?= base_url('documentos/' . $documento['id'] . '/resolver') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="estado" value="pagado">
                                <button class="boton" type="submit">Marcar pagado</button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
