<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1><?= esc($pieza['titulo']) ?></h1>
        <p><?= esc($periodo) ?>. Cada rendidor tiene su cuenta Global66. El abono confirma que la nómina llegó. El egreso abre la rendición.</p>
    </div>
    <a class="boton" href="<?= base_url('informes?' . $consulta) ?>">Volver</a>
</header>
<section class="bloque"><?= $this->include('informes/filtros') ?></section>
<?php if ($filas === []) : ?>
    <p class="bloque">Este período no tiene movimientos de Global66 ni de MACH.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Tarjeta</th><th>Movimiento</th><th>Sentido</th><th>Monto</th><th>Estado</th><th></th></tr></thead>
        <tbody>
            <?php
                $estados = [
                    'trazado' => 'Trazado',
                    'por_rendir' => 'Por rendir',
                    'en_rendicion' => 'En rendición',
                    'en_cuenta' => 'Llegó con la nómina',
                    'sin_nomina' => 'Llegó sin nómina',
                    'comercio' => 'Pago de comercio',
                ];
            ?>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['persona'] ?? '') ?></td>
                    <td>
                        <?= esc($fila['tarjeta'] ?? 'Sin tarjeta identificada') ?>
                        <?= ($fila['ultimos4'] ?? '') !== '' ? '<small>****' . esc($fila['ultimos4']) . '</small>' : '' ?>
                    </td>
                    <td><?= esc($fila['detalle']) ?></td>
                    <td><?= (string) $fila['sentido'] === 'abono' ? 'Abono' : 'Cargo' ?></td>
                    <td><?= (float) $fila['monto'] > 0 ? esc(\App\Libraries\Montos::formato((float) $fila['monto'], (string) $fila['moneda'])) : 'Sin monto' ?></td>
                    <td><?= esc($estados[$fila['estado']] ?? $fila['estado']) ?></td>
                    <td>
                        <?php if ((string) $fila['estado'] === 'por_rendir' && $categorias !== []) : ?>
                            <form method="post" action="<?= base_url('informes/medios/' . $fila['id'] . '/rendir') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="anio" value="<?= esc((string) $anio) ?>">
                                <input type="hidden" name="mes" value="<?= esc((string) $mes) ?>">
                                <select name="categoria_id">
                                    <?php foreach ($categorias as $categoria) : ?>
                                        <option value="<?= esc($categoria['id']) ?>"><?= esc($categoria['nombre']) ?></option>
                                    <?php endforeach ?>
                                </select>
                                <button class="boton" type="submit">Pasar a rendición</button>
                            </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>