<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Activa tu cuenta</h1>
    <p>Hola, <?= esc($usuario['nombre'] ?? '') ?>. Elige una contraseña para entrar a Rindete.</p>
    <form method="post" action="<?= base_url('invitar/' . $token) ?>">
        <?= csrf_field() ?>
        <label>Correo
            <input type="email" value="<?= esc($usuario['email'] ?? '') ?>" disabled>
        </label>
        <label>Contraseña
            <input type="password" name="password" minlength="8" autocomplete="new-password" required>
        </label>
        <button class="boton" type="submit">Activar y entrar</button>
    </form>
</section>
<?= $this->endSection() ?>
