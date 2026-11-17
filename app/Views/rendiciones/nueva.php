<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <h1>Nueva rendición</h1>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('rendiciones') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="titulo" required maxlength="140" value="<?= esc(old('titulo', '')) ?>" placeholder="Viaje a Santiago, noviembre">
        </label>
        <label>Nota
            <textarea name="nota" maxlength="500" placeholder="Para quién la envías y qué cubre."><?= esc(old('nota', '')) ?></textarea>
        </label>
        <div class="acciones">
            <a class="boton claro" href="<?= base_url('rendiciones') ?>">Cancelar</a>
            <button class="boton" type="submit">Abrir rendición</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
