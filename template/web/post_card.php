<a class="post-card" href="<?= e(web_href('blog/' . $art['slug'])) ?>">
  <time datetime="<?= e($art['fecha']) ?>"><?= e(web_fecha($art['fecha'])) ?></time>
  <strong><?= e($art['titulo']) ?></strong>
  <span><?= e($art['extracto']) ?></span>
</a>
