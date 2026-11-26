<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <h1>Tu cuenta</h1>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('cuenta') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="nombre" value="<?= esc(old('nombre', $usuario['nombre'] ?? '')) ?>" maxlength="80" required>
        </label>
        <label>Correo
            <input type="email" name="email" value="<?= esc(old('email', $usuario['email'] ?? '')) ?>" maxlength="160" required>
        </label>
        <label>Nueva contraseña
            <input type="password" name="password" minlength="8" autocomplete="new-password" <?= (int) ($usuario['clave_inicial'] ?? 0) === 1 ? 'required' : '' ?> placeholder="<?= (int) ($usuario['clave_inicial'] ?? 0) === 1 ? 'Reemplaza la clave inicial' : 'Déjala vacía para no cambiarla' ?>">
        </label>
        <?php if ((int) ($usuario['clave_inicial'] ?? 0) === 1) : ?>
            <p>Esta es la contraseña inicial. Cámbiala para seguir.</p>
        <?php endif ?>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<?= $this->endSection() ?>
