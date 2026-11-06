<?php
$monedas = [
    'CLP' => 'pesos chilenos',
    'COP' => 'pesos colombianos',
    'MXN' => 'pesos mexicanos',
    'USD' => 'dólares',
    'PEN' => 'soles',
];
$moneda = $monedas[$fiscal['moneda']] ?? $fiscal['moneda'];
?>
<main id="contenido">
  <header class="mast">
    <div class="mast-inner guide">
      <?php render('web/crumbs', ['items' => [
          ['Inicio', ''],
          [$landing['nombre'], $landing['slug']],
      ]]); ?>
      <p class="kicker"><i><?= e($landing['codigo']) ?></i> Control de gastos</p>
      <h1><?= e($landing['h1']) ?></h1>
      <p class="lede"><?= e($landing['lede']) ?></p>
    </div>
  </header>
  <div class="wrap guide pais-page">
    <h2 class="block-title">Qué cierra la cuenta en <?= e($landing['nombre']) ?></h2>
    <p class="intro">Tres movimientos distintos. Si van en el mismo informe, la caja no cuadra y el reembolso se paga mal.</p>
    <div class="use-grid">
      <?php foreach ($landing['usos'] as $i => $uso): ?>
        <article class="use-card">
          <em><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></em>
          <h3><?= e($uso['titulo']) ?></h3>
          <p><?= e($uso['detalle']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <h2 class="block-title">Así se aprueba en la cuenta</h2>
    <div class="steps steps-page">
      <?php foreach ($landing['pasos'] as $i => $paso): ?>
        <article class="step">
          <em><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></em>
          <h2><?= e($paso['titulo']) ?></h2>
          <p><?= e($paso['texto']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>

    <section class="doc-band">
      <div>
        <h2>El comprobante de <?= e($landing['nombre']) ?> entra con su nombre.</h2>
        <p>La empresa pide <?= e($fiscal['id_fiscal']) ?> y <?= e($fiscal['codigo']) ?>. Los topes de categoría parten en <?= e($moneda) ?>. PideTuFactura no consulta a <?= e($fiscal['autoridad']) ?>: marca el duplicado y el dato fiscal vacío para que quien aprueba lo vea antes de reembolsar o de reponer la caja.</p>
      </div>
      <dl class="doc-facts">
        <div><dt>Autoridad</dt><dd><?= e($fiscal['autoridad']) ?></dd></div>
        <div><dt>Identificación</dt><dd><?= e($fiscal['id_fiscal']) ?></dd></div>
        <div><dt>Código del documento</dt><dd><?= e($fiscal['codigo']) ?></dd></div>
        <div><dt>Moneda de los topes</dt><dd><?= e($moneda) ?></dd></div>
      </dl>
      <ul class="doc-pills">
        <?php foreach ($fiscal['docs'] as $doc): ?>
          <?php if ($doc === 'Otro') continue; ?>
          <li><?= e($doc) ?></li>
        <?php endforeach; ?>
      </ul>
    </section>

    <div class="prose"><?= web_bloques($landing['cuerpo']) ?></div>
    <aside class="note"><p><?= e($landing['nota']) ?></p></aside>

    <h2 class="block-title">Antes de abrir la cuenta en <?= e($landing['nombre']) ?></h2>
    <?php render('web/faq', ['faq' => $landing['faq']]); ?>

    <?php
      $guias = [];
      foreach ($landing['guias'] as $slug) {
          $guia = web_articulo($slug);
          if ($guia) {
              $guias[] = $guia;
          }
      }
    ?>
    <?php if ($guias): ?>
      <h2 class="block-title">Guías de <?= e($landing['nombre']) ?></h2>
      <div class="post-grid">
        <?php foreach ($guias as $art): ?>
          <?php render('web/post_card', ['art' => $art]); ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <h2 class="block-title">El mismo control en otro país</h2>
    <div class="related">
      <?php foreach (web_landings() as $otro): ?>
        <?php if ($otro['slug'] === $landing['slug']) continue; ?>
        <a href="<?= e(web_href($otro['slug'])) ?>"><?= e($otro['nombre']) ?> · <?= e($otro['frase']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php render('web/cta', [
      'titulo' => 'Abre la empresa de ' . $landing['nombre'] . '.',
      'texto' => 'Al crearla eliges el país. El formulario pide ' . $fiscal['id_fiscal'] . ' y ' . $fiscal['codigo'] . ', y los topes quedan en ' . $moneda . '.',
  ]); ?>
</main>
