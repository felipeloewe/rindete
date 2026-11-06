<?php
require __DIR__ . '/includes/init.php';

$empresa = factura_empresa_por_token($_GET['t'] ?? '');
if (!$empresa) {
    http_response_code(404);
    page_open('Enlace no disponible', ['css/base.css', 'css/app.css'], 'onb-body');
    echo '<main class="auth-main" id="contenido"><div class="auth-card onb-card"><h1>Este enlace ya no está activo.</h1><p class="hint">Pide a la empresa que te envíe el onboarding de nuevo.</p></div></main>';
    page_close('js/app.js');
    exit;
}

if ($empresa['envio_id'] !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST' && $empresa['envio_estado'] === 'enviado') {
    factura_envio_abrir($empresa['envio_id']);
    $empresa['envio_estado'] = 'abierto';
}

$fiscal = pais($empresa['pais']);
$documento = $fiscal['id_fiscal'];
$campos = factura_campos($empresa['id']);
$pedidos = factura_pedidos($empresa['id'], true);
$errores = [];
$old = [];
foreach (array_keys(factura_campos_nombres()) as $claveInicial) {
    $old[$claveInicial] = '';
}
$old['pais_cliente'] = $empresa['pais'];
$old['email'] = $empresa['envio_email'];
$old['comercial_nombre'] = $empresa['envio_nombre'];
$old['comercial_email'] = $empresa['envio_email'];
$lista = isset($_GET['listo']);
$folio = strtoupper((string) ($_GET['folio'] ?? ''));
if (!preg_match('/^[A-Z0-9]{4}-[A-Z0-9]{4}$/', $folio)) {
    $folio = '';
}
$via = (string) ($_POST['via'] ?? 'formulario');
if (!in_array($via, ['formulario', 'archivo'], true)) {
    $via = 'formulario';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$lista) {
    csrf_check();
    if (trim((string) ($_POST['sitio'] ?? '')) !== '') {
        redirect('pide.php?t=' . rawurlencode($empresa['token']) . '&listo=1');
    }
    $porGuardar = [];
    if ($via === 'archivo') {
        $subido = $_FILES['documento_fiscal'] ?? null;
        $archivo = (is_array($subido) && ($subido['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) ? $subido : null;
        if (!$archivo) {
            $errores[] = 'Sube el documento fiscal.';
        } else {
            $revision = factura_revisar_archivo('El documento fiscal', $archivo);
            if (!$revision['ok']) {
                $errores[] = $revision['error'];
            } else {
                $porGuardar[] = ['Documento fiscal', $archivo];
            }
        }
    } else {
    foreach ($campos as $campo) {
        $clave = $campo['clave'];
        $modo = $campo['modo'];
        $obligatorio = !empty($campo['obligatorio']);
        $nombre = $campo['nombre'];
        $largos = [
            'razon' => 160,
            'direccion' => 180,
            'email' => 160,
            'comercial_email' => 160,
            'financiero_email' => 160,
            'id_fiscal' => 40,
            'telefono' => 40,
            'pais_cliente' => 8,
        ];
        $texto = field($clave, $largos[$clave] ?? 80);
        $old[$clave] = $texto;
        $archivo = null;
        if ($modo === 'documento' || $modo === 'ambos') {
            $archivo = factura_archivo_subido('dato', $clave);
        }
        if (($modo === 'digitar' || $modo === 'ambos') && $obligatorio && $texto === '') {
            $errores[] = 'Escribe ' . $nombre . '.';
        }
        if ($clave === 'pais_cliente' && $texto !== '' && !isset(paises()[$texto])) {
            $errores[] = 'Elige el país del cliente.';
        }
        if ($clave === 'id_fiscal' && $texto !== '') {
            $codigoPais = isset(paises()[$old['pais_cliente']]) ? $old['pais_cliente'] : $empresa['pais'];
            $fiscalOk = id_fiscal_validar($codigoPais, $texto);
            if (!$fiscalOk['ok']) {
                $errores[] = $fiscalOk['error'];
            } else {
                $old['id_fiscal'] = $fiscalOk['valor'];
            }
        }
        if (in_array($clave, ['email', 'comercial_email', 'financiero_email'], true) && $texto !== '' && !email_valido($texto)) {
            $errores[] = 'El correo no es válido.';
        }
        if (($modo === 'documento' || $modo === 'ambos') && $obligatorio && !$archivo) {
            $errores[] = 'Adjunta el documento de ' . $nombre . '.';
        }
        if ($archivo) {
            $revision = factura_revisar_archivo($nombre, $archivo);
            if (!$revision['ok']) {
                $errores[] = $revision['error'];
            } else {
                $porGuardar[] = [$nombre, $archivo];
            }
        }
    }
    foreach ($pedidos as $pedido) {
        $archivo = factura_archivo_subido('adjunto', $pedido['id']);
        if (!empty($pedido['obligatorio']) && !$archivo) {
            $errores[] = 'Adjunta ' . $pedido['nombre'] . '.';
            continue;
        }
        if (!$archivo) {
            continue;
        }
        $revision = factura_revisar_archivo($pedido['nombre'], $archivo);
        if (!$revision['ok']) {
            $errores[] = $revision['error'];
            continue;
        }
        $porGuardar[] = [$pedido['nombre'], $archivo];
    }
    }
    if (!$errores) {
        $folioNuevo = factura_folio();
        $nota = '';
        if ($via === 'archivo') {
            $old['razon'] = 'Documento fiscal';
            $old['id_fiscal'] = $folioNuevo;
            $old['pais_cliente'] = $empresa['pais'];
            $old['contacto'] = '';
            $nota = 'archivo';
        }
        if ($old['razon'] === '') {
            $old['razon'] = 'Sin razón social';
        }
        if ($old['id_fiscal'] === '') {
            $old['id_fiscal'] = $folioNuevo;
        }
        if ($old['pais_cliente'] === '' || !isset(paises()[$old['pais_cliente']])) {
            $old['pais_cliente'] = $empresa['pais'];
        }
        if ($old['email'] === '' && $empresa['envio_email'] !== '') {
            $old['email'] = $empresa['envio_email'];
        }
        $cliente = factura_cliente($empresa['id'], $old);
        $solicitud = factura_pedir($empresa['id'], $cliente, $old['contacto'], $nota, $folioNuevo, $empresa['envio_id']);
        foreach ($porGuardar as $item) {
            $guardado = factura_guardar_archivo($empresa['id'], $solicitud, $item[0], $item[1]);
            if (!$guardado['ok']) {
                $errores[] = $guardado['error'];
            }
        }
        if (!$errores) {
            if ($empresa['envio_id'] !== '') {
                factura_envio_completar($empresa['envio_id'], $solicitud, $folioNuevo);
            }
            $extraVia = $via === 'archivo' ? '&via=archivo' : '';
            redirect('pide.php?t=' . rawurlencode($empresa['token']) . '&listo=1&folio=' . rawurlencode($folioNuevo) . $extraVia);
        }
    }
}

if ($lista && (string) ($_GET['via'] ?? '') === 'archivo') {
    $via = 'archivo';
}

page_open('Datos para facturar', ['css/base.css', 'css/app.css'], 'onb-body');
render('app/pide', [
    'empresa' => $empresa,
    'fiscal' => $fiscal,
    'documento' => $documento,
    'old' => $old,
    'errores' => $errores,
    'lista' => $lista,
    'folio' => $folio,
    'campos' => $campos,
    'pedidos' => $pedidos,
    'via' => $via,
]);
page_close('js/app.js');
