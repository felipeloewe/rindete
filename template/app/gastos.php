<section class="page-head">
  <div>
    <h1>Gastos</h1>
    <p>Cada gasto lleva concepto, categoría, nota y el comprobante. Las alertas aparecen antes de armar la rendición.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('gasto.php?nuevo=1')) ?>">Nuevo gasto</a>
</section>

<form class="filters" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Concepto, proveedor o documento">
  <select name="estado">
    <option value="">Todos los estados</option>
    <?php foreach (['observado', 'legalizado', 'en_rendicion', 'aprobado', 'devuelto'] as $key): ?>
      <option value="<?= e($key) ?>" <?= $estado === $key ? 'selected' : '' ?>><?= e(estado_gasto_label($key)) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="categoria">
    <option value="">Todas las categorías</option>
    <?php foreach ($categorias as $cat): ?>
      <option value="<?= e($cat['id']) ?>" <?= $categoria === $cat['id'] ? 'selected' : '' ?>><?= e($cat['nombre']) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn btn-ghost" type="submit">Filtrar</button>
</form>

<?php if (!$items): ?>
  <div class="empty">
    <h2>Nada por aquí</h2>
    <p class="muted">Prueba otro filtro o registra un gasto con foto y una nota corta para quien aprueba.</p>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="data">
      <thead>
        <tr>
          <th>Gasto</th>
          <th>Categoría</th>
          <th>Fecha</th>
          <th>Monto</th>
          <th>Estado</th>
          <th>Alertas</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $expense): ?>
          <tr>
            <td data-label="Gasto">
              <a href="<?= e(url('gasto.php?id=' . urlencode($expense['id']))) ?>"><?= e($expense['concepto']) ?></a>
              <div class="muted"><?= e($expense['proveedor']) ?></div>
            </td>
            <td data-label="Categoría"><?= e(category_name($expense['category_id'])) ?></td>
            <td data-label="Fecha"><?= e(fecha_latam($expense['fecha'])) ?></td>
            <td data-label="Monto"><?= e(money($expense['monto'], $expense['moneda'])) ?></td>
            <td data-label="Estado"><span class="chip <?= e(chip_class($expense['estado'])) ?>"><?= e(estado_gasto_label($expense['estado'])) ?></span></td>
            <td data-label="Alertas">
              <?php include ROOT . '/template/partials/alertas.php'; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
