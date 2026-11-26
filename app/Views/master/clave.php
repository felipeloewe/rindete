<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Cambia la contraseña</h1>
        <p>La clave inicial se reemplaza antes de ver las empresas.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('master/clave') ?>">
        <?= csrf_field() ?>
        <label>Contraseña actual
            <input type="password" name="actual" required autocomplete="current-password">
        </label>
        <label>Contraseña nueva
            <input type="password" name="password" minlength="8" required autocomplete="new-password">
        </label>
        <label>Repite la contraseña nueva
            <input type="password" name="password2" minlength="8" required autocomplete="new-password">
        </label>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<?= $this->endSection() ?>
