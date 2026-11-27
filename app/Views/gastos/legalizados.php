<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Documentos legalizados</h1>
        <p>De toda la empresa. No depende de quién los rindió.</p>
    </div>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('gastos/legalizados') ?>">
        <label>Buscar
            <input name="q" value="<?= esc($q) ?>" placeholder="Proveedor, RUT, número o folio">
        </label>
        <button class="boton claro" type="submit">Filtrar</button>
    </form>
    <?php if ($items === []) : ?>
        <p><?= $q === '' ? 'Todavía no hay documentos legalizados.' : 'No hay documentos legalizados con ese filtro.' ?></p>
    <?php else : ?>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Quién rinde</th>
                    <th>Proveedor</th>
                    <th>Documento</th>
                    <th><?= esc($pais['id_proveedor'] ?? 'Identificación') ?></th>
                    <th>Número</th>
                    <th><?= esc($pais['codigo'] ?? 'Código') ?></th>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $gasto) : ?>
                    <tr>
                        <td><?= esc($personas[(int) $gasto['usuario_id']] ?? '') ?></td>
                        <td><a href="<?= base_url('gastos/' . $gasto['id']) ?>"><?= esc($gasto['proveedor']) ?></a></td>
                        <td><?= esc((string) $gasto['tipo_doc']) ?></td>
                        <td><?= esc((string) $gasto['id_fiscal']) ?></td>
                        <td><?= esc((string) $gasto['num_doc']) ?></td>
                        <td><?= esc((string) $gasto['codigo_fiscal']) ?></td>
                        <td><?= esc(date('d/m/Y', strtotime((string) $gasto['fecha']))) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
