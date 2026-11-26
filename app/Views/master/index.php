<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>trabajo<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $texto = static function (array $sumas): string {
        $partes = [];
        foreach ($sumas as $suma) {
            $partes[] = \App\Libraries\Montos::formato((float) $suma['monto'], (string) $suma['moneda']);
        }

        return implode(' · ', $partes);
    };
?>
<header class="pagina">
    <div>
        <h1>Empresas</h1>
        <p>El administrador no rinde ni ocupa un cupo hasta que lo marques. Si lo marcas, cuenta entre las personas que rinden. En Plan Free caben 4.</p>
    </div>
</header>
<section class="bloque">
    <h2>Incorporación</h2>
    <div class="pendientes">
        <a href="#empresas"><strong><?= (int) $tablero['activas'] ?></strong><span>Activas</span></a>
        <a href="#usuarios"><strong><?= count($tablero['personas']) ?></strong><span>Usuarios</span></a>
        <?php foreach ($tablero['planes'] as $plan) : ?>
            <a href="#empresas"><strong><?= (int) $plan['cantidad'] ?></strong><span><?= esc($plan['nombre']) ?></span></a>
        <?php endforeach ?>
        <a href="#empresas"><strong><?= esc($texto($tablero['mensual'])) ?></strong><span>Contratación mensual</span></a>
        <a href="#empresas"><strong><?= esc($texto($tablero['anual'])) ?></strong><span>Contratación anual</span></a>
    </div>
</section>
<section class="bloque" id="empresas">
    <h2>Altas</h2>
    <?php if ($tablero['empresas'] === []) : ?>
        <p>Todavía no hay empresas.</p>
    <?php else : ?>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Empresa</th>
                    <th>Plan</th>
                    <th>Cobro</th>
                    <th>Rinden</th>
                    <th>Administrador</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tablero['empresas'] as $empresa) : ?>
                    <tr>
                        <td>
                            <?= esc($empresa['nombre']) ?>
                            <small><?= esc($empresa['pais']) ?> · <?= esc($empresa['id_fiscal']) ?> · <?= esc($empresa['creado']) ?></small>
                        </td>
                        <td><?= esc($empresa['plan']) ?><?= $empresa['vigente'] ? '' : ' · En pausa' ?></td>
                        <td><?= $empresa['es_free'] ? 'Sin cobro' : esc($empresa['monto'] . ' · ' . $empresa['periodicidad']) ?></td>
                        <td><?= esc($empresa['cupo']) ?></td>
                        <td>
                            <?php foreach ($empresa['admins'] as $admin) : ?>
                                <?= esc($admin['email']) ?>
                                <small><?= esc($admin['papel']) ?></small>
                            <?php endforeach ?>
                        </td>
                        <td>
                            <form method="post" action="<?= base_url('master/empresas/' . $empresa['id'] . '/vigencia') ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="vigente" value="<?= $empresa['vigente'] ? '0' : '1' ?>">
                                <button class="boton claro" type="submit"><?= $empresa['vigente'] ? 'Pausar' : 'Activar' ?></button>
                            </form>
                            <form method="post" action="<?= base_url('master/empresas/' . $empresa['id'] . '/administrador') ?>">
                                <?= csrf_field() ?>
                                <label>Otro administrador
                                    <input name="nombre" maxlength="80" required placeholder="Nombre">
                                </label>
                                <label>Correo
                                    <input type="email" name="email" maxlength="160" required autocomplete="off">
                                </label>
                                <button class="boton claro" type="submit">Enviar clave</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>
<section class="bloque" id="usuarios">
    <h2>Usuarios registrados</h2>
    <?php if ($tablero['personas'] === []) : ?>
        <p>Todavía no hay usuarios.</p>
    <?php else : ?>
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Empresa</th>
                    <th>Papel</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tablero['personas'] as $persona) : ?>
                    <tr>
                        <td>
                            <?= esc($persona['nombre']) ?>
                            <?= $persona['activo'] ? '' : '<small>Inactiva</small>' ?>
                        </td>
                        <td><?= esc($persona['email']) ?></td>
                        <td><?= esc($persona['empresa']) ?></td>
                        <td><?= esc($persona['papel']) ?></td>
                        <td>
                            <?php if ($persona['administrador']) : ?>
                                <form method="post" action="<?= base_url('master/usuarios/' . $persona['id'] . '/rendidor') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="rinde" value="<?= $persona['rinde'] ? '0' : '1' ?>">
                                    <button class="boton claro" type="submit"><?= $persona['rinde'] ? 'Dejar solo administrador' : 'Marcar como usuario rendidor' ?></button>
                                </form>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
</section>
<section class="bloque">
    <h2>Crear empresa</h2>
    <form method="post" action="<?= base_url('master/empresas') ?>">
        <?= csrf_field() ?>
        <label>País
            <select name="pais">
                <?php foreach ($paises as $codigo => $pais) : ?>
                    <option value="<?= esc($codigo) ?>" <?= old('pais', 'CL') === $codigo ? 'selected' : '' ?>><?= esc($pais['nombre']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label>Empresa
            <input name="empresa" value="<?= esc(old('empresa', '')) ?>" maxlength="120" required>
        </label>
        <label>Identificación fiscal
            <input name="id_fiscal" value="<?= esc(old('id_fiscal', '')) ?>" maxlength="40" required>
        </label>
        <label>Plan
            <select name="plan">
                <option value="free" <?= old('plan', 'free') === 'free' ? 'selected' : '' ?>>Plan Free</option>
                <option value="cobro" <?= old('plan') === 'cobro' ? 'selected' : '' ?>>Plan de cobro</option>
            </select>
        </label>
        <label>Nombre del plan de cobro
            <input name="cobro_plan" value="<?= esc(old('cobro_plan', '')) ?>" maxlength="80" placeholder="Solo si es un plan de cobro">
        </label>
        <label>Monto del cobro
            <input name="cobro_monto" value="<?= esc(old('cobro_monto', '')) ?>" inputmode="decimal" placeholder="50000">
        </label>
        <label>Periodicidad
            <select name="cobro_periodicidad">
                <option value="">Sin cobro</option>
                <option value="mensual" <?= old('cobro_periodicidad') === 'mensual' ? 'selected' : '' ?>>Mensual</option>
                <option value="anual" <?= old('cobro_periodicidad') === 'anual' ? 'selected' : '' ?>>Anual</option>
            </select>
        </label>
        <label>Cupo de quienes rinden en el plan de cobro
            <input name="cupo_usuarios" value="<?= esc(old('cupo_usuarios', '')) ?>" inputmode="numeric" placeholder="Plan Free queda en 4">
        </label>
        <label>Administrador
            <input name="nombre" value="<?= esc(old('nombre', '')) ?>" maxlength="80" required>
        </label>
        <label>Correo del administrador
            <input type="email" name="email" value="<?= esc(old('email', '')) ?>" maxlength="160" required autocomplete="off">
        </label>
        <button class="boton" type="submit">Crear empresa y enviar la clave</button>
    </form>
</section>
<?= $this->endSection() ?>
