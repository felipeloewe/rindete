<?php $flash = consume_flash(); ?>
<section class="auth-brand">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <div class="stack">
    <h1>Entra al flujo de tu empresa.</h1>
    <p>Legaliza gastos, arma rendiciones y aprueba con el contexto fiscal de tu país.</p>
  </div>
  <p>Chile, Colombia, México, El Salvador, Ecuador y Perú.</p>
</section>
<main class="auth-main" id="contenido">
  <div class="auth-wrap">
  <form class="auth-card" method="post" action="<?= e(url('login.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="login">
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <h2>Ingresar</h2>
    <p class="muted">Usa el correo con el que creaste tu empresa.</p>
    <?php include ROOT . '/template/partials/flash.php'; ?>
    <label class="field">Correo
      <input id="correo" type="email" name="email" required maxlength="160" autocomplete="username" inputmode="email" value="<?= e($email ?? '') ?>" data-email>
    </label>
    <label class="field">Contraseña
      <input id="clave" type="password" name="password" required autocomplete="current-password">
    </label>
    <label class="remember">
      <input type="checkbox" name="recordar" value="1" <?= !empty($recordar) ? 'checked' : '' ?>>
      Recordar contraseña
    </label>
    <p class="hint">Mantiene la sesión abierta 30 días en este equipo.</p>
    <button class="btn btn-primary btn-block" type="submit">Entrar</button>
    <div class="spread">
      <a href="<?= e(url('recuperar.php')) ?>">Recuperar contraseña</a>
      <a href="<?= e(url('registro.php')) ?>">Crear empresa</a>
    </div>
  </form>
  </div>
</main>
<?php page_close('js/app.js'); ?>
