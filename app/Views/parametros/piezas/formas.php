<form method="post" action="<?= base_url('parametros/formas') ?>">
    <?= csrf_field() ?>
    <div class="grid-2">
        <label>Forma de pago
            <input name="nombre" required maxlength="80" placeholder="Cheque, vale vista…">
        </label>
        <label class="check"><input type="checkbox" name="efectivo" value="1"> Es efectivo y exige comprobante de pago al contado</label>
    </div>
    <button class="boton" type="submit">Agregar</button>
</form>
<?php if ($formas === []) : ?>
    <p>Todavía no hay formas de pago.</p>
<?php else : ?>
    <table class="tabla">
        <tbody>
            <?php foreach ($formas as $forma) : ?>
                <tr>
                    <td><?= esc($forma['nombre']) ?></td>
                    <td><?= (int) $forma['efectivo'] === 1 ? 'Exige comprobante' : 'Sin comprobante obligatorio' ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
