<form method="post" action="<?= base_url('parametros/medios') ?>">
    <?= csrf_field() ?>
    <label>Medio
        <select name="proveedor">
            <?php foreach ($proveedores as $clave => $texto) : ?>
                <option value="<?= esc($clave) ?>"><?= esc($texto) ?></option>
            <?php endforeach ?>
        </select>
    </label>
    <label>Clave del webhook
        <input name="secreto" required maxlength="500" autocomplete="off" placeholder="x-api-key de Global66 o token de MACH">
    </label>
    <label>Token de consulta de Global66
        <input name="token_consulta" maxlength="500" autocomplete="off" placeholder="access_token, solo para traer movimientos">
    </label>
    <p>En Global66 registra la dirección de abajo y los eventos de abono y de remesa. La clave viaja en el header x-api-key. El token de consulta, sin la palabra Bearer, sirve para leer la billetera. La cuenta es una por persona y se anota en Personas.</p>
    <p>En MACH registra la otra dirección. MACH avisa pagos y devoluciones de comercio, con el token en Authorization. No envía los movimientos de la tarjeta de débito de quien rinde: esa tarjeta se identifica y se carga en la rendición.</p>
    <button class="boton" type="submit">Guardar conexión</button>
</form>
<?php if ($medios !== []) : ?>
    <table class="tabla">
        <tbody>
            <?php foreach ($medios as $medio) : ?>
                <tr>
                    <td>
                        <?= esc($proveedores[$medio['proveedor']] ?? $medio['proveedor']) ?>
                        <small>
                            <?= esc((new \App\Libraries\MediosConexion())->url($medio)) ?>
                            <?= ($medio['token_consulta'] ?? '') !== '' && $medio['token_consulta'] !== null ? ' · token de consulta guardado' : '' ?>
                        </small>
                    </td>
                    <td>
                        <form method="post" action="<?= base_url('parametros/medios/' . $medio['id'] . '/eliminar') ?>">
                            <?= csrf_field() ?>
                            <button class="boton claro" type="submit">Quitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
