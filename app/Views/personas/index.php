<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Personas</h1>
        <p>Caben <?= esc((string) $cupo) ?> personas que rinden. El administrador no ocupa cupo, salvo que también rinda. Cada persona que rinde tiene su cuenta Global66. La nómina deposita ahí y el egreso abre la rendición.</p>
    </div>
</header>
<p class="bloque"><?= esc((string) $usadas) ?> de <?= esc((string) $cupo) ?> usuarios.</p>
<?php if ($puedeSumar) : ?>
    <section class="bloque">
        <h2>Dar de alta</h2>
        <form method="post" action="<?= base_url('personas') ?>">
            <?= csrf_field() ?>
            <div class="grid-2">
                <label>Nombre
                    <input name="nombre" required maxlength="80" value="<?= esc(old('nombre', '')) ?>">
                </label>
                <label>Correo
                    <input name="email" type="email" required maxlength="160" value="<?= esc(old('email', '')) ?>">
                </label>
                <label>Teléfono de envío
                    <input name="telefono" maxlength="20" value="<?= esc(old('telefono', '')) ?>" placeholder="+56 9 1234 5678">
                </label>
            </div>
            <div class="grid-2">
                <label>Clave inicial
                    <input name="password" type="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Tope propio de rendición
                    <input name="monto_maximo" inputmode="decimal" value="<?= esc(old('monto_maximo', '')) ?>" placeholder="Vacío usa el de la empresa">
                </label>
            </div>
            <div class="grid-2">
                <label><?= esc($documento) ?>
                    <input name="id_fiscal" maxlength="40" value="<?= esc(old('id_fiscal', '')) ?>" placeholder="<?= esc($ejemplo) ?>">
                </label>
                <label>Cuenta del banco
                    <input name="cuenta_banco" maxlength="18" value="<?= esc(old('cuenta_banco', '')) ?>" placeholder="Para la nómina de fondos">
                </label>
                <label>Identificador Global66
                    <input name="global_cliente" maxlength="80" value="<?= esc(old('global_cliente', '')) ?>" placeholder="Cliente de la cuenta de esta persona">
                </label>
                <label>Billetera Global66
                    <input name="global_billetera" maxlength="40" value="<?= esc(old('global_billetera', '')) ?>" placeholder="Id numérico de la billetera">
                </label>
            </div>
            <?php if ($chile) : ?>
                <label>Banco de destino
                    <select name="banco_destino">
                        <option value="">Elige el banco</option>
                        <?php foreach ($bancos as $codigo => $banco) : ?>
                            <option value="<?= esc($codigo) ?>" <?= old('banco_destino') === $codigo ? 'selected' : '' ?>><?= esc($codigo) ?> <?= esc($banco) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            <?php endif ?>
            <label class="cheque">
                <input type="checkbox" name="puede_rendir_sin_solicitud" value="1" <?= old('puede_rendir_sin_solicitud') ? 'checked' : '' ?>>
                Puede rendir sin solicitud
            </label>
            <?= view('politicas/apartado', ['texto' => 'La persona acepta las condiciones de uso y los efectos que se indican para cada ley.'], ['saveData' => false]) ?>
            <button class="boton" type="submit">Agregar persona</button>
        </form>
    </section>
<?php else : ?>
    <p class="bloque">El plan Free ya tiene sus <?= esc((string) $cupo) ?> usuarios.</p>
<?php endif ?>
<?php foreach ($personas as $persona) : ?>
    <section class="bloque">
        <div class="cabeza">
            <h2><?= esc($persona['nombre']) ?></h2>
            <span><?= $persona['rol'] === 'administrador' ? 'Administrador' : 'Rinde' ?></span>
        </div>
        <p><?= esc($persona['email']) ?><?= ($persona['telefono'] ?? '') !== '' ? ' · envía desde ' . esc($persona['telefono']) : '' ?><?= ($persona['id_fiscal'] ?? '') !== '' && $persona['id_fiscal'] !== null ? ' · ' . esc($persona['id_fiscal']) : '' ?></p>
        <?php $cuenta = $cuentas[(int) $persona['id']] ?? null; ?>
        <?php if (is_array($cuenta)) : ?>
            <p>Cuenta Global66 <?= esc((string) $cuenta['referencia']) ?> · billetera <?= esc((string) $cuenta['billetera']) ?></p>
        <?php endif ?>
        <?php if (\App\Libraries\PoliticaDatos::aceptada($persona)) : ?>
            <p>Aceptó las condiciones de uso.</p>
        <?php else : ?>
            <p>Falta aceptar las condiciones de uso.</p>
        <?php endif ?>
        <form method="post" action="<?= base_url('personas/' . $persona['id']) ?>">
            <?= csrf_field() ?>
            <label class="cheque">
                <input type="checkbox" name="puede_rendir_sin_solicitud" value="1" <?= (int) ($persona['puede_rendir_sin_solicitud'] ?? 0) === 1 ? 'checked' : '' ?>>
                Puede rendir sin solicitud
            </label>
            <div class="grid-2">
                <label>Tope propio de rendición
                    <input name="monto_maximo" inputmode="decimal" value="<?= $persona['monto_maximo_rendicion'] === null || $persona['monto_maximo_rendicion'] === '' ? '' : esc((string) $persona['monto_maximo_rendicion']) ?>" placeholder="Vacío usa el de la empresa">
                </label>
                <label>Nueva clave
                    <input name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Solo si quieres cambiarla">
                </label>
            </div>
            <div class="grid-2">
                <label><?= esc($documento) ?>
                    <input name="id_fiscal" maxlength="40" value="<?= esc((string) ($persona['id_fiscal'] ?? '')) ?>" placeholder="<?= esc($ejemplo) ?>">
                </label>
                <label>Cuenta del banco
                    <input name="cuenta_banco" maxlength="18" value="<?= esc((string) ($persona['cuenta_banco'] ?? '')) ?>" placeholder="Para la nómina de fondos">
                </label>
                <label>Identificador Global66
                    <input name="global_cliente" maxlength="80" value="<?= esc(is_array($cuenta) ? (string) $cuenta['referencia'] : '') ?>" placeholder="Cliente de la cuenta de esta persona">
                </label>
                <label>Billetera Global66
                    <input name="global_billetera" maxlength="40" value="<?= esc(is_array($cuenta) ? (string) $cuenta['billetera'] : '') ?>" placeholder="Id numérico de la billetera">
                </label>
                <label>Teléfono de envío
                    <input name="telefono" maxlength="20" value="<?= esc((string) ($persona['telefono'] ?? '')) ?>" placeholder="+56 9 1234 5678">
                </label>
            </div>
            <?php if ($chile) : ?>
                <label>Banco de destino
                    <select name="banco_destino">
                        <option value="">Elige el banco</option>
                        <?php foreach ($bancos as $codigo => $banco) : ?>
                            <option value="<?= esc($codigo) ?>" <?= (string) ($persona['banco_destino'] ?? '') === $codigo ? 'selected' : '' ?>><?= esc($codigo) ?> <?= esc($banco) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            <?php endif ?>
            <?php if ((int) $persona['id'] === $propias) : ?>
                <p>Esta es tu cuenta. Sigue activa.</p>
            <?php else : ?>
                <label class="cheque">
                    <input type="checkbox" name="activo" value="1" <?= (int) $persona['activo'] === 1 ? 'checked' : '' ?>>
                    Habilitada para entrar
                </label>
            <?php endif ?>
            <button class="boton" type="submit">Guardar</button>
        </form>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
