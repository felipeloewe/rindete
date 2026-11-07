<?php
require dirname(__DIR__) . '/includes/init.php';
require_login();

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nombre = field('nombre', 80);
    if ($nombre === '') {
        flash('error', 'El nombre no puede quedar vacío.');
        redirect('perfil.php');
    }
    $changes = ['nombre' => $nombre];
    $nueva = (string) ($_POST['password'] ?? '');
    if ($nueva !== '') {
        $actual = (string) ($_POST['actual'] ?? '');
        if (!password_verify($actual, $user['password'])) {
            flash('error', 'La contraseña actual no coincide.');
            redirect('perfil.php');
        }
        if (strlen($nueva) < 8 || $nueva !== (string) ($_POST['password2'] ?? '')) {
            flash('error', 'La nueva contraseña debe coincidir y tener al menos 8 caracteres.');
            redirect('perfil.php');
        }
        $changes['password'] = password_hash($nueva, PASSWORD_DEFAULT);
        $changes['remember_selector'] = '';
        $changes['remember_token'] = '';
        $changes['remember_expires'] = 0;
    }
    update_where('users', $user['id'], $changes);
    if ($nueva !== '') {
        olvidar_recordar();
    }
    flash('ok', 'Perfil actualizado.');
    redirect('perfil.php');
}

app_open('Mi perfil', 'perfil');
render('app/perfil', ['user' => current_user(), 'company' => company_of()]);
app_close();
