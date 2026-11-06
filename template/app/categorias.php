<section class="page-head">
  <div>
    <h1>Categorías</h1>
    <p>Cada concepto de gasto tiene un tope. Si el monto lo pasa, el gasto queda observado para quien aprueba.</p>
  </div>
</section>
<section class="split">
  <div class="cat-grid">
    <?php foreach ($items as $cat): ?>
      <article class="cat-card card">
        <div class="spread">
          <h3><?= e($cat['nombre']) ?></h3>
          <?php if (empty($cat['activa'])): ?><span class="chip">Inactiva</span><?php endif; ?>
        </div>
        <p class="muted"><?= e($cat['detalle']) ?></p>
        <p style="margin:.6rem 0"><strong>Tope <?= e(money($cat['tope'], $moneda)) ?></strong></p>
        <p class="muted"><?= (int) ($conteo[$cat['id']] ?? 0) ?> gastos</p>
        <div class="row" style="margin-top:.8rem">
          <a class="btn btn-ghost btn-small" href="<?= e(url('categorias.php?id=' . urlencode($cat['id']))) ?>">Editar</a>
          <?php if (($conteo[$cat['id']] ?? 0) === 0): ?>
            <form method="post" onsubmit="return confirm('¿Eliminar esta categoría?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
              <button class="btn btn-danger btn-small" type="submit" name="accion" value="eliminar">Eliminar</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <?php if ($edit): ?><input type="hidden" name="id" value="<?= e($edit['id']) ?>"><?php endif; ?>
    <h2><?= $edit ? 'Editar' : 'Nueva categoría' ?></h2>
    <label class="field">Nombre
      <input type="text" name="nombre" required maxlength="80" value="<?= e($edit['nombre'] ?? '') ?>">
    </label>
    <label class="field">Detalle
      <input type="text" name="detalle" maxlength="180" value="<?= e($edit['detalle'] ?? '') ?>">
    </label>
    <label class="field">Tope por gasto (<?= e($moneda) ?>)
      <input type="text" name="tope" inputmode="decimal" required value="<?= e($edit['tope'] ?? '') ?>">
      <small>0 significa sin límite.</small>
    </label>
    <?php if ($edit): ?>
      <label class="field"><span><input type="checkbox" name="activa" value="1" <?= !empty($edit['activa']) ? 'checked' : '' ?>> Activa para nuevos gastos</span></label>
      <button class="btn btn-primary" type="submit" name="accion" value="guardar">Guardar</button>
      <a href="<?= e(url('categorias.php')) ?>">Cancelar</a>
    <?php else: ?>
      <button class="btn btn-primary" type="submit" name="accion" value="crear">Crear categoría</button>
    <?php endif; ?>
  </form>
</section>
