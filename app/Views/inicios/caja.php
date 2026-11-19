<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Caja chica</h1>
        <p>La entrega sale de la caja y queda centralizada: la persona recibe el fondo y la caja lleva el saldo. La reposición vuelve a la caja y no entra a la planilla del banco.</p>
    </div>
    <a class="boton" href="<?= base_url('panel') ?>">Volver</a>
</header>
<p class="bloque">Saldo de la caja: <?= esc(\App\Libraries\Montos::formato($saldo, $moneda)) ?>.</p>
<section class="bloque">
    <h2>Entrega</h2>
    <form method="post" action="<?= base_url('caja/entrega') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Persona
                <select name="usuario_id" required>
                    <?php foreach ($personas as $persona) : ?>
                        <option value="<?= esc((string) $persona['id']) ?>"><?= esc($persona['nombre']) ?></option>
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
        <button class="boton" type="submit">Entregar y centralizar</button>
    </form>
</section>
<section class="bloque">
    <h2>Reposición</h2>
    <form method="post" action="<?= base_url('caja/reposicion') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Motivo
                <input name="motivo" required maxlength="180">
            </label>
            <label>Monto (<?= esc($moneda) ?>)
                <input name="monto" inputmode="decimal" required>
            </label>
        </div>
        <button class="boton" type="submit">Reponer</button>
    </form>
</section>
<?php if ($filas !== []) : ?>
    <table class="tabla">
        <thead><tr><th>Movimiento</th><th>Persona</th><th>Monto</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= (string) $fila['tipo'] === 'reposicion' ? 'Reposición' : 'Entrega' ?> · <?= esc($fila['motivo']) ?></td>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], (string) $fila['moneda'])) ?></td>
                    <td><?= (int) $fila['duplicado'] === 1 ? 'Marcado para revisión de caja' : '' ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
