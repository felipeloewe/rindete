<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <h1>Empresa</h1>
    <?php
    $paisActual = old('pais', $empresa['pais'] ?? 'CL');
    $esAdmin    = ($usuario['rol'] ?? '') === 'administrador';
    ?>
    <form method="post" action="<?= base_url('empresa') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="nombre" value="<?= esc(old('nombre', $empresa['nombre'] ?? '')) ?>" maxlength="120" required <?= $esAdmin ? '' : 'disabled' ?>>
        </label>
        <label>País
            <select name="pais" <?= $esAdmin ? '' : 'disabled' ?>>
                <?php foreach ($paises as $codigo => $pais) : ?>
                    <option value="<?= esc($codigo) ?>" <?= $paisActual === $codigo ? 'selected' : '' ?>><?= esc($pais['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Identificación fiscal
            <input name="id_fiscal" value="<?= esc(old('id_fiscal', $empresa['id_fiscal'] ?? '')) ?>" maxlength="40" required <?= $esAdmin ? '' : 'disabled' ?>>
        </label>

        <?php if ($esAdmin) : ?>
            <fieldset>
                <legend>Políticas de gastos</legend>
                <label class="check">
                    <input type="hidden" name="detectar_duplicados" value="0">
                    <input type="checkbox" name="detectar_duplicados" value="1" <?= (string) old('detectar_duplicados', (string) ($empresa['detectar_duplicados'] ?? '1')) === '1' ? 'checked' : '' ?>>
                    Detectar posibles duplicados
                </label>
                <label>Días de ventana para duplicados
                    <input type="number" name="dias_duplicado" min="0" max="90" value="<?= esc(old('dias_duplicado', (string) ($empresa['dias_duplicado'] ?? '3'))) ?>" required>
                </label>
                <label>Exigir comprobante desde (monto)
                    <input name="comprobante_desde" value="<?= esc(old('comprobante_desde', (string) ($empresa['comprobante_desde'] ?? '1'))) ?>" required>
                </label>
                <label>Zona horaria (cupo mensual y fechas)
                    <?php $tzActual = old('timezone', $empresa['timezone'] ?? 'America/Santiago'); ?>
                    <select name="timezone">
                        <?php foreach ($timezones as $zona) : ?>
                            <option value="<?= esc($zona) ?>" <?= $tzActual === $zona ? 'selected' : '' ?>><?= esc($zona) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            </fieldset>
            <button class="boton" type="submit">Guardar</button>
        <?php endif ?>
    </form>
</section>
<?= $this->endSection() ?>
