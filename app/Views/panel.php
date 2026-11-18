<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($empresa['nombre'] ?? 'Tu empresa') ?></h1>
        <p>Hola, <?= esc($usuario['nombre'] ?? '') ?>. Esta es la cuenta de la empresa.</p>
    </div>
    <a class="boton" href="<?= base_url('gastos/nuevo') ?>">Registrar un gasto</a>
</header>
<div class="mosaico">
    <a class="losa losa-gastos" href="<?= base_url('gastos') ?>">
        <strong>Gastos</strong>
        <span>Comprobantes, topes y estados.</span>
    </a>
    <a class="losa losa-rendiciones" href="<?= base_url('rendiciones') ?>">
        <strong>Rendiciones</strong>
        <span>Junta los gastos legalizados.</span>
    </a>
    <a class="losa losa-fondos" href="<?= base_url('fondos') ?>">
        <strong>Fondos</strong>
        <span>Pide el dinero y sigue el saldo.</span>
    </a>
    <a class="losa losa-informes" href="<?= base_url('informes') ?>">
        <strong>Informes</strong>
        <span>Cartola, rendiciones del mes y asientos.</span>
    </a>
    <a class="losa losa-categorias" href="<?= base_url('categorias') ?>">
        <strong>Categorías</strong>
        <span>Los límites de la política.</span>
    </a>
    <?php if (($usuario['rol'] ?? '') === 'administrador') : ?>
        <a class="losa losa-parametros" href="<?= base_url('parametros') ?>">
            <strong>Parámetros</strong>
            <span>Cuentas, documentos y quién puede cargar.</span>
        </a>
    <?php endif ?>
    <?php if (($usuario['rol'] ?? '') === 'administrador') : ?>
        <a class="losa losa-personas" href="<?= base_url('personas') ?>">
            <strong>Personas</strong>
            <span>Quién entra y quién rinde sin solicitud.</span>
        </a>
    <?php endif ?>
    <a class="losa losa-empresa" href="<?= base_url('empresa') ?>">
        <strong>Empresa</strong>
        <span>País, moneda e identificación.</span>
    </a>
</div>
<?php if ($empresa && $pais) : ?>
    <section class="bloque">
        <dl class="ficha">
            <div><dt>País</dt><dd><?= esc($pais['nombre']) ?></dd></div>
            <div><dt><?= esc($pais['id_fiscal']) ?></dt><dd><?= esc($empresa['id_fiscal']) ?></dd></div>
            <div><dt>Moneda</dt><dd><?= esc($pais['moneda']) ?></dd></div>
            <div><dt>Plan</dt><dd>Free · 4 usuarios · 2 GB</dd></div>
            <div><dt>Correo</dt><dd><?= esc($usuario['email'] ?? '') ?></dd></div>
        </dl>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
