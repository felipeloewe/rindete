<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Política de tratamiento y uso del software</h1>
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
    <p>Identificar a quien rinde, abrir rendiciones y fondos, armar la nómina y los preasientos, y cuidar el acceso a la cuenta. No se usan para otro fin.</p>
</section>
<section class="bloque">
    <h2>Quién decide</h2>
    <p>La empresa es responsable de los datos de quienes rinden. Rindete los trata por cuenta de la empresa para prestar el software. Del correo y de la clave de acceso, Rindete es responsable, porque los necesita para entregar el servicio.</p>
</section>
<section class="bloque">
    <h2>Normas chilenas</h2>
    <ul>
        <li>Ley N° 19.628, sobre protección de la vida privada. Es la norma vigente del tratamiento de datos personales.</li>
        <li>Ley N° 21.719, sobre protección de datos personales. Rindete la considera desde ya en esta política. Entra en vigencia en la fecha que fije la ley: el artículo primero transitorio señala el 1 de diciembre de 2026, y el Congreso despachó una postergación al 15 de octubre de 2027 que todavía debe promulgarse y publicarse.</li>
        <li>Ley N° 20.575, que establece el principio de finalidad en el tratamiento de datos personales.</li>
        <li>Ley N° 19.799, sobre documentos electrónicos, firma electrónica y servicios de certificación de dicha firma. El comprobante y los registros del servicio se tratan como documentos electrónicos.</li>
        <li>Ley N° 19.496, sobre protección de los derechos de los consumidores, en lo que alcanza al contrato de uso del software como servicio.</li>
        <li>Ley N° 21.459, que establece normas sobre delitos informáticos. El uso del software queda sujeto a esa ley.</li>
    </ul>
</section>
<section class="bloque">
    <h2>Aceptación</h2>
    <p>La empresa acepta esta política al crear la cuenta. Cada persona la acepta al ser dada de alta y, si todavía no consta, al entrar. La aceptación queda guardada con esta versión.</p>
</section>
<?= $this->endSection() ?>
