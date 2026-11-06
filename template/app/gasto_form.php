<?php
$gasto = is_array($expense) ? $expense : [];
$concepto = $valor('concepto');
$proveedor = $valor('proveedor');
$fecha = $valor('fecha', date('Y-m-d'));
$monto = $valor('monto', '');
$categoryId = $valor('category_id');
$tipo = $valor('tipo_doc');
$idFiscal = $valor('id_fiscal');
$numDoc = $valor('num_doc');
$codigo = $valor('codigo_fiscal');
$nota = $valor('nota');
$owner = $valor('user_id', $usuarioActual);
$foto = $gasto['foto'] ?? '';
?>
<section class="page-head">
  <div>
    <h1><?= !empty($gasto['id']) ? 'Editar gasto' : 'Nuevo gasto' ?></h1>
    <p>Datos para quien aprueba y para <?= e($pais['autoridad']) ?>. La vista previa se actualiza mientras escribes.</p>
  </div>
</section>

<form class="expense-layout" method="post" enctype="multipart/form-data" data-expense-form data-currency="<?= e($moneda) ?>" data-autoridad="<?= e($pais['autoridad']) ?>" data-id="<?= e($gasto['id'] ?? '') ?>" data-has-file="<?= $foto ? '1' : '0' ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($gasto['id'] ?? '') ?>">
  <div class="expense-fields">
    <?php if ($esAdmin): ?>
      <label class="field">Colaborador
        <select name="user_id">
          <?php foreach ($equipo as $member): if (empty($member['activo'])) continue; ?>
            <option value="<?= e($member['id']) ?>" <?= $owner === $member['id'] ? 'selected' : '' ?>><?= e($member['nombre']) ?> · <?= e(rol_label($member['rol'])) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    <?php endif; ?>
    <label class="field">Concepto
      <input type="text" name="concepto" required maxlength="140" value="<?= e($concepto) ?>" placeholder="Almuerzo con cliente, taxi al aeropuerto…">
    </label>
    <div class="grid-2">
      <label class="field">Categoría
        <select name="category_id" required>
          <option value="">Selecciona</option>
          <?php foreach ($categorias as $cat): ?>
            <?php if (empty($cat['activa']) && $cat['id'] !== $categoryId) continue; ?>
            <option value="<?= e($cat['id']) ?>" data-tope="<?= e($cat['tope']) ?>" <?= $categoryId === $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="field">Proveedor
        <input type="text" name="proveedor" required maxlength="140" value="<?= e($proveedor) ?>" placeholder="Quién emitió el comprobante">
      </label>
    </div>
    <div class="grid-2">
      <label class="field">Fecha del gasto
        <input type="date" name="fecha" required value="<?= e($fecha) ?>">
      </label>
      <label class="field">Monto (<?= e($moneda) ?>)
        <input type="text" name="monto" inputmode="decimal" required value="<?= e($monto) ?>" placeholder="0">
      </label>
    </div>
    <fieldset class="fiscal-box">
      <legend>Datos para <?= e($pais['autoridad']) ?></legend>
      <label class="field">Tipo de documento
        <select name="tipo_doc">
          <option value="">Selecciona</option>
          <?php foreach ($pais['docs'] as $doc): ?>
            <option value="<?= e($doc) ?>" <?= $tipo === $doc ? 'selected' : '' ?>><?= e($doc) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="grid-2">
        <label class="field"><?= e($pais['id_proveedor']) ?>
          <input type="text" name="id_fiscal" maxlength="40" value="<?= e($idFiscal) ?>">
        </label>
        <label class="field">Número de documento
          <input type="text" name="num_doc" maxlength="40" value="<?= e($numDoc) ?>">
        </label>
      </div>
      <label class="field"><?= e($pais['codigo']) ?>
        <input type="text" name="codigo_fiscal" maxlength="80" value="<?= e($codigo) ?>">
        <small><?= e($pais['codigo_ayuda']) ?></small>
      </label>
    </fieldset>
    <label class="field">Nota para quien aprueba
      <textarea name="nota" maxlength="500" placeholder="Para qué fue el gasto y con quién."><?= e($nota) ?></textarea>
    </label>
  </div>
  <aside class="expense-side">
    <label class="drop" data-drop>
      <strong>Comprobante</strong>
      <p class="muted">Arrastra la foto o el PDF. Máximo 5 MB.</p>
      <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
    </label>
    <?php if ($foto): ?>
      <?php if (preg_match('/\.pdf$/i', $foto)): ?>
        <p><a href="<?= e(url($foto)) ?>" target="_blank" rel="noopener">Ver PDF actual</a></p>
      <?php else: ?>
        <img class="preview-photo" id="pvPhoto" data-existing="1" src="<?= e(url($foto)) ?>" alt="Comprobante actual">
      <?php endif; ?>
      <label class="field"><span><input type="checkbox" name="quitar_foto" value="1"> Quitar comprobante actual</span></label>
    <?php else: ?>
      <img class="preview-photo" id="pvPhoto" alt="" hidden>
    <?php endif; ?>
    <article class="live" aria-live="polite">
      <p>Vista previa para aprobación</p>
      <h3 id="pvConcepto">Nuevo gasto</h3>
      <p id="pvProveedor">Sin proveedor</p>
      <strong id="pvMonto"><?= e($moneda) ?> 0</strong>
      <p id="pvMeta"></p>
      <p id="pvDoc"></p>
      <p id="pvNota"></p>
      <div class="insights" id="pvInsights"></div>
    </article>
  </aside>
  <div class="expense-actions">
    <a class="btn btn-ghost" href="<?= e(!empty($gasto['id']) ? url('gasto.php?id=' . urlencode($gasto['id'])) : url('gastos.php')) ?>">Cancelar</a>
    <button class="btn btn-citrus" type="submit">Guardar gasto</button>
  </div>
</form>
<script id="expense-index" type="application/json"><?= json_encode($indice, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
