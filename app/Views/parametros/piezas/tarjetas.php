<form method="post" action="<?= base_url('parametros/tarjetas') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Nombre
            <input name="nombre" required maxlength="120" placeholder="Visa empresa">
        </label>
        <label>Monto aceptado
            <input name="monto_aceptado" value="0">
        </label>
    </div>
    <label class="check"><input type="checkbox" name="personal" value="1"> De uso personal</label>
    <p>Personas con acceso</p>
    <div class="marcas">
        <?php foreach ($usuarios as $miembro) : ?>
            <label class="check"><input type="checkbox" name="usuario_id[]" value="<?= esc($miembro['id']) ?>"> <?= esc($miembro['nombre']) ?></label>
        <?php endforeach ?>
    </div>
    <p>Gastos de la tarjeta</p>
    <div class="marcas">
        <?php foreach ($categorias as $categoria) : ?>
            <label class="check"><input type="checkbox" name="categoria_id[]" value="<?= esc($categoria['id']) ?>"> <?= esc($categoria['nombre']) ?></label>
        <?php endforeach ?>
    </div>
    <button class="boton" type="submit">Guardar tarjeta</button>
</form>
<?php if ($tarjetas !== []) : ?>
    <table class="tabla">
        <tbody>
            <?php foreach ($tarjetas as $tarjeta) : ?>
                <tr>
                    <td>
                        <?= esc($tarjeta['nombre']) ?>
                        <small>
                            <?= (int) $tarjeta['personal'] === 1 ? 'Personal' : 'Empresa' ?>
                            · <?= esc(\App\Libraries\Montos::formato((float) $tarjeta['monto_aceptado'], $moneda)) ?>
                            <?php foreach ($tarjeta['personas'] as $id) : ?>
                                · <?= esc($nombres['usuarios'][$id] ?? '') ?>
                            <?php endforeach ?>
                        </small>
                    </td>
                    <td>
                        <form method="post" action="<?= base_url('parametros/tarjetas/' . $tarjeta['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="boton claro" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
