<form method="post" action="<?= base_url('parametros/tipos') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Tipo
            <input name="nombre" required maxlength="80" placeholder="Vale, Transbank…">
        </label>
        <label>Monto aceptado
            <input name="monto_aceptado" value="0">
        </label>
    </div>
    <label class="check"><input type="checkbox" name="sin_comprobante" value="1"> Permite guardar sin comprobante</label>
    <button class="boton" type="submit">Agregar tipo</button>
</form>
<?php foreach ($tipos as $tipo) : ?>
    <form method="post" action="<?= base_url('parametros/tipos/' . $tipo['id']) ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Tipo
                <input name="nombre" required maxlength="80" value="<?= esc($tipo['nombre']) ?>">
            </label>
            <label>Monto aceptado
                <input name="monto_aceptado" value="<?= esc($tipo['monto_aceptado']) ?>">
            </label>
        </div>
        <label class="check"><input type="checkbox" name="sin_comprobante" value="1" <?= (int) $tipo['sin_comprobante'] === 1 ? 'checked' : '' ?>> Permite guardar sin comprobante</label>
        <label class="check"><input type="checkbox" name="activo" value="1" <?= (int) $tipo['activo'] === 1 ? 'checked' : '' ?>> Vigente</label>
        <button class="boton" type="submit">Actualizar</button>
    </form>
<?php endforeach ?>
