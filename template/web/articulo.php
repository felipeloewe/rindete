<main id="contenido">
  <header class="mast">
    <div class="mast-inner guide">
      <?php render('web/crumbs', ['items' => [
          ['Inicio', ''],
          ['Guías', 'blog'],
          [$art['titulo'], 'blog/' . $art['slug']],
      ]]); ?>
      <p class="kicker"><i>Guía</i> <time datetime="<?= e($art['fecha']) ?>"><?= e(web_fecha($art['fecha'])) ?></time></p>
      <h1><?= e($art['titulo']) ?></h1>
      <p class="lede"><?= e($art['extracto']) ?></p>
    </div>
  </header>
  <div class="wrap guide">
    <article class="prose"><?= web_bloques($art['cuerpo']) ?></article>
    <p class="byline">Equipo PideTuFactura</p>
  </div>
  <section class="band-white">
    <div class="wrap guide">
      <h2 class="block-title">Sigue con otra guía</h2>
      <div class="post-grid">
        <?php $otras = 0; ?>
        <?php foreach (web_articulos_ordenados() as $otra): ?>
          <?php if ($otra['slug'] === $art['slug'] || $otras >= 3) continue; ?>
          <?php $otras++; ?>
          <?php render('web/post_card', ['art' => $otra]); ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php render('web/cta', [
      'titulo' => 'Lleva este lenguaje a la cuenta.',
      'texto' => 'Crea la empresa, elige el país y carga el primer comprobante con sus datos fiscales.',
  ]); ?>
</main>
