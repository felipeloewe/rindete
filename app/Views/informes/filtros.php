<form class="filtros" method="get" action="<?= esc($accion) ?>">
    <label>Año
        <select name="anio">
            <?php foreach ($anios as $opcion) : ?>
                <option value="<?= esc((string) $opcion) ?>" <?= (int) $anio === (int) $opcion ? 'selected' : '' ?>><?= esc((string) $opcion) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <label>Mes
        <select name="mes">
            <option value="0" <?= (int) $mes === 0 ? 'selected' : '' ?>>Todo el año</option>
            <?php foreach ($meses as $numero => $texto) : ?>
                <option value="<?= esc((string) $numero) ?>" <?= (int) $mes === (int) $numero ? 'selected' : '' ?>><?= esc($texto) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <?php if ($esAdmin) : ?>
        <label>Persona
            <select name="persona">
                <option value="0">Todas</option>
                <?php foreach ($personas as $miembro) : ?>
                    <option value="<?= esc($miembro['id']) ?>" <?= (int) $persona === (int) $miembro['id'] ? 'selected' : '' ?>><?= esc($miembro['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
    <?php endif ?>
    <button class="boton" type="submit">Ver</button>
</form>
<?php if (($clave ?? '') !== '') : ?>
    <p class="lado">
        <a class="boton claro" href="<?= base_url('informes/' . $clave . '/excel?' . $consulta) ?>">Excel</a>
        <a class="boton claro" href="<?= base_url('informes/' . $clave . '/pdf?' . $consulta) ?>">PDF</a>
    </p>
<?php endif ?>
