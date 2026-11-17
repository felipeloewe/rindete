<form method="get" action="<?= base_url('parametros/aceptados') ?>">
    <label>Persona
        <select name="persona">
            <?php foreach ($usuarios as $item) : ?>
                <option value="<?= esc($item['id']) ?>" <?= (int) $persona === (int) $item['id'] ? 'selected' : '' ?>><?= esc($item['nombre']) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <button class="boton claro" type="submit">Ver</button>
</form>
<form method="post" action="<?= base_url('parametros/aceptados') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="usuario_id" value="<?= esc((string) $persona) ?>">
    <div class="marcas">
        <?php foreach ($categorias as $categoria) : ?>
            <label class="check"><input type="checkbox" name="categoria_id[]" value="<?= esc($categoria['id']) ?>" <?= in_array((int) $categoria['id'], $aceptados, true) ? 'checked' : '' ?>> <?= esc($categoria['nombre']) ?></label>
        <?php endforeach ?>
    </div>
    <button class="boton" type="submit">Guardar gastos aceptados</button>
</form>
