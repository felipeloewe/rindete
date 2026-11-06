<?php

function url_absoluta($path) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

function enviar_correo($destino, $nombre, $asunto, $texto, $html) {
    $config = ptf_config();
    $clave = trim((string) ($config['sendgrid_key'] ?? ''));
    $desde = trim((string) ($config['mail_from'] ?? 'no-reply@pidetufactura.com'));
    $desdeNombre = trim((string) ($config['mail_from_name'] ?? 'PideTuFactura'));
    if ($clave === '') {
        return ['ok' => false, 'error' => 'Falta la clave de SendGrid en includes/config.php.'];
    }
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'El servidor no tiene cURL para enviar correos.'];
    }
    $payload = json_encode([
        'personalizations' => [[
            'to' => [[
                'email' => $destino,
                'name' => $nombre,
            ]],
        ]],
        'from' => [
            'email' => $desde,
            'name' => $desdeNombre,
        ],
        'subject' => $asunto,
        'content' => [
            ['type' => 'text/plain', 'value' => $texto],
            ['type' => 'text/html', 'value' => $html],
        ],
    ], JSON_UNESCAPED_UNICODE);
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $clave,
            'Content-Type: application/json',
        ],
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($errno) {
        return ['ok' => false, 'error' => 'No se pudo contactar a SendGrid.'];
    }
    if ($status < 200 || $status >= 300) {
        $detalle = 'SendGrid respondió ' . $status . '.';
        $json = json_decode((string) $body, true);
        if (!empty($json['errors'][0]['message'])) {
            $detalle = $json['errors'][0]['message'];
        }
        return ['ok' => false, 'error' => $detalle];
    }
    return ['ok' => true, 'error' => ''];
}

function correo_bienvenida($nombre, $email, $clave, $empresa) {
    $mensaje = correo_mensaje_bienvenida($nombre, $email, $clave, $empresa);
    return enviar_correo($email, $nombre, $mensaje['asunto'], $mensaje['texto'], $mensaje['html']);
}

function correo_recuperacion($nombre, $email, $enlace) {
    $mensaje = correo_mensaje_recuperacion($nombre, $enlace);
    return enviar_correo($email, $nombre, $mensaje['asunto'], $mensaje['texto'], $mensaje['html']);
}

function correo_mensaje_bienvenida($nombre, $email, $clave, $empresa) {
    $usuarios = function_exists('plan_valor') ? (int) plan_valor('plan_usuarios', 3) : 3;
    $documentos = function_exists('plan_valor') ? (int) plan_valor('plan_documentos_mes', 50) : 50;
    $rendiciones = function_exists('plan_valor') ? (int) plan_valor('plan_rendiciones_usuario', 1) : 1;
    $entrada = url_absoluta('login.php');
    $asunto = 'Ya puedes entrar a PideTuFactura';
    $texto = "Hola {$nombre},\n\n"
        . "Ya tienes acceso a {$empresa} en PideTuFactura.\n\n"
        . "Correo: {$email}\n"
        . "Clave: {$clave}\n\n"
        . "Entra aquí: {$entrada}\n\n"
        . "Plan gratis: hasta {$usuarios} usuarios, {$documentos} documentos al mes y {$rendiciones} rendición por persona.\n";
    $cuerpo = correo_saludo($nombre)
        . correo_parrafo('Ya tienes acceso a <strong style="color:#15241e;">' . e($empresa) . '</strong> en PideTuFactura. Guarda estos datos para entrar.')
        . correo_datos([
            ['Correo', $email],
            ['Clave', $clave],
        ])
        . correo_boton($entrada, 'Entrar al sistema')
        . correo_enlace_plano($entrada)
        . correo_plan($usuarios, $documentos, $rendiciones);
    $html = correo_documento($asunto, 'Tu acceso a ' . $empresa . ' ya está listo.', $cuerpo);
    return ['asunto' => $asunto, 'texto' => $texto, 'html' => $html];
}

function correo_mensaje_recuperacion($nombre, $enlace) {
    $asunto = 'Elige una clave nueva en PideTuFactura';
    $texto = "Hola {$nombre},\n\n"
        . "Recibimos un pedido para cambiar tu clave. El enlace sirve durante la próxima hora:\n"
        . $enlace . "\n\n"
        . "Si no fuiste tú, ignora este mensaje. Tu clave sigue igual.\n";
    $cuerpo = correo_saludo($nombre)
        . correo_parrafo('Recibimos un pedido para cambiar tu clave. El enlace sirve durante la próxima hora. Si no fuiste tú, ignora este mensaje: tu clave sigue igual.')
        . correo_boton($enlace, 'Elegir una clave nueva')
        . correo_enlace_plano($enlace);
    $html = correo_documento($asunto, 'El enlace para cambiar tu clave sirve durante la próxima hora.', $cuerpo);
    return ['asunto' => $asunto, 'texto' => $texto, 'html' => $html];
}

function correo_documento($titulo, $preheader, $cuerpo) {
    $preheader = e($preheader);
    $titulo = e($titulo);
    return '<!DOCTYPE html>'
        . '<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">'
        . '<head>'
        . '<meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta http-equiv="X-UA-Compatible" content="IE=edge">'
        . '<meta name="x-apple-disable-message-reformatting">'
        . '<meta name="color-scheme" content="light">'
        . '<meta name="supported-color-schemes" content="light">'
        . '<title>' . $titulo . '</title>'
        . '<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->'
        . '</head>'
        . '<body style="margin:0;padding:0;background-color:#f4f0e6;color:#15241e;" bgcolor="#f4f0e6">'
        . '<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f4f0e6;">' . $preheader . '</div>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f0e6" style="background-color:#f4f0e6;">'
        . '<tr><td align="center" style="padding:28px 12px;">'
        . '<!--[if mso]><table role="presentation" align="center" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#fffcf6" style="max-width:600px;background-color:#fffcf6;">'
        . '<tr><td bgcolor="#10281f" style="background-color:#10281f;padding:28px 32px 22px 32px;">'
        . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:22px;line-height:28px;font-weight:bold;color:#e4f56a;">PideTuFactura</p>'
        . '<p style="margin:8px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:18px;color:#d5e0d8;">Legaliza, aprueba y controla</p>'
        . '</td></tr>'
        . '<tr><td bgcolor="#e4f56a" height="4" style="background-color:#e4f56a;font-size:0;line-height:0;">&nbsp;</td></tr>'
        . '<tr><td style="padding:32px 32px 8px 32px;">' . $cuerpo . '</td></tr>'
        . '<tr><td bgcolor="#f4f0e6" style="background-color:#f4f0e6;padding:18px 32px 22px 32px;">'
        . '<p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:18px;color:#5e6e66;">Este mensaje lo envió PideTuFactura desde no-reply@pidetufactura.com. No respondas a este correo.</p>'
        . '</td></tr>'
        . '</table>'
        . '<!--[if mso]></td></tr></table><![endif]-->'
        . '</td></tr></table>'
        . '</body></html>';
}

function correo_saludo($nombre) {
    return '<h1 style="margin:0 0 12px 0;font-family:Georgia,\'Times New Roman\',serif;font-size:28px;line-height:34px;font-weight:normal;color:#15241e;">Hola ' . e($nombre) . '</h1>';
}

function correo_parrafo($html) {
    return '<p style="margin:0 0 22px 0;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:24px;color:#3d4f47;">' . $html . '</p>';
}

function correo_datos($filas) {
    $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f4f0e6" style="background-color:#f4f0e6;margin:0 0 24px 0;">';
    foreach ($filas as $fila) {
        $html .= '<tr><td style="padding:14px 18px 0 18px;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:16px;font-weight:bold;letter-spacing:0.04em;text-transform:uppercase;color:#5e6e66;">' . e($fila[0]) . '</td></tr>'
            . '<tr><td style="padding:4px 18px 14px 18px;font-family:Consolas,\'Courier New\',monospace;font-size:18px;line-height:24px;color:#10281f;">' . e($fila[1]) . '</td></tr>';
    }
    return $html . '</table>';
}

function correo_plan($usuarios, $documentos, $rendiciones) {
    $textoRendicion = $rendiciones === 1 ? '1 rendición por persona' : $rendiciones . ' rendiciones por persona';
    $puntos = [
        'Hasta ' . $usuarios . ' usuarios',
        $documentos . ' documentos al mes',
        $textoRendicion,
    ];
    $html = '<p style="margin:28px 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:16px;font-weight:bold;letter-spacing:0.04em;text-transform:uppercase;color:#5e6e66;">Plan gratis</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">';
    foreach ($puntos as $punto) {
        $html .= '<tr>'
            . '<td width="22" valign="middle" style="padding:7px 10px 7px 0;">'
            . '<table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td width="8" height="8" bgcolor="#e4f56a" style="background-color:#e4f56a;font-size:0;line-height:0;">&nbsp;</td></tr></table>'
            . '</td>'
            . '<td valign="middle" style="padding:7px 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;color:#15241e;">' . e($punto) . '</td>'
            . '</tr>';
    }
    return $html . '</table>';
}

function correo_boton($url, $etiqueta) {
    $href = e($url);
    $texto = e($etiqueta);
    return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">'
        . '<tr><td align="left" style="padding:0;">'
        . '<table role="presentation" cellpadding="0" cellspacing="0" border="0">'
        . '<tr><td align="center" bgcolor="#10281f" style="background-color:#10281f;border-radius:8px;">'
        . '<!--[if mso]>'
        . '<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="' . $href . '" style="height:48px;v-text-anchor:middle;width:260px;" arcsize="14%" strokecolor="#10281f" fillcolor="#10281f">'
        . '<w:anchorlock/>'
        . '<center style="color:#fffdf9;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;">' . $texto . '</center>'
        . '</v:roundrect>'
        . '<![endif]-->'
        . '<!--[if !mso]><!-->'
        . '<a href="' . $href . '" style="display:inline-block;padding:14px 28px;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:20px;font-weight:bold;color:#fffdf9;text-decoration:none;border-radius:8px;">' . $texto . '</a>'
        . '<!--<![endif]-->'
        . '</td></tr></table>'
        . '</td></tr></table>';
}

function correo_onboarding($nombre, $email, $empresa, $enlace, $documento) {
    $asunto = $empresa . ' te pide datos para facturar';
    $texto = "Hola {$nombre},\n\n"
        . "{$empresa} necesita tus datos de facturación para emitirte el comprobante.\n"
        . "El documento que van a pedir es el {$documento}.\n\n"
        . "Completa el formulario aquí:\n{$enlace}\n\n"
        . "PideTuFactura solo recolecta estos datos. La factura la emite {$empresa}.\n";
    $cuerpo = ($nombre !== '' ? correo_saludo($nombre) : '')
        . correo_parrafo('<strong style="color:#15241e;">' . e($empresa) . '</strong> necesita tus datos para facturarte. El formulario pide el ' . e($documento) . ' y los archivos que hayan solicitado.')
        . correo_boton($enlace, 'Enviar mis datos')
        . correo_enlace_plano($enlace)
        . correo_parrafo('PideTuFactura solo recolecta esta ficha. La factura la emite ' . e($empresa) . '.');
    $html = correo_documento($asunto, $empresa . ' te pide datos para facturar.', $cuerpo);
    return enviar_correo($email, $nombre !== '' ? $nombre : $email, $asunto, $texto, $html);
}

function correo_enlace_plano($url) {
    $href = e($url);
    return '<p style="margin:16px 0 8px 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#5e6e66;clear:both;">Si el botón no abre, copia este enlace en el navegador:<br>'
        . '<a href="' . $href . '" style="color:#1f6a49;text-decoration:underline;word-break:break-all;">' . $href . '</a></p>';
}
