<?php
$tags = [];
if (!empty($expense['duplicado'])) {
    $tags[] = ['Duplicado', 'chip-danger'];
}
if (!empty($expense['fuera_politica'])) {
    $tags[] = ['Fuera de tope', 'chip-warn'];
}
if (!empty($expense['sin_comprobante'])) {
    $tags[] = ['Sin comprobante', 'chip-warn'];
}
if (!empty($expense['fiscal_incompleto'])) {
    $tags[] = ['Fiscal incompleto', 'chip-sand'];
}
if (!$tags) {
    echo '<span class="muted">Sin alertas</span>';
}
foreach ($tags as $tag): ?>
  <span class="chip <?= e($tag[1]) ?>"><?= e($tag[0]) ?></span>
<?php endforeach; ?>
