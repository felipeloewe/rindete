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
    <label>Nombre de la credencial
        <input name="credencial" maxlength="120" autocomplete="off" placeholder="Como figura en Integraciones API">
    </label>
    <label>Client ID
        <input name="client_id" maxlength="120" autocomplete="off" placeholder="Identificador público de la credencial">
    </label>
    <label>Client Secret
        <input name="client_secret" type="password" maxlength="500" autocomplete="new-password" placeholder="No se vuelve a mostrar">
    </label>
    <p>Opcional. El Client ID y el Client Secret son de la credencial de la API. La lista de IPs se autoriza en Global66. Al guardar esta conexión, el alta de cada persona puede anotar, si quieres, el accountID de su cuenta.</p>
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
                            <?= ($medio['credencial'] ?? '') !== '' ? ' · ' . esc((string) $medio['credencial']) : '' ?>
                            <?= ($medio['client_id'] ?? '') !== '' ? ' · Client ID ' . esc((string) $medio['client_id']) : '' ?>
                            <?= ($medio['client_secret'] ?? '') !== '' && $medio['client_secret'] !== null ? ' · Client Secret guardado' : '' ?>
                            <?= ($medio['token_consulta'] ?? '') !== '' && $medio['token_consulta'] !== null ? ' · token de consulta guardado' : '' ?>
                        </small>
                    </td>
                    <td>
                        <?php if ((string) $medio['proveedor'] === 'global66' && (string) ($medio['client_id'] ?? '') !== '') : ?>
                            <form method="post" action="<?= base_url('parametros/medios/' . $medio['id'] . '/probar') ?>">
                                <?= csrf_field() ?>
                                <button class="boton claro" type="submit">Probar credencial</button>
                            </form>
                        <?php endif ?>
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
