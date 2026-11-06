<?php
$anio = '2026';
?>
<footer class="site-footer">
  <div class="footer-grid">
    <div class="foot-brand">
      <a class="brand" href="<?= e(url('')) ?>">
        <?php include ROOT . '/template/partials/mark.php'; ?>
        PideTuFactura
      </a>
      <p>Recolecta datos de facturación por correo o con un QR. La factura la emite tu empresa.</p>
    </div>
    <div>
      <strong>Producto</strong>
      <?php foreach (web_temas() as $temaNav): ?>
        <a href="<?= e(web_href($temaNav['slug'])) ?>"><?= e($temaNav['nav']) ?></a>
      <?php endforeach; ?>
    </div>
    <div>
      <strong>Países</strong>
      <?php foreach (web_landings() as $paisNav): ?>
        <a href="<?= e(web_href($paisNav['slug'])) ?>"><?= e($paisNav['nombre']) ?></a>
      <?php endforeach; ?>
    </div>
    <div>
      <strong>Guías</strong>
      <?php foreach (array_slice(web_articulos_ordenados(), 0, 4, true) as $artNav): ?>
        <a href="<?= e(web_href('blog/' . $artNav['slug'])) ?>"><?= e($artNav['titulo']) ?></a>
      <?php endforeach; ?>
      <a href="<?= e(web_href('blog')) ?>">Ver todas</a>
    </div>
  </div>
  <div class="foot-legal">
    <span>© <?= e($anio) ?> PideTuFactura. Las guías ordenan el lenguaje del equipo y no reemplazan a tu contador.</span>
    <?php if (current_user()): ?>
      <a href="<?= e(url('panel.php')) ?>">Ir al panel</a>
    <?php else: ?>
      <a href="<?= e(url('registro.php')) ?>">Crear empresa</a>
    <?php endif; ?>
  </div>
</footer>
<script src="<?= e(url('js/web.js')) ?>"></script>
</body>
</html>
