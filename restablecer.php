<?php
require __DIR__ . '/includes/init.php';
if (current_user()) {
    redirect('panel.php');
}
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$hash = $token !== '' ? hash('sha256', $token) : '';
$match = null;
foreach (store()['users'] as $user) {
    if ($hash !== '' && hash_equals((string) $user['reset_token'], $hash) && (int) $user['reset_expires'] >= time()) {
        $match = $user;
        break;
    }
}
if (!$match) {
    flash('error', 'El enlace no es válido o ya expiró.');
    redirect('recuperar.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');
    if (strlen($password) < 8 || $password !== $password2) {
        flash('error', 'Las contraseñas deben coincidir y tener al menos 8 caracteres.');
    } else {
        update_where('users', $match['id'], [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'reset_token' => '',
            'reset_expires' => 0,
            'remember_selector' => '',
            'remember_token' => '',
            'remember_expires' => 0,
        ]);
        olvidar_recordar();
        flash('ok', 'Contraseña actualizada. Ya puedes ingresar.');
        redirect('login.php');
    }
}
page_open('Nueva contraseña', ['css/base.css', 'css/app.css'], 'auth-body');
render('auth/restablecer', ['token' => $token]);
