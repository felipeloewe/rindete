<div class="page-tabs" role="tablist" aria-label="Informe">
  <?php if ($esAdmin): ?>
    <a role="tab" href="<?= e(url('informes.php?vista=onboarding')) ?>" class="<?= $vista === 'onboarding' ? 'is-active' : '' ?>" <?= $vista === 'onboarding' ? 'aria-selected="true"' : '' ?>>Onboarding</a>
  <?php endif; ?>
  <a role="tab" href="<?= e(url('informes.php?vista=gastos')) ?>" class="<?= $vista === 'gastos' ? 'is-active' : '' ?>" <?= $vista === 'gastos' ? 'aria-selected="true"' : '' ?>>Gastos</a>
</div>

<?php if ($vista === 'onboarding'): ?>
  <section class="page-head">
    <div>
      <h1>Informes</h1>
      <p>Las fichas que llegaron del formulario. La bandeja sigue sirviendo para archivar y pedir datos nuevos.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('facturas.php')) ?>">Abrir bandeja</a>
  </section>
  <form class="filters" method="get">
    <input type="hidden" name="vista" value="onboarding">
    <select name="estado">
      <option value="">Todas</option>
      <option value="nueva" <?= $estado === 'nueva' ? 'selected' : '' ?>>Nuevas</option>
      <option value="archivada" <?= $estado === 'archivada' ? 'selected' : '' ?>>Archivadas</option>
    </select>
    <button class="btn btn-ghost" type="submit">Filtrar</button>
  </form>
  <?php if (!$solicitudes): ?>
    <div class="empty">
      <h2>Todavía no hay fichas</h2>
      <p class="muted">Cuando un cliente complete el onboarding, el informe aparece aquí.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Cliente</th><th>Contacto</th><th>Fecha</th><th>Número</th><th>Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($solicitudes as $sol): ?>
            <tr>
              <?php $infoPais = paises()[$sol['pais_cliente'] ?? ''] ?? null; ?>
              <td data-label="Cliente"><strong><?= e($sol['razon']) ?></strong><?php if (($sol['nota'] ?? '') !== 'archivo'): ?><div class="muted"><?= e($infoPais['nombre'] ?? '') ?><?= $infoPais ? ' · ' : '' ?><?= e($sol['id_fiscal']) ?></div><?php endif; ?></td>
              <td data-label="Contacto"><?= e(trim(($sol['comercial_nombre'] ?? '') . ' · ' . ((($sol['comercial_email'] ?? '') !== '') ? $sol['comercial_email'] : ($sol['email'] ?? '')), ' ·')) ?></td>
              <td data-label="Fecha"><?= e(date('d/m/Y H:i', strtotime($sol['creado']))) ?></td>
              <td data-label="Número"><?= e($sol['folio'] !== '' ? $sol['folio'] : '—') ?></td>
              <td data-label="Estado"><span class="chip <?= factura_es_abierta($sol['estado']) ? 'chip-info' : '' ?>"><?= e(factura_estado_label($sol['estado'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php else: ?>
  <section class="page-head">
    <div>
      <h1>Informes</h1>
      <p>Agrupa gastos de un viaje o un periodo, cuenta el contexto y envíalos a quien aprueba.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('informe.php?nuevo=1')) ?>">Nueva rendición</a>
  </section>
  <form class="filters" method="get">
    <input type="hidden" name="vista" value="gastos">
    <select name="estado">
      <option value="">Todos los estados</option>
      <?php foreach (['borrador', 'en_revision', 'aprobado', 'rechazado'] as $key): ?>
        <option value="<?= e($key) ?>" <?= $estado === $key ? 'selected' : '' ?>><?= e(estado_informe_label($key)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-ghost" type="submit">Filtrar</button>
  </form>
  <?php if (!$items): ?>
    <div class="empty">
      <h2>Todavía no hay rendiciones</h2>
      <p class="muted">Cuando tengas gastos legalizados, ármalas aquí.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Rendición</th><th>Persona</th><th>Periodo</th><th>Total</th><th>Estado</th></tr>
        </thead>
        <tbody>
          <?php foreach ($items as $report): ?>
            <?php [$total, $moneda] = report_total($report); ?>
            <tr>
              <td data-label="Rendición"><a href="<?= e(url('informe.php?id=' . urlencode($report['id']))) ?>"><?= e($report['titulo']) ?></a><div class="muted"><?= count($report['expense_ids']) ?> gastos</div></td>
              <td data-label="Persona"><?= e(user_name($report['user_id'])) ?></td>
              <td data-label="Periodo"><?= e(($report['desde'] ? fecha_latam($report['desde']) : '—') . ' – ' . ($report['hasta'] ? fecha_latam($report['hasta']) : '—')) ?></td>
              <td data-label="Total"><?= e(money($total, $moneda)) ?></td>
              <td data-label="Estado"><span class="chip <?= e(chip_class($report['estado'])) ?>"><?= e(estado_informe_label($report['estado'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
<?php endif; ?>
