<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Antes de entrar</h1>
        <p>Revisa los efectos del software en cada ley y acepta las condiciones de uso para seguir.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('politicas/aceptar') ?>">
        <?= csrf_field() ?>
        <?= view('politicas/apartado', ['texto' => 'Acepto las condiciones de uso y los efectos que se indican para cada ley.'], ['saveData' => false]) ?>
        <button class="boton" type="submit">Aceptar y entrar</button>
    </form>
</section>
<?= $this->endSection() ?>
