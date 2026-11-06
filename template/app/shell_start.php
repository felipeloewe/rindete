<button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-label="Abrir menú">Menú</button>
<button class="scrim" type="button" data-scrim aria-label="Cerrar menú"></button>
<aside class="sidebar">
  <a class="brand" href="<?= e(url('panel.php')) ?>">
    <?php include ROOT . '/template/partials/mark.php'; ?>
    PideTuFactura
  </a>
  <div class="company-pill">
    <strong><?= e($company['nombre']) ?></strong>
    <span><?= e($pais['nombre']) ?> · <?= e($pais['autoridad']) ?> · <?= e($pais['moneda']) ?></span>
  </div>
  <?php
    $vistaMenu = (string) ($_GET['vista'] ?? 'gastos');
    if ($vistaMenu !== 'onboarding') {
        $vistaMenu = 'gastos';
    }
    $proceso = 'gastos';
    if ($user['rol'] === 'administrador' && ($active === 'facturas' || ($active === 'rendiciones' && $vistaMenu === 'onboarding'))) {
        $proceso = 'onboarding';
    }
    $enInformes = $active === 'rendiciones';
  ?>
  <nav class="side-nav">
    <?php if ($user['rol'] === 'administrador'): ?>
      <div class="proc-tabs" role="tablist" aria-label="Proceso">
        <a role="tab" class="<?= $proceso === 'onboarding' ? 'is-active' : '' ?>" href="<?= e(url($enInformes ? 'informes.php?vista=onboarding' : 'facturas.php')) ?>" <?= $proceso === 'onboarding' ? 'aria-selected="true"' : '' ?>>
          <span>Onboarding</span>
          <?php if (($facturasPendientes ?? 0) > 0): ?><span class="badge"><?= (int) $facturasPendientes ?></span><?php endif; ?>
        </a>
        <a role="tab" class="<?= $proceso === 'gastos' ? 'is-active' : '' ?>" href="<?= e(url($enInformes ? 'informes.php?vista=gastos' : 'gastos.php')) ?>" <?= $proceso === 'gastos' ? 'aria-selected="true"' : '' ?>>Gastos</a>
      </div>
    <?php endif; ?>
    <?php if ($proceso === 'onboarding'): ?>
      <a class="<?= $active === 'facturas' ? 'is-active' : '' ?>" href="<?= e(url('facturas.php')) ?>" <?= $active === 'facturas' ? 'aria-current="page"' : '' ?>>Bandeja</a>
      <a class="<?= $enInformes ? 'is-active' : '' ?>" href="<?= e(url('informes.php?vista=onboarding')) ?>" <?= $enInformes ? 'aria-current="page"' : '' ?>>Informes</a>
    <?php else: ?>
      <a class="<?= $active === 'gastos' ? 'is-active' : '' ?>" href="<?= e(url('gastos.php')) ?>" <?= $active === 'gastos' ? 'aria-current="page"' : '' ?>>Gastos</a>
      <a class="<?= $enInformes ? 'is-active' : '' ?>" href="<?= e(url('informes.php?vista=gastos')) ?>" <?= $enInformes ? 'aria-current="page"' : '' ?>>
        <span>Informes</span>
        <?php if ($pending > 0 && $user['rol'] !== 'rendidor'): ?>
          <span class="badge"><?= (int) $pending ?></span>
        <?php endif; ?>
      </a>
    <?php endif; ?>
  </nav>
  <div class="side-foot">
    <?php if ($user['rol'] === 'administrador'): ?>
      <?php $configAbierta = in_array($active, ['categorias', 'equipo', 'politicas'], true); ?>
      <details class="nav-config" <?= $configAbierta ? 'open' : '' ?>>
        <summary>Configuración</summary>
        <a class="<?= $active === 'categorias' ? 'is-active' : '' ?>" href="<?= e(url('categorias.php')) ?>" <?= $active === 'categorias' ? 'aria-current="page"' : '' ?>>Categorías</a>
        <a class="<?= $active === 'equipo' ? 'is-active' : '' ?>" href="<?= e(url('usuarios.php')) ?>" <?= $active === 'equipo' ? 'aria-current="page"' : '' ?>>Equipo</a>
        <a class="<?= $active === 'politicas' ? 'is-active' : '' ?>" href="<?= e(url('politicas.php')) ?>" <?= $active === 'politicas' ? 'aria-current="page"' : '' ?>>Políticas</a>
      </details>
    <?php endif; ?>
    <a class="<?= $active === 'perfil' ? 'is-active' : '' ?>" href="<?= e(url('perfil.php')) ?>" <?= $active === 'perfil' ? 'aria-current="page"' : '' ?>>Mi perfil</a>
    <a href="<?= e(url('logout.php')) ?>">Cerrar sesión</a>
  </div>
</aside>
<div class="main">
  <header class="topbar">
    <form class="search" action="<?= e(url('gastos.php')) ?>" method="get">
      <label class="sr-only" for="q" style="position:absolute;left:-999px">Buscar</label>
      <input id="q" type="search" name="q" placeholder="Buscar concepto, proveedor o <?= e($pais['id_fiscal']) ?>">
    </form>
    <details class="user-menu">
      <summary>
        <span class="avatar"><?= e(function_exists('mb_substr') ? mb_substr($user['nombre'], 0, 1) : substr($user['nombre'], 0, 1)) ?></span>
        <span class="who"><?= e($user['nombre']) ?><br><small class="muted"><?= e(rol_label($user['rol'])) ?></small></span>
      </summary>
      <div class="menu-pop">
        <a href="<?= e(url('perfil.php')) ?>">Mi perfil</a>
        <a href="<?= e(url('logout.php')) ?>">Cerrar sesión</a>
      </div>
    </details>
  </header>
  <div class="content" id="contenido">
    <?php include ROOT . '/template/partials/flash.php'; ?>
