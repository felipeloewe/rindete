<?php
$flash = consume_flash();
$paises = paises();
$paisCodigo = $old['pais'] ?? 'CO';
$documento = pais($paisCodigo)['id_fiscal'];
?>
<section class="auth-brand">
  <a class="brand" href="<?= e(url('index.php')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <div class="stack">
    <h1>Abre la empresa en minutos.</h1>
    <p>Elige el país y el sistema nace con categorías, topes y el vocabulario fiscal correcto.</p>
  </div>
  <p>Después invitas a quien rinde y a quien aprueba.</p>
</section>
<main class="auth-main" id="contenido">
  <form class="auth-card" method="post" data-registro>
    <?= csrf_field() ?>
    <h2>Crear cuenta</h2>
    <?php include ROOT . '/template/partials/flash.php'; ?>
    <label class="field">País
      <select name="pais" required data-country>
        <?php foreach ($paises as $code => $pais): ?>
          <option value="<?= e($code) ?>" data-id="<?= e($pais['id_fiscal']) ?>" data-ejemplo="<?= e($pais['ejemplo']) ?>" data-hint="<?= e($pais['autoridad'] . ' · ' . $pais['id_fiscal'] . ' · ' . $pais['moneda']) ?>" <?= $paisCodigo === $code ? 'selected' : '' ?>><?= e($pais['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <small data-country-hint></small>
    </label>
    <div class="grid-2">
      <label class="field">Empresa
        <input type="text" name="empresa" required maxlength="120" value="<?= e($old['empresa'] ?? '') ?>">
      </label>
      <label class="field"><span data-fiscal-label><?= e($documento) ?></span>
        <input type="text" name="id_fiscal" required maxlength="40" value="<?= e($old['id_fiscal'] ?? '') ?>" placeholder="<?= e(pais($paisCodigo)['ejemplo']) ?>" autocomplete="off" spellcheck="false" data-fiscal-input>
        <small data-fiscal-hint>Ejemplo: <?= e(pais($paisCodigo)['ejemplo']) ?></small>
      </label>
    </div>
    <label class="field">Tu nombre
      <input type="text" name="nombre" required maxlength="80" value="<?= e($old['nombre'] ?? '') ?>">
    </label>
    <label class="field">Correo
      <input id="correo" type="email" name="email" required maxlength="160" autocomplete="email" inputmode="email" value="<?= e($old['email'] ?? '') ?>" data-email>
    </label>
    <label class="field">Contraseña
      <input type="password" name="password" required minlength="8" autocomplete="new-password">
      <small>Mínimo 8 caracteres. Serás quien administra la empresa.</small>
    </label>
    <p class="hint">Plan gratis: hasta 3 usuarios, 50 documentos al mes y 1 rendición por persona. Te enviamos la bienvenida y tu clave por correo.</p>
    <button class="btn btn-primary btn-block" type="submit">Crear empresa</button>
    <a href="<?= e(url('login.php')) ?>">Ya tengo cuenta</a>
  </form>
</main>
<?php page_close('js/app.js'); ?>
