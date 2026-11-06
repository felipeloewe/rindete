<?php
require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$company = company_of($user);
$pais = pais($company['pais']);

function gasto_visible($expense) {
    $actor = current_user();
    if (!$expense || $expense['company_id'] !== $actor['company_id']) {
        return false;
    }
    if ($actor['rol'] === 'rendidor' && $expense['user_id'] !== $actor['id']) {
        return false;
    }
    return true;
}

function gasto_bloqueado($expense) {
    return in_array($expense['estado'], ['en_rendicion', 'aprobado'], true);
}

function borrar_archivo($path) {
    if (!$path || strpos((string) $path, 'uploads/') !== 0) {
        return;
    }
    $full = ROOT . '/' . $path;
    if (is_file($full)) {
        unlink($full);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = (string) ($_POST['accion'] ?? 'guardar');
    $id = (string) ($_POST['id'] ?? '');
    $existing = $id !== '' ? find_by_id('expenses', $id) : null;

    if ($accion === 'eliminar') {
        if (!$existing || !gasto_visible($existing)) {
            flash('error', 'No encontramos ese gasto.');
            redirect('gastos.php');
        }
        if (gasto_bloqueado($existing)) {
            flash('error', 'Este gasto ya está en una rendición y no se puede eliminar.');
            redirect('gasto.php?id=' . rawurlencode($id));
        }
        borrar_archivo($existing['foto']);
        $data = &store();
        foreach ($data['reports'] as $i => $report) {
            $data['reports'][$i]['expense_ids'] = array_values(array_filter(
                $report['expense_ids'],
                function ($expenseId) use ($id) {
                    return $expenseId !== $id;
                }
            ));
        }
        store_touch();
        remove_where('expenses', $id);
        reevaluar_empresa($company['id']);
        flash('ok', 'Gasto eliminado.');
        redirect('gastos.php');
    }

    if ($existing && (!gasto_visible($existing) || gasto_bloqueado($existing))) {
        flash('error', 'No puedes editar este gasto.');
        redirect($existing && gasto_visible($existing) ? 'gasto.php?id=' . rawurlencode($id) : 'gastos.php');
    }

    $concepto = field('concepto', 140);
    $proveedor = field('proveedor', 140);
    $fecha = parse_date($_POST['fecha'] ?? '');
    $monto = parse_amount($_POST['monto'] ?? '');
    $categoryId = (string) ($_POST['category_id'] ?? '');
    $category = find_by_id('categories', $categoryId);
    $ownerId = $existing['user_id'] ?? $user['id'];
    if (!$existing && $user['rol'] === 'administrador') {
        $candidate = (string) ($_POST['user_id'] ?? $user['id']);
        foreach (company_users($company['id']) as $member) {
            if ($member['id'] === $candidate && !empty($member['activo'])) {
                $ownerId = $candidate;
            }
        }
    }

    $errores = [];
    if ($concepto === '' || $proveedor === '') {
        $errores[] = 'Escribe el concepto y el proveedor.';
    }
    if (!$fecha) {
        $errores[] = 'La fecha no es válida.';
    }
    if ($monto === null) {
        $errores[] = 'El monto no es válido.';
    }
    $categoriaOk = $category && $category['company_id'] === $company['id'];
    if ($categoriaOk && empty($category['activa']) && (!$existing || $existing['category_id'] !== $categoryId)) {
        $categoriaOk = false;
    }
    if (!$categoriaOk) {
        $errores[] = 'Elige una categoría activa.';
    }
    $tipo = field('tipo_doc', 80);
    if ($tipo !== '' && !in_array($tipo, $pais['docs'], true)) {
        $errores[] = 'El tipo de documento no corresponde al país.';
    }

    $volver = $existing ? 'gasto.php?id=' . rawurlencode($existing['id']) . '&editar=1' : 'gasto.php?nuevo=1';
    if (!$existing && cupo_documentos($company['id']) >= plan_valor('plan_documentos_mes', 50)) {
        $errores[] = 'El plan gratis incluye ' . plan_valor('plan_documentos_mes', 50) . ' documentos al mes.';
    }
    if ($errores) {
        $_SESSION['gasto_old'] = $_POST;
        flash('error', $errores[0]);
        redirect($volver);
    }

    $foto = $existing['foto'] ?? '';
    if (!empty($_POST['quitar_foto'])) {
        borrar_archivo($foto);
        $foto = '';
    }
    $upload = save_upload('foto', $company['id']);
    if (!$upload['ok']) {
        $_SESSION['gasto_old'] = $_POST;
        flash('error', $upload['error']);
        redirect($volver);
    }
    if ($upload['path'] !== '') {
        if ($foto !== $upload['path']) {
            borrar_archivo($foto);
        }
        $foto = $upload['path'];
    }

    $expense = [
        'company_id' => $company['id'],
        'user_id' => $ownerId,
        'category_id' => $categoryId,
        'concepto' => $concepto,
        'proveedor' => $proveedor,
        'fecha' => $fecha,
        'monto' => $monto,
        'moneda' => $existing['moneda'] ?? $pais['moneda'],
        'tipo_doc' => $tipo,
        'id_fiscal' => field('id_fiscal', 40),
        'num_doc' => field('num_doc', 40),
        'codigo_fiscal' => field('codigo_fiscal', 80),
        'nota' => field('nota', 500),
        'foto' => $foto,
        'estado' => $existing['estado'] ?? 'legalizado',
        'report_id' => $existing['report_id'] ?? '',
        'creado' => $existing['creado'] ?? date('c'),
    ];
    if ($existing) {
        $expense['id'] = $existing['id'];
        $expense = evaluar_gasto($expense, $existing['id']);
        update_where('expenses', $existing['id'], $expense);
    } else {
        $expense['id'] = next_id('exp');
        $expense = evaluar_gasto($expense, null);
        insert_item('expenses', $expense);
    }
    reevaluar_empresa($company['id']);
    $saved = find_by_id('expenses', $expense['id']);
    flash('ok', ($saved['estado'] ?? '') === 'observado'
        ? 'Gasto guardado con observaciones. Revísalas antes de rendir.'
        : 'Gasto legalizado. Ya puedes incluirlo en una rendición.');
    redirect('gasto.php?id=' . rawurlencode($expense['id']));
}

$id = (string) ($_GET['id'] ?? '');
$expense = $id !== '' ? find_by_id('expenses', $id) : null;
if ($id !== '' && !gasto_visible($expense)) {
    flash('error', 'No encontramos ese gasto.');
    redirect('gastos.php');
}
if (!$expense && !isset($_GET['nuevo'])) {
    redirect('gastos.php');
}
if ($expense && isset($_GET['editar']) && gasto_bloqueado($expense)) {
    flash('error', 'Este gasto está en una rendición y no se puede editar.');
    redirect('gasto.php?id=' . rawurlencode($expense['id']));
}

$old = $_SESSION['gasto_old'] ?? null;
unset($_SESSION['gasto_old']);
$editing = isset($_GET['nuevo']) || isset($_GET['editar']) || !$expense;

app_open($expense && empty($_GET['editar']) && empty($_GET['nuevo']) ? $expense['concepto'] : ($expense ? 'Editar gasto' : 'Nuevo gasto'), 'gastos');
if ($expense && empty($_GET['editar']) && empty($_GET['nuevo'])) {
    render('app/gasto_ver', [
        'expense' => $expense,
        'pais' => $pais,
        'puedeEditar' => !gasto_bloqueado($expense) && ($user['rol'] !== 'rendidor' || $expense['user_id'] === $user['id']),
    ]);
} else {
    $valor = function ($key, $fallback = '') use ($old, $expense) {
        if (is_array($old) && array_key_exists($key, $old)) {
            return $old[$key];
        }
        if (is_array($expense) && array_key_exists($key, $expense)) {
            return $expense[$key];
        }
        return $fallback;
    };
    $indice = [];
    foreach (company_expenses($company['id']) as $item) {
        $indice[] = [
            'id' => $item['id'],
            'concepto' => $item['concepto'],
            'proveedor' => $item['proveedor'],
            'monto' => $item['monto'],
            'fecha' => $item['fecha'],
            'num_doc' => $item['num_doc'],
        ];
    }
    render('app/gasto_form', [
        'expense' => $expense,
        'pais' => $pais,
        'valor' => $valor,
        'categorias' => company_categories(),
        'equipo' => company_users($company['id']),
        'moneda' => (is_array($expense) && !empty($expense['moneda'])) ? $expense['moneda'] : $pais['moneda'],
        'indice' => $indice,
        'esAdmin' => $user['rol'] === 'administrador' && !$expense,
        'usuarioActual' => $user['id'],
    ]);
}
app_close();
