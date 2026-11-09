<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Empresa</h1>
    <?php $paisActual = old('pais', $empresa['pais'] ?? 'CL'); ?>
    <form method="post" action="<?= base_url('empresa') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="nombre" value="<?= esc(old('nombre', $empresa['nombre'] ?? '')) ?>" maxlength="120" required>
        </label>
        <label>País
            <select name="pais">
                <?php foreach ($paises as $codigo => $pais) : ?>
                    <option value="<?= esc($codigo) ?>" <?= $paisActual === $codigo ? 'selected' : '' ?>><?= esc($pais['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Identificación fiscal
            <input name="id_fiscal" value="<?= esc(old('id_fiscal', $empresa['id_fiscal'] ?? '')) ?>" maxlength="40" required>
        </label>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<?= $this->endSection() ?>
