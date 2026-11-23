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
<section class="bloque">
    <h2>Acciones pendientes</h2>
    <p>Las rendiciones juntan los gastos. Cada flujo queda a la vista hasta cerrarlo.</p>
    <div class="pendientes">
        <a href="#abiertas"><strong><?= count($tablero['abiertas']) ?></strong><span>Abiertas</span></a>
        <a href="#proceso"><strong><?= count($tablero['proceso']) ?></strong><span>En proceso</span></a>
        <a href="#cerradas"><strong><?= count($tablero['cerradas']) ?></strong><span>Cerradas</span></a>
        <a href="#duplicados"><strong><?= count($tablero['duplicados']) ?></strong><span>Posible duplicado</span></a>
    </div>
    <?php if ($entradas !== []) : ?>
        <p><a href="<?= base_url('gastos') ?>">Hay <?= count($entradas) ?> boleta<?= count($entradas) === 1 ? '' : 's' ?> por confirmar.</a> Llegaron por WhatsApp o por correo y faltan los datos del gasto.</p>
    <?php endif ?>
    <?php
        $bloques = [
            'abiertas' => ['id' => 'abiertas', 'titulo' => 'Rendiciones abiertas', 'vacio' => 'No hay rendiciones abiertas.'],
            'proceso' => ['id' => 'proceso', 'titulo' => 'Rendiciones en proceso', 'vacio' => 'No hay rendiciones esperando la devolución de fondos.'],
            'cerradas' => ['id' => 'cerradas', 'titulo' => 'Rendiciones cerradas', 'vacio' => 'Todavía no hay un flujo cerrado.'],
        ];
    ?>
    <?php foreach ($bloques as $clave => $bloque) : ?>
        <h3 id="<?= esc($bloque['id']) ?>"><?= esc($bloque['titulo']) ?></h3>
        <?php if ($tablero[$clave] === []) : ?>
            <p><?= esc($bloque['vacio']) ?></p>
        <?php else : ?>
            <table class="tabla">
                <thead><tr><th>Rendición</th><th>Saldo</th><th>Para cerrar el flujo</th></tr></thead>
                <tbody>
                    <?php foreach (array_slice($tablero[$clave], 0, 6) as $fila) : ?>
                        <tr>
                            <td><a href="<?= base_url('rendiciones/' . $fila['id']) ?>"><?= esc($fila['titulo']) ?></a></td>
                            <td><?= esc(\App\Libraries\Montos::formato((float) $fila['saldo_calculado'], $moneda)) ?></td>
                            <td><?= esc($fila['aviso']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    <?php endforeach ?>
    <h3 id="duplicados">Gasto duplicado o posible error</h3>
    <?php if ($tablero['duplicados'] === []) : ?>
        <p>No hay gastos marcados como posible duplicado.</p>
    <?php else : ?>
        <table class="tabla">
            <thead><tr><th>Gasto</th><th>Fecha</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($tablero['duplicados'], 0, 6) as $gasto) : ?>
                    <tr>
                        <td><a href="<?= base_url('gastos/' . $gasto['id']) ?>"><?= esc($gasto['concepto']) ?></a></td>
                        <td><?= esc((string) $gasto['fecha']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) ($gasto['moneda'] ?? $moneda))) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>
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
    <?php if (($usuario['rol'] ?? '') === 'administrador') : ?>
        <a class="losa losa-gastos" href="<?= base_url('documentos') ?>">
            <strong>Documento único</strong>
            <span>Una forma de pago y el aviso a tesorería.</span>
        </a>
        <a class="losa losa-fondos" href="<?= base_url('caja') ?>">
            <strong>Caja chica</strong>
            <span>Entrega, centralización y reposición.</span>
        </a>
        <a class="losa losa-personas" href="<?= base_url('socios') ?>">
            <strong>Socios</strong>
            <span>Varios documentos y lo que hay que recuperar.</span>
        </a>
    <?php endif ?>
    <a class="losa losa-empresa" href="<?= base_url('cargos') ?>">
        <strong>Tarjeta</strong>
        <span>Cargo, validación y fondo por rendir.</span>
    </a>
    <a class="losa losa-informes" href="<?= base_url('informes') ?>">
        <strong>Informes</strong>
        <span>Cartola y rendiciones del mes.</span>
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
    <?php if (($usuario['rol'] ?? '') === 'administrador') : ?>
        <a class="losa losa-parametros" href="<?= base_url('modulos') ?>">
            <strong>Módulos</strong>
            <span>Lo incluido y lo que se contrata.</span>
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
            <div><dt>Plan</dt><dd><?= esc(\App\Libraries\Modulos::resumen()) ?></dd></div>
            <div><dt>Correo</dt><dd><?= esc($usuario['email'] ?? '') ?></dd></div>
        </dl>
    </section>
<?php endif ?>
<?= $this->endSection() ?>
