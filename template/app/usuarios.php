<section class="page-head">
  <div>
    <h1>Equipo</h1>
    <p>Plan gratis: <?= (int) $cupo ?> de <?= (int) $limite ?> usuarios. Quien entra recibe la bienvenida y su clave por correo.</p>
  </div>
</section>
<section class="split">
  <div class="people">
    <?php foreach ($items as $member): ?>
      <form class="person" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= e($member['id']) ?>">
        <div>
          <strong><?= e($member['nombre']) ?></strong>
          <div class="muted"><?= e($member['email']) ?></div>
        </div>
        <select name="rol" <?= $member['id'] === $actorId ? 'disabled' : '' ?>>
          <?php foreach (['administrador' => 'Administra', 'aprobador' => 'Aprueba', 'rendidor' => 'Rinde'] as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $member['rol'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <?php if ($member['id'] === $actorId): ?>
          <input type="hidden" name="rol" value="administrador">
          <input type="hidden" name="activo" value="1">
          <span class="chip chip-ok">Tú</span>
        <?php else: ?>
          <div class="row">
            <label><input type="checkbox" name="activo" value="1" <?= !empty($member['activo']) ? 'checked' : '' ?>> Activo</label>
            <button class="btn btn-ghost btn-small" type="submit" name="accion" value="guardar">Guardar</button>
          </div>
        <?php endif; ?>
      </form>
    <?php endforeach; ?>
  </div>
  <?php if ($cupo >= $limite): ?>
    <aside class="panel">
      <h2>Cupo completo</h2>
      <p class="muted">El plan gratis admite hasta <?= (int) $limite ?> usuarios.</p>
    </aside>
  <?php else: ?>
  <form class="panel" method="post">
    <?= csrf_field() ?>
    <h2>Nueva persona</h2>
    <label class="field">Nombre
      <input type="text" name="nombre" required maxlength="80">
    </label>
    <label class="field">Correo
      <input type="email" name="email" required maxlength="160">
    </label>
    <label class="field">Perfil
      <select name="rol">
        <option value="rendidor">Rinde gastos</option>
        <option value="aprobador">Aprueba rendiciones</option>
        <option value="administrador">Administra la empresa</option>
      </select>
    </label>
    <label class="field">Clave temporal
      <input id="clave" type="text" name="password" required minlength="8">
      <button class="btn btn-ghost btn-small" type="button" data-generate>Generar</button>
    </label>
    <button class="btn btn-primary" type="submit" name="accion" value="crear">Crear usuario</button>
  </form>
  <?php endif; ?>
</section>
