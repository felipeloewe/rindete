<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Pagos de fondos</h1>
        <p><?= esc($periodo) ?>. Pendiente espera la nómina. En proceso ya está creada en el banco. Pagado es lo que el banco registró, o lo que tesorería entregó a mano.</p>
    </div>
    <div class="lado">
        <a class="boton claro" href="<?= base_url('fondos/nomina?' . $consulta) ?>">Nómina</a>
        <a class="boton" href="<?= base_url('fondos') ?>">Volver</a>
    </div>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('fondos/pagos') ?>">
        <label>Año
            <select name="anio">
                <?php foreach ($anios as $opcion) : ?>
                    <option value="<?= esc((string) $opcion) ?>" <?= (int) $anio === (int) $opcion ? 'selected' : '' ?>><?= esc((string) $opcion) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Mes
            <select name="mes">
                <?php foreach ($meses as $numero => $texto) : ?>
                    <option value="<?= esc((string) $numero) ?>" <?= (int) $mes === (int) $numero ? 'selected' : '' ?>><?= esc($texto) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <button class="boton" type="submit">Ver</button>
    </form>
</section>
<?php if ($filas === []) : ?>
    <p class="bloque">Este mes no tiene fondos aceptados ni entregados.</p>
<?php else : ?>
    <table class="tabla">
        <thead><tr><th>Persona</th><th>Motivo</th><th>Monto</th><th>Estado</th></tr></thead>
        <tbody>
            <?php foreach ($filas as $fila) : ?>
                <tr>
                    <td><?= esc($fila['nombre']) ?></td>
                    <td><a href="<?= base_url('fondos/' . $fila['id']) ?>"><?= esc($fila['motivo']) ?></a></td>
                    <td><?= esc(\App\Libraries\Montos::formato((float) $fila['monto'], (string) $fila['moneda'])) ?></td>
                    <td><span class="<?= esc(\App\Libraries\Estados::clasePago((string) $fila['estado'])) ?>"><?= esc(\App\Libraries\Estados::pago((string) $fila['estado'])) ?></span></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
<?= $this->endSection() ?>
