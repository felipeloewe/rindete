<section class="politica">
    <h2>Aceptación de políticas</h2>
    <p>Lo que se acepta es el efecto del software en cada ley. El nombre de la norma no queda a interpretación: los tres puntos de Infórmate más son las condiciones.</p>
    <p class="enlaces"><a href="<?= base_url('condiciones') ?>">Condiciones de uso del software</a> · <a href="<?= base_url('politicas') ?>">Política de tratamiento</a></p>
    <details class="informate" open>
        <summary>Infórmate más</summary>
        <p>Estos son los tres efectos que el software asume en cada ley.</p>
        <?= $this->include('politicas/efectos') ?>
    </details>
    <label class="cheque">
        <input type="checkbox" name="acepta_politica" value="1" required<?= old('acepta_politica') ? ' checked' : '' ?>>
        <?= esc($texto) ?>
    </label>
</section>
