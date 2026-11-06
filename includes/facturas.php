<?php

function factura_clave($valor) {
    $valor = trim((string) $valor);
    $valor = preg_replace('/[\s.]+/u', '', $valor);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($valor, 'UTF-8');
    }
    return strtoupper($valor);
}

function factura_estado_label($estado) {
    if ($estado === 'vista') {
        return 'Archivada';
    }
    return 'Nueva';
}

function factura_es_abierta($estado) {
    return in_array($estado, ['nueva', 'recibida'], true);
}

function factura_url_publica($token) {
    return absolute_url('pide.php?t=' . $token);
}

function factura_folio() {
    $abc = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $codigo = '';
    $tope = strlen($abc) - 1;
    for ($i = 0; $i < 8; $i++) {
        $codigo .= $abc[random_int(0, $tope)];
    }
    return substr($codigo, 0, 4) . '-' . substr($codigo, 4);
}

function factura_token_libre() {
    $pdo = db();
    $enlace = $pdo->prepare('SELECT company_id FROM invoice_links WHERE token = ?');
    $envio = $pdo->prepare('SELECT id FROM invoice_sends WHERE token = ?');
    do {
        $token = bin2hex(random_bytes(8));
        $enlace->execute([$token]);
        $envio->execute([$token]);
    } while ($enlace->fetchColumn() || $envio->fetchColumn());
    return $token;
}

function factura_enlace($companyId) {
    $pdo = db();
    $buscar = $pdo->prepare('SELECT token FROM invoice_links WHERE company_id = ?');
    $buscar->execute([$companyId]);
    $token = $buscar->fetchColumn();
    if ($token) {
        return (string) $token;
    }
    $token = factura_token_libre();
    $insertar = $pdo->prepare('INSERT INTO invoice_links (company_id, token, creado) VALUES (?,?,?)');
    $insertar->execute([$companyId, $token, date('Y-m-d H:i:s')]);
    return $token;
}

function factura_empresa_por_token($token) {
    $token = strtolower(trim((string) $token));
    if (!preg_match('/^[a-f0-9]{16}$/', $token)) {
        return null;
    }
    $pdo = db();
    $envio = $pdo->prepare('SELECT id, company_id, email, nombre, estado FROM invoice_sends WHERE token = ?');
    $envio->execute([$token]);
    $send = $envio->fetch();
    $companyId = $send['company_id'] ?? '';
    if ($companyId === '') {
        $buscar = $pdo->prepare('SELECT company_id FROM invoice_links WHERE token = ?');
        $buscar->execute([$token]);
        $companyId = (string) $buscar->fetchColumn();
    }
    if ($companyId === '') {
        return null;
    }
    $empresa = $pdo->prepare('SELECT id, nombre, pais FROM companies WHERE id = ?');
    $empresa->execute([$companyId]);
    $fila = $empresa->fetch();
    if (!$fila) {
        return null;
    }
    $fila['token'] = $token;
    $fila['envio_id'] = (string) ($send['id'] ?? '');
    $fila['envio_email'] = (string) ($send['email'] ?? '');
    $fila['envio_nombre'] = (string) ($send['nombre'] ?? '');
    $fila['envio_estado'] = (string) ($send['estado'] ?? '');
    return $fila;
}

function factura_cliente($companyId, array $datos) {
    $pdo = db();
    $paisCliente = (string) ($datos['pais_cliente'] ?? '');
    $documento = (string) ($datos['id_fiscal'] ?? '');
    $clave = $paisCliente . '|' . factura_clave($documento);
    $valores = [
        $datos['razon'] ?? '',
        $documento,
        $datos['email'] ?? '',
        $datos['telefono'] ?? '',
        $datos['contacto'] ?? '',
        $paisCliente,
        $datos['direccion'] ?? '',
        $datos['ciudad'] ?? '',
        $datos['comercial_nombre'] ?? '',
        $datos['comercial_email'] ?? '',
        $datos['financiero_nombre'] ?? '',
        $datos['financiero_email'] ?? '',
    ];
    $buscar = $pdo->prepare('SELECT id FROM invoice_clients WHERE company_id = ? AND id_fiscal_key = ? LIMIT 1');
    $buscar->execute([$companyId, $clave]);
    $id = $buscar->fetchColumn();
    if ($id) {
        $actualizar = $pdo->prepare('UPDATE invoice_clients SET razon = ?, id_fiscal = ?, email = ?, telefono = ?, contacto = ?, pais_cliente = ?, direccion = ?, ciudad = ?, comercial_nombre = ?, comercial_email = ?, financiero_nombre = ?, financiero_email = ? WHERE id = ? AND company_id = ?');
        $actualizar->execute(array_merge($valores, [$id, $companyId]));
        return (string) $id;
    }
    $id = 'cli_' . bin2hex(random_bytes(6));
    $insertar = $pdo->prepare('INSERT INTO invoice_clients (id, company_id, razon, id_fiscal, id_fiscal_key, email, telefono, contacto, pais_cliente, direccion, ciudad, comercial_nombre, comercial_email, financiero_nombre, financiero_email, creado) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $insertar->execute(array_merge([$id, $companyId], array_slice($valores, 0, 2), [$clave], array_slice($valores, 2), [date('Y-m-d H:i:s')]));
    return $id;
}

function factura_pedir($companyId, $clientId, $contacto, $nota, $folio = '', $sendId = '') {
    $id = 'sol_' . bin2hex(random_bytes(6));
    $ahora = date('Y-m-d H:i:s');
    $insertar = db()->prepare('INSERT INTO invoice_requests (id, company_id, client_id, contacto, nota, estado, codigo_fiscal, folio, send_id, creado, actualizado) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $insertar->execute([$id, $companyId, $clientId, $contacto, $nota, 'nueva', '', $folio, $sendId, $ahora, $ahora]);
    return $id;
}

function factura_solicitudes($companyId) {
    $consulta = db()->prepare('SELECT r.id, r.client_id, r.contacto, r.nota, r.estado, r.codigo_fiscal, r.folio, r.send_id, r.creado, c.razon, c.id_fiscal, c.email, c.telefono, c.pais_cliente, c.direccion, c.ciudad, c.comercial_nombre, c.comercial_email, c.financiero_nombre, c.financiero_email FROM invoice_requests r INNER JOIN invoice_clients c ON c.id = r.client_id WHERE r.company_id = ? ORDER BY r.creado DESC');
    $consulta->execute([$companyId]);
    return $consulta->fetchAll();
}

function factura_clientes($companyId) {
    $consulta = db()->prepare('SELECT id, razon, id_fiscal, email, telefono, contacto, pais_cliente, direccion, ciudad, comercial_nombre, comercial_email, financiero_nombre, financiero_email, creado FROM invoice_clients WHERE company_id = ? ORDER BY creado DESC');
    $consulta->execute([$companyId]);
    return $consulta->fetchAll();
}

function facturas_pendientes($companyId) {
    $consulta = db()->prepare("SELECT COUNT(*) FROM invoice_requests WHERE company_id = ? AND estado IN ('nueva', 'recibida')");
    $consulta->execute([$companyId]);
    return (int) $consulta->fetchColumn();
}

function factura_pedidos($companyId, $soloActivos = false) {
    $sql = 'SELECT id, nombre, obligatorio, formato, orden, activo FROM invoice_asks WHERE company_id = ?';
    if ($soloActivos) {
        $sql .= ' AND activo = 1';
    }
    $sql .= ' ORDER BY orden, creado, nombre';
    $consulta = db()->prepare($sql);
    $consulta->execute([$companyId]);
    return $consulta->fetchAll();
}

function factura_agregar_pedido($companyId, $nombre, $obligatorio, $formato = '') {
    $nombre = trim($nombre);
    if ($nombre === '') {
        return false;
    }
    $id = 'ask_' . bin2hex(random_bytes(4));
    $maximo = db()->prepare('SELECT COALESCE(MAX(orden), 0) FROM invoice_asks WHERE company_id = ?');
    $maximo->execute([$companyId]);
    $orden = (int) $maximo->fetchColumn() + 1;
    $consulta = db()->prepare('INSERT INTO invoice_asks (id, company_id, nombre, obligatorio, formato, orden, activo, creado) VALUES (?,?,?,?,?,?,?,?)');
    $consulta->execute([$id, $companyId, $nombre, $obligatorio ? 1 : 0, $formato, $orden, 1, date('Y-m-d H:i:s')]);
    return true;
}

function factura_adjuntos_guardar($companyId, array $orden, array $nombres, array $activos, array $obligatorios) {
    $pdo = db();
    $conocer = $pdo->prepare('SELECT id FROM invoice_asks WHERE company_id = ?');
    $conocer->execute([$companyId]);
    $validos = [];
    foreach ($conocer->fetchAll() as $fila) {
        $validos[$fila['id']] = true;
    }
    $actualizar = $pdo->prepare('UPDATE invoice_asks SET nombre = ?, obligatorio = ?, orden = ?, activo = ? WHERE id = ? AND company_id = ?');
    $vistos = [];
    $pos = 0;
    foreach ($orden as $id) {
        $id = (string) $id;
        if (!isset($validos[$id]) || isset($vistos[$id])) {
            continue;
        }
        $vistos[$id] = true;
        $pos++;
        $nombre = trim((string) ($nombres[$id] ?? ''));
        if ($nombre === '') {
            $nombre = 'Adjunto';
        }
        if (mb_strlen($nombre) > 120) {
            $nombre = mb_substr($nombre, 0, 120);
        }
        $actualizar->execute([
            $nombre,
            !empty($obligatorios[$id]) ? 1 : 0,
            $pos,
            !empty($activos[$id]) ? 1 : 0,
            $id,
            $companyId,
        ]);
    }
    return true;
}

function factura_quitar_pedido($companyId, $id) {
    $pdo = db();
    $buscar = $pdo->prepare('SELECT formato FROM invoice_asks WHERE id = ? AND company_id = ?');
    $buscar->execute([$id, $companyId]);
    $formato = (string) $buscar->fetchColumn();
    $consulta = $pdo->prepare('DELETE FROM invoice_asks WHERE id = ? AND company_id = ?');
    $consulta->execute([$id, $companyId]);
    if ($consulta->rowCount() > 0 && $formato !== '' && is_file(ROOT . '/' . $formato)) {
        unlink(ROOT . '/' . $formato);
    }
    return $consulta->rowCount() > 0;
}

function factura_guardar_formato($companyId, array $archivo) {
    $revision = factura_revisar_archivo('El formato', $archivo);
    if (!$revision['ok']) {
        return $revision;
    }
    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => ''];
    }
    $ext = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    $dir = ROOT . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $nombre = 'fmt_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el formato.'];
    }
    return ['ok' => true, 'path' => 'uploads/' . $nombre];
}

function factura_campos_nombres() {
    return [
        'razon' => 'Razón social',
        'pais_cliente' => 'País',
        'id_fiscal' => 'Número fiscal',
        'direccion' => 'Dirección',
        'ciudad' => 'Ciudad',
        'email' => 'Correo para la factura',
        'telefono' => 'Teléfono',
        'comercial_nombre' => 'Contacto comercial',
        'comercial_email' => 'Correo del contacto comercial',
        'financiero_nombre' => 'Contacto financiero',
        'financiero_email' => 'Correo del contacto financiero',
        'contacto' => 'Quién pide',
    ];
}

function factura_campo_es_obligatorio($clave) {
    return $clave !== 'contacto';
}

function factura_campos($companyId) {
    $pdo = db();
    $consulta = $pdo->prepare('SELECT clave, modo, obligatorio, orden FROM invoice_fields WHERE company_id = ?');
    $consulta->execute([$companyId]);
    $guardados = [];
    foreach ($consulta->fetchAll() as $fila) {
        $guardados[$fila['clave']] = $fila;
    }
    $insertar = $pdo->prepare('INSERT INTO invoice_fields (company_id, clave, modo, obligatorio, orden) VALUES (?,?,?,?,?)');
    $catalogo = factura_campos_nombres();
    $posicion = 0;
    foreach ($catalogo as $clave => $nombre) {
        $posicion++;
        if (!isset($guardados[$clave])) {
            $obligatorio = factura_campo_es_obligatorio($clave) ? 1 : 0;
            $insertar->execute([$companyId, $clave, 'digitar', $obligatorio, $posicion]);
            $guardados[$clave] = ['clave' => $clave, 'modo' => 'digitar', 'obligatorio' => $obligatorio, 'orden' => $posicion];
        }
        $guardados[$clave]['nombre'] = $nombre;
    }
    $lista = [];
    foreach ($catalogo as $clave => $nombre) {
        if (isset($guardados[$clave])) {
            $lista[] = $guardados[$clave];
        }
    }
    usort($lista, function ($a, $b) use ($catalogo) {
        $ordenA = (int) ($a['orden'] ?? 0);
        $ordenB = (int) ($b['orden'] ?? 0);
        if ($ordenA === $ordenB) {
            return array_search($a['clave'], array_keys($catalogo), true) <=> array_search($b['clave'], array_keys($catalogo), true);
        }
        if ($ordenA === 0) {
            return 1;
        }
        if ($ordenB === 0) {
            return -1;
        }
        return $ordenA <=> $ordenB;
    });
    $sinOrden = true;
    foreach ($lista as $campo) {
        if ((int) ($campo['orden'] ?? 0) > 0) {
            $sinOrden = false;
            break;
        }
    }
    if ($sinOrden) {
        $poner = $pdo->prepare('UPDATE invoice_fields SET orden = ? WHERE company_id = ? AND clave = ?');
        $posicion = 0;
        foreach ($lista as $i => $campo) {
            $posicion++;
            $poner->execute([$posicion, $companyId, $campo['clave']]);
            $lista[$i]['orden'] = $posicion;
        }
    }
    return $lista;
}

function factura_campos_guardar($companyId, array $orden, array $modos, array $obligatorios) {
    factura_campos($companyId);
    $nombres = factura_campos_nombres();
    $vistos = [];
    $posicion = 0;
    $actualizar = db()->prepare('UPDATE invoice_fields SET modo = ?, obligatorio = ?, orden = ? WHERE company_id = ? AND clave = ?');
    $aplicar = function ($clave) use (&$vistos, &$posicion, $modos, $obligatorios, $actualizar, $companyId, $nombres) {
        if (!isset($nombres[$clave]) || isset($vistos[$clave])) {
            return;
        }
        $vistos[$clave] = true;
        $posicion++;
        $modo = (string) ($modos[$clave] ?? 'digitar');
        if (!in_array($modo, ['digitar', 'documento', 'ambos'], true)) {
            $modo = 'digitar';
        }
        $actualizar->execute([$modo, !empty($obligatorios[$clave]) ? 1 : 0, $posicion, $companyId, $clave]);
    };
    foreach ($orden as $clave) {
        $aplicar((string) $clave);
    }
    foreach (array_keys($nombres) as $clave) {
        $aplicar($clave);
    }
    return true;
}

function factura_envio_estado_label($estado) {
    $map = [
        'enviado' => 'Enviado',
        'abierto' => 'Abierto',
        'completado' => 'Completado',
        'error' => 'No salió',
    ];
    return $map[$estado] ?? 'Enviado';
}

function factura_envios($companyId) {
    $consulta = db()->prepare('SELECT id, email, nombre, token, estado, request_id, folio, creado, abierto, completado FROM invoice_sends WHERE company_id = ? ORDER BY creado DESC');
    $consulta->execute([$companyId]);
    return $consulta->fetchAll();
}

function factura_envio_crear($companyId, $email, $nombre) {
    $id = 'env_' . bin2hex(random_bytes(6));
    $token = factura_token_libre();
    $consulta = db()->prepare('INSERT INTO invoice_sends (id, company_id, email, nombre, token, estado, request_id, folio, creado, abierto, completado) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $consulta->execute([$id, $companyId, $email, $nombre, $token, 'enviado', '', '', date('Y-m-d H:i:s'), null, null]);
    return ['id' => $id, 'token' => $token];
}

function factura_envio_por_id($companyId, $id) {
    $consulta = db()->prepare('SELECT id, email, nombre, token, estado FROM invoice_sends WHERE id = ? AND company_id = ?');
    $consulta->execute([$id, $companyId]);
    $fila = $consulta->fetch();
    return $fila ?: null;
}

function factura_envio_marcar($id, $estado) {
    if ($estado === 'error') {
        $consulta = db()->prepare("UPDATE invoice_sends SET estado = 'error' WHERE id = ? AND estado = 'enviado'");
        $consulta->execute([$id]);
        return;
    }
    if ($estado === 'enviado') {
        $consulta = db()->prepare("UPDATE invoice_sends SET estado = 'enviado' WHERE id = ? AND estado = 'error'");
        $consulta->execute([$id]);
    }
}

function factura_envio_abrir($id) {
    $consulta = db()->prepare("UPDATE invoice_sends SET estado = 'abierto', abierto = ? WHERE id = ? AND estado = 'enviado'");
    $consulta->execute([date('Y-m-d H:i:s'), $id]);
}

function factura_envio_completar($id, $requestId, $folio) {
    $consulta = db()->prepare("UPDATE invoice_sends SET estado = 'completado', request_id = ?, folio = ?, completado = ? WHERE id = ?");
    $consulta->execute([$requestId, $folio, date('Y-m-d H:i:s'), $id]);
}

function factura_archivo_subido($campo, $id) {
    if (!isset($_FILES[$campo]['name'][$id]) || $_FILES[$campo]['name'][$id] === '') {
        return null;
    }
    return [
        'name' => $_FILES[$campo]['name'][$id],
        'tmp_name' => $_FILES[$campo]['tmp_name'][$id],
        'error' => $_FILES[$campo]['error'][$id],
        'size' => $_FILES[$campo]['size'][$id],
    ];
}

function factura_revisar_archivo($etiqueta, array $archivo) {
    if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true];
    }
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'No se pudo subir ' . $etiqueta . '.'];
    }
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => $etiqueta . ' supera 5 MB.'];
    }
    $ext = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    $map = ['jpg' => true, 'jpeg' => true, 'png' => true, 'webp' => true, 'pdf' => true];
    if (!isset($map[$ext])) {
        return ['ok' => false, 'error' => $etiqueta . ' debe ser JPG, PNG, WEBP o PDF.'];
    }
    return ['ok' => true];
}

function factura_guardar_archivo($companyId, $requestId, $etiqueta, array $archivo) {
    $revision = factura_revisar_archivo($etiqueta, $archivo);
    if (!$revision['ok'] || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return $revision;
    }
    $ext = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    $dir = ROOT . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $nombre = 'onb_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($archivo['tmp_name'], $dir . '/' . $nombre)) {
        return ['ok' => false, 'error' => 'No se pudo guardar ' . $etiqueta . '.'];
    }
    $id = 'fil_' . bin2hex(random_bytes(4));
    $consulta = db()->prepare('INSERT INTO invoice_files (id, company_id, request_id, nombre, path) VALUES (?,?,?,?,?)');
    $consulta->execute([$id, $companyId, $requestId, $etiqueta, 'uploads/' . $nombre]);
    return ['ok' => true];
}

function factura_archivos($companyId) {
    $consulta = db()->prepare('SELECT request_id, nombre, path FROM invoice_files WHERE company_id = ?');
    $consulta->execute([$companyId]);
    $grupos = [];
    foreach ($consulta->fetchAll() as $fila) {
        $grupos[$fila['request_id']][] = $fila;
    }
    return $grupos;
}

function factura_marcar($companyId, $id, $estado, $codigo) {
    if (!in_array($estado, ['vista', 'recibida', 'en_emision', 'emitida'], true)) {
        return false;
    }
    $consulta = db()->prepare('UPDATE invoice_requests SET estado = ?, codigo_fiscal = ?, actualizado = ? WHERE id = ? AND company_id = ?');
    $consulta->execute([$estado, $codigo, date('Y-m-d H:i:s'), $id, $companyId]);
    return $consulta->rowCount() > 0;
}
