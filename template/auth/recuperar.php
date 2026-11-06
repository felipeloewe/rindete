<?php $flash = consume_flash(); ?>
<section class="auth-brand">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <div class="stack">
    <h1>Recupera el acceso sin tickets.</h1>
    <p>Te escribimos desde no-reply@pidetufactura.com con un enlace para elegir una clave nueva.</p>
  </div>
</section>
<main class="auth-main" id="contenido">
  <form class="auth-card" method="post">
    <?= csrf_field() ?>
    <h2>Recuperar</h2>
    <?php include ROOT . '/template/partials/flash.php'; ?>
    <label class="field">Correo
      <input type="email" name="email" required value="<?= e($email ?? '') ?>">
    </label>
    <button class="btn btn-primary btn-block" type="submit">Enviar enlace</button>
    <a href="<?= e(url('login.php')) ?>">Volver a ingresar</a>
  </form>
</main>
<?php page_close('js/app.js'); ?>
