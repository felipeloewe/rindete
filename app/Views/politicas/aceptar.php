<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Antes de entrar</h1>
        <p>Tu cuenta usa datos personales. Acepta la política para seguir.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('politicas/aceptar') ?>">
        <?= csrf_field() ?>
        <?= view('politicas/apartado', ['texto' => 'Acepto la política de tratamiento de datos personales y las condiciones de uso del software.'], ['saveData' => false]) ?>
        <button class="boton" type="submit">Aceptar y entrar</button>
    </form>
</section>
<?= $this->endSection() ?>
