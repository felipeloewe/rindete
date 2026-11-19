<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($rendicion['titulo']) ?></h1>
        <?php if ($rendicion['nota'] !== '') : ?>
            <p><?= esc($rendicion['nota']) ?></p>
        <?php endif ?>
    </div>
    <span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $rendicion['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $rendicion['estado'])) ?></span>
</header>
<section class="bloque">
    <h2>Saldo</h2>
    <dl class="ficha">
        <div><dt>Por rendir</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $saldo['por_rendir'], (string) $moneda)) ?></dd></div>
        <div><dt>Rendido</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $saldo['rendido'], (string) $moneda)) ?></dd></div>
        <div><dt>Saldo</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $saldo['saldo'], (string) $moneda)) ?></dd></div>
    </dl>
    <?php if ($previa !== null) : ?>
        <p>Nace del saldo de <a href="<?= base_url('rendiciones/' . $previa['id']) ?>"><?= esc($previa['titulo']) ?></a>.</p>
    <?php endif ?>
    <?php if ($hija !== null) : ?>
        <p>El saldo que sobró sigue en <a href="<?= base_url('rendiciones/' . $hija['id']) ?>"><?= esc($hija['titulo']) ?></a>.</p>
    <?php endif ?>
    <?php if ((string) $rendicion['estado'] === 'cierre_pedido' && ! $puedeAceptar) : ?>
        <p>Cierre pedido. Espera a <?= esc($turnoNombre) ?>.</p>
    <?php endif ?>
</section>
<?php if ($directa) : ?>
    <section class="bloque">
        <h2>Sin solicitud</h2>
        <dl class="ficha">
            <div><dt>Tope aplicado</dt><dd><?= esc($tope) ?></dd></div>
            <div><dt>Viático</dt><dd><?= esc($viatico) ?></dd></div>
            <div><dt>Asignación</dt><dd><?= esc($asignacion) ?></dd></div>
        </dl>
    </section>
<?php endif ?>
<section class="bloque">
    <div class="cabeza">
        <h2>Gastos incluidos</h2>
        <strong><?= esc(\App\Libraries\Montos::formato((float) $total, (string) $moneda)) ?></strong>
    </div>
    <?php if ($gastos === []) : ?>
        <p>Esta rendición todavía no tiene gastos.</p>
    <?php else : ?>
        <table class="tabla">
            <thead>
                <tr><th>Gasto</th><th>Fecha</th><th>Monto</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($gastos as $gasto) : ?>
                    <tr>
                        <td><a href="<?= base_url('gastos/' . $gasto['id']) ?>"><?= esc($gasto['concepto']) ?></a></td>
                        <td><?= esc(date('d/m/Y', strtotime((string) $gasto['fecha']))) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></td>
                        <td>
                            <?php if ($abierta) : ?>
                                <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/quitar/' . $gasto['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton claro" type="submit">Quitar</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
    <?php if ($abierta && $disponibles === []) : ?>
        <p>No hay gastos legalizados libres para sumar.</p>
    <?php elseif ($abierta) : ?>
        <form method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/agregar') ?>">
            <?= csrf_field() ?>
            <label>Sumar un gasto legalizado
                <select name="gasto_id" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($disponibles as $gasto) : ?>
                        <option value="<?= esc($gasto['id']) ?>"><?= esc($gasto['concepto']) ?> · <?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <button class="boton" type="submit">Sumar gasto</button>
        </form>
    <?php endif ?>
    <?php if ($abierta) : ?>
        <form method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/sumar') ?>">
            <?= csrf_field() ?>
            <label>Sumar un monto
                <input name="monto" inputmode="decimal" required placeholder="10000">
            </label>
            <button class="boton claro" type="submit">Sumar monto</button>
        </form>
        <div class="acciones">
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/eliminar') ?>">
                <?= csrf_field() ?>
                <button class="boton claro" type="submit">Eliminar</button>
            </form>
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/enviar') ?>">
                <?= csrf_field() ?>
                <?php if ((float) $saldo['saldo'] > 0) : ?>
                    <label>Monto adicional
                        <input name="monto_adicional" inputmode="decimal" placeholder="0">
                    </label>
                    <button class="boton" type="submit">Cerrar y abrir el saldo</button>
                <?php elseif ((float) $saldo['saldo'] < 0) : ?>
                    <button class="boton" type="submit">Pedir cierre</button>
                <?php else : ?>
                    <button class="boton" type="submit">Cerrar rendición</button>
                <?php endif ?>
            </form>
        </div>
    <?php elseif ($puedeAceptar) : ?>
        <div class="acciones">
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/rechazar') ?>">
                <?= csrf_field() ?>
                <label>Motivo
                    <input name="detalle" maxlength="180" placeholder="Falta un gasto">
                </label>
                <button class="boton claro" type="submit">Rechazar cierre</button>
            </form>
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/aceptar') ?>">
                <?= csrf_field() ?>
                <button class="boton" type="submit">Aceptar cierre</button>
            </form>
        </div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
