<?php
require dirname(__DIR__) . '/includes/init.php';
if (current_user()) {
    redirect('panel.php');
}

$old = [
    'pais' => 'CO',
    'empresa' => '',
    'id_fiscal' => '',
    'nombre' => '',
    'email' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
    csrf_check();
    $old['pais'] = $_POST['pais'] ?? 'CO';
    $old['empresa'] = field('empresa', 120);
    $old['id_fiscal'] = field('id_fiscal', 40);
    $old['nombre'] = field('nombre', 80);
    $old['email'] = field('email', 160);
    $password = (string) ($_POST['password'] ?? '');
    $catalogo = paises();
    $errores = [];
    if (!isset($catalogo[$old['pais']])) {
        $errores[] = 'Elige un país de la lista.';
    }
    if ($old['empresa'] === '' || $old['nombre'] === '' || $old['email'] === '') {
        $errores[] = 'Completa empresa, nombre y correo.';
    }
    if (!email_valido($old['email'])) {
        $errores[] = 'El correo no es válido.';
    } else {
        $old['email'] = strtolower($old['email']);
    }
    $fiscal = isset($catalogo[$old['pais']]) ? id_fiscal_validar($old['pais'], $old['id_fiscal']) : null;
    if ($fiscal && !$fiscal['ok']) {
        $errores[] = $fiscal['error'];
    } elseif ($fiscal) {
        $old['id_fiscal'] = $fiscal['valor'];
        if (empresa_por_id_fiscal($old['pais'], $old['id_fiscal'])) {
            $documento = $catalogo[$old['pais']]['id_fiscal'];
            $errores[] = 'Ese ' . $documento . ' ya tiene una cuenta. Contacta al administrador de la empresa para que te dé acceso.';
        }
    }
    if ($old['email'] !== '' && find_user_by_email($old['email'])) {
        $errores[] = 'Ese correo ya tiene una cuenta.';
    }
    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($errores) {
        flash('error', $errores[0]);
    } else {
        $pais = $catalogo[$old['pais']];
        $companyId = next_id('company');
        insert_item('companies', [
            'id' => $companyId,
            'nombre' => $old['empresa'],
            'pais' => $old['pais'],
            'id_fiscal' => $old['id_fiscal'],
            'detectar_duplicados' => true,
            'dias_duplicado' => 3,
            'comprobante_desde' => 1,
            'creado' => date('c'),
        ]);
        crear_categorias_empresa($companyId, $pais['moneda']);
        $user = insert_item('users', [
            'id' => next_id('user'),
            'company_id' => $companyId,
            'nombre' => $old['nombre'],
            'email' => $old['email'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'rol' => 'administrador',
            'activo' => true,
            'reset_token' => '',
            'reset_expires' => 0,
        ]);
        login_user($user);
        $envio = correo_bienvenida($user['nombre'], $user['email'], $password, $old['empresa']);
        if ($envio['ok']) {
            flash('ok', 'Empresa lista. Te enviamos la bienvenida y tu clave a ' . $user['email'] . '.');
        } else {
            flash('info', 'Empresa lista. No pudimos enviar el correo: ' . $envio['error'] . ' Puedes entrar con la clave que acabas de elegir.');
        }
        redirect('panel.php');
    }
    } catch (Throwable $error) {
        registrar_log('error', 'registro', $error->getMessage(), $error->getFile(), $error->getLine());
        flash('error', $error->getMessage());
    }
}

page_open('Crear empresa', ['css/base.css', 'css/app.css'], 'auth-body');
render('auth/registro', ['old' => $old]);
