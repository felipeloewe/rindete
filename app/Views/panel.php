<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1><?= esc($empresa['nombre'] ?? 'Tu empresa') ?></h1>
    <p>Hola, <?= esc($usuario['nombre'] ?? '') ?>. Esta es la cuenta de la empresa.</p>
    <?php if ($empresa && $pais) : ?>
        <dl class="ficha">
            <div><dt>País</dt><dd><?= esc($pais['nombre']) ?></dd></div>
            <div><dt><?= esc($pais['id_fiscal']) ?></dt><dd><?= esc($empresa['id_fiscal']) ?></dd></div>
            <div><dt>Moneda</dt><dd><?= esc($pais['moneda']) ?></dd></div>
            <div><dt>Correo</dt><dd><?= esc($usuario['email'] ?? '') ?></dd></div>
        </dl>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
