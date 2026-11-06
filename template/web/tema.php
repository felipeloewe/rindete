<main id="contenido">
  <header class="mast">
    <div class="mast-inner">
      <?php render('web/crumbs', ['items' => [
          ['Inicio', ''],
          [$tema['nav'], $tema['slug']],
      ]]); ?>
      <p class="kicker"><i>Producto</i> <?= e($tema['kicker']) ?></p>
      <h1><?= e($tema['h1']) ?></h1>
      <p class="lede"><?= e($tema['lede']) ?></p>
    </div>
  </header>
  <div class="wrap">
    <?php if (!empty($tema['pasos'])): ?>
      <div class="steps steps-page">
        <?php foreach ($tema['pasos'] as $i => $paso): ?>
          <article class="step">
            <em><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></em>
            <h2><?= e($paso['titulo']) ?></h2>
            <p><?= e($paso['texto']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="prose"><?= web_bloques($tema['cuerpo']) ?></div>
    <?php if (!empty($tema['faq'])): ?>
      <h2 class="block-title">Preguntas frecuentes</h2>
      <?php render('web/faq', ['faq' => $tema['faq']]); ?>
    <?php endif; ?>
    <?php if (!empty($tema['enlaces'])): ?>
      <h2 class="block-title">Sigue por aquí</h2>
      <div class="related">
        <?php foreach ($tema['enlaces'] as $enlace): ?>
          <a href="<?= e(web_href($enlace[1])) ?>"><?= e($enlace[0]) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php render('web/cta', [
      'titulo' => 'Arma el primer informe con tu equipo.',
      'texto' => 'Hasta 3 usuarios, 50 documentos al mes y 1 rendición por persona.',
  ]); ?>
</main>
