<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Personas</h1>
        <p>El plan Free admite <?= esc((string) $cupo) ?> usuarios. Marca quién puede abrir una rendición sin solicitud y, si la tiene, su tope propio.</p>
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
            </div>
            <div class="grid-2">
                <label>Clave inicial
                    <input name="password" type="password" required minlength="8" autocomplete="new-password">
                </label>
                <label>Tope propio de rendición
                    <input name="monto_maximo" inputmode="decimal" value="<?= esc(old('monto_maximo', '')) ?>" placeholder="Vacío usa el de la empresa">
                </label>
            </div>
            <label class="cheque">
                <input type="checkbox" name="puede_rendir_sin_solicitud" value="1" <?= old('puede_rendir_sin_solicitud') ? 'checked' : '' ?>>
                Puede rendir sin solicitud
            </label>
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
        <p><?= esc($persona['email']) ?></p>
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
