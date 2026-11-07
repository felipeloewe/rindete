<?php $actual = pais($company['pais']); ?>
<section class="page-head">
  <div>
    <h1>Políticas</h1>
    <p>Estas reglas no bloquean el registro: marcan el gasto para que quien aprueba vea el problema.</p>
  </div>
</section>
<form class="split" method="post">
  <?= csrf_field() ?>
  <div class="card" style="display:grid;gap:.9rem">
    <h2>Empresa</h2>
    <label class="field">Nombre
      <input type="text" name="nombre" required maxlength="120" value="<?= e($company['nombre']) ?>">
    </label>
    <div class="grid-2">
      <label class="field">País
        <select name="pais" data-country>
          <?php foreach ($paises as $code => $pais): ?>
            <option value="<?= e($code) ?>" data-id="<?= e($pais['id_fiscal']) ?>" data-ejemplo="<?= e($pais['ejemplo']) ?>" data-hint="<?= e($pais['autoridad'] . ' · ' . $pais['moneda'] . ' · ' . $pais['codigo']) ?>" <?= $company['pais'] === $code ? 'selected' : '' ?>><?= e($pais['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <small data-country-hint></small>
      </label>
      <label class="field"><span data-fiscal-label data-fiscal-suffix=" de la empresa"><?= e($actual['id_fiscal']) ?> de la empresa</span>
        <input type="text" name="id_fiscal" maxlength="40" value="<?= e($company['id_fiscal']) ?>" placeholder="<?= e($actual['ejemplo']) ?>" autocomplete="off" spellcheck="false" data-fiscal-input>
        <small data-fiscal-hint>Ejemplo: <?= e($actual['ejemplo']) ?></small>
      </label>
    </div>
    <p class="hint">Los gastos nuevos usan <?= e($actual['moneda']) ?> y piden <?= e($actual['codigo']) ?>. Los ya cargados conservan su moneda.</p>
  </div>
  <div class="panel" style="display:grid;gap:.9rem">
    <h2>Control</h2>
    <label class="field"><span><input type="checkbox" name="detectar_duplicados" value="1" <?= !empty($company['detectar_duplicados']) ? 'checked' : '' ?>> Detectar duplicados</span></label>
    <label class="field">Ventana de duplicado (días)
      <input type="number" name="dias_duplicado" min="0" max="30" value="<?= e($company['dias_duplicado']) ?>">
      <small>Mismo proveedor, monto y fecha, o el mismo número de documento.</small>
    </label>
    <label class="field">Exigir comprobante desde (<?= e($actual['moneda']) ?>)
      <input type="text" name="comprobante_desde" inputmode="decimal" value="<?= e($company['comprobante_desde']) ?>">
      <small>Si el monto es igual o mayor y no hay foto, el gasto queda observado.</small>
    </label>
    <button class="btn btn-primary" type="submit">Guardar política</button>
  </div>
</form>
