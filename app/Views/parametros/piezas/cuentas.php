<form method="post" action="<?= base_url('parametros/cuentas') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Clase
            <select name="clase">
                <option value="generica">Genérica</option>
                <option value="remuneracion">Remuneración</option>
            </select>
        </label>
        <label>Código
            <input name="codigo" required maxlength="32" placeholder="5101001">
        </label>
    </div>
    <label>Nombre
        <input name="nombre" required maxlength="120" placeholder="Gastos de viaje">
    </label>
    <div class="grid-2">
        <label>Concepto de remuneración
            <select name="concepto">
                <option value="">No aplica</option>
                <?php foreach ($conceptos as $clave => $texto) : ?>
                    <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Concepto propio de
            <select name="usuario_id">
                <option value="0">Toda la empresa</option>
                <?php foreach ($usuarios as $miembro) : ?>
                    <option value="<?= esc($miembro['id']) ?>"><?= esc($miembro['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    </div>
    <button class="boton" type="submit">Guardar cuenta</button>
</form>
<?php if ($cuentas !== []) : ?>
    <table class="tabla">
        <thead><tr><th>Cuenta</th><th>Uso</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($cuentas as $cuenta) : ?>
                <tr>
                    <td><?= esc($cuenta['codigo']) ?> <?= esc($cuenta['nombre']) ?></td>
                    <td>
                        <?= $cuenta['clase'] === 'remuneracion' ? 'Remuneración' : 'Genérica' ?>
                        <?php if ($cuenta['concepto'] !== '') : ?>
                            · <?= esc($conceptos[$cuenta['concepto']] ?? $cuenta['concepto']) ?>
                        <?php endif ?>
                        <?php if ((int) ($cuenta['usuario_id'] ?? 0) > 0) : ?>
                            · <?= esc($nombres['usuarios'][(int) $cuenta['usuario_id']] ?? '') ?>
                        <?php endif ?>
                    </td>
                    <td>
                        <form method="post" action="<?= base_url('parametros/cuentas/' . $cuenta['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="boton claro" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
