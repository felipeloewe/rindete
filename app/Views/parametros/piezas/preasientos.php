<?php if ((string) ($empresa['pais'] ?? '') !== 'CL') : ?>
    <p>El preasiento normativo está definido para Chile. Esta empresa usa otra normativa.</p>
<?php else : ?>
    <form method="post" action="<?= base_url('parametros/preasientos') ?>">
        <?= csrf_field() ?>
        <label>Agrupación
            <select name="agrupacion">
                <option value="mes" <?= ($empresa['preasiento_agrupacion'] ?? 'mes') !== 'usuario' ? 'selected' : '' ?>>Un preasiento del mes, todas las personas</option>
                <option value="usuario" <?= ($empresa['preasiento_agrupacion'] ?? '') === 'usuario' ? 'selected' : '' ?>>Un preasiento por persona en el mes</option>
            </select>
        </label>
        <label class="cheque">
            <input type="checkbox" name="auxiliar" value="1" <?= (int) ($empresa['preasiento_auxiliar'] ?? 0) === 1 ? 'checked' : '' ?>>
            Incluir el RUT del trabajador como auxiliar
        </label>
        <label>Cuenta de donde sale el dinero
            <select name="salida_id">
                <option value="0">Elige el banco o la caja</option>
                <?php foreach ($cuentas as $cuenta) : ?>
                    <?php if ((int) $cuenta['activa'] !== 1) { continue; } ?>
                    <option value="<?= esc($cuenta['id']) ?>" <?= (int) ($empresa['preasiento_salida_id'] ?? 0) === (int) $cuenta['id'] ? 'selected' : '' ?>><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Fondo por rendir
            <select name="cuenta_id">
                <option value="0">Elige la cuenta del fondo</option>
                <?php foreach ($cuentas as $cuenta) : ?>
                    <?php if ((int) $cuenta['activa'] !== 1) { continue; } ?>
                    <option value="<?= esc($cuenta['id']) ?>" <?= (int) ($empresa['preasiento_cuenta_id'] ?? 0) === (int) $cuenta['id'] ? 'selected' : '' ?>><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <p>Al entregar el fondo, el dinero sale del banco o de la caja y nace el fondo por rendir. Al rendir, ese fondo pasa a la cuenta de cada gasto.</p>
        <button class="boton" type="submit">Guardar</button>
    </form>
<?php endif ?>
