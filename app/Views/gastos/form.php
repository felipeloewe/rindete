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
        <?php if (! empty($entrada)) : ?>
            <p>Esta boleta llegó por <?= $entrada['canal'] === 'correo' ? 'correo' : 'WhatsApp' ?>. Al guardar, el archivo queda como comprobante.</p>
            <?php if ((string) ($entrada['texto'] ?? '') !== '') : ?>
                <p><?= esc($entrada['texto']) ?></p>
            <?php endif ?>
            <?php if (($lectura ?? []) !== []) : ?>
                <p>Leímos monto, fecha, RUT o folio de la boleta. Confirma o corrige antes de guardar.</p>
            <?php endif ?>
            <input type="hidden" name="entrada_id" value="<?= (int) $entrada['id'] ?>">
        <?php endif ?>
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
        <?php if ($usaCentro || $usaSucursal) : ?>
            <div class="grid-2">
                <?php if ($usaCentro) : ?>
                    <label>Centro de costo
                        <select name="centro_id">
                            <option value="">Sin centro</option>
                            <?php foreach ($centros as $centro) : ?>
                                <option value="<?= esc((string) $centro['id']) ?>" <?= $valor('centro_id') === (string) $centro['id'] ? 'selected' : '' ?>><?= esc($centro['nombre']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                <?php endif ?>
                <?php if ($usaSucursal) : ?>
                    <label>Sucursal
                        <select name="sucursal_id">
                            <option value="">Sin sucursal</option>
                            <?php foreach ($sucursales as $sucursal) : ?>
                                <option value="<?= esc((string) $sucursal['id']) ?>" <?= $valor('sucursal_id') === (string) $sucursal['id'] ? 'selected' : '' ?>><?= esc($sucursal['nombre']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                <?php endif ?>
            </div>
        <?php endif ?>
        <?php if ($usaConcepto) : ?>
            <label>Otro concepto
                <select name="concepto_extra_id">
                    <option value="">Sin otro concepto</option>
                    <?php foreach ($conceptos as $extra) : ?>
                        <option value="<?= esc((string) $extra['id']) ?>" <?= $valor('concepto_extra_id') === (string) $extra['id'] ? 'selected' : '' ?>><?= esc($extra['nombre']) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
        <?php endif ?>
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
                    <?php foreach ($tipos as $doc) : ?>
                        <option value="<?= esc($doc['nombre']) ?>" <?= $valor('tipo_doc') === $doc['nombre'] ? 'selected' : '' ?>><?= esc($doc['nombre']) ?><?php if ((float) $doc['monto_aceptado'] > 0) : ?> · hasta <?= esc(\App\Libraries\Montos::formato((float) $doc['monto_aceptado'], $moneda)) ?><?php endif ?></option>
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
            <small>JPG, PNG, WEBP o PDF. Máximo 5 MB. Si el archivo trae proveedor, tipo, fecha, monto, RUT o folio, los guardamos en el gasto y te dejamos confirmar.</small>
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
