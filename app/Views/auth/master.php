<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Superadmin</h1>
        <p>Empresas en Plan Free y en cobro.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('master') ?>">
        <?= csrf_field() ?>
        <label>Usuario
            <input name="usuario" value="<?= esc($usuario) ?>" maxlength="40" required autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="boton" type="submit">Entrar</button>
    </form>
</section>
<?= $this->endSection() ?>
