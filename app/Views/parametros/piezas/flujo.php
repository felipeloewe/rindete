<form method="post" action="<?= base_url('parametros/flujo') ?>">
    <?= csrf_field() ?>
    <label>Monto máximo de una rendición
        <input name="monto_maximo_rendicion" value="<?= esc($empresa['monto_maximo_rendicion'] ?? '0') ?>">
        <small>Usa 0 si no hay máximo.</small>
    </label>
    <label class="check"><input type="checkbox" name="detener_monto" value="1" <?= (int) ($empresa['detener_monto'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si el monto supera el máximo</label>
    <label class="check"><input type="checkbox" name="detener_tipo" value="1" <?= (int) ($empresa['detener_tipo'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si el tipo de gasto no está permitido</label>
    <label class="check"><input type="checkbox" name="detener_habilitacion" value="1" <?= (int) ($empresa['detener_habilitacion'] ?? 1) === 1 ? 'checked' : '' ?>> Detener si la persona no está habilitada</label>
    <button class="boton" type="submit">Guardar reglas</button>
</form>
<form method="post" action="<?= base_url('parametros/aceptadores') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Acepta
            <select name="usuario_id" required>
                <?php foreach ($usuarios as $miembro) : ?>
                    <option value="<?= esc($miembro['id']) ?>"><?= esc($miembro['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Orden
            <input name="orden" inputmode="numeric" value="1" required>
        </label>
    </div>
    <button class="boton" type="submit">Agregar al flujo</button>
</form>
<?php if ($aceptadores !== []) : ?>
    <table class="tabla">
        <tbody>
            <?php foreach ($aceptadores as $paso) : ?>
                <tr>
                    <td><?= esc((string) $paso['orden']) ?>. <?= esc($nombres['usuarios'][(int) $paso['usuario_id']] ?? '') ?></td>
                    <td>
                        <form method="post" action="<?= base_url('parametros/aceptadores/' . $paso['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="boton claro" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
