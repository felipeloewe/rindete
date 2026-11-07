<main id="contenido">
  <header class="page-hero">
    <h1>Esa página no está.</h1>
    <p class="lede">Puedes volver al inicio, leer las guías o entrar al país con el que trabajas.</p>
  </header>
  <div class="wrap">
    <div class="related">
      <a href="<?= e(url('')) ?>">Inicio</a>
      <a href="<?= e(web_href('blog')) ?>">Guías</a>
      <?php foreach (web_landings() as $paisNav): ?>
        <a href="<?= e(web_href($paisNav['slug'])) ?>"><?= e($paisNav['nombre']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</main>
