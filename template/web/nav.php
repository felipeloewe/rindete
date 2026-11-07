<?php
$logueado = (bool) current_user();
$temas = web_temas();
$paisesNav = web_landings();
?>
<header class="nav">
  <a class="brand" href="<?= e(url('')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <button class="menu-btn" type="button" data-menu aria-expanded="false" aria-controls="menu">Menú</button>
  <nav class="nav-links" id="menu" data-nav aria-label="Secciones">
    <details class="nav-drop">
      <summary>Producto</summary>
      <div class="nav-panel">
        <?php foreach ($temas as $temaNav): ?>
          <a href="<?= e(web_href($temaNav['slug'])) ?>" <?= web_actual($temaNav['slug']) ? 'aria-current="page"' : '' ?>>
            <?= e($temaNav['nav']) ?>
            <small><?= e($temaNav['kicker']) ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </details>
    <details class="nav-drop">
      <summary>Países</summary>
      <div class="nav-panel">
        <?php foreach ($paisesNav as $paisNav): ?>
          <a href="<?= e(web_href($paisNav['slug'])) ?>" <?= web_actual($paisNav['slug']) ? 'aria-current="page"' : '' ?>>
            <?= e($paisNav['nombre']) ?>
            <small><?= e($paisNav['frase']) ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    </details>
    <a href="<?= e(web_href('blog')) ?>" <?= web_actual('blog') ? 'aria-current="page"' : '' ?>>Guías</a>
    <div class="nav-cta">
      <?php if ($logueado): ?>
        <a class="btn btn-primary btn-small" href="<?= e(url('panel.php')) ?>">Ir al panel</a>
      <?php else: ?>
        <a class="btn btn-ghost btn-small" href="<?= e(url('login.php')) ?>">Ingresar</a>
        <a class="btn btn-primary btn-small" href="<?= e(url('registro.php')) ?>">Crear empresa</a>
      <?php endif; ?>
    </div>
  </nav>
</header>
