<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>politica<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Mi perfil</h1>
        <p class="saltos"><a href="#datos">Mis datos</a><a href="#estado">Mi estado</a><a href="#salir">Salir</a></p>
    </div>
</header>
<section class="bloque" id="datos">
    <h2>Mis datos</h2>
    <dl class="ficha">
        <div><dt>Empresa</dt><dd><?= esc((string) ($empresa['nombre'] ?? '')) ?></dd></div>
        <div><dt>Perfil</dt><dd><?= esc($perfil) ?></dd></div>
        <?php if (($usuario['telefono'] ?? '') !== '') : ?>
            <div><dt>Teléfono</dt><dd><?= esc((string) $usuario['telefono']) ?></dd></div>
        <?php endif ?>
        <?php if (($usuario['id_fiscal'] ?? '') !== '') : ?>
            <div><dt><?= esc((string) ($pais['id_fiscal'] ?? 'RUT')) ?></dt><dd><?= esc((string) $usuario['id_fiscal']) ?></dd></div>
        <?php endif ?>
        <?php if (($usuario['cuenta_banco'] ?? '') !== '') : ?>
            <div><dt>Cuenta del banco</dt><dd><?= esc((string) $usuario['cuenta_banco']) ?></dd></div>
        <?php endif ?>
        <?php if ($banco !== '') : ?>
            <div><dt>Banco de destino</dt><dd><?= esc($banco) ?></dd></div>
        <?php endif ?>
    </dl>
    <form method="post" action="<?= base_url('cuenta') ?>">
        <?= csrf_field() ?>
        <label>Nombre
            <input name="nombre" value="<?= esc(old('nombre', $usuario['nombre'] ?? '')) ?>" maxlength="80" required>
        </label>
        <label>Correo
            <input type="email" name="email" value="<?= esc(old('email', $usuario['email'] ?? '')) ?>" maxlength="160" required>
        </label>
        <label>Nueva contraseña
            <input type="password" name="password" minlength="8" autocomplete="new-password" <?= (int) ($usuario['clave_inicial'] ?? 0) === 1 ? 'required' : '' ?> placeholder="Mínimo 8 caracteres">
            <p>Mínimo 8 caracteres.<?= (int) ($usuario['clave_inicial'] ?? 0) === 1 ? '' : ' Déjala vacía para no cambiarla.' ?></p>
        </label>
        <?php if ((int) ($usuario['clave_inicial'] ?? 0) === 1) : ?>
            <p>Esta es la contraseña inicial. Cámbiala para seguir.</p>
        <?php endif ?>
        <button class="boton" type="submit">Guardar</button>
    </form>
</section>
<section class="bloque" id="estado">
    <h2>Mi estado</h2>
    <h3>On work</h3>
    <?php if ($estado['trabajo'] === []) : ?>
        <p>No tienes nada que hacer.</p>
    <?php else : ?>
        <ul>
            <?php foreach ($estado['trabajo'] as $item) : ?>
                <li><a href="<?= base_url($item['href']) ?>"><?= esc($item['texto']) ?></a></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <h3>Fondos a rendir</h3>
    <?php if ($estado['fondos'] === []) : ?>
        <p>No tienes fondos abiertos por rendir.</p>
    <?php else : ?>
        <table class="tabla">
            <thead><tr><th>Fondo</th><th>Saldo</th></tr></thead>
            <tbody>
                <?php foreach ($estado['fondos'] as $fondo) : ?>
                    <tr>
                        <td><a href="<?= base_url('fondos/' . (int) $fondo['solicitud_id']) ?>"><?= esc((string) $fondo['motivo']) ?></a></td>
                        <td><?= esc(\App\Libraries\Montos::formato((float) $fondo['saldo'], (string) ($fondo['moneda'] ?? 'CLP'))) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
    <h3>Rendiciones</h3>
    <?php if ($estado['rendiciones'] === []) : ?>
        <p>No tienes rendiciones abiertas.</p>
    <?php else : ?>
        <ul>
            <?php foreach ($estado['rendiciones'] as $rendicion) : ?>
                <li><a href="<?= base_url('rendiciones/' . (int) $rendicion['id']) ?>"><?= esc((string) $rendicion['titulo']) ?></a></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
    <h3>Alertas</h3>
    <?php if ($estado['alertas'] === []) : ?>
        <p>No hay alertas.</p>
    <?php else : ?>
        <ul>
            <?php foreach ($estado['alertas'] as $item) : ?>
                <li><a href="<?= base_url($item['href']) ?>"><?= esc($item['texto']) ?></a></li>
            <?php endforeach ?>
        </ul>
    <?php endif ?>
</section>
<section class="bloque" id="salir">
    <h2>Salir</h2>
    <p>Cierra la sesión de esta cuenta.</p>
    <a class="boton claro" href="<?= base_url('logout') ?>">Salir</a>
</section>
<?= $this->endSection() ?>
