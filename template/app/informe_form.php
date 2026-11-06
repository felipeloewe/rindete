<?php
$base = is_array($report) ? $report : [];
$titulo = is_array($old) && array_key_exists('titulo', $old) ? $old['titulo'] : ($base['titulo'] ?? '');
$desde = is_array($old) && array_key_exists('desde', $old) ? $old['desde'] : ($base['desde'] ?? '');
$hasta = is_array($old) && array_key_exists('hasta', $old) ? $old['hasta'] : ($base['hasta'] ?? '');
$nota = is_array($old) && array_key_exists('nota', $old) ? $old['nota'] : ($base['nota'] ?? '');
$marcados = [];
if (is_array($old) && isset($old['gastos']) && is_array($old['gastos'])) {
    $marcados = $old['gastos'];
} else {
    $marcados = $base['expense_ids'] ?? [];
}
$grupos = [];
foreach ($disponibles as $expense) {
    $grupos[$expense['user_id']][] = $expense;
}
?>
<section class="page-head">
  <div>
    <h1><?= !empty($base['id']) ? 'Editar rendición' : 'Nueva rendición' ?></h1>
    <p>Elige gastos de una misma persona. Puedes guardarla en borrador o enviarla a revisión.</p>
  </div>
</section>

<form class="split" method="post" data-report-form data-currency="<?= e($moneda) ?>">
  <div class="card" style="display:grid;gap:.9rem">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= e($base['id'] ?? '') ?>">
    <label class="field">Nombre
      <input type="text" name="titulo" required maxlength="140" value="<?= e($titulo) ?>" placeholder="Viaje Medellín · septiembre">
    </label>
    <div class="grid-2">
      <label class="field">Desde
        <input type="date" name="desde" value="<?= e($desde) ?>">
      </label>
      <label class="field">Hasta
        <input type="date" name="hasta" value="<?= e($hasta) ?>">
      </label>
    </div>
    <label class="field">Nota
      <textarea name="nota" maxlength="500" placeholder="Motivo del viaje o del periodo."><?= e($nota) ?></textarea>
    </label>
    <h2>Gastos</h2>
    <?php if (!$disponibles): ?>
      <div class="empty">
        <h2>No hay gastos libres</h2>
        <p class="muted">Registra un gasto antes de armar la rendición.</p>
        <a class="btn btn-primary btn-small" href="<?= e(url('gasto.php?nuevo=1')) ?>">Nuevo gasto</a>
      </div>
    <?php else: ?>
      <?php foreach ($grupos as $userId => $grupo): ?>
        <h3 style="font-size:1.3rem"><?= e(user_name($userId)) ?></h3>
        <div class="check-list">
          <?php foreach ($grupo as $expense): ?>
            <label class="check-item">
              <input type="checkbox" name="gastos[]" value="<?= e($expense['id']) ?>" data-monto="<?= e($expense['monto']) ?>" <?= in_array($expense['id'], $marcados, true) ? 'checked' : '' ?>>
              <span>
                <b><?= e($expense['concepto']) ?></b>
                <small class="muted" style="display:block"><?= e($expense['proveedor']) ?> · <?= e(fecha_latam($expense['fecha'])) ?></small>
              </span>
              <span><?= e(money($expense['monto'], $expense['moneda'])) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <aside class="panel">
    <h2>Total</h2>
    <strong id="reportTotal" style="font-family:var(--display);font-size:2rem"><?= e($moneda) ?> 0</strong>
    <p class="muted">Suma de los gastos marcados.</p>
    <div class="stack" style="margin-top:1rem">
      <button class="btn btn-ghost" type="submit" name="accion" value="guardar">Guardar borrador</button>
      <button class="btn btn-citrus" type="submit" name="accion" value="enviar">Enviar a revisión</button>
      <a href="<?= e(url('informes.php')) ?>">Volver</a>
    </div>
  </aside>
</form>
