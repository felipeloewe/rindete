<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Empresa</h1>
        <p><?= esc(\App\Libraries\Planes::texto()) ?></p>
    </div>
</header>
<section class="bloque">
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
        <label>WhatsApp Business, el que recibe
            <input name="whatsapp" value="<?= esc(old('whatsapp', $empresa['whatsapp'] ?? '')) ?>" maxlength="20" placeholder="+56 9 1111 2222">
        </label>
        <p>Quien rinde manda la boleta desde su teléfono de envío. Este número es el WhatsApp Business que la recibe.</p>
        <label>Correo que recibe boletas y facturas
            <input name="correo_boletas" type="email" value="<?= esc(old('correo_boletas', $empresa['correo_boletas'] ?? '')) ?>" maxlength="160" placeholder="boletas@empresa.cl">
        </label>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<?= $this->endSection() ?>
