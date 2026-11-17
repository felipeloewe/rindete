<?php foreach ($categorias as $categoria) : ?>
    <?php $cuentaActual = (int) ($enlaces[(int) $categoria['id']] ?? 0); ?>
    <form method="post" action="<?= base_url('parametros/clasificacion') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="categoria_id" value="<?= esc($categoria['id']) ?>">
        <div class="grid-2">
            <label>Categoría
                <input value="<?= esc($categoria['nombre']) ?>" readonly>
            </label>
            <label>Cuenta genérica
                <select name="cuenta_id">
                    <option value="0">Sin cuenta</option>
                    <?php foreach ($cuentas as $cuenta) : ?>
                        <?php if ($cuenta['clase'] !== 'generica') continue; ?>
                        <option value="<?= esc($cuenta['id']) ?>" <?= $cuentaActual === (int) $cuenta['id'] ? 'selected' : '' ?>><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        </div>
        <button class="boton" type="submit">Guardar</button>
    </form>
<?php endforeach ?>
