<section class="page-head">
  <div>
    <h1><?= e($report['titulo']) ?></h1>
    <p><?= e(user_name($report['user_id'])) ?> · <?= e($report['desde'] ? fecha_latam($report['desde']) : 'sin inicio') ?> – <?= e($report['hasta'] ? fecha_latam($report['hasta']) : 'sin fin') ?></p>
  </div>
  <span class="chip <?= e(chip_class($report['estado'])) ?>"><?= e(estado_informe_label($report['estado'])) ?></span>
</section>

<section class="split">
  <article class="card">
    <div class="spread">
      <h2><?= e(money($total, $moneda)) ?></h2>
      <span class="muted"><?= count($gastos) ?> gastos</span>
    </div>
    <?php if ($report['nota']): ?>
      <p style="margin:.6rem 0 1rem"><?= e($report['nota']) ?></p>
    <?php endif; ?>
    <div class="list">
      <?php foreach ($gastos as $expense): ?>
        <a class="item" href="<?= e(url('gasto.php?id=' . urlencode($expense['id']))) ?>">
          <b><?= e($expense['concepto']) ?></b>
          <span><?= e(money($expense['monto'], $expense['moneda'])) ?></span>
          <small><?= e($expense['proveedor']) ?> · <?= e(fecha_latam($expense['fecha'])) ?></small>
          <small><?php include ROOT . '/template/partials/alertas.php'; ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  </article>
  <aside class="panel">
    <h2>Decisión</h2>
    <?php if ($report['comentario_revision']): ?>
      <p><strong><?= e(user_name($report['revisor_id'])) ?></strong></p>
      <p class="muted"><?= e($report['comentario_revision']) ?></p>
    <?php elseif ($report['estado'] === 'en_revision'): ?>
      <p class="muted">Quien aprueba ve las mismas alertas y el comprobante de cada gasto.</p>
    <?php else: ?>
      <p class="muted">Esta rendición está <?= e(strtolower(estado_informe_label($report['estado']))) ?>.</p>
    <?php endif; ?>

    <?php if ($puedeDecidir): ?>
      <form method="post" class="stack" style="margin-top:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($report['id']) ?>">
        <label class="field">Observación
          <textarea name="comentario" maxlength="500" placeholder="Opcional si apruebas. Obligatoria si devuelves."></textarea>
        </label>
        <button class="btn btn-citrus" type="submit" name="accion" value="aprobar">Aprobar</button>
        <button class="btn btn-danger" type="submit" name="accion" value="devolver">Devolver</button>
      </form>
    <?php endif; ?>

    <?php if ($puedeReabrir): ?>
      <form method="post" style="margin-top:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($report['id']) ?>">
        <button class="btn btn-primary" type="submit" name="accion" value="reabrir">Reabrir como borrador</button>
      </form>
    <?php endif; ?>
  </aside>
</section>
