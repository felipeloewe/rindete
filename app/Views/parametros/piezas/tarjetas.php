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
    <div class="grid-2">
        <label>Últimos 4 dígitos
            <input name="ultimos4" inputmode="numeric" maxlength="4" placeholder="4242">
        </label>
        <label>Medio
            <select name="emisor">
                <?php foreach ($emisores as $clave => $texto) : ?>
                    <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    </div>
    <div class="grid-2">
        <label>Identificador de la cuenta
            <input name="referencia" maxlength="80" placeholder="customerId de Global66">
        </label>
        <label>Billetera Global66
            <input name="billetera" maxlength="40" placeholder="walletId">
        </label>
    </div>
    <label>Persona que rinde
        <select name="titular_id">
            <option value="0">Sin titular</option>
            <?php foreach ($usuarios as $miembro) : ?>
                <option value="<?= esc($miembro['id']) ?>"><?= esc($miembro['nombre']) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <label class="check"><input type="checkbox" name="personal" value="1"> De uso personal</label>
    <p>La tarjeta de la empresa queda exclusiva de quien rinde: sus salidas entran al proceso de rendiciones. La personal se identifica y no entra sola.</p>
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
                            <?= $tarjeta['ultimos4'] !== '' ? '****' . esc($tarjeta['ultimos4']) . ' · ' : '' ?>
                            <?= esc($emisores[$tarjeta['emisor']] ?? 'A mano') ?>
                            · <?php if ((int) $tarjeta['exclusiva'] === 1) : ?>
                                Exclusiva de <?= esc($nombres['usuarios'][(int) $tarjeta['titular_id']] ?? '') ?>
                            <?php else : ?>
                                <?= (int) $tarjeta['personal'] === 1 ? 'Personal' : 'Empresa' ?>
                            <?php endif ?>
                            · <?= esc(\App\Libraries\Montos::formato((float) $tarjeta['monto_aceptado'], $moneda)) ?>
                            <?php if (($tarjeta['referencia'] ?? '') !== '') : ?>
                                · <?= esc($tarjeta['referencia']) ?>
                            <?php endif ?>
                        </small>
                    </td>
                    <td class="lado">
                        <?php if (($tarjeta['emisor'] ?? '') === 'global66' && ($tarjeta['billetera'] ?? '') !== '') : ?>
                            <form method="post" action="<?= base_url('parametros/tarjetas/' . $tarjeta['id'] . '/sincronizar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Traer movimientos</button>
                            </form>
                        <?php endif ?>
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
