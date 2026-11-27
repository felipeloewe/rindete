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
<section class="caja">
    <div class="caja-cabeza">
        <h2>Acciones pendientes</h2>
        <span>Lo que hoy espera una revisión.</span>
    </div>
    <div class="pendientes">
        <a href="<?= base_url('rendiciones') ?>"><strong><?= count($tablero['abiertas']) ?></strong><span>Rendiciones abiertas</span></a>
        <a href="<?= base_url('rendiciones') ?>"><strong><?= count($tablero['proceso']) ?></strong><span>Rendiciones en proceso</span></a>
        <a href="<?= base_url('rendiciones') ?>"><strong><?= count($tablero['cerradas']) ?></strong><span>Rendiciones cerradas</span></a>
        <a href="<?= base_url('gastos') ?>"><strong><?= count($tablero['duplicados']) ?></strong><span>Posible duplicado (gasto)</span></a>
    </div>
    <?php if ($entradas !== []) : ?>
        <p><a href="<?= base_url('gastos') ?>">Hay <?= count($entradas) ?> boleta<?= count($entradas) === 1 ? '' : 's' ?> por confirmar.</a> Llegaron por WhatsApp o por correo y faltan los datos del gasto.</p>
    <?php endif ?>
</section>
<section class="caja">
    <div class="caja-cabeza">
        <h2>Accesos</h2>
        <span>Entra a cada parte de la cuenta.</span>
    </div>
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
        <span>Cartola y rendiciones del mes.</span>
    </a>
    <?php if (($usuario['rol'] ?? '') === 'administrador') : ?>
        <a class="losa losa-gastos" href="<?= base_url('gastos/legalizados') ?>">
            <strong>Legalizados</strong>
            <span>Todos los documentos de la empresa.</span>
        </a>
        <a class="losa losa-gastos" href="<?= base_url('documentos') ?>">
            <strong>Documento único</strong>
            <span>Una forma de pago y aviso a tesorería.</span>
        </a>
        <a class="losa losa-fondos" href="<?= base_url('caja') ?>">
            <strong>Caja chica</strong>
            <span>Entrega, centralización y reposición.</span>
        </a>
        <a class="losa losa-personas" href="<?= base_url('socios') ?>">
            <strong>Socios</strong>
            <span>Documentos y lo que hay que recuperar.</span>
        </a>
        <a class="losa losa-empresa" href="<?= base_url('cargos') ?>">
            <strong>Tarjeta</strong>
            <span>Cargo, validación y fondo por rendir.</span>
        </a>
        <a class="losa losa-categorias" href="<?= base_url('categorias') ?>">
            <strong>Categorías</strong>
            <span>Los límites de la política.</span>
        </a>
        <a class="losa losa-parametros" href="<?= base_url('parametros') ?>">
            <strong>Parámetros</strong>
            <span>Cuentas, documentos y quién carga.</span>
        </a>
        <a class="losa losa-personas" href="<?= base_url('personas') ?>">
            <strong>Usuarios</strong>
            <span>Quién entra y quién rinde.</span>
        </a>
        <a class="losa losa-parametros" href="<?= base_url('modulos') ?>">
            <strong>Módulos</strong>
            <span>Lo incluido y lo que se contrata.</span>
        </a>
        <a class="losa losa-empresa" href="<?= base_url('empresa') ?>">
            <strong>Empresa</strong>
            <span>País, moneda e identificación.</span>
        </a>
    <?php endif ?>
</div>
</section>
<?php if (($usuario['rol'] ?? '') === 'administrador' && $empresa && $pais) : ?>
    <section class="bloque">
        <dl class="ficha">
            <div><dt>País</dt><dd><?= esc($pais['nombre']) ?></dd></div>
            <div><dt><?= esc($pais['id_fiscal']) ?></dt><dd><?= esc($empresa['id_fiscal']) ?></dd></div>
            <div><dt>Moneda</dt><dd><?= esc($pais['moneda']) ?></dd></div>
            <div><dt>Plan</dt><dd><?= esc(\App\Libraries\Modulos::resumen()) ?></dd></div>
            <div><dt>Correo</dt><dd><?= esc($usuario['email'] ?? '') ?></dd></div>
        </dl>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
