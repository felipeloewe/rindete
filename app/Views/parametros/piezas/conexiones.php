<form method="post" action="<?= base_url('parametros/conexiones') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Sistema
            <select name="sistema">
                <?php foreach ($sistemas as $clave => $sistema) : ?>
                    <option value="<?= esc($clave) ?>"><?= esc($sistema['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Nombre
            <input name="nombre" required maxlength="120" placeholder="Contabilidad de la empresa">
        </label>
    </div>
    <label>Sirve a
        <select name="destino">
            <?php foreach ($destinos as $clave => $texto) : ?>
                <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <label class="check"><input type="checkbox" name="vigente" value="1" checked> Vigente</label>
    <button class="boton" type="submit">Guardar conexión</button>
</form>
<?php foreach ($sistemas as $sistema) : ?>
    <p><?= esc($sistema['nombre']) ?>. <?= esc($sistema['detalle']) ?></p>
<?php endforeach ?>
<?php if (session('token')) : ?>
    <p class="aviso nota">Token de esta conexión: <code><?= esc(session('token')) ?></code></p>
<?php endif ?>
<?php if ($conexiones !== []) : ?>
    <table class="tabla">
        <tbody>
            <?php foreach ($conexiones as $conexion) : ?>
                <tr>
                    <td><?= esc($conexion['nombre']) ?><small><?= esc($sistemas[$conexion['sistema']]['nombre'] ?? 'Sin sistema') ?> · <?= esc($destinos[$conexion['destino']] ?? '') ?><?= (int) $conexion['vigente'] === 1 ? ' · vigente' : ' · no vigente' ?></small></td>
                    <td>
                        <form method="post" action="<?= base_url('parametros/conexiones/' . $conexion['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="boton claro" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
