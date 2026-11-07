<?php
require dirname(__DIR__) . '/includes/init.php';
require_login();

$user = current_user();
$company = company_of($user);

function informe_visible($report) {
    $actor = current_user();
    if (!$report || $report['company_id'] !== $actor['company_id']) {
        return false;
    }
    if ($actor['rol'] === 'rendidor' && $report['user_id'] !== $actor['id']) {
        return false;
    }
    return true;
}

function puede_editar_informe($report) {
    $actor = current_user();
    if (!$report || $report['estado'] !== 'borrador') {
        return false;
    }
    return $actor['rol'] === 'administrador' || $report['user_id'] === $actor['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = (string) ($_POST['accion'] ?? 'guardar');
    $id = (string) ($_POST['id'] ?? '');
    $report = $id !== '' ? find_by_id('reports', $id) : null;
    if ($report && !informe_visible($report)) {
        flash('error', 'No encontramos esa rendición.');
        redirect('informes.php');
    }

    if (in_array($accion, ['aprobar', 'devolver'], true)) {
        if (!$report || $report['estado'] !== 'en_revision') {
            flash('error', 'Esta rendición no está en revisión.');
            redirect($report ? 'informe.php?id=' . rawurlencode($report['id']) : 'informes.php');
        }
        if (!in_array($user['rol'], ['administrador', 'aprobador'], true)) {
            flash('error', 'Solo quien aprueba puede cerrar esta rendición.');
            redirect('informe.php?id=' . rawurlencode($report['id']));
        }
        if ($user['rol'] === 'aprobador' && $report['user_id'] === $user['id']) {
            flash('error', 'Pide a otra persona que apruebe tu propia rendición.');
            redirect('informe.php?id=' . rawurlencode($report['id']));
        }
        if ($accion === 'devolver' && field('comentario', 500) === '') {
            flash('error', 'Escribe qué hay que corregir.');
            redirect('informe.php?id=' . rawurlencode($report['id']));
        }
        if ($accion === 'aprobar') {
            update_where('reports', $report['id'], [
                'estado' => 'aprobado',
                'comentario_revision' => field('comentario', 500),
                'revisor_id' => $user['id'],
                'actualizado' => date('c'),
            ]);
            foreach ($report['expense_ids'] as $expenseId) {
                $expense = find_by_id('expenses', $expenseId);
                if ($expense) {
                    update_where('expenses', $expenseId, ['estado' => 'aprobado', 'report_id' => $report['id']]);
                }
            }
            flash('ok', 'Rendición aprobada.');
        } else {
            update_where('reports', $report['id'], [
                'estado' => 'rechazado',
                'comentario_revision' => field('comentario', 500),
                'revisor_id' => $user['id'],
                'actualizado' => date('c'),
            ]);
            foreach ($report['expense_ids'] as $expenseId) {
                $expense = find_by_id('expenses', $expenseId);
                if ($expense && $expense['report_id'] === $report['id']) {
                    update_where('expenses', $expenseId, ['report_id' => '', 'estado' => 'legalizado']);
                }
            }
            reevaluar_empresa($company['id']);
            flash('ok', 'Rendición devuelta. Los gastos quedaron libres para corregir.');
        }
        redirect('informe.php?id=' . rawurlencode($report['id']));
    }

    if ($accion === 'reabrir') {
        if (!$report || $report['estado'] !== 'rechazado') {
            flash('error', 'Solo se reabre una rendición devuelta.');
            redirect('informes.php');
        }
        if ($user['rol'] !== 'administrador' && $report['user_id'] !== $user['id']) {
            flash('error', 'No puedes reabrir esta rendición.');
            redirect('informe.php?id=' . rawurlencode($report['id']));
        }
        $ids = [];
        foreach ($report['expense_ids'] as $expenseId) {
            $expense = find_by_id('expenses', $expenseId);
            if (!$expense || $expense['report_id'] !== '' || in_array($expense['estado'], ['aprobado', 'en_rendicion'], true)) {
                continue;
            }
            update_where('expenses', $expenseId, ['report_id' => $report['id']]);
            $ids[] = $expenseId;
        }
        if (!$ids) {
            flash('error', 'Esos gastos ya están en otra rendición.');
            redirect('informe.php?id=' . rawurlencode($report['id']));
        }
        update_where('reports', $report['id'], [
            'estado' => 'borrador',
            'expense_ids' => $ids,
            'actualizado' => date('c'),
        ]);
        flash('ok', 'La rendición volvió a borrador.');
        redirect('informe.php?id=' . rawurlencode($report['id']));
    }

    if ($report && !puede_editar_informe($report)) {
        flash('error', 'Esta rendición ya no se puede editar.');
        redirect($report ? 'informe.php?id=' . rawurlencode($report['id']) : 'informes.php');
    }

    $titulo = field('titulo', 140);
    $desde = parse_date($_POST['desde'] ?? '');
    $hasta = parse_date($_POST['hasta'] ?? '');
    $nota = field('nota', 500);
    $seleccion = isset($_POST['gastos']) && is_array($_POST['gastos']) ? $_POST['gastos'] : [];
    $clean = [];
    foreach ($seleccion as $expenseId) {
        $expense = find_by_id('expenses', (string) $expenseId);
        if (!$expense || $expense['company_id'] !== $company['id']) {
            continue;
        }
        $enlazadoAqui = $report && $expense['report_id'] === $report['id'];
        $libre = $expense['report_id'] === '' && !in_array($expense['estado'], ['aprobado', 'en_rendicion'], true);
        if (!$enlazadoAqui && !$libre) {
            continue;
        }
        if ($user['rol'] === 'rendidor' && $expense['user_id'] !== $user['id']) {
            continue;
        }
        $clean[] = $expense;
    }
    $errores = [];
    if ($titulo === '') {
        $errores[] = 'Ponle un nombre a la rendición.';
    }
    if (!$clean) {
        $errores[] = 'Elige al menos un gasto.';
    }
    $duenos = array_unique(array_map(function ($expense) {
        return $expense['user_id'];
    }, $clean));
    if (count($duenos) > 1) {
        $errores[] = 'Una rendición junta gastos de una sola persona.';
    }
    if ($desde && $hasta && $desde > $hasta) {
        $errores[] = 'La fecha final es anterior a la inicial.';
    }
    if (!$report && $clean) {
        $dueno = $clean[0]['user_id'];
        $limiteRendiciones = plan_valor('plan_rendiciones_usuario', 1);
        if (cupo_rendiciones($dueno) >= $limiteRendiciones) {
            $errores[] = 'En el plan gratis cada persona puede tener ' . $limiteRendiciones . ($limiteRendiciones === 1 ? ' rendición.' : ' rendiciones.');
        }
    }
    if ($errores) {
        $_SESSION['informe_old'] = $_POST;
        flash('error', $errores[0]);
        redirect($report ? 'informe.php?id=' . rawurlencode($report['id']) : 'informe.php?nuevo=1');
    }

    $reportId = $report ? $report['id'] : next_id('rep');
    $enviar = $accion === 'enviar';
    $previos = $report ? $report['expense_ids'] : [];
    $nuevosIds = array_map(function ($expense) {
        return $expense['id'];
    }, $clean);
    foreach ($previos as $expenseId) {
        if (!in_array($expenseId, $nuevosIds, true)) {
            $expense = find_by_id('expenses', $expenseId);
            if ($expense && $expense['report_id'] === $reportId) {
                update_where('expenses', $expenseId, ['report_id' => '', 'estado' => 'legalizado']);
            }
        }
    }
    foreach ($clean as $expense) {
        update_where('expenses', $expense['id'], [
            'report_id' => $reportId,
            'estado' => $enviar ? 'en_rendicion' : ($expense['estado'] === 'en_rendicion' ? 'legalizado' : $expense['estado']),
        ]);
    }
    $payload = [
        'id' => $reportId,
        'company_id' => $company['id'],
        'user_id' => $clean[0]['user_id'],
        'titulo' => $titulo,
        'desde' => $desde ?: '',
        'hasta' => $hasta ?: '',
        'nota' => $nota,
        'estado' => $enviar ? 'en_revision' : 'borrador',
        'expense_ids' => $nuevosIds,
        'comentario_revision' => $report ? $report['comentario_revision'] : '',
        'revisor_id' => $report ? $report['revisor_id'] : '',
        'creado' => $report ? $report['creado'] : date('c'),
        'actualizado' => date('c'),
    ];
    if ($report) {
        update_where('reports', $reportId, $payload);
    } else {
        insert_item('reports', $payload);
    }
    reevaluar_empresa($company['id']);
    flash('ok', $enviar ? 'Rendición enviada a revisión.' : 'Borrador guardado.');
    redirect('informe.php?id=' . rawurlencode($reportId));
}

$id = (string) ($_GET['id'] ?? '');
$report = $id !== '' ? find_by_id('reports', $id) : null;
if ($id !== '' && !informe_visible($report)) {
    flash('error', 'No encontramos esa rendición.');
    redirect('informes.php');
}
if (!$report && !isset($_GET['nuevo'])) {
    redirect('informes.php');
}

$old = $_SESSION['informe_old'] ?? null;
unset($_SESSION['informe_old']);
$editar = !$report || puede_editar_informe($report);

app_open($report ? $report['titulo'] : 'Nueva rendición', 'rendiciones');
if ($editar) {
    $disponibles = [];
    foreach (visible_expenses() as $expense) {
        $enlazado = $report && ($expense['report_id'] === $report['id'] || in_array($expense['id'], $report['expense_ids'], true));
        $libre = $expense['report_id'] === '' && !in_array($expense['estado'], ['aprobado', 'en_rendicion'], true);
        if ($enlazado || $libre) {
            $disponibles[] = $expense;
        }
    }
    if ($report) {
        $disponibles = array_values(array_filter($disponibles, function ($expense) use ($report) {
            return $expense['user_id'] === $report['user_id'];
        }));
    }
    render('app/informe_form', [
        'report' => $report,
        'disponibles' => $disponibles,
        'old' => $old,
        'moneda' => pais($company['pais'])['moneda'],
    ]);
} else {
    $gastos = [];
    foreach ($report['expense_ids'] as $expenseId) {
        $expense = find_by_id('expenses', $expenseId);
        if ($expense) {
            $gastos[] = $expense;
        }
    }
    [$total, $moneda] = report_total($report);
    render('app/informe_ver', [
        'report' => $report,
        'gastos' => $gastos,
        'total' => $total,
        'moneda' => $moneda,
        'puedeDecidir' => $report['estado'] === 'en_revision' && in_array($user['rol'], ['administrador', 'aprobador'], true) && !($user['rol'] === 'aprobador' && $report['user_id'] === $user['id']),
        'puedeReabrir' => $report['estado'] === 'rechazado' && ($user['rol'] === 'administrador' || $report['user_id'] === $user['id']),
    ]);
}
app_close();
