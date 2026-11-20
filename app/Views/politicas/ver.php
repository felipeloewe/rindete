<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Política de tratamiento</h1>
        <p>Versión <?= esc(\App\Libraries\PoliticaDatos::VERSION) ?>.</p>
    </div>
</header>
<section class="bloque">
    <h2>Qué datos se tratan</h2>
    <p>De la persona: nombre, correo, RUT, cuenta bancaria y clave. De la empresa: nombre, RUT y cuenta de cargo. También el comprobante, el gasto y lo que la rendición necesita para cuadrar.</p>
    <p>El nombre, el correo, el RUT y la cuenta viven en la capa de identidades. La operación de rendiciones guarda el id.</p>
</section>
<section class="bloque">
    <h2>Para qué</h2>
    <p>Identificar a quien rinde, abrir rendiciones y fondos, armar la nómina y los preasientos, y cuidar el acceso a la cuenta.</p>
</section>
<section class="bloque">
    <h2>Quién decide</h2>
    <p>La empresa es responsable de los datos de quienes rinden. Rindete los trata por cuenta de la empresa para prestar el software. Del correo y de la clave de acceso, Rindete es responsable, porque los necesita para entregar el servicio.</p>
</section>
<section class="bloque">
    <h2>En qué afecta cada ley</h2>
    <p>Lo que se acepta son estos efectos. Las <a href="<?= base_url('condiciones') ?>">condiciones de uso del software</a> los recogen como documento.</p>
    <?= $this->include('politicas/efectos') ?>
</section>
<section class="bloque">
    <h2>Aceptación</h2>
    <p>La empresa acepta estas condiciones al crear la cuenta. Cada persona las acepta al ser dada de alta y, si todavía no consta esta versión, al entrar. La aceptación queda guardada con esta versión.</p>
</section>
<?= $this->endSection() ?>
