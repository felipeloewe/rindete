<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Invitación no válida</h1>
    <p>El enlace expiró o ya se usó. Pide al administrador de tu empresa que te invite de nuevo.</p>
    <p><a class="boton" href="<?= base_url('login') ?>">Ir a entrar</a></p>
</section>
<?= $this->endSection() ?>
