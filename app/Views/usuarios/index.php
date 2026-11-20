<?= $this->extend('layout') ?>

<?= $this->section('content') ?>
<section class="bloque">
    <div class="cabeza">
        <h1>Personas</h1>
    </div>
    <p>Invita rendidores a <?= esc($empresa['nombre'] ?? 'tu empresa') ?>. Ellos solo ven sus propios gastos.</p>

    <?php if (! empty($enlace)) : ?>
        <p class="aviso ok">Enlace de invitación (válido unos días): <code><?= esc($enlace) ?></code></p>
    <?php endif ?>

    <form method="post" action="<?= base_url('usuarios/invitar') ?>">
        <?= csrf_field() ?>
        <div class="grid-2">
            <label>Nombre
                <input name="nombre" value="<?= esc(old('nombre', '')) ?>" maxlength="80" required>
            </label>
            <label>Correo
                <input type="email" name="email" value="<?= esc(old('email', '')) ?>" maxlength="160" required>
            </label>
        </div>
        <button class="boton" type="submit">Invitar rendidor</button>
    </form>
</section>

<section class="bloque" style="margin-top:1rem">
    <h2>Equipo</h2>
    <table class="tabla">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Rol</th>
                <th>Estado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($personas as $persona) : ?>
                <?php
                $pendiente = trim((string) ($persona['invite_token'] ?? '')) !== '';
                $activo    = (int) ($persona['activo'] ?? 0) === 1;
                if ($pendiente) {
                    $estado = 'Invitación pendiente';
                } elseif ($activo) {
                    $estado = 'Activo';
                } else {
                    $estado = 'Inactivo';
                }
                ?>
                <tr>
                    <td><?= esc($persona['nombre']) ?></td>
                    <td><?= esc($persona['email']) ?></td>
                    <td><?= esc($persona['rol']) ?></td>
                    <td><span class="chip <?= $activo ? 'chip-ok' : 'chip-warn' ?>"><?= esc($estado) ?></span></td>
                    <td class="acciones">
                        <?php if ((int) $persona['id'] !== (int) $usuario['id'] && ($persona['rol'] ?? '') !== 'administrador') : ?>
                            <?php if ($activo) : ?>
                                <form class="plano" method="post" action="<?= base_url('usuarios/' . $persona['id'] . '/desactivar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton claro" type="submit">Desactivar</button>
                                </form>
                            <?php elseif (! $pendiente) : ?>
                                <form class="plano" method="post" action="<?= base_url('usuarios/' . $persona['id'] . '/activar') ?>">
                                    <?= csrf_field() ?>
                                    <button class="boton claro" type="submit">Activar</button>
                                </form>
                            <?php endif ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>
