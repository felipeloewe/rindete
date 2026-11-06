<?php
require __DIR__ . '/includes/init.php';
if (current_user()) {
    redirect('panel.php');
}
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = field('email', 160);
    $user = find_user_by_email($email);
    if ($user) {
        $token = bin2hex(random_bytes(16));
        update_where('users', $user['id'], [
            'reset_token' => hash('sha256', $token),
            'reset_expires' => time() + 3600,
        ]);
        $envio = correo_recuperacion($user['nombre'], $user['email'], url_absoluta('restablecer.php?token=' . $token));
        if (!$envio['ok']) {
            flash('error', 'No pudimos enviar el correo: ' . $envio['error']);
            redirect('recuperar.php');
        }
    }
    flash('info', 'Si el correo existe, enviamos un enlace para elegir una nueva clave.');
}
page_open('Recuperar contraseña', ['css/base.css', 'css/app.css'], 'auth-body');
render('auth/recuperar', ['email' => $email]);
