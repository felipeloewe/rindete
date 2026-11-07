<div class="faq">
  <?php foreach ($faq as $item): ?>
    <details>
      <summary><?= e($item['q']) ?></summary>
      <p><?= e($item['a']) ?></p>
    </details>
  <?php endforeach; ?>
</div>
