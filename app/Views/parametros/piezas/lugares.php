<form method="post" action="<?= base_url('parametros/lugares') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Qué se agrega
            <select name="tipo">
                <?php foreach (\App\Libraries\Lugares::ETIQUETAS as $tipo => $etiqueta) : ?>
                    <option value="<?= esc($tipo) ?>"><?= esc($etiqueta) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Nombre
            <input name="nombre" required maxlength="80" placeholder="Santiago, Comercial, Peaje…">
        </label>
    </div>
    <button class="boton" type="submit">Agregar</button>
</form>
<?php foreach (\App\Libraries\Lugares::ETIQUETAS as $tipo => $etiqueta) : ?>
    <h2><?= esc($etiqueta) ?></h2>
    <?php if ($lugares[$tipo] === []) : ?>
        <p>Todavía no hay.</p>
    <?php else : ?>
        <table class="tabla">
            <tbody>
                <?php foreach ($lugares[$tipo] as $fila) : ?>
                    <tr>
                        <td><?= esc($fila['nombre']) ?></td>
                        <td>
                            <form method="post" action="<?= base_url('parametros/lugares/quitar') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="tipo" value="<?= esc($tipo) ?>">
                                <input type="hidden" name="id" value="<?= esc((string) $fila['id']) ?>">
                                <button class="boton claro" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
<?php endforeach ?>
