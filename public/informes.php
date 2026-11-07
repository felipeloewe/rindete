<?php
require dirname(__DIR__) . '/includes/init.php';
require_login();

$user = current_user();
$vista = (string) ($_GET['vista'] ?? 'gastos');
if ($vista !== 'onboarding' || $user['rol'] !== 'administrador') {
    $vista = 'gastos';
}
$estado = (string) ($_GET['estado'] ?? '');
$items = [];
$solicitudes = [];
$archivos = [];
if ($vista === 'onboarding') {
    $company = company_of($user);
    $solicitudes = factura_solicitudes($company['id']);
    if ($estado === 'nueva') {
        $solicitudes = array_values(array_filter($solicitudes, function ($sol) {
            return factura_es_abierta($sol['estado']);
        }));
    } elseif ($estado === 'archivada') {
        $solicitudes = array_values(array_filter($solicitudes, function ($sol) {
            return !factura_es_abierta($sol['estado']);
        }));
    }
    $archivos = factura_archivos($company['id']);
} else {
    $items = visible_reports();
    if ($estado !== '') {
        $items = array_values(array_filter($items, function ($report) use ($estado) {
            return $report['estado'] === $estado;
        }));
    }
}

app_open('Informes', 'rendiciones');
render('app/informes', [
    'vista' => $vista,
    'estado' => $estado,
    'items' => $items,
    'solicitudes' => $solicitudes,
    'archivos' => $archivos,
    'esAdmin' => $user['rol'] === 'administrador',
]);
app_close();
