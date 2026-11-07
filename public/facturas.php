<?php
require dirname(__DIR__) . '/includes/init.php';
require_role('administrador');

$user = current_user();
$company = company_of($user);
$pais = pais($company['pais']);
$token = factura_enlace($company['id']);
$enlace = factura_url_publica($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'enviar' || $accion === 'reenviar') {
        $envioFila = null;
        if ($accion === 'reenviar') {
            $envioFila = factura_envio_por_id($company['id'], (string) ($_POST['id'] ?? ''));
            $email = (string) ($envioFila['email'] ?? '');
            $nombre = (string) ($envioFila['nombre'] ?? '');
        } else {
            $email = field('email', 160);
            $nombre = field('nombre', 80);
        }
        if (!$envioFila && $accion === 'reenviar') {
            flash('error', 'Ese envío ya no está.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Escribe un correo válido.');
        } else {
            if (!$envioFila) {
                $creado = factura_envio_crear($company['id'], $email, $nombre);
                $envioFila = ['id' => $creado['id'], 'token' => $creado['token']];
            }
            $correo = correo_onboarding($nombre, $email, $company['nombre'], factura_url_publica($envioFila['token']), $pais['id_fiscal']);
            factura_envio_marcar($envioFila['id'], $correo['ok'] ? 'enviado' : 'error');
            if ($correo['ok']) {
                flash('ok', 'Enviamos el onboarding a ' . $email . '.');
            } else {
                flash('info', 'No salió el correo: ' . $correo['error'] . ' Quedó en la lista para reenviar.');
            }
        }
    } elseif ($accion === 'campos') {
        $orden = isset($_POST['orden']) && is_array($_POST['orden']) ? $_POST['orden'] : [];
        $modos = isset($_POST['modo']) && is_array($_POST['modo']) ? $_POST['modo'] : [];
        $obligatorios = isset($_POST['obligatorio']) && is_array($_POST['obligatorio']) ? $_POST['obligatorio'] : [];
        factura_campos_guardar($company['id'], $orden, $modos, $obligatorios);
        flash('ok', 'El formulario quedó actualizado.');
    } elseif ($accion === 'adjunto') {
        $nombre = field('nombre_adjunto', 120);
        $formato = '';
        $archivo = null;
        if (isset($_FILES['formato']) && (int) $_FILES['formato']['error'] !== UPLOAD_ERR_NO_FILE) {
            $archivo = $_FILES['formato'];
        }
        $seguir = true;
        if ($archivo) {
            $guardado = factura_guardar_formato($company['id'], $archivo);
            if (!$guardado['ok']) {
                flash('error', $guardado['error']);
                $seguir = false;
            } else {
                $formato = $guardado['path'];
            }
        }
        if ($seguir && factura_agregar_pedido($company['id'], $nombre, !empty($_POST['obligatorio']), $formato)) {
            flash('ok', 'El adjunto quedó activo.');
        } elseif ($seguir) {
            flash('error', 'Escribe el nombre del adjunto.');
        }
    } elseif ($accion === 'adjuntos') {
        $orden = isset($_POST['orden_adjunto']) && is_array($_POST['orden_adjunto']) ? $_POST['orden_adjunto'] : [];
        $nombres = isset($_POST['nombre_adjunto']) && is_array($_POST['nombre_adjunto']) ? $_POST['nombre_adjunto'] : [];
        $activos = isset($_POST['activo']) && is_array($_POST['activo']) ? $_POST['activo'] : [];
        $obligatorios = isset($_POST['obligatorio_adjunto']) && is_array($_POST['obligatorio_adjunto']) ? $_POST['obligatorio_adjunto'] : [];
        factura_adjuntos_guardar($company['id'], $orden, $nombres, $activos, $obligatorios);
        flash('ok', 'Los adjuntos quedaron actualizados.');
    } elseif ($accion === 'quitar') {
        factura_quitar_pedido($company['id'], (string) ($_POST['id'] ?? ''));
        flash('ok', 'Ya no se pide ese archivo.');
    } elseif ($accion === 'archivar') {
        if (factura_marcar($company['id'], (string) ($_POST['id'] ?? ''), 'vista', '')) {
            flash('ok', 'Salió de la bandeja.');
        }
    }
    redirect('facturas.php');
}

app_open('Pide tu factura', 'facturas');
render('app/facturas', [
    'company' => $company,
    'pais' => $pais,
    'enlace' => $enlace,
    'solicitudes' => factura_solicitudes($company['id']),
    'archivos' => factura_archivos($company['id']),
    'clientes' => factura_clientes($company['id']),
    'pedidos' => factura_pedidos($company['id']),
    'campos' => factura_campos($company['id']),
    'envios' => factura_envios($company['id']),
]);
app_close();
