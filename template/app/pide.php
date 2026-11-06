<main class="auth-main" id="contenido">
  <div class="auth-card onb-card">
    <p class="kicker"><i><?= e($fiscal['sigla']) ?></i> <?= e($empresa['nombre']) ?></p>
    <?php if ($lista): ?>
      <h1><?= ($via ?? '') === 'archivo' ? 'Recibimos el archivo.' : 'Recibimos tus datos.' ?></h1>
      <p><?= e($empresa['nombre']) ?> ya puede facturarte.</p>
      <?php if ($folio !== ''): ?>
        <div class="folio-box">
          <span>Número para confirmar</span>
          <strong><?= e($folio) ?></strong>
        </div>
        <p class="hint">Guárdalo. Con ese número la empresa confirma que enviaste la ficha.</p>
      <?php endif; ?>
      <p class="hint">PideTuFactura solo entregó esta ficha. La factura la emite <?= e($empresa['nombre']) ?>.</p>
    <?php else: ?>
      <h1>Datos para facturar</h1>
      <p class="onb-lead">Completa los datos o sube el documento fiscal si ya lo tienes.</p>
      <?php if ($errores): ?>
        <div class="flash flash-error">
          <?php foreach ($errores as $error): ?>
            <div><?= e($error) ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="onb-form">
        <?= csrf_field() ?>
        <label class="hp">Sitio web
          <input type="text" name="sitio" tabindex="-1" autocomplete="off">
        </label>
        <div class="via-switch">
          <label class="via-opt">
            <input type="radio" name="via" value="formulario" <?= ($via ?? 'formulario') !== 'archivo' ? 'checked' : '' ?>>
            Datos
          </label>
          <label class="via-opt">
            <input type="radio" name="via" value="archivo" <?= ($via ?? '') === 'archivo' ? 'checked' : '' ?>>
            Archivo
          </label>
        </div>
        <div id="bloque-archivo" <?= ($via ?? '') === 'archivo' ? '' : 'hidden' ?>>
          <label class="file-drop">
            <input id="documento-fiscal" type="file" name="documento_fiscal" accept=".pdf,.jpg,.jpeg,.png,.webp">
            <strong>Documento fiscal</strong>
            <span>RUT, NIT, RFC o RUC. PDF o imagen, máximo 5 MB.</span>
            <em id="documento-nombre">Ningún archivo seleccionado</em>
          </label>
        </div>
        <div id="bloque-formulario" <?= ($via ?? '') === 'archivo' ? 'hidden' : '' ?>>
        <?php
          $mapaFiscal = [];
          foreach (paises() as $codigoPais => $infoPais) {
              $mapaFiscal[$codigoPais] = ['nombre' => $infoPais['id_fiscal'], 'ejemplo' => $infoPais['ejemplo'], 'pais' => $infoPais['nombre']];
          }
          $paisElegido = isset($mapaFiscal[$old['pais_cliente']]) ? $old['pais_cliente'] : $empresa['pais'];
          $fiscalElegido = $mapaFiscal[$paisElegido];
        ?>
        <div class="onb-sec">
          <?php foreach ($campos as $campo): ?>
            <?php
              $clave = $campo['clave'];
              $modo = $campo['modo'];
              $obligatorio = !empty($campo['obligatorio']);
              $nombre = $clave === 'id_fiscal' ? $fiscalElegido['nombre'] : $campo['nombre'];
              $marca = $obligatorio ? '' : ' (opcional)';
            ?>
            <?php if ($modo === 'digitar' || $modo === 'ambos'): ?>
              <label class="field">
                <span<?php if ($clave === 'id_fiscal'): ?> id="fiscal-label"<?php endif; ?>><?= e($nombre) ?><?= e($marca) ?></span>
                <?php if ($clave === 'pais_cliente'): ?>
                  <select name="pais_cliente" id="pais-cliente" data-fiscal="<?= e(json_encode($mapaFiscal, JSON_UNESCAPED_UNICODE)) ?>" <?= $obligatorio ? 'required' : '' ?>>
                    <?php foreach (paises() as $codigoPais => $infoPais): ?>
                      <option value="<?= e($codigoPais) ?>" <?= $paisElegido === $codigoPais ? 'selected' : '' ?>><?= e($infoPais['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                <?php elseif (in_array($clave, ['email', 'comercial_email', 'financiero_email'], true)): ?>
                  <input type="email" name="<?= e($clave) ?>" maxlength="160" autocomplete="email" value="<?= e($old[$clave] ?? '') ?>" <?= $obligatorio ? 'required' : '' ?>>
                <?php elseif ($clave === 'telefono'): ?>
                  <input type="tel" name="telefono" maxlength="40" autocomplete="tel" value="<?= e($old['telefono']) ?>" <?= $obligatorio ? 'required' : '' ?>>
                <?php elseif ($clave === 'id_fiscal'): ?>
                  <input type="text" name="id_fiscal" id="fiscal-input" maxlength="40" value="<?= e($old['id_fiscal']) ?>" placeholder="<?= e($fiscalElegido['ejemplo']) ?>" <?= $obligatorio ? 'required' : '' ?>>
                <?php else: ?>
                  <input type="text" name="<?= e($clave) ?>" maxlength="<?= $clave === 'razon' ? 160 : ($clave === 'direccion' ? 180 : 80) ?>" value="<?= e($old[$clave] ?? '') ?>" <?= $obligatorio ? 'required' : '' ?>>
                <?php endif; ?>
              </label>
            <?php endif; ?>
            <?php if ($modo === 'documento' || $modo === 'ambos'): ?>
              <label class="field">
                <span>Documento de <?= e($nombre) ?><?= $obligatorio && $modo === 'documento' ? '' : ' (opcional)' ?></span>
                <input type="file" name="dato[<?= e($clave) ?>]" accept=".pdf,.jpg,.jpeg,.png,.webp" <?= $obligatorio && $modo === 'documento' ? 'required' : '' ?>>
              </label>
            <?php endif; ?>
          <?php endforeach; ?>
          <p class="hint">El número fiscal sigue el país del cliente.</p>
        </div>
        <?php if ($pedidos): ?>
          <section class="onb-sec">
            <h2>Archivos</h2>
            <p class="hint">JPG, PNG, WEBP o PDF. Máximo 5 MB por archivo.</p>
            <?php foreach ($pedidos as $pedido): ?>
              <label class="field">
                <span><?= e($pedido['nombre']) ?><?= !empty($pedido['obligatorio']) ? '' : ' (opcional)' ?></span>
                <?php if (!empty($pedido['formato'])): ?>
                  <a href="<?= e(url($pedido['formato'])) ?>">Descargar formato</a>
                <?php endif; ?>
                <input type="file" name="adjunto[<?= e($pedido['id']) ?>]" accept=".pdf,.jpg,.jpeg,.png,.webp" <?= !empty($pedido['obligatorio']) ? 'required' : '' ?>>
              </label>
            <?php endforeach; ?>
          </section>
        <?php endif; ?>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Enviar</button>
      </form>
      <script>
        const vias = document.querySelectorAll('input[name="via"]');
        const bloqueFormulario = document.getElementById('bloque-formulario');
        const bloqueArchivo = document.getElementById('bloque-archivo');
        const syncVia = function () {
          const elegido = document.querySelector('input[name="via"]:checked');
          const archivo = elegido && elegido.value === 'archivo';
          if (!bloqueFormulario || !bloqueArchivo) return;
          bloqueFormulario.hidden = archivo;
          bloqueArchivo.hidden = !archivo;
          bloqueFormulario.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = archivo; });
          bloqueArchivo.querySelectorAll('input, select, textarea').forEach(function (el) { el.disabled = !archivo; });
        };
        vias.forEach(function (el) { el.addEventListener('change', syncVia); });
        if (location.hash === '#archivo') {
          const radioArchivo = document.querySelector('input[name="via"][value="archivo"]');
          if (radioArchivo) radioArchivo.checked = true;
        }
        syncVia();
        const documentoFiscal = document.getElementById('documento-fiscal');
        const documentoNombre = document.getElementById('documento-nombre');
        if (documentoFiscal && documentoNombre) {
          documentoFiscal.addEventListener('change', function () {
            const elegido = documentoFiscal.files && documentoFiscal.files[0];
            documentoNombre.textContent = elegido ? elegido.name : 'Ningún archivo seleccionado';
          });
        }
        const paisCliente = document.getElementById('pais-cliente');
        const fiscalLabel = document.getElementById('fiscal-label');
        const fiscalInput = document.getElementById('fiscal-input');
        if (paisCliente && fiscalLabel && fiscalInput) {
          const mapa = JSON.parse(paisCliente.dataset.fiscal || '{}');
          const syncFiscal = function () {
            const info = mapa[paisCliente.value];
            if (!info) return;
            fiscalLabel.textContent = info.nombre;
            fiscalInput.placeholder = info.ejemplo;
          };
          paisCliente.addEventListener('change', syncFiscal);
        }
      </script>
    <?php endif; ?>
  </div>
</main>
