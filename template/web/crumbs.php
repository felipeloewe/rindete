<nav class="crumbs" aria-label="Miga de pan">
  <?php foreach ($items as $i => $item): ?>
    <?php if ($i > 0): ?><span aria-hidden="true">/</span><?php endif; ?>
    <?php if ($i === count($items) - 1): ?>
      <span><?= e($item[0]) ?></span>
    <?php else: ?>
      <a href="<?= e(web_href($item[1])) ?>"><?= e($item[0]) ?></a>
    <?php endif; ?>
  <?php endforeach; ?>
</nav>
