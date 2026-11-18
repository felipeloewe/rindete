<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Pedir fondo</h1>
        <p>Monto y motivo. Si una regla lo detiene, la solicitud queda ahí.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" action="<?= base_url('fondos') ?>">
        <?= csrf_field() ?>
        <label>Motivo
            <input name="motivo" required maxlength="180" value="<?= esc(old('motivo', '')) ?>" placeholder="Viaje a Concepción, noviembre">
        </label>
        <div class="grid-2">
            <label>Gasto
                <select name="categoria_id" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($categorias as $categoria) : ?>
                        <option value="<?= esc($categoria['id']) ?>" <?= old('categoria_id') === (string) $categoria['id'] ? 'selected' : '' ?>><?= esc($categoria['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Monto (<?= esc($moneda) ?>)
                <input name="monto" inputmode="decimal" required value="<?= esc(old('monto', '')) ?>" placeholder="0">
                <?php if ($maximo > 0) : ?>
                    <small>El máximo de una rendición es <?= esc(\App\Libraries\Montos::formato($maximo, $moneda)) ?>.</small>
                <?php endif ?>
            </label>
        </div>
        <div class="acciones">
            <a class="boton claro" href="<?= base_url('fondos') ?>">Cancelar</a>
            <button class="boton" type="submit">Enviar solicitud</button>
        </div>
    </form>
</section>
<?= $this->endSection() ?>
