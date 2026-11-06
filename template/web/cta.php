<?php
$titulo = $titulo ?? 'Empieza con tu empresa.';
$texto = $texto ?? 'Hasta 3 usuarios, 50 documentos al mes y 1 rendición por persona. Te enviamos la bienvenida y tu clave al crear la cuenta.';
?>
<section class="cta">
  <div>
    <h2><?= e($titulo) ?></h2>
    <p><?= e($texto) ?></p>
  </div>
  <?php if (current_user()): ?>
    <a class="btn btn-citrus" href="<?= e(url('panel.php')) ?>">Ir al panel</a>
  <?php else: ?>
    <a class="btn btn-citrus" href="<?= e(url('registro.php')) ?>">Crear cuenta</a>
  <?php endif; ?>
</section>
