<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Entrar</h1>
    <form method="post" action="<?= base_url('login') ?>">
        <?= csrf_field() ?>
        <label>Correo
            <input type="email" name="email" value="<?= esc($email) ?>" maxlength="160" required autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="boton" type="submit">Entrar</button>
    </form>
</section>
<?= $this->endSection() ?>
