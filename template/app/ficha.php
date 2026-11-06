<article class="ficha">
  <div>
    <div class="spread">
      <strong><?= e($sol['razon']) ?></strong>
      <span class="chip <?= $abierta ? 'chip-info' : '' ?>"><?= e(factura_estado_label($sol['estado'])) ?></span>
    </div>
    <?php $paisCliente = paises()[$sol['pais_cliente'] ?? ''] ?? $pais; ?>
    <?php if (($sol['nota'] ?? '') === 'archivo'): ?>
      <div class="muted">Envió el documento fiscal · <?= e(date('d/m/Y H:i', strtotime($sol['creado']))) ?><?= !empty($sol['folio']) ? ' · ' . $sol['folio'] : '' ?></div>
    <?php else: ?>
      <div class="muted"><?= e($paisCliente['nombre']) ?> · <?= e($paisCliente['id_fiscal']) ?> <?= e($sol['id_fiscal']) ?> · <?= e(date('d/m/Y H:i', strtotime($sol['creado']))) ?><?= !empty($sol['folio']) ? ' · ' . $sol['folio'] : '' ?></div>
    <?php endif; ?>
    <?php if (($sol['direccion'] ?? '') !== '' || ($sol['ciudad'] ?? '') !== ''): ?>
      <div class="muted"><?= e(trim(($sol['direccion'] ?? '') . ', ' . ($sol['ciudad'] ?? ''), ', ')) ?></div>
    <?php endif; ?>
    <?php if ($sol['email'] !== '' || $sol['telefono'] !== ''): ?>
      <div class="muted"><?= e(trim($sol['email'] . ' · ' . $sol['telefono'], ' ·')) ?></div>
    <?php endif; ?>
    <?php if (($sol['comercial_nombre'] ?? '') !== '' || ($sol['comercial_email'] ?? '') !== ''): ?>
      <div class="muted">Comercial: <?= e(trim(($sol['comercial_nombre'] ?? '') . ' · ' . ($sol['comercial_email'] ?? ''), ' ·')) ?></div>
    <?php endif; ?>
    <?php if (($sol['financiero_nombre'] ?? '') !== '' || ($sol['financiero_email'] ?? '') !== ''): ?>
      <div class="muted">Financiero: <?= e(trim(($sol['financiero_nombre'] ?? '') . ' · ' . ($sol['financiero_email'] ?? ''), ' ·')) ?></div>
    <?php endif; ?>
    <?php if ($sol['contacto'] !== ''): ?>
      <div class="muted">Quién pide: <?= e($sol['contacto']) ?></div>
    <?php endif; ?>
    <?php if ($archivos): ?>
      <div class="ficha-files">
        <?php foreach ($archivos as $archivo): ?>
          <a href="<?= e(url($archivo['path'])) ?>"><?= e($archivo['nombre']) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if ($abierta): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="archivar">
      <input type="hidden" name="id" value="<?= e($sol['id']) ?>">
      <button class="btn btn-ghost btn-small" type="submit">Archivar</button>
    </form>
  <?php endif; ?>
</article>
