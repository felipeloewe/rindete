<?php
require dirname(__DIR__) . '/includes/init.php';
require_login();

$user = current_user();
$company = company_of($user);
$expenses = visible_expenses();
$reports = visible_reports();
$month = date('Y-m');
$delMes = array_values(array_filter($expenses, function ($expense) use ($month) {
    return strpos($expense['fecha'], $month) === 0;
}));
$montoMes = 0;
$moneda = pais($company['pais'])['moneda'];
foreach ($delMes as $expense) {
    $montoMes += (float) $expense['monto'];
    $moneda = $expense['moneda'];
}
$alertas = array_values(array_filter($expenses, function ($expense) {
    return $expense['estado'] !== 'aprobado' && ($expense['duplicado'] || $expense['fuera_politica'] || $expense['sin_comprobante']);
}));
$porAprobar = array_values(array_filter($reports, function ($report) {
    return $report['estado'] === 'en_revision';
}));
$recientes = array_slice($expenses, 0, 5);
$meses = [1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre'];
$mesNombre = $meses[(int) date('n')];
$partes = preg_split('/\s+/', trim($user['nombre']));
$saludo = $partes[0] ?: $user['nombre'];

app_open('Inicio', 'inicio');
render('app/panel', [
    'user' => $user,
    'company' => $company,
    'saludo' => $saludo,
    'totalMes' => count($delMes),
    'montoMes' => $montoMes,
    'moneda' => $moneda,
    'alertas' => $alertas,
    'porAprobar' => $porAprobar,
    'recientes' => $recientes,
    'sinGastos' => count(company_expenses($company['id'])) === 0,
    'paisInfo' => pais($company['pais']),
    'mesNombre' => $mesNombre,
    'planUsuarios' => cupo_usuarios($company['id']),
    'planUsuariosMax' => plan_valor('plan_usuarios', 3),
    'planDocs' => cupo_documentos($company['id']),
    'planDocsMax' => plan_valor('plan_documentos_mes', 50),
    'planRendiciones' => plan_valor('plan_rendiciones_usuario', 1),
    'onboardingNuevas' => $user['rol'] === 'administrador' ? facturas_pendientes($company['id']) : 0,
]);
app_close();
