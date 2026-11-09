<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <div class="cabeza">
        <h1><?= esc($gasto['concepto']) ?></h1>
        <span class="chip <?= $gasto['estado'] === 'observado' ? 'chip-warn' : 'chip-ok' ?>"><?= esc(\App\Libraries\Estados::gasto((string) $gasto['estado'])) ?></span>
    </div>
    <dl class="ficha">
        <div><dt>Proveedor</dt><dd><?= esc($gasto['proveedor']) ?></dd></div>
        <div><dt>Fecha</dt><dd><?= esc(date('d/m/Y', strtotime((string) $gasto['fecha']))) ?></dd></div>
        <div><dt>Monto</dt><dd><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></dd></div>
        <div><dt>Categoría</dt><dd><?= esc($categoria['nombre'] ?? '') ?></dd></div>
        <div><dt>Persona</dt><dd><?= esc($persona['nombre'] ?? '') ?></dd></div>
        <?php if ($gasto['tipo_doc'] !== '') : ?>
            <div><dt>Documento</dt><dd><?= esc($gasto['tipo_doc']) ?></dd></div>
        <?php endif ?>
        <?php if ($gasto['id_fiscal'] !== '') : ?>
            <div><dt><?= esc($pais['id_proveedor'] ?? 'Identificación') ?></dt><dd><?= esc($gasto['id_fiscal']) ?></dd></div>
        <?php endif ?>
        <?php if ($gasto['num_doc'] !== '') : ?>
            <div><dt>Número</dt><dd><?= esc($gasto['num_doc']) ?></dd></div>
        <?php endif ?>
        <?php if ($gasto['codigo_fiscal'] !== '') : ?>
            <div><dt><?= esc($pais['codigo'] ?? 'Código') ?></dt><dd><?= esc($gasto['codigo_fiscal']) ?></dd></div>
        <?php endif ?>
    </dl>
    <?php if ($gasto['nota'] !== '') : ?>
        <p><?= esc($gasto['nota']) ?></p>
    <?php endif ?>
    <?php $observaciones = \App\Libraries\Estados::observaciones($gasto); ?>
    <?php if ($observaciones !== []) : ?>
        <ul>
            <?php foreach ($observaciones as $texto) : ?>
                <li><?= esc($texto) ?></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <?php if ($gasto['comprobante'] !== '') : ?>
        <p><a href="<?= base_url('gastos/' . $gasto['id'] . '/comprobante') ?>">Ver comprobante</a></p>
    <?php endif ?>
    <?php if ($puedeEditar) : ?>
        <div class="acciones">
            <a class="boton" href="<?= base_url('gastos/' . $gasto['id'] . '/editar') ?>">Editar</a>
            <form class="plano" method="post" action="<?= base_url('gastos/' . $gasto['id'] . '/eliminar') ?>">
                <?= csrf_field() ?>
                <button class="boton claro" type="submit">Eliminar</button>
            </form>
        </div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
