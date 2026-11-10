<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <h1>Crear empresa</h1>
</header>
<section class="bloque">
    <?php $ejemplo = $paises[$old['pais']]['ejemplo'] ?? ''; ?>
    <form method="post" action="<?= base_url('registro') ?>">
        <?= csrf_field() ?>
        <label>País
            <select name="pais">
                <?php foreach ($paises as $codigo => $pais) : ?>
                    <option value="<?= esc($codigo) ?>" <?= $old['pais'] === $codigo ? 'selected' : '' ?>><?= esc($pais['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Empresa
            <input name="empresa" value="<?= esc($old['empresa']) ?>" maxlength="120" required>
        </label>
        <label>Identificación fiscal
            <input name="id_fiscal" value="<?= esc($old['id_fiscal']) ?>" maxlength="40" placeholder="<?= esc($ejemplo) ?>" required>
        </label>
        <label>Tu nombre
            <input name="nombre" value="<?= esc($old['nombre']) ?>" maxlength="80" required>
        </label>
        <label>Correo
            <input type="email" name="email" value="<?= esc($old['email']) ?>" maxlength="160" required autocomplete="username">
        </label>
        <label>Contraseña
            <input type="password" name="password" minlength="8" required autocomplete="new-password">
        </label>
        <button class="boton" type="submit">Crear empresa</button>
    </form>
</section>
<?= $this->endSection() ?>
