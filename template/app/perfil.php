<section class="page-head">
  <div>
    <h1>Mi perfil</h1>
    <p><?= e(rol_label($user['rol'])) ?> en <?= e($company['nombre']) ?>.</p>
  </div>
</section>
<form class="card" method="post" style="display:grid;gap:.9rem;max-width:560px">
  <?= csrf_field() ?>
  <label class="field">Nombre
    <input type="text" name="nombre" required maxlength="80" value="<?= e($user['nombre']) ?>">
  </label>
  <label class="field">Correo
    <input type="email" value="<?= e($user['email']) ?>" disabled>
    <small>El correo es tu acceso. Para cambiarlo, pide ayuda a quien administra.</small>
  </label>
  <h2>Cambiar contraseña</h2>
  <label class="field">Contraseña actual
    <input type="password" name="actual" autocomplete="current-password">
  </label>
  <div class="grid-2">
    <label class="field">Nueva
      <input type="password" name="password" minlength="8" autocomplete="new-password">
    </label>
    <label class="field">Confirmar
      <input type="password" name="password2" minlength="8" autocomplete="new-password">
    </label>
  </div>
  <button class="btn btn-primary" type="submit">Guardar perfil</button>
</form>
