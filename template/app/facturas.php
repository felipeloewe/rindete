<?php
$nuevas = [];
$archivadas = [];
$fichas = [];
$porCerrar = 0;
foreach ($envios as $envio) {
    if (in_array($envio['estado'], ['enviado', 'abierto', 'error'], true)) {
        $porCerrar++;
    }
}
foreach ($solicitudes as $sol) {
    $fichas[$sol['client_id']] = ($fichas[$sol['client_id']] ?? 0) + 1;
    if (factura_es_abierta($sol['estado'])) {
        $nuevas[] = $sol;
    } else {
        $archivadas[] = $sol;
    }
}
?>
<section class="page-head">
  <div>
    <h1>Onboarding de clientes</h1>
    <p>Pides los datos para facturar. El cliente los manda por el enlace o por el QR. La factura la emites tú, en tu facturador. El informe de estas fichas está en Informes, pestaña Onboarding.</p>
  </div>
</section>

<section class="kpis onb-kpis">
  <article class="kpi"><span>Fichas nuevas</span><strong><?= count($nuevas) ?></strong></article>
  <article class="kpi"><span>Envíos por cerrar</span><strong><?= (int) $porCerrar ?></strong></article>
  <article class="kpi"><span>Clientes</span><strong><?= count($clientes) ?></strong></article>
</section>

<section class="split">
  <div class="panel">
    <h2>Enviar el formulario</h2>
    <p class="muted">El cliente recibe el enlace y completa el <?= e($pais['id_fiscal']) ?>, el contacto y los adjuntos.</p>
    <form method="post" class="sol-form">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="enviar">
      <label class="field">Correo del cliente
        <input type="email" name="email" required maxlength="160" placeholder="cliente@empresa.com">
      </label>
      <label class="field">Nombre
        <input type="text" name="nombre" maxlength="80" placeholder="Opcional, para el saludo">
      </label>
      <button class="btn btn-primary" type="submit">Enviar onboarding</button>
    </form>
    <?php if ($envios): ?>
      <h2>Lo enviado</h2>
      <p class="muted">El estado cambia solo: enviado, abierto cuando lo ve, completado cuando termina. Si no salió el correo, reenvías el mismo enlace.</p>
      <div class="sol-list">
        <?php foreach ($envios as $envio): ?>
          <article class="ask-row">
            <div>
              <strong><?= e($envio['email']) ?></strong>
              <span class="chip <?= $envio['estado'] === 'completado' ? 'chip-ok' : ($envio['estado'] === 'error' ? 'chip-warn' : 'chip-info') ?>"><?= e(factura_envio_estado_label($envio['estado'])) ?></span>
              <div class="muted"><?= e($envio['nombre']) ?><?= $envio['nombre'] !== '' ? ' · ' : '' ?><?= e(date('d/m/Y H:i', strtotime($envio['creado']))) ?><?= $envio['folio'] !== '' ? ' · ' . $envio['folio'] : '' ?></div>
            </div>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="accion" value="reenviar">
              <input type="hidden" name="id" value="<?= e($envio['id']) ?>">
              <button class="btn btn-ghost btn-small" type="submit">Reenviar</button>
            </form>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <h2>El mismo enlace</h2>
    <p class="muted">Sirve para el correo, para el QR y para copiarlo a mano.</p>
    <div class="link-row">
      <input id="enlace-onb" type="text" readonly value="<?= e($enlace) ?>">
      <button class="btn btn-ghost" type="button" id="copiar-enlace">Copiar</button>
    </div>
  </div>
  <div class="panel">
    <h2>QR del mesón</h2>
    <p class="muted">Imprímelo en el local. Abre este mismo formulario, no otro.</p>
    <div id="qr-factura" class="qr-box" data-qr="<?= e($enlace) ?>" data-empresa="<?= e($company['nombre']) ?>"></div>
    <button class="btn btn-ghost" type="button" id="imprimir-qr">Imprimir QR</button>
  </div>
</section>

<section class="panel" style="margin-top:1rem">
  <h2>Datos del formulario</h2>
  <p class="muted">El número de la izquierda es la posición en el formulario. Sube o baja cada dato, elige si se digita o se adjunta, y marca si es obligatorio.</p>
  <form method="post" id="form-campos">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="campos">
    <div class="cfg-list" id="lista-campos">
      <?php foreach ($campos as $i => $campo): ?>
        <div class="cfg-row">
          <span class="cfg-pos"><?= $i + 1 ?></span>
          <strong><?= e($campo['nombre']) ?></strong>
          <input type="hidden" name="orden[]" value="<?= e($campo['clave']) ?>">
          <select name="modo[<?= e($campo['clave']) ?>]">
            <option value="digitar" <?= $campo['modo'] === 'digitar' ? 'selected' : '' ?>>Digitar</option>
            <option value="documento" <?= $campo['modo'] === 'documento' ? 'selected' : '' ?>>Cargar documento</option>
            <option value="ambos" <?= $campo['modo'] === 'ambos' ? 'selected' : '' ?>>Digitar y documento</option>
          </select>
          <label class="remember"><input type="checkbox" name="obligatorio[<?= e($campo['clave']) ?>]" value="1" <?= !empty($campo['obligatorio']) ? 'checked' : '' ?>> Obligatorio</label>
          <span class="cfg-move">
            <button class="btn btn-ghost btn-small" type="button" data-move="up" aria-label="Subir">Subir</button>
            <button class="btn btn-ghost btn-small" type="button" data-move="down" aria-label="Bajar">Bajar</button>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Guardar datos</button>
  </form>
</section>

<section class="panel" style="margin-top:1rem">
  <h2>Adjuntos</h2>
  <p class="muted">Escribe el nombre que verá el cliente y actívalo. Si lo apagas, deja de salir en el formulario. También puedes marcarlo obligatorio y cambiar su posición.</p>
  <form method="post" class="cfg-activar">
    <?= csrf_field() ?>
    <input type="hidden" name="accion" value="adjunto">
    <label class="field">Nombre del adjunto
      <input type="text" name="nombre_adjunto" maxlength="120" placeholder="Cámara de comercio" required>
    </label>
    <button class="btn btn-primary" type="submit">Activar</button>
  </form>
  <?php if ($pedidos): ?>
    <form method="post" id="form-adjuntos">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="adjuntos">
      <div class="cfg-list" id="lista-adjuntos">
        <?php foreach ($pedidos as $i => $pedido): ?>
          <div class="cfg-row cfg-row-adj">
            <span class="cfg-pos"><?= $i + 1 ?></span>
            <input type="hidden" name="orden_adjunto[]" value="<?= e($pedido['id']) ?>">
            <input type="text" name="nombre_adjunto[<?= e($pedido['id']) ?>]" maxlength="120" value="<?= e($pedido['nombre']) ?>">
            <label class="remember"><input type="checkbox" name="activo[<?= e($pedido['id']) ?>]" value="1" <?= !empty($pedido['activo']) ? 'checked' : '' ?>> Activo</label>
            <label class="remember"><input type="checkbox" name="obligatorio_adjunto[<?= e($pedido['id']) ?>]" value="1" <?= !empty($pedido['obligatorio']) ? 'checked' : '' ?>> Obligatorio</label>
            <span class="cfg-move">
              <button class="btn btn-ghost btn-small" type="button" data-move="up" aria-label="Subir">Subir</button>
              <button class="btn btn-ghost btn-small" type="button" data-move="down" aria-label="Bajar">Bajar</button>
              <button class="btn btn-ghost btn-small" type="submit" form="quitar-<?= e($pedido['id']) ?>">Quitar</button>
            </span>
          </div>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary" type="submit">Guardar adjuntos</button>
    </form>
    <?php foreach ($pedidos as $pedido): ?>
      <form method="post" id="quitar-<?= e($pedido['id']) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="accion" value="quitar">
        <input type="hidden" name="id" value="<?= e($pedido['id']) ?>">
      </form>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<section class="panel" style="margin-top:1rem">
  <h2>Bandeja</h2>
  <?php if (!$nuevas): ?>
    <p class="muted">Cuando alguien complete el formulario, la ficha aparece aquí.</p>
  <?php else: ?>
    <div class="sol-list">
      <?php foreach ($nuevas as $sol): ?>
        <?php render('app/ficha', ['sol' => $sol, 'pais' => $pais, 'archivos' => $archivos[$sol['id']] ?? [], 'abierta' => true]); ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if ($archivadas): ?>
    <details class="onb-archivo">
      <summary>Archivadas (<?= count($archivadas) ?>)</summary>
      <div class="sol-list">
        <?php foreach ($archivadas as $sol): ?>
          <?php render('app/ficha', ['sol' => $sol, 'pais' => $pais, 'archivos' => $archivos[$sol['id']] ?? [], 'abierta' => false]); ?>
        <?php endforeach; ?>
      </div>
    </details>
  <?php endif; ?>
</section>

<section class="panel" style="margin-top:1rem">
  <h2>Clientes</h2>
  <p class="muted">El mismo número fiscal, en el mismo país, actualiza la ficha.</p>
  <?php if (!$clientes): ?>
    <p class="muted">Aún no hay clientes.</p>
  <?php else: ?>
    <div class="people">
      <?php foreach ($clientes as $cliente): ?>
        <div class="person">
          <div>
            <strong><?= e($cliente['razon']) ?></strong>
            <?php $paisFicha = paises()[$cliente['pais_cliente'] ?? ''] ?? $pais; ?>
            <?php if (($cliente['razon'] ?? '') === 'Documento fiscal'): ?>
              <div class="muted">Envió el archivo. No digitó el formulario.</div>
            <?php else: ?>
              <div class="muted"><?= e($paisFicha['nombre']) ?> · <?= e($paisFicha['id_fiscal']) ?> <?= e($cliente['id_fiscal']) ?><?= ($cliente['ciudad'] ?? '') !== '' ? ' · ' . e($cliente['ciudad']) : '' ?></div>
            <?php endif; ?>
          </div>
          <div class="muted"><?= e(trim($cliente['email'] . ' · ' . $cliente['telefono'] . ' · ' . ($cliente['comercial_nombre'] ?? ''), ' ·')) ?></div>
          <div class="muted"><?= (int) ($fichas[$cliente['id']] ?? 0) ?> <?= ($fichas[$cliente['id']] ?? 0) === 1 ? 'ficha' : 'fichas' ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<script src="<?= e(url('js/qrcode.min.js')) ?>"></script>
<script>
  const caja = document.getElementById('qr-factura');
  if (caja && window.QRCode) {
    new QRCode(caja, { text: caja.dataset.qr, width: 196, height: 196, correctLevel: QRCode.CorrectLevel.M });
  }
  const copiar = document.getElementById('copiar-enlace');
  const enlace = document.getElementById('enlace-onb');
  if (copiar && enlace) {
    copiar.addEventListener('click', function () {
      const hecho = function () { copiar.textContent = 'Copiado'; };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(enlace.value).then(hecho).catch(function () {
          enlace.select();
          document.execCommand('copy');
          hecho();
        });
        return;
      }
      enlace.select();
      document.execCommand('copy');
      hecho();
    });
  }
  const numerar = function (lista) {
    if (!lista) return;
    lista.querySelectorAll('.cfg-pos').forEach(function (pos, i) { pos.textContent = String(i + 1); });
  };
  document.querySelectorAll('.cfg-list').forEach(function (lista) {
    lista.addEventListener('click', function (evento) {
      const boton = evento.target.closest('[data-move]');
      if (!boton || !lista.contains(boton)) return;
      const fila = boton.closest('.cfg-row');
      if (!fila) return;
      if (boton.dataset.move === 'up' && fila.previousElementSibling) {
        lista.insertBefore(fila, fila.previousElementSibling);
      }
      if (boton.dataset.move === 'down' && fila.nextElementSibling) {
        lista.insertBefore(fila.nextElementSibling, fila);
      }
      numerar(lista);
    });
  });
  const imprimir = document.getElementById('imprimir-qr');
  if (imprimir && caja) {
    imprimir.addEventListener('click', function () {
      const canvas = caja.querySelector('canvas');
      const img = caja.querySelector('img');
      const src = canvas ? canvas.toDataURL('image/png') : (img ? img.src : '');
      const ventana = window.open('', '_blank', 'noopener,width=520,height=720');
      if (!ventana) return;
      ventana.document.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>QR</title><style>body{font-family:sans-serif;text-align:center;padding:2rem;color:#10281f}h1{font-size:1.6rem}img{width:280px;height:280px}p{word-break:break-all;color:#31443b}</style></head><body><h1></h1><img alt=""><p></p></body></html>');
      ventana.document.querySelector('h1').textContent = caja.dataset.empresa || '';
      ventana.document.querySelector('img').src = src;
      ventana.document.querySelector('p').textContent = caja.dataset.qr || '';
      ventana.document.close();
      ventana.focus();
      ventana.print();
    });
  }
</script>
