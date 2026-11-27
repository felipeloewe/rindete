<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <h1>Olvidaste tus credenciales</h1>
</header>
<section class="bloque">
    <p>Escribe el correo de tu cuenta. Si está en Rindete, te enviamos una clave nueva. Al entrar la cambias.</p>
    <form method="post" action="<?= base_url('login/credenciales') ?>">
        <?= csrf_field() ?>
        <label>Correo
            <input type="email" name="email" value="<?= esc($email) ?>" maxlength="160" required autocomplete="username">
        </label>
        <button class="boton" type="submit">Enviar clave</button>
    </form>
    <p><a href="<?= base_url('login') ?>">Volver a entrar</a></p>
</section>
<?= $this->endSection() ?>
