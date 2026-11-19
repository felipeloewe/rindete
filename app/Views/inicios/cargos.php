<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Cargo de tarjeta</h1>
        <p>El pago baja facturas por pagar, abre un fondo por rendir y queda en validación contra la mantención de la tarjeta. Un DTE ya pagado entra sin documento físico.</p>
    </div>
    <a class="boton" href="<?= base_url('panel') ?>">Volver</a>
</header>
<?php if ($tarjetas === []) : ?>
    <p class="bloque">Primero se da de alta la tarjeta en Parámetros, con las personas y los gastos que le corresponden.</p>
<?php else : ?>
    <section class="bloque">
        <form method="post" action="<?= base_url('cargos') ?>">
            <?= csrf_field() ?>
            <div class="grid-2">
                <label>Tarjeta
                    <select name="tarjeta_id" required>
                        <?php foreach ($tarjetas as $tarjeta) : ?>
                            <option value="<?= esc((string) $tarjeta['id']) ?>"><?= esc($tarjeta['nombre']) ?> · <?= (int) $tarjeta['personal'] === 1 ? 'Personal' : 'Empresa' ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label>Categoría
                    <select name="categoria_id" required>
                        <?php foreach ($categorias as $categoria) : ?>
                            <option value="<?= esc((string) $categoria['id']) ?>"><?= esc($categoria['nombre']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            </div>
            <div class="grid-2">
                <label>Motivo
                    <input name="motivo" required maxlength="180">
                </label>
                <label>Monto (<?= esc($moneda) ?>)
                    <input name="monto" inputmode="decimal" required>
                </label>
            </div>
            <label class="check"><input type="checkbox" name="dte" value="1"> DTE ya pagado, sin documento físico</label>
            <button class="boton" type="submit">Registrar cargo</button>
        </form>
    </section>
<?php endif ?>
<?php if ($cargos !== []) : ?>
    <table class="tabla">
        <thead><tr><th>Cargo</th><th>Tarjeta</th><th>Facturas por pagar</th><th>Validación</th><th>Rendido</th></tr></thead>
        <tbody>
            <?php foreach ($cargos as $cargo) : ?>
                <tr>
                    <td>
                        <?php if ((int) ($cargo['rendicion_id'] ?? 0) > 0) : ?>
                            <a href="<?= base_url('rendiciones/' . $cargo['rendicion_id']) ?>"><?= esc($cargo['motivo']) ?></a>
                        <?php else : ?>
                            <?= esc($cargo['motivo']) ?>
                        <?php endif ?>
                        <?php if ((string) $cargo['detalle'] !== '') : ?>
                            <small><?= esc($cargo['detalle']) ?></small>
                        <?php endif ?>
                    </td>
                    <td><?= esc($cargo['tarjeta']) ?><?= (int) $cargo['dte'] === 1 ? ' · DTE' : '' ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $cargo['facturas'], (string) $cargo['moneda'])) ?></td>
                    <td><span class="chip <?= (string) $cargo['estado'] === 'validado' ? 'chip-aprobado' : 'chip-observado' ?>"><?= (string) $cargo['estado'] === 'validado' ? 'Validado' : 'Observado' ?></span></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $cargo['rendido'], (string) $cargo['moneda'])) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
