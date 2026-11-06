<main id="contenido">
  <section class="hero">
    <div>
      <div class="kicker"><i>Latam</i> Chile · Colombia · México · Perú · Ecuador · El Salvador</div>
      <h1>Rendición de gastos, caja menor y reembolsos.</h1>
      <p class="lede">En Chile se rinde y el fondo menor es la caja chica. En Colombia se legaliza y se habla de caja menor. En México se comprueba el gasto. El reembolso, en todos, es la devolución de lo que la persona adelantó.</p>
      <div class="hero-actions">
        <?php if (current_user()): ?>
          <a class="btn btn-citrus" href="<?= e(url('panel.php')) ?>">Ir al panel</a>
        <?php else: ?>
          <a class="btn btn-citrus" href="<?= e(url('registro.php')) ?>">Empezar con mi empresa</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(web_href('blog/como-se-dice-el-gasto-en-cada-pais')) ?>">Cómo se dice en cada país</a>
      </div>
      <div class="hero-points">
        <span>Comprobante con el dato fiscal del país</span>
        <span>Informe listo para aprobar</span>
        <span>Reembolso separado de la caja</span>
      </div>
    </div>
    <div class="stage">
      <div class="stage-top"><span>Andina Retail · Colombia</span><span class="dots" aria-hidden="true"><i></i><i></i><i></i></span></div>
      <div class="mock">
        <div class="mock-head">
          <strong class="mock-title">Viaje Medellín · legalización</strong>
          <span class="chip chip-info">En revisión</span>
        </div>
        <div class="mock-grid">
          <div class="mock-card"><span class="muted">Total</span><strong>COP 448.400</strong></div>
          <div class="mock-card"><span class="muted">Alertas</span><strong>1 duplicado</strong></div>
        </div>
        <div class="mock-alert">
          <div>
            <strong>Almuerzo con cliente</strong>
            <div class="muted">Mismo NIT y número de documento</div>
          </div>
          <span class="chip chip-warn">Duplicado</span>
        </div>
        <div class="mock-row">
          <span class="chip chip-ok">Sin alertas</span>
          <span class="chip">Hotel Estelar</span>
        </div>
      </div>
      <div class="quote-card">En Colombia se legaliza. En Chile se rinde.</div>
    </div>
  </section>

  <section class="collect" id="pide" data-reveal>
    <div class="collect-panel">
      <div>
        <div class="kicker"><i>Onboarding</i> Correo o QR. El mismo formulario.</div>
        <h2>El cliente manda los datos. Tú emites la factura.</h2>
        <p>Llegan la razón social, el documento del país y los archivos que pediste. Caen en tu bandeja. La factura la emites tú, en tu facturador.</p>
        <ol class="collect-steps">
          <li><em>01</em><span>Envías correo o QR</span></li>
          <li><em>02</em><span>El cliente completa</span></li>
          <li><em>03</em><span>Llega a la bandeja</span></li>
        </ol>
        <a class="btn btn-citrus" href="<?= e(web_href('pide-tu-factura')) ?>">Cómo se recolecta</a>
      </div>
      <div class="sheet" aria-hidden="true">
        <div class="sheet-bar"><span>Formulario del cliente</span><span class="chip">Chile</span></div>
        <span class="sheet-label">Razón social</span>
        <div class="fake">Comercial Andina SpA</div>
        <span class="sheet-label">RUT</span>
        <div class="fake">12.345.678-5</div>
        <span class="sheet-label">Adjunto</span>
        <div class="fake file"><span>Copia del RUT.pdf</span><i>Listo</i></div>
      </div>
    </div>
    <div class="id-strip">
      <?php foreach (web_landings() as $landing): ?>
        <?php $info = pais($landing['codigo']); ?>
        <a href="<?= e(web_href($landing['slug'])) ?>"><b><?= e($info['id_fiscal']) ?></b> <?= e($landing['nombre']) ?></a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section" id="producto" data-reveal>
    <h2>Tres controles. Si los mezclas, pagas mal.</h2>
    <p class="intro">El informe documenta el gasto. La caja repone un fondo de la empresa. El reembolso devuelve lo que la persona adelantó. Cada uno cierra por su lado, con el nombre que usa tu país.</p>
    <div class="concept-grid">
      <?php foreach (web_conceptos() as $tema): ?>
        <a class="concept" href="<?= e(web_href($tema['slug'])) ?>">
          <h3><?= e($tema['nav']) ?></h3>
          <p><?= e($tema['extracto']) ?></p>
          <span>Ver el concepto</span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section band-white" id="paises" data-reveal>
    <h2>El mismo control, con el documento de tu país.</h2>
    <p class="intro">Abres la empresa y el formulario pide el dato de esa autoridad: RUT, NIT, RFC o RUC. El equipo no traduce el gasto a mano.</p>
    <div class="country-grid">
      <?php foreach (web_landings() as $landing): ?>
        <a class="country-card" href="<?= e(web_href($landing['slug'])) ?>">
          <span class="code"><?= e($landing['codigo']) ?></span>
          <strong><?= e($landing['nombre']) ?></strong>
          <span class="meta"><?= e($landing['frase']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="section" id="flujo" data-reveal>
    <h2>El comprobante entra. El equipo lo cierra.</h2>
    <p class="intro">Quien gastó deja el respaldo en la cuenta. Quien aprueba ve un informe, no fotos sueltas en el chat.</p>
    <div class="steps">
      <article class="step"><em>01</em><h3>Sube el gasto</h3><p>Foto, monto y categoría. Se acabó reenviar la boleta por el chat.</p></article>
      <article class="step"><em>02</em><h3>La cuenta avisa</h3><p>Duplicado, tope o dato fiscal vacío, antes de que alguien lo pague dos veces.</p></article>
      <article class="step"><em>03</em><h3>Arma el informe</h3><p>Viaje, caja o reembolso, cada uno aparte. La caja no se mezcla con el hotel.</p></article>
      <article class="step"><em>04</em><h3>Alguien aprueba</h3><p>Ve el total y las alertas. Si falta algo, lo devuelve con una nota.</p></article>
    </div>
  </section>

  <section class="pay-band" id="tarjetas" data-reveal>
    <div>
      <div class="kicker"><i>Pagos</i> Un riel posible: API de Global66</div>
      <h2>La tarjeta no reemplaza el comprobante.</h2>
      <p>Aunque se pague con tarjeta, el gasto se aprueba con el comprobante del país. Si el dinero cruza de frontera, el camino de pago que dejamos descrito es la API de Global66. En la cuenta, hoy, se rinde y se aprueba.</p>
      <a class="btn btn-citrus" href="<?= e(web_href('tarjetas')) ?>">Ver tarjetas y pagos</a>
    </div>
    <ol class="pay-steps">
      <li><strong>Paga</strong><span>Viaje, caja o compra menor.</span></li>
      <li><strong>Respalda</strong><span>Boleta, factura, CFDI o DTE.</span></li>
      <li><strong>Concilia</strong><span>El movimiento vuelve al informe.</span></li>
    </ol>
  </section>

  <section class="roles-stage" data-reveal>
    <div class="roles-copy">
      <h2>Tres perfiles. Una empresa.</h2>
      <p>No es el mismo trabajo. Quien gastó no se aprueba solo, y quien cuida la caja no caza boletas por el chat.</p>
    </div>
    <div class="role-grid">
      <article class="role">
        <em>01</em>
        <span class="chip chip-sand">Rinde</span>
        <h3>Colaborador</h3>
        <p>Es quien puso el gasto: el viaje, la caja o plata de su bolsillo. Sube la foto y manda un informe, en vez de reenviar comprobantes sueltos.</p>
      </article>
      <article class="role">
        <em>02</em>
        <span class="chip">Aprueba</span>
        <h3>Aprobador</h3>
        <p>Es quien decide si la empresa paga. Ve el total, el comprobante y si hay duplicado o un monto fuera de tope.</p>
      </article>
      <article class="role">
        <em>03</em>
        <span class="chip chip-ok">Administra</span>
        <h3>Administrador</h3>
        <p>Es quien fija la regla: el equipo, los topes en la moneda del país y el documento que se exige. La política queda en la cuenta.</p>
      </article>
    </div>
  </section>

  <section class="section" id="guias" data-reveal>
    <div class="guides-head">
      <h2>Guías con el nombre de cada país.</h2>
      <a href="<?= e(web_href('blog')) ?>">Ver todas las guías</a>
    </div>
    <div class="guide-mosaic">
      <?php foreach (web_articulos_recientes(3) as $art): ?>
        <?php render('web/post_card', ['art' => $art]); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="faq-stage" data-reveal>
    <div class="faq-copy">
      <h2>¿Te cuadra para tu empresa?</h2>
      <p>Cinco respuestas cortas. Si cierran, el siguiente paso es abrir la cuenta.</p>
      <?php if (current_user()): ?>
        <a class="btn btn-citrus" href="<?= e(url('panel.php')) ?>">Ir al panel</a>
      <?php else: ?>
        <a class="btn btn-citrus" href="<?= e(url('registro.php')) ?>">Crear empresa</a>
      <?php endif; ?>
    </div>
    <?php render('web/faq', ['faq' => web_home_faq()]); ?>
  </section>

  <?php render('web/cta'); ?>
</main>
