<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Nómina de fondos por rendir</h1>
        <p>Si hay varias personas con un fondo aceptado, entran en una sola nómina. Crearla entrega el dinero y deja el fondo por rendir. El archivo es el de Pago de Nómina en Línea: texto separado por punto y coma, tipo de pago OTR. En el banco se carga como Texto, con separador punto y coma.</p>
    </div>
    <a class="boton" href="<?= base_url('fondos') ?>">Volver</a>
</header>
<section class="bloque">
    <form class="filtros" method="get" action="<?= base_url('fondos/nomina') ?>">
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
    <?php if ($pendientes === []) : ?>
        <p>No hay solicitudes aceptadas de este mes fuera de una nómina.</p>
    <?php else : ?>
        <table class="tabla">
            <thead><tr><th>Persona</th><th>Banco</th><th>Cuenta del banco</th><th>Motivo</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach ($pendientes as $solicitud) : ?>
                    <?php $codigoBanco = $bancosPersona[(int) $solicitud['usuario_id']] ?? ''; ?>
                    <tr>
                        <td><?= esc($nombres[(int) $solicitud['usuario_id']] ?? '') ?></td>
                        <td><?= $codigoBanco !== '' ? esc($codigoBanco . ' ' . ($bancos[$codigoBanco] ?? '')) : 'Sin banco' ?></td>
                        <td><?= ($cuentas[(int) $solicitud['usuario_id']] ?? '') !== '' ? esc($cuentas[(int) $solicitud['usuario_id']]) : 'Sin cuenta' ?></td>
                        <td><?= esc($solicitud['motivo']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $solicitud['monto'], (string) $solicitud['moneda'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <form method="post" action="<?= base_url('fondos/nomina') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="anio" value="<?= esc((string) $anio) ?>">
            <input type="hidden" name="mes" value="<?= esc((string) $mes) ?>">
            <?php if ($chile) : ?>
                <label>Cuenta de cargo
                    <input name="cuenta_cargo" inputmode="numeric" maxlength="12" required value="<?= esc(old('cuenta_cargo', $cuentaCargo)) ?>" placeholder="Cuenta de la empresa, hasta 12 números">
                </label>
            <?php endif ?>
            <button class="boton" type="submit">Crear en el banco</button>
        </form>
    <?php endif ?>
</section>
<?php foreach ($nominas as $nomina) : ?>
    <section class="bloque">
        <div class="cabeza">
            <h2>Nómina de fondos por rendir</h2>
            <span><?= esc(\App\Libraries\Estados::nomina((string) $nomina['estado'])) ?><?= ($nomina['erp_ref'] ?? '') !== '' ? ' · ' . esc((string) $nomina['erp_ref']) : '' ?></span>
        </div>
        <?php if ($chile) : ?>
            <p>Cuenta de cargo <?= ($nomina['cuenta_cargo'] ?? '') !== '' ? esc((string) $nomina['cuenta_cargo']) : 'sin anotar' ?>.</p>
            <p><a class="boton claro" href="<?= base_url('fondos/nomina/' . $nomina['id'] . '/archivo') ?>">Descargar archivo</a></p>
        <?php endif ?>
        <table class="tabla">
            <thead><tr><th>Persona</th><th>RUT</th><th>Banco</th><th>Cuenta del banco</th><th>Motivo</th><th>Monto</th></tr></thead>
            <tbody>
                <?php foreach ($nomina['lineas'] as $linea) : ?>
                    <tr>
                        <td><?= esc($linea['nombre']) ?></td>
                        <td><?= esc((string) $linea['rut']) ?></td>
                        <td><?= esc((string) ($linea['banco_destino'] ?? '')) ?></td>
                        <td><?= esc((string) ($linea['cuenta_banco'] ?? '')) ?></td>
                        <td><?= esc($linea['motivo']) ?></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $linea['monto'], (string) $linea['moneda'])) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </section>
<?php endforeach ?>
<?= $this->endSection() ?>
