<?php
require dirname(__DIR__) . '/includes/init.php';
require_role('administrador');

$company = company_of();
$actor = current_user();

function admins_activos($companyId) {
    $total = 0;
    foreach (company_users($companyId) as $member) {
        if ($member['rol'] === 'administrador' && !empty($member['activo'])) {
            $total++;
        }
    }
    return $total;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'crear') {
        $nombre = field('nombre', 80);
        $email = field('email', 160);
        $rol = (string) ($_POST['rol'] ?? 'rendidor');
        $clave = (string) ($_POST['password'] ?? '');
        $roles = ['administrador', 'aprobador', 'rendidor'];
        if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Revisa el nombre y el correo.');
        } elseif (!in_array($rol, $roles, true)) {
            flash('error', 'Elige un perfil válido.');
        } elseif (find_user_by_email($email)) {
            flash('error', 'Ese correo ya existe.');
        } elseif (strlen($clave) < 8) {
            flash('error', 'La clave temporal necesita al menos 8 caracteres.');
        } elseif (cupo_usuarios($company['id']) >= plan_valor('plan_usuarios', 3)) {
            flash('error', 'El plan gratis incluye hasta ' . plan_valor('plan_usuarios', 3) . ' usuarios.');
        } else {
            insert_item('users', [
                'id' => next_id('user'),
                'company_id' => $company['id'],
                'nombre' => $nombre,
                'email' => $email,
                'password' => password_hash($clave, PASSWORD_DEFAULT),
                'rol' => $rol,
                'activo' => true,
                'reset_token' => '',
                'reset_expires' => 0,
            ]);
            $envio = correo_bienvenida($nombre, $email, $clave, $company['nombre']);
            if ($envio['ok']) {
                flash('ok', 'Usuario creado. Le enviamos la bienvenida y la clave a ' . $email . '.');
            } else {
                flash('info', 'Usuario creado, pero el correo no salió: ' . $envio['error'] . ' Clave temporal: ' . $clave);
            }
        }
        redirect('usuarios.php');
    }

    $member = find_by_id('users', (string) ($_POST['id'] ?? ''));
    if (!$member || $member['company_id'] !== $company['id']) {
        flash('error', 'No encontramos a esa persona.');
        redirect('usuarios.php');
    }
    if ($accion === 'guardar') {
        $rol = (string) ($_POST['rol'] ?? $member['rol']);
        $activo = !empty($_POST['activo']);
        if (!in_array($rol, ['administrador', 'aprobador', 'rendidor'], true)) {
            flash('error', 'Perfil no válido.');
            redirect('usuarios.php');
        }
        if ($member['id'] === $actor['id'] && (!$activo || $rol !== 'administrador')) {
            flash('error', 'No puedes quitarte el perfil de administración.');
            redirect('usuarios.php');
        }
        $eraAdmin = $member['rol'] === 'administrador' && !empty($member['activo']);
        $sigueAdmin = $rol === 'administrador' && $activo;
        if ($eraAdmin && !$sigueAdmin && admins_activos($company['id']) <= 1) {
            flash('error', 'La empresa necesita al menos una persona que administre.');
            redirect('usuarios.php');
        }
        update_where('users', $member['id'], ['rol' => $rol, 'activo' => $activo]);
        flash('ok', 'Perfil actualizado.');
    }
    redirect('usuarios.php');
}

app_open('Equipo', 'equipo');
render('app/usuarios', [
    'items' => company_users($company['id']),
    'actorId' => $actor['id'],
    'cupo' => cupo_usuarios($company['id']),
    'limite' => plan_valor('plan_usuarios', 3),
]);
app_close();
