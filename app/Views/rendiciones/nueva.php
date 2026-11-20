<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Nueva rendición</h1>
        <p>Se abre sin solicitud. El tope y los conceptos son los tuyos si existen, y si no los de la empresa.</p>
    </div>
</header>
<section class="bloque">
    <dl class="ficha">
        <div><dt>Tope</dt><dd><?= esc($tope) ?></dd></div>
        <div><dt>Viático</dt><dd><?= esc($viatico) ?></dd></div>
        <div><dt>Asignación</dt><dd><?= esc($asignacion) ?></dd></div>
    </dl>
</section>
<section class="bloque">
    <form method="post" action="<?= base_url('rendiciones') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="titulo" required maxlength="140" value="<?= esc(old('titulo', '')) ?>" placeholder="Viaje a Santiago, noviembre">
        </label>
        <?php if ($fondos !== []) : ?>
            <label>Fondo por rendir
                <select name="fondo_id">
                    <option value="0">Sin fondo entregado</option>
                    <?php foreach ($fondos as $fondo) : ?>
                        <option value="<?= esc($fondo['id']) ?>"<?= (string) old('fondo_id') === (string) $fondo['id'] ? ' selected' : '' ?>><?= esc($fondo['motivo']) ?> · <?= esc(\App\Libraries\Montos::formato((float) $fondo['monto'], (string) $moneda)) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <p>Si eliges un fondo, lo entregado es lo que hay que rendir.</p>
        <?php endif ?>
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
