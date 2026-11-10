<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= $gasto ? 'Editar gasto' : 'Nuevo gasto' ?></h1>
        <p>Datos para quien aprueba y para <?= esc($pais['autoridad'] ?? '') ?>.</p>
    </div>
</header>
<section class="bloque">
    <form method="post" enctype="multipart/form-data" action="<?= base_url($gasto ? 'gastos/' . $gasto['id'] : 'gastos') ?>">
        <?= csrf_field() ?>
        <label>Concepto
            <input name="concepto" required maxlength="140" value="<?= esc($valor('concepto')) ?>" placeholder="Almuerzo con cliente, taxi al aeropuerto…">
        </label>
        <div class="grid-2">
            <label>Categoría
                <select name="categoria_id" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($categorias as $cat) : ?>
                        <?php if ((int) $cat['activa'] !== 1 && (string) $cat['id'] !== $valor('categoria_id')) continue; ?>
                        <option value="<?= esc($cat['id']) ?>" <?= $valor('categoria_id') === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label>Proveedor
                <input name="proveedor" required maxlength="140" value="<?= esc($valor('proveedor')) ?>" placeholder="Quién emitió el comprobante">
            </label>
        </div>
        <div class="grid-2">
            <label>Fecha del gasto
                <input type="date" name="fecha" required value="<?= esc($valor('fecha', date('Y-m-d'))) ?>">
            </label>
            <label>Monto (<?= esc($moneda) ?>)
                <input name="monto" inputmode="decimal" required value="<?= esc($valor('monto')) ?>" placeholder="0">
            </label>
        </div>
        <fieldset>
            <legend>Datos para <?= esc($pais['autoridad'] ?? '') ?></legend>
            <label>Tipo de documento
                <select name="tipo_doc">
                    <option value="">Selecciona</option>
                    <?php foreach ($pais['docs'] ?? [] as $doc) : ?>
                        <option value="<?= esc($doc) ?>" <?= $valor('tipo_doc') === $doc ? 'selected' : '' ?>><?= esc($doc) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <div class="grid-2">
                <label><?= esc($pais['id_proveedor'] ?? 'Identificación') ?>
                    <input name="id_fiscal" maxlength="40" value="<?= esc($valor('id_fiscal')) ?>">
                </label>
                <label>Número de documento
                    <input name="num_doc" maxlength="40" value="<?= esc($valor('num_doc')) ?>">
                </label>
            </div>
            <label><?= esc($pais['codigo'] ?? 'Código') ?>
                <input name="codigo_fiscal" maxlength="80" value="<?= esc($valor('codigo_fiscal')) ?>">
                <small><?= esc($pais['codigo_ayuda'] ?? '') ?></small>
            </label>
        </fieldset>
        <label>Nota para quien aprueba
            <textarea name="nota" maxlength="500" placeholder="Para qué fue el gasto y con quién."><?= esc($valor('nota')) ?></textarea>
        </label>
        <label>Comprobante
            <input type="file" name="comprobante" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
            <small>JPG, PNG, WEBP o PDF. Máximo 5 MB.</small>
        </label>
        <?php if (! empty($gasto['comprobante'])) : ?>
            <p><a href="<?= base_url('gastos/' . $gasto['id'] . '/comprobante') ?>">Ver comprobante actual</a></p>
            <label class="check"><input type="checkbox" name="quitar_comprobante" value="1"> Quitar comprobante actual</label>
        <?php endif ?>
        <p class="acciones">
            <a class="boton claro" href="<?= base_url($gasto ? 'gastos/' . $gasto['id'] : 'gastos') ?>">Cancelar</a>
            <button class="boton" type="submit">Guardar gasto</button>
        </p>
    </form>
</section>
<?= $this->endSection() ?>
