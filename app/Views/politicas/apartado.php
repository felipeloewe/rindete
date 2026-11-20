<section class="politica">
    <h2>Aceptación de políticas</h2>
    <p>Rindete trata datos personales para prestar el software de rendiciones. La empresa es responsable de los datos de quien rinde. Rindete los trata por cuenta de la empresa, y es responsable de la cuenta de acceso.</p>
    <ul>
        <li>Ley N° 19.628, sobre protección de la vida privada. Es la norma que hoy rige el tratamiento.</li>
        <li>Ley N° 21.719, sobre protección de datos personales. Se aplica cuando entre en vigencia. El artículo primero transitorio señala el 1 de diciembre de 2026, y el Congreso despachó una postergación al 15 de octubre de 2027 que todavía debe promulgarse y publicarse.</li>
        <li>Ley N° 20.575, principio de finalidad: los datos se usan para rendir y para prestar el software.</li>
        <li>Ley N° 19.799, sobre documentos electrónicos y firma electrónica.</li>
        <li>Ley N° 19.496, sobre protección de los derechos de los consumidores, en lo que alcanza al uso del software.</li>
        <li>Ley N° 21.459, sobre delitos informáticos: el uso del software queda sujeto a esa ley.</li>
    </ul>
    <p><a href="<?= base_url('politicas') ?>">Lee la política completa</a></p>
    <label class="cheque">
        <input type="checkbox" name="acepta_politica" value="1" <?= old('acepta_politica') ? 'checked' : '' ?>>
        <?= esc($texto) ?>
    </label>
</section>
