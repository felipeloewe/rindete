<?php if (!empty($flash)): ?>
  <div class="flash flash-<?= e($flash['type'] === 'error' ? 'error' : ($flash['type'] === 'info' ? 'info' : 'ok')) ?>" role="status">
    <?= e($flash['message']) ?>
  </div>
<?php endif; ?>
