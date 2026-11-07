<section class="page-head">
  <div>
    <h1>Hola, <?= e($saludo) ?>.</h1>
    <p><?= e($company['nombre']) ?> opera con reglas de <?= e($paisInfo['nombre']) ?>. Plan gratis: <?= (int) $planUsuarios ?> de <?= (int) $planUsuariosMax ?> usuarios, <?= (int) $planDocs ?> de <?= (int) $planDocsMax ?> documentos en <?= e($mesNombre) ?> y <?= (int) $planRendiciones ?> rendición por persona.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('gasto.php?nuevo=1')) ?>">Nuevo gasto</a>
</section>

<?php if ($sinGastos): ?>
  <section class="card" style="margin-bottom:1rem">
    <h2>Tu empresa ya tiene el esqueleto.</h2>
    <p class="muted">Creamos categorías con topes en <?= e($paisInfo['moneda']) ?>. Falta el primer comprobante.</p>
    <div class="steps" style="margin-top:1rem">
      <?php if ($user['rol'] === 'administrador'): ?>
        <article class="step"><em>01</em><h3>Revisa categorías</h3><p>Ajusta los topes a la política real de la empresa.</p><a href="<?= e(url('categorias.php')) ?>">Ver categorías</a></article>
        <article class="step"><em>02</em><h3>Invita al equipo</h3><p>Suma a quien rinde y a quien aprueba.</p><a href="<?= e(url('usuarios.php')) ?>">Crear usuarios</a></article>
      <?php endif; ?>
      <article class="step"><em>03</em><h3>Carga un gasto</h3><p>Concepto, foto, nota y datos de <?= e($paisInfo['autoridad']) ?>.</p><a href="<?= e(url('gasto.php?nuevo=1')) ?>">Crear gasto</a></article>
    </div>
  </section>
<?php endif; ?>

<?php if ($user['rol'] === 'administrador'): ?>
  <section class="card onb-home">
    <div class="spread">
      <div>
        <h2>Onboarding de clientes</h2>
        <p class="muted"><?php if ($onboardingNuevas > 0): ?><?= (int) $onboardingNuevas ?> <?= $onboardingNuevas === 1 ? 'ficha nueva' : 'fichas nuevas' ?> en la bandeja.<?php else: ?>Envía el formulario por correo o imprime el QR del mesón.<?php endif; ?> Arriba cambias entre Onboarding y Gastos. Informes sigue a la pestaña que esté activa.</p>
      </div>
      <a class="btn btn-primary" href="<?= e(url('facturas.php')) ?>">Abrir onboarding</a>
    </div>
  </section>
<?php endif; ?>

<section class="kpis">
  <article class="kpi"><span>Gastos de <?= e($mesNombre) ?></span><strong><?= (int) $totalMes ?></strong></article>
  <article class="kpi"><span>Monto del mes</span><strong><?= e(money($montoMes, $moneda)) ?></strong></article>
  <article class="kpi"><span>Con alertas</span><strong><?= count($alertas) ?></strong></article>
  <article class="kpi"><span>Rendiciones en revisión</span><strong><?= count($porAprobar) ?></strong></article>
</section>

<section class="split">
  <article class="card">
    <div class="spread"><h2>Movimiento reciente</h2><a href="<?= e(url('gastos.php')) ?>">Ver todos</a></div>
    <?php if (!$recientes): ?>
      <div class="empty"><h2>Aún no hay gastos</h2><p class="muted">El primero puede ser un almuerzo, un taxi o un hotel.</p></div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($recientes as $expense): ?>
          <a class="item" href="<?= e(url('gasto.php?id=' . urlencode($expense['id']))) ?>">
            <b><?= e($expense['concepto']) ?></b>
            <span class="chip <?= e(chip_class($expense['estado'])) ?>"><?= e(estado_gasto_label($expense['estado'])) ?></span>
            <small><?= e($expense['proveedor']) ?> · <?= e(fecha_latam($expense['fecha'])) ?></small>
            <small><?= e(money($expense['monto'], $expense['moneda'])) ?></small>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </article>
  <aside class="panel">
    <h2>Para resolver</h2>
    <?php if (!$alertas && !$porAprobar): ?>
      <p class="muted">No hay duplicados ni rendiciones esperando. Buen momento para cerrar el periodo.</p>
    <?php endif; ?>
    <div class="list">
      <?php foreach (array_slice($porAprobar, 0, 3) as $report): ?>
        <a class="item" href="<?= e(url('informe.php?id=' . urlencode($report['id']))) ?>">
          <b><?= e($report['titulo']) ?></b>
          <span class="chip chip-info">Revisar</span>
          <small><?= e(user_name($report['user_id'])) ?></small>
          <small><?php [$t, $c] = report_total($report); echo e(money($t, $c)); ?></small>
        </a>
      <?php endforeach; ?>
      <?php foreach (array_slice($alertas, 0, 4) as $expense): ?>
        <a class="item" href="<?= e(url('gasto.php?id=' . urlencode($expense['id']))) ?>">
          <b><?= e($expense['concepto']) ?></b>
          <span class="chip chip-warn">Alerta</span>
          <small>
            <?php
            $bits = [];
            if ($expense['duplicado']) $bits[] = 'Duplicado';
            if ($expense['fuera_politica']) $bits[] = 'Tope';
            if ($expense['sin_comprobante']) $bits[] = 'Sin foto';
            echo e(implode(' · ', $bits));
            ?>
          </small>
          <small><?= e(money($expense['monto'], $expense['moneda'])) ?></small>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
</section>
