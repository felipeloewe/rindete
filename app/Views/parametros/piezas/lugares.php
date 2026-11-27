<?php if (empty($dePago)) : ?>
    <p>Centro de costo, sucursal y otro concepto están en los planes de pago. Quien rinde no los ve en el gasto.</p>
<?php else : ?>
    <?php foreach (\App\Libraries\Lugares::ETIQUETAS as $tipo => $etiqueta) : ?>
        <?php $prendido = ! empty($lugaresUso[$tipo]); ?>
        <h2><?= esc($etiqueta) ?></h2>
        <p><?= $prendido ? 'Quien rinde puede elegirlo en el gasto.' : 'Quien rinde no lo selecciona en el gasto.' ?></p>
        <form method="post" action="<?= base_url('parametros/lugares/uso') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="tipo" value="<?= esc($tipo) ?>">
            <input type="hidden" name="habilitado" value="<?= $prendido ? '0' : '1' ?>">
            <button class="boton<?= $prendido ? ' claro' : '' ?>" type="submit"><?= $prendido ? 'Deshabilitar para quien rinde' : 'Habilitar para quien rinde' ?></button>
        </form>
        <?php if ($prendido) : ?>
            <form method="post" action="<?= base_url('parametros/lugares') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="tipo" value="<?= esc($tipo) ?>">
                <label>Nombre
                    <input name="nombre" required maxlength="80" placeholder="Santiago, Comercial, Peaje…">
                </label>
                <button class="boton" type="submit">Agregar</button>
            </form>
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
        <?php endif ?>
    <?php endforeach ?>
<?php endif ?>
