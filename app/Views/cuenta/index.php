<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Tu cuenta</h1>
    <form method="post" action="<?= base_url('cuenta') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="nombre" value="<?= esc(old('nombre', $usuario['nombre'] ?? '')) ?>" maxlength="80" required>
        </label>
        <label>Correo
            <input type="email" name="email" value="<?= esc(old('email', $usuario['email'] ?? '')) ?>" maxlength="160" required>
        </label>
        <label>Nueva contraseña
            <input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Déjala vacía para no cambiarla">
        </label>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<?= $this->endSection() ?>
