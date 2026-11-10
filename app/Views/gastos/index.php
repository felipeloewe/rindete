<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="bloque">
    <div class="cabeza">
        <h1>Gastos</h1>
        <a class="boton" href="<?= base_url('gastos/nuevo') ?>">Nuevo gasto</a>
    </div>
    <form class="filtros" method="get" action="<?= base_url('gastos') ?>">
        <label>Buscar
            <input name="q" value="<?= esc($q) ?>" placeholder="Concepto, proveedor o documento">
        </label>
        <label>Estado
            <select name="estado">
                <option value="">Todos</option>
                <?php foreach (['observado' => 'Observado', 'legalizado' => 'Legalizado', 'en_rendicion' => 'En rendición', 'aprobado' => 'Aprobado'] as $codigo => $etiqueta) : ?>
                    <option value="<?= esc($codigo) ?>" <?= $estado === $codigo ? 'selected' : '' ?>><?= esc($etiqueta) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Categoría
            <select name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $cat) : ?>
                    <option value="<?= esc($cat['id']) ?>" <?= (string) $categoria === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <button class="boton claro" type="submit">Filtrar</button>
    </form>
</section>
<?php if ($items === []) : ?>
    <p class="bloque">Todavía no hay gastos con ese filtro.</p>
<?php else : ?>
    <table class="tabla">
        <thead>
            <tr><th>Gasto</th><th>Fecha</th><th>Monto</th><th>Estado</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $gasto) : ?>
                <tr>
                    <td>
                        <a href="<?= base_url('gastos/' . $gasto['id']) ?>"><?= esc($gasto['concepto']) ?></a>
                        <small><?= esc($gasto['proveedor']) ?> · <?= esc($nombres[(int) $gasto['categoria_id']] ?? '') ?> · <?= esc($personas[(int) $gasto['usuario_id']] ?? '') ?></small>
                    </td>
                    <td><?= esc(date('d/m/Y', strtotime((string) $gasto['fecha']))) ?></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::clase((string) $gasto['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::gasto((string) $gasto['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
