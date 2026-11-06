<?php

define('ROOT', dirname(__DIR__));

require ROOT . '/includes/paises.php';
require ROOT . '/includes/identidad.php';
require ROOT . '/includes/db.php';
require ROOT . '/includes/correo.php';
require ROOT . '/includes/facturas.php';

$ptfStore = null;
$ptfDirty = false;

function base_path() {
    static $base;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    if ($dir === '/' || $dir === '.' || $dir === '') {
        $base = '';
        return $base;
    }
    $dir = rtrim($dir, '/');
    $fs = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')));
    $root = str_replace('\\', '/', ROOT);
    if ($fs !== '' && stripos($fs, $root . '/') === 0) {
        $rel = substr($fs, strlen($root) + 1);
        $depth = substr_count($rel, '/') + 1;
        $parts = explode('/', trim($dir, '/'));
        if (count($parts) >= $depth) {
            $parts = array_slice($parts, 0, count($parts) - $depth);
            $base = $parts ? '/' . implode('/', $parts) : '';
            return $base;
        }
    }
    $base = $dir;
    return $base;
}

$cookiePath = base_path() === '' ? '/' : base_path();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath,
    'httponly' => true,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url($path = '') {
    $path = ltrim($path, '/');
    $base = base_path();
    if ($path === '') {
        return ($base === '' ? '/' : $base . '/');
    }
    return ($base === '' ? '' : $base) . '/' . $path;
}

function site_host() {
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    if (!preg_match('/^[a-z0-9.-]+(?::\d+)?$/', $host)) {
        return 'localhost';
    }
    return $host;
}

function absolute_url($path = '') {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    return ($https ? 'https' : 'http') . '://' . site_host() . url($path);
}

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function flash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function consume_flash() {
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check() {
    $sent = (string) ($_POST['csrf'] ?? '');
    $known = (string) ($_SESSION['csrf'] ?? '');
    if ($known === '' || !hash_equals($known, $sent)) {
        flash('error', 'La sesión del formulario expiró. Vuelve a intentarlo.');
        $back = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        $target = basename(parse_url($back, PHP_URL_PATH) ?: 'index.php');
        if (!preg_match('/^[a-z0-9_-]+\.php$/', $target)) {
            $target = 'index.php';
        }
        redirect($target);
    }
}

function str_cut($value, $max) {
    $value = trim((string) $value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

function field($key, $max = 180) {
    return str_cut($_POST[$key] ?? '', $max);
}

function &store() {
    global $ptfStore, $ptfDirty;
    if ($ptfStore === null) {
        $loaded = db_load();
        if ($loaded === null) {
            $ptfStore = [
                'seq' => ['company' => 0, 'user' => 0, 'cat' => 0, 'exp' => 0, 'rep' => 0],
                'companies' => [],
                'users' => [],
                'categories' => [],
                'expenses' => [],
                'reports' => [],
            ];
        } else {
            $ptfStore = $loaded;
        }
    }
    return $ptfStore;
}

function store_touch() {
    global $ptfDirty;
    $ptfDirty = true;
}

function store_commit() {
    global $ptfStore, $ptfDirty;
    if (!$ptfDirty || !is_array($ptfStore)) {
        return;
    }
    try {
        db_sync($ptfStore);
        $ptfDirty = false;
    } catch (Throwable $error) {
        registrar_log('error', 'base', $error->getMessage(), $error->getFile(), $error->getLine());
        throw $error;
    }
}

register_shutdown_function('store_commit');

function next_id($prefix) {
    $data = &store();
    $data['seq'][$prefix] = (int) ($data['seq'][$prefix] ?? 0) + 1;
    store_touch();
    return $prefix . '_' . $data['seq'][$prefix];
}

function normalize_text($value) {
    $value = trim((string) $value);
    if (function_exists('mb_strtolower')) {
        $value = mb_strtolower($value);
    } else {
        $value = strtolower($value);
    }
    return preg_replace('/\s+/', ' ', $value);
}

function find_user_by_email($email) {
    $email = normalize_text($email);
    foreach (store()['users'] as $user) {
        if (normalize_text($user['email']) === $email) {
            return $user;
        }
    }
    return null;
}

function empresa_por_id_fiscal($pais, $idFiscal, $exceptoId = '') {
    $buscado = id_fiscal_validar($pais, $idFiscal);
    if (empty($buscado['ok'])) {
        return null;
    }
    foreach (store()['companies'] as $company) {
        if (($company['id'] ?? '') === $exceptoId || ($company['pais'] ?? '') !== $pais) {
            continue;
        }
        $guardado = id_fiscal_validar($pais, $company['id_fiscal'] ?? '');
        if (!empty($guardado['ok']) && $guardado['valor'] === $buscado['valor']) {
            return $company;
        }
    }
    return null;
}

function find_by_id($collection, $id) {
    foreach (store()[$collection] as $item) {
        if (($item['id'] ?? '') === $id) {
            return $item;
        }
    }
    return null;
}

function cookie_opciones($expires) {
    $params = session_get_cookie_params();
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    return [
        'expires' => $expires,
        'path' => $params['path'] ?: '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

function recordar_usuario($userId, $email) {
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(24));
    $expires = time() + 30 * 86400;
    update_where('users', $userId, [
        'remember_selector' => $selector,
        'remember_token' => hash('sha256', $validator),
        'remember_expires' => $expires,
    ]);
    setcookie('ptf_recordar', $selector . ':' . $validator, cookie_opciones($expires));
    setcookie('ptf_correo', strtolower(trim((string) $email)), cookie_opciones($expires));
}

function olvidar_recordar($userId = null) {
    if ($userId) {
        update_where('users', $userId, [
            'remember_selector' => '',
            'remember_token' => '',
            'remember_expires' => 0,
        ]);
    }
    $pasado = cookie_opciones(time() - 42000);
    setcookie('ptf_recordar', '', $pasado);
    setcookie('ptf_correo', '', $pasado);
}

function user_from_remember() {
    $raw = (string) ($_COOKIE['ptf_recordar'] ?? '');
    if (!preg_match('/^[a-f0-9]{18}:[a-f0-9]{48}$/', $raw)) {
        return null;
    }
    [$selector, $validator] = explode(':', $raw, 2);
    foreach (store()['users'] as $user) {
        $guardado = (string) ($user['remember_selector'] ?? '');
        if ($guardado === '' || !hash_equals($guardado, $selector)) {
            continue;
        }
        $expires = (int) ($user['remember_expires'] ?? 0);
        $hash = (string) ($user['remember_token'] ?? '');
        if ($expires < time() || $hash === '' || !hash_equals($hash, hash('sha256', $validator)) || empty($user['activo'])) {
            olvidar_recordar($user['id']);
            return null;
        }
        return $user;
    }
    olvidar_recordar();
    return null;
}

function current_user() {
    static $listo = false;
    static $user = null;
    if ($listo) {
        return $user;
    }
    $listo = true;
    if (empty($_SESSION['user_id'])) {
        $recordado = user_from_remember();
        if ($recordado) {
            login_user($recordado, true);
            $user = find_by_id('users', $recordado['id']);
            return $user;
        }
        return null;
    }
    $user = find_by_id('users', $_SESSION['user_id']);
    if (!$user || empty($user['activo'])) {
        unset($_SESSION['user_id']);
        $user = null;
    }
    return $user;
}

function login_user($user, $recordar = false) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    if ($recordar) {
        recordar_usuario($user['id'], $user['email'] ?? '');
        return;
    }
    olvidar_recordar($user['id']);
}

function cerrar_sesion() {
    $user = current_user();
    if ($user) {
        olvidar_recordar($user['id']);
    } else {
        olvidar_recordar();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login() {
    if (!current_user()) {
        $next = basename($_SERVER['SCRIPT_NAME'] ?? 'panel.php');
        redirect('login.php?next=' . urlencode($next));
    }
}

function require_role($roles) {
    require_login();
    $user = current_user();
    if (!in_array($user['rol'], (array) $roles, true)) {
        flash('error', 'Esa sección es solo para quien administra la empresa.');
        redirect('panel.php');
    }
}

function safe_next($next) {
    $next = basename((string) $next);
    $allowed = [
        'panel.php', 'gastos.php', 'gasto.php', 'informes.php', 'informe.php',
        'categorias.php', 'usuarios.php', 'politicas.php', 'perfil.php', 'facturas.php',
    ];
    return in_array($next, $allowed, true) ? $next : 'panel.php';
}

function company_of($user = null) {
    $user = $user ?: current_user();
    if (!$user) {
        return null;
    }
    return find_by_id('companies', $user['company_id']);
}

function update_where($collection, $id, $changes) {
    $data = &store();
    foreach ($data[$collection] as $i => $item) {
        if (($item['id'] ?? '') === $id) {
            $data[$collection][$i] = array_merge($item, $changes);
            store_touch();
            return $data[$collection][$i];
        }
    }
    return null;
}

function insert_item($collection, $item) {
    $data = &store();
    $data[$collection][] = $item;
    store_touch();
    return $item;
}

function remove_where($collection, $id) {
    $data = &store();
    $data[$collection] = array_values(array_filter(
        $data[$collection],
        function ($item) use ($id) {
            return ($item['id'] ?? '') !== $id;
        }
    ));
    store_touch();
}

function company_users($companyId = null) {
    $companyId = $companyId ?: current_user()['company_id'];
    return array_values(array_filter(store()['users'], function ($user) use ($companyId) {
        return $user['company_id'] === $companyId;
    }));
}

function company_categories($companyId = null, $onlyActive = false) {
    $companyId = $companyId ?: current_user()['company_id'];
    $items = array_values(array_filter(store()['categories'], function ($cat) use ($companyId, $onlyActive) {
        if ($cat['company_id'] !== $companyId) {
            return false;
        }
        if ($onlyActive && empty($cat['activa'])) {
            return false;
        }
        return true;
    }));
    usort($items, function ($a, $b) {
        return strcmp($a['nombre'], $b['nombre']);
    });
    return $items;
}

function company_expenses($companyId = null) {
    $companyId = $companyId ?: current_user()['company_id'];
    $items = array_values(array_filter(store()['expenses'], function ($item) use ($companyId) {
        return $item['company_id'] === $companyId;
    }));
    usort($items, function ($a, $b) {
        return strcmp($b['fecha'] . $b['id'], $a['fecha'] . $a['id']);
    });
    return $items;
}

function company_reports($companyId = null) {
    $companyId = $companyId ?: current_user()['company_id'];
    $items = array_values(array_filter(store()['reports'], function ($item) use ($companyId) {
        return $item['company_id'] === $companyId;
    }));
    usort($items, function ($a, $b) {
        return strcmp($b['actualizado'], $a['actualizado']);
    });
    return $items;
}

function visible_expenses() {
    $user = current_user();
    $items = company_expenses($user['company_id']);
    if ($user['rol'] === 'rendidor') {
        $items = array_values(array_filter($items, function ($item) use ($user) {
            return $item['user_id'] === $user['id'];
        }));
    }
    return $items;
}

function visible_reports() {
    $user = current_user();
    $items = company_reports($user['company_id']);
    if ($user['rol'] === 'rendidor') {
        $items = array_values(array_filter($items, function ($item) use ($user) {
            return $item['user_id'] === $user['id'];
        }));
    }
    return $items;
}

function plan_valor($clave, $defecto) {
    $config = ptf_config();
    return (int) ($config[$clave] ?? $defecto);
}

function cupo_usuarios($companyId) {
    return count(company_users($companyId));
}

function cupo_documentos($companyId) {
    $mes = date('Y-m');
    $total = 0;
    foreach (company_expenses($companyId) as $expense) {
        if (strpos((string) $expense['creado'], $mes) === 0) {
            $total++;
        }
    }
    return $total;
}

function cupo_rendiciones($userId) {
    $total = 0;
    foreach (store()['reports'] as $report) {
        if ($report['user_id'] === $userId) {
            $total++;
        }
    }
    return $total;
}

function user_name($id) {
    $user = find_by_id('users', $id);
    return $user['nombre'] ?? 'Usuario';
}

function category_name($id) {
    $cat = find_by_id('categories', $id);
    return $cat['nombre'] ?? 'Sin categoría';
}

function money($amount, $currency) {
    $decimals = in_array($currency, ['CLP', 'COP'], true) ? 0 : 2;
    return $currency . ' ' . number_format((float) $amount, $decimals, ',', '.');
}

function fecha_latam($iso) {
    $dt = DateTime::createFromFormat('Y-m-d', (string) $iso);
    if (!$dt) {
        return (string) $iso;
    }
    return $dt->format('d/m/Y');
}

function rol_label($rol) {
    $map = [
        'administrador' => 'Administra',
        'aprobador' => 'Aprueba',
        'rendidor' => 'Rinde',
    ];
    return $map[$rol] ?? $rol;
}

function estado_gasto_label($estado) {
    $map = [
        'observado' => 'Observado',
        'legalizado' => 'Legalizado',
        'en_rendicion' => 'En rendición',
        'aprobado' => 'Aprobado',
        'devuelto' => 'Devuelto',
    ];
    return $map[$estado] ?? $estado;
}

function chip_class($estado) {
    $map = [
        'legalizado' => 'chip-ok',
        'aprobado' => 'chip-ok',
        'observado' => 'chip-warn',
        'devuelto' => 'chip-danger',
        'rechazado' => 'chip-danger',
        'en_rendicion' => 'chip-info',
        'en_revision' => 'chip-info',
        'borrador' => 'chip-sand',
    ];
    return $map[$estado] ?? 'chip';
}

function estado_informe_label($estado) {
    $map = [
        'borrador' => 'Borrador',
        'en_revision' => 'En revisión',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Devuelto',
    ];
    return $map[$estado] ?? $estado;
}

function parse_amount($raw) {
    $raw = trim(str_replace([' ', '$'], '', (string) $raw));
    if ($raw === '') {
        return null;
    }
    if (preg_match('/,\d{1,2}$/', $raw)) {
        $raw = str_replace('.', '', $raw);
        $raw = str_replace(',', '.', $raw);
    } else {
        $raw = str_replace(',', '', $raw);
    }
    if (!is_numeric($raw)) {
        return null;
    }
    $number = round((float) $raw, 2);
    if ($number < 0 || $number > 9999999999) {
        return null;
    }
    return $number;
}

function parse_date($raw) {
    $raw = trim((string) $raw);
    $dt = DateTime::createFromFormat('Y-m-d', $raw);
    if (!$dt || $dt->format('Y-m-d') !== $raw) {
        return null;
    }
    $year = (int) $dt->format('Y');
    if ($year < 2000 || $year > (int) date('Y') + 1) {
        return null;
    }
    return $raw;
}

function fiscal_incompleto($expense) {
    return trim((string) ($expense['id_fiscal'] ?? '')) === ''
        || trim((string) ($expense['num_doc'] ?? '')) === ''
        || trim((string) ($expense['tipo_doc'] ?? '')) === '';
}

function evaluar_gasto($expense, $exceptId = null) {
    $company = find_by_id('companies', $expense['company_id']);
    $category = find_by_id('categories', $expense['category_id']);
    $duplicado = false;
    if ($company && !empty($company['detectar_duplicados'])) {
        $days = (int) ($company['dias_duplicado'] ?? 0);
        $target = strtotime($expense['fecha']);
        foreach (company_expenses($expense['company_id']) as $other) {
            if ($other['id'] === $exceptId) {
                continue;
            }
            $sameDoc = trim((string) $expense['num_doc']) !== ''
                && normalize_text($other['num_doc']) === normalize_text($expense['num_doc'])
                && normalize_text($other['id_fiscal']) === normalize_text($expense['id_fiscal']);
            $sameBill = normalize_text($other['proveedor']) === normalize_text($expense['proveedor'])
                && abs((float) $other['monto'] - (float) $expense['monto']) < 0.001
                && abs(strtotime($other['fecha']) - $target) <= ($days * 86400);
            if ($sameDoc || $sameBill) {
                $duplicado = true;
                break;
            }
        }
    }
    $fuera = $category && (float) $category['tope'] > 0 && (float) $expense['monto'] > (float) $category['tope'];
    $desde = (float) ($company['comprobante_desde'] ?? 0);
    $sin = ($expense['foto'] ?? '') === '' && (float) $expense['monto'] >= $desde;
    $fiscal = fiscal_incompleto($expense);
    $estado = $expense['estado'] ?? 'legalizado';
    if (!in_array($estado, ['en_rendicion', 'aprobado'], true)) {
        $estado = ($duplicado || $fuera || $sin) ? 'observado' : 'legalizado';
    }
    $expense['duplicado'] = $duplicado;
    $expense['fuera_politica'] = $fuera;
    $expense['sin_comprobante'] = $sin;
    $expense['fiscal_incompleto'] = $fiscal;
    $expense['estado'] = $estado;
    return $expense;
}

function reevaluar_empresa($companyId) {
    $data = &store();
    foreach ($data['expenses'] as $i => $expense) {
        if ($expense['company_id'] !== $companyId) {
            continue;
        }
        $data['expenses'][$i] = evaluar_gasto($expense, $expense['id']);
    }
    store_touch();
}

function save_upload($field, $companyId) {
    if (empty($_FILES[$field]['name'])) {
        return ['ok' => true, 'path' => ''];
    }
    $file = $_FILES[$field];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'path' => ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'No se pudo subir el comprobante.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'El comprobante supera 5 MB.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $map = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];
    if (!isset($map[$ext])) {
        return ['ok' => false, 'error' => 'Usa JPG, PNG, WEBP o PDF.'];
    }
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
    if ($finfo) {
        finfo_close($finfo);
    }
    $accepted = [$map[$ext], 'application/octet-stream'];
    if ($ext === 'jpg' || $ext === 'jpeg') {
        $accepted[] = 'image/jpg';
        $accepted[] = 'image/jpeg';
    }
    if ($mime && !in_array($mime, $accepted, true)) {
        return ['ok' => false, 'error' => 'El archivo no coincide con su tipo.'];
    }
    $dir = ROOT . '/uploads';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = preg_replace('/[^a-z0-9_-]/', '', $companyId) . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return ['ok' => false, 'error' => 'No se pudo guardar el comprobante.'];
    }
    return ['ok' => true, 'path' => 'uploads/' . $name];
}

function report_total($report) {
    $sum = 0;
    $currency = 'COP';
    foreach ($report['expense_ids'] as $id) {
        $expense = find_by_id('expenses', $id);
        if ($expense) {
            $sum += (float) $expense['monto'];
            $currency = $expense['moneda'];
        }
    }
    return [$sum, $currency];
}

function pending_reports_count() {
    $user = current_user();
    if (!$user || $user['rol'] === 'rendidor') {
        return 0;
    }
    $count = 0;
    foreach (company_reports($user['company_id']) as $report) {
        if ($report['estado'] === 'en_revision') {
            $count++;
        }
    }
    return $count;
}

function crear_categorias_empresa($companyId, $moneda) {
    foreach (categorias_base($moneda) as $base) {
        insert_item('categories', [
            'id' => next_id('cat'),
            'company_id' => $companyId,
            'nombre' => $base['nombre'],
            'detalle' => $base['detalle'],
            'tope' => $base['tope'],
            'activa' => true,
        ]);
    }
}

function app_open($title, $active) {
    $user = current_user();
    $company = company_of($user);
    $pais = pais($company['pais']);
    $flash = consume_flash();
    $pending = pending_reports_count();
    $facturasPendientes = 0;
    if ($user && $user['rol'] === 'administrador' && function_exists('facturas_pendientes')) {
        $facturasPendientes = facturas_pendientes($user['company_id']);
    }
    render('partials/head', [
        'title' => $title,
        'styles' => ['css/base.css', 'css/app.css'],
        'bodyClass' => 'app-body',
    ]);
    render('app/shell_start', compact('title', 'active', 'user', 'company', 'pais', 'flash', 'pending', 'facturasPendientes'));
}

function app_close() {
    render('app/shell_end');
}

function render($template, $vars = []) {
    extract($vars, EXTR_SKIP);
    include ROOT . '/template/' . $template . '.php';
}

function page_open($title, $styles, $bodyClass = '', $seo = []) {
    render('partials/head', [
        'title' => $title,
        'styles' => $styles,
        'bodyClass' => $bodyClass,
        'seo' => $seo,
    ]);
}

function page_close($script) {
    echo '<script src="' . e(url($script)) . '"></script></body></html>';
}

if (PHP_SAPI !== 'cli' && !empty($_COOKIE['ptf_recordar']) && empty($_SESSION['user_id'])) {
    current_user();
}

set_exception_handler(function (Throwable $error) {
    registrar_log('error', 'sistema', $error->getMessage(), $error->getFile(), $error->getLine());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $error->getMessage() . PHP_EOL);
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="es"><meta charset="utf-8"><title>Error</title><p>' . e($error->getMessage()) . '</p></html>';
    exit(1);
});
