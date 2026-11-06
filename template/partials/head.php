<?php
$seo = $seo ?? [];
$description = $seo['description'] ?? 'PideTuFactura legaliza, aprueba y controla gastos para empresas de Chile, Colombia, México, El Salvador, Ecuador y Perú.';
$robots = $seo['robots'] ?? 'noindex';
$lang = $seo['lang'] ?? 'es';
$canonical = $seo['canonical'] ?? '';
$ogType = $seo['type'] ?? 'website';
$locale = $seo['locale'] ?? 'es_ES';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($description) ?>">
  <meta name="robots" content="<?= e($robots) ?>">
  <title><?= e($title) ?> · PideTuFactura</title>
  <?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?> · PideTuFactura">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:type" content="<?= e($ogType) ?>">
    <meta property="og:locale" content="<?= e($locale) ?>">
    <meta property="og:site_name" content="PideTuFactura">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= e($title) ?> · PideTuFactura">
    <meta name="twitter:description" content="<?= e($description) ?>">
  <?php endif; ?>
  <link rel="icon" href="<?= e(url('favicon.svg')) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,560;9..144,640&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= e(url($style)) ?>">
  <?php endforeach; ?>
  <?php if (!empty($seo['jsonld'])): ?>
    <script type="application/ld+json"><?= json_encode($seo['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?></script>
  <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<script>document.documentElement.classList.add('js')</script>
<a class="skip" href="#contenido">Saltar al contenido</a>
