<section class="page-head">
  <div>
    <h1><?= e($expense['concepto']) ?></h1>
    <p><?= e($expense['proveedor']) ?> · <?= e(fecha_latam($expense['fecha'])) ?> · <?= e(user_name($expense['user_id'])) ?></p>
  </div>
  <div class="row">
    <span class="chip <?= e(chip_class($expense['estado'])) ?>"><?= e(estado_gasto_label($expense['estado'])) ?></span>
    <?php if ($puedeEditar): ?>
      <a class="btn btn-primary btn-small" href="<?= e(url('gasto.php?id=' . urlencode($expense['id']) . '&editar=1')) ?>">Editar</a>
    <?php endif; ?>
  </div>
</section>

<section class="detail">
  <article class="panel">
    <?php if ($expense['foto'] && preg_match('/\.pdf$/i', $expense['foto'])): ?>
      <a class="btn btn-ghost btn-block" href="<?= e(url($expense['foto'])) ?>" target="_blank" rel="noopener">Abrir PDF</a>
    <?php elseif ($expense['foto']): ?>
      <img class="receipt" src="<?= e(url($expense['foto'])) ?>" alt="Comprobante de <?= e($expense['proveedor']) ?>">
    <?php else: ?>
      <div class="empty"><h2>Sin foto</h2><p class="muted">Este gasto no tiene comprobante adjunto.</p></div>
    <?php endif; ?>
  </article>
  <article class="card">
    <div class="spread">
      <h2><?= e(money($expense['monto'], $expense['moneda'])) ?></h2>
      <div class="row"><?php include ROOT . '/template/partials/alertas.php'; ?></div>
    </div>
    <div class="meta">
      <div><span>Categoría</span><strong><?= e(category_name($expense['category_id'])) ?></strong></div>
      <div><span>Fecha</span><strong><?= e(fecha_latam($expense['fecha'])) ?></strong></div>
      <div><span>Tipo</span><strong><?= e($expense['tipo_doc'] ?: '—') ?></strong></div>
      <div><span><?= e($pais['id_proveedor']) ?></span><strong><?= e($expense['id_fiscal'] ?: '—') ?></strong></div>
      <div><span>Número</span><strong><?= e($expense['num_doc'] ?: '—') ?></strong></div>
      <div><span><?= e($pais['codigo']) ?></span><strong><?= e($expense['codigo_fiscal'] ?: '—') ?></strong></div>
    </div>
    <div class="stack" style="margin-top:1rem">
      <span class="muted">Nota</span>
      <p><?= e($expense['nota'] ?: 'Sin nota.') ?></p>
    </div>
    <?php if ($expense['report_id']): ?>
      <p style="margin-top:1rem"><a href="<?= e(url('informe.php?id=' . urlencode($expense['report_id']))) ?>">Ver rendición</a></p>
    <?php elseif ($puedeEditar): ?>
      <p style="margin-top:1rem"><a class="btn btn-primary btn-small" href="<?= e(url('informe.php?nuevo=1')) ?>">Incluir en una rendición</a></p>
    <?php endif; ?>
    <?php if ($puedeEditar): ?>
      <form method="post" style="margin-top:1rem" onsubmit="return confirm('¿Eliminar este gasto?');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($expense['id']) ?>">
        <input type="hidden" name="accion" value="eliminar">
        <button class="btn btn-danger btn-small" type="submit">Eliminar</button>
      </form>
    <?php endif; ?>
  </article>
</section>
