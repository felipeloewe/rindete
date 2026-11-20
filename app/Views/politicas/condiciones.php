<?= $this->extend('layout') ?>

<?= $this->section('etapa') ?>entrada<?= $this->endSection() ?>

<?= $this->section('content') ?>
<header class="pagina">
    <div>
        <h1>Condiciones de uso del software</h1>
        <p>Versión <?= esc(\App\Libraries\PoliticaDatos::VERSION) ?>.</p>
    </div>
</header>
<section class="bloque">
    <h2>Qué es</h2>
    <p>Rindete es el software con el que una empresa rinde gastos. La empresa crea la cuenta. Cada persona entra con su correo y carga los gastos, los fondos y los comprobantes de esa empresa.</p>
</section>
<section class="bloque">
    <h2>Quién usa la cuenta</h2>
    <p>La empresa administra a sus personas, los topes y el cierre. La persona usa la cuenta que la empresa le da y rinde lo que esa empresa le habilitó.</p>
</section>
<section class="bloque">
    <h2>Plan de alta</h2>
    <p>La cuenta parte en el plan Free: 4 usuarios y 2 GB. Los preasientos y el seguimiento de cuentas se contratan aparte. El alta no incluye un cobro por esos módulos.</p>
</section>
<section class="bloque">
    <h2>Registros del servicio</h2>
    <p>El comprobante, el gasto, el fondo y el preasiento quedan como registros electrónicos. La aceptación de estas condiciones queda guardada con esta versión y la fecha. Esos registros respaldan la rendición y no sustituyen una firma electrónica avanzada ni un certificado de firma.</p>
</section>
<section class="bloque">
    <h2>Uso de la cuenta</h2>
    <p>Cada persona entra con su propia cuenta. El comprobante, el fondo y el preasiento se registran como ocurrieron. Entrar a los datos de otra empresa o saltar el acceso queda fuera del servicio.</p>
</section>
<section class="bloque">
    <h2>En qué afecta cada ley</h2>
    <p>Esto es lo que se acepta. Cada ley queda traducida a tres efectos del software. <a href="<?= base_url('politicas') ?>">La política de tratamiento</a> describe qué datos se usan y quién decide.</p>
    <?= $this->include('politicas/efectos') ?>
</section>
<section class="bloque">
    <h2>Cambios</h2>
    <p>Estas condiciones valen con esta versión. Si cambian, hay que aceptarlas de nuevo para seguir usando la cuenta.</p>
</section>
<?= $this->endSection() ?>
