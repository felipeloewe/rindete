<label>Banco de destino
    <select name="banco_destino">
        <option value="">Elige el banco o el TPPF</option>
        <?php foreach ($grupos as $grupo => $opciones) : ?>
            <optgroup label="<?= esc($grupo) ?>">
                <?php foreach ($opciones as $codigo => $banco) : ?>
                    <?php $codigo = sprintf('%03d', $codigo); ?>
                    <option value="<?= esc($codigo) ?>" <?= (string) $seleccionado === $codigo ? 'selected' : '' ?>><?= esc($codigo) ?> <?= esc($banco) ?></option>
                <?php endforeach ?>
            </optgroup>
        <?php endforeach ?>
    </select>
</label>
