<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Categorías</h1>
        <p>El tope está en <?= esc($moneda) ?>. Usa 0 si la categoría no tiene límite.</p>
    </div>
</header>
<section class="bloque">
    <?php if ($edit) : ?>
        <form method="post" action="<?= base_url('categorias/' . $edit['id']) ?>">
            <?= csrf_field() ?>
            <label>Nombre
                <input name="nombre" required maxlength="80" value="<?= esc(old('nombre', $edit['nombre'])) ?>">
            </label>
            <label>Detalle
                <input name="detalle" maxlength="180" value="<?= esc(old('detalle', $edit['detalle'])) ?>">
            </label>
            <label>Tope
                <input name="tope" required value="<?= esc(old('tope', $edit['tope'])) ?>">
            </label>
            <label class="check"><input type="checkbox" name="activa" value="1" <?= (int) $edit['activa'] === 1 ? 'checked' : '' ?>> Activa</label>
            <button class="boton" type="submit">Guardar</button>
        </form>
    <?php else : ?>
        <form method="post" action="<?= base_url('categorias') ?>">
            <?= csrf_field() ?>
            <label>Nombre
                <input name="nombre" required maxlength="80" value="<?= esc(old('nombre', '')) ?>">
            </label>
            <label>Detalle
                <input name="detalle" maxlength="180" value="<?= esc(old('detalle', '')) ?>">
            </label>
            <label>Tope
                <input name="tope" required value="<?= esc(old('tope', '0')) ?>">
            </label>
            <button class="boton" type="submit">Crear categoría</button>
        </form>
    <?php endif ?>
</section>
<table class="tabla">
    <thead>
        <tr><th>Categoría</th><th>Tope</th><th>Gastos</th><th></th></tr>
    </thead>
    <tbody>
        <?php foreach ($items as $cat) : ?>
            <tr>
                <td><?= esc($cat['nombre']) ?><?= (int) $cat['activa'] === 1 ? '' : ' (inactiva)' ?><small><?= esc($cat['detalle']) ?></small></td>
                <td><?= esc(\App\Libraries\Montos::formato((float) $cat['tope'], $moneda)) ?></td>
                <td><?= esc((string) ($conteo[(int) $cat['id']] ?? 0)) ?></td>
                <td><a href="<?= base_url('categorias?id=' . $cat['id']) ?>">Editar</a></td>
            </tr>
        <?php endforeach ?>
    </tbody>
</table>
<?php if ($edit && ($conteo[(int) $edit['id']] ?? 0) === 0) : ?>
    <form method="post" action="<?= base_url('categorias/' . $edit['id'] . '/eliminar') ?>">
        <?= csrf_field() ?>
        <button class="boton claro" type="submit">Eliminar categoría</button>
    </form>
<?php endif ?>
<?= $this->endSection() ?>
