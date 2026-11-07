<?php $flash = consume_flash(); ?>
<section class="auth-brand">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <div class="stack">
    <h1>Elige una contraseña nueva.</h1>
    <p>Al guardarla, el enlace queda invalidado.</p>
  </div>
</section>
<main class="auth-main" id="contenido">
  <form class="auth-card" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <h2>Nueva clave</h2>
    <?php include ROOT . '/template/partials/flash.php'; ?>
    <label class="field">Contraseña
      <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </label>
    <label class="field">Confirmar
      <input type="password" name="password2" required minlength="8" autocomplete="new-password">
    </label>
    <button class="btn btn-primary btn-block" type="submit">Guardar contraseña</button>
  </form>
</main>
<?php page_close('js/app.js'); ?>
