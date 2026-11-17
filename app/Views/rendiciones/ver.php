<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($rendicion['titulo']) ?></h1>
        <?php if ($rendicion['nota'] !== '') : ?>
            <p><?= esc($rendicion['nota']) ?></p>
        <?php endif ?>
    </div>
    <span class="<?= esc(\App\Libraries\Estados::claseRendicion((string) $rendicion['estado']), 'attr') ?>"><?= esc(\App\Libraries\Estados::rendicion((string) $rendicion['estado'])) ?></span>
</header>
<section class="bloque">
    <div class="cabeza">
        <h2>Gastos incluidos</h2>
        <strong><?= esc(\App\Libraries\Montos::formato((float) $total, (string) $moneda)) ?></strong>
    </div>
    <?php if ($gastos === []) : ?>
        <p>Esta rendición todavía no tiene gastos.</p>
    <?php else : ?>
        <table class="tabla">
            <thead>
                <tr><th>Gasto</th><th>Fecha</th><th>Monto</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($gastos as $gasto) : ?>
                    <tr>
                        <td><a href="<?= base_url('gastos/' . $gasto['id']) ?>"><?= esc($gasto['concepto']) ?></a></td>
                        <td><?= esc(date('d/m/Y', strtotime((string) $gasto['fecha']))) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></td>
                        <td>
                            <?php if ($abierta) : ?>
                                <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/quitar/' . $gasto['id']) ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton claro" type="submit">Quitar</button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
    <?php if ($abierta && $disponibles === []) : ?>
        <p>No hay gastos legalizados libres para sumar.</p>
    <?php elseif ($abierta) : ?>
        <form method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/agregar') ?>">
            <?= csrf_field() ?>
            <label>Sumar un gasto legalizado
                <select name="gasto_id" required>
                    <option value="">Selecciona</option>
                    <?php foreach ($disponibles as $gasto) : ?>
                        <option value="<?= esc($gasto['id']) ?>"><?= esc($gasto['concepto']) ?> · <?= esc(\App\Libraries\Montos::formato((float) $gasto['monto'], (string) $gasto['moneda'])) ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <button class="boton" type="submit">Sumar gasto</button>
        </form>
    <?php endif ?>
    <?php if ($abierta) : ?>
        <div class="acciones">
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/eliminar') ?>">
                <?= csrf_field() ?>
                <button class="boton claro" type="submit">Eliminar</button>
            </form>
            <form class="plano" method="post" action="<?= base_url('rendiciones/' . $rendicion['id'] . '/enviar') ?>">
                <?= csrf_field() ?>
                <button class="boton" type="submit">Enviar rendición</button>
            </form>
        </div>
    <?php endif ?>
</section>
<?= $this->endSection() ?>
