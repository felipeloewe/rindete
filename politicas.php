<?php
require __DIR__ . '/includes/init.php';
require_role('administrador');

$company = company_of();
$catalogo = paises();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $paisCode = (string) ($_POST['pais'] ?? $company['pais']);
    if (!isset($catalogo[$paisCode])) {
        $paisCode = $company['pais'];
    }
    $dias = (int) ($_POST['dias_duplicado'] ?? 3);
    if ($dias < 0) {
        $dias = 0;
    }
    if ($dias > 30) {
        $dias = 30;
    }
    $desde = parse_amount($_POST['comprobante_desde'] ?? '0');
    if ($desde === null) {
        $desde = 0;
    }
    $idFiscal = field('id_fiscal', 40);
    if ($idFiscal !== '') {
        $fiscal = id_fiscal_validar($paisCode, $idFiscal);
        if (!$fiscal['ok']) {
            flash('error', $fiscal['error']);
            redirect('politicas.php');
        }
        $idFiscal = $fiscal['valor'];
        if (empresa_por_id_fiscal($paisCode, $idFiscal, $company['id'])) {
            flash('error', 'Ese ' . $catalogo[$paisCode]['id_fiscal'] . ' ya pertenece a otra empresa.');
            redirect('politicas.php');
        }
    }
    update_where('companies', $company['id'], [
        'nombre' => field('nombre', 120) ?: $company['nombre'],
        'pais' => $paisCode,
        'id_fiscal' => $idFiscal,
        'detectar_duplicados' => !empty($_POST['detectar_duplicados']),
        'dias_duplicado' => $dias,
        'comprobante_desde' => $desde,
    ]);
    reevaluar_empresa($company['id']);
    flash('ok', 'Política actualizada. Los gastos abiertos se volvieron a leer.');
    redirect('politicas.php');
}

app_open('Políticas', 'politicas');
render('app/politicas', [
    'company' => company_of(),
    'paises' => $catalogo,
]);
app_close();
