<?php
require __DIR__ . '/includes/init.php';
if (current_user()) {
    redirect('panel.php');
}
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? 'panel.php');
$email = (string) ($_COOKIE['ptf_correo'] ?? '');
$recordar = $email !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = field('email', 160);
    $recordar = !empty($_POST['recordar']);
    $password = (string) ($_POST['password'] ?? '');
    if (!email_valido($email)) {
        flash('error', 'Escribe un correo válido.');
    } elseif ($password === '') {
        flash('error', 'Escribe la contraseña.');
    } else {
        $user = find_user_by_email($email);
        if (!$user || empty($user['activo']) || !password_verify($password, $user['password'])) {
            flash('error', 'Correo o contraseña incorrectos.');
        } else {
            login_user($user, $recordar);
            redirect($next);
        }
    }
}

page_open('Ingresar', ['css/base.css', 'css/app.css'], 'auth-body');
render('auth/login', ['next' => $next, 'email' => $email, 'recordar' => $recordar]);
