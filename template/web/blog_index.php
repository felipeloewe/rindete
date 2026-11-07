<main id="contenido">
  <header class="mast">
    <div class="mast-inner">
      <?php render('web/crumbs', ['items' => [
          ['Inicio', ''],
          ['Guías', 'blog'],
      ]]); ?>
      <p class="kicker"><i>Blog</i> Vocabulario y operación</p>
      <h1>Guías para equipos de gastos en Latam.</h1>
      <p class="lede">Cómo se dice rendición, legalización, caja menor, caja chica y reembolso, y qué comprobante pide cada país.</p>
    </div>
  </header>
  <div class="wrap">
    <div class="post-grid posts-all">
      <?php foreach ($articulos as $art): ?>
        <?php render('web/post_card', ['art' => $art]); ?>
      <?php endforeach; ?>
    </div>
  </div>
  <?php render('web/cta'); ?>
</main>
