<?php
require __DIR__ . '/includes/init.php';
require_login();

$q = trim((string) ($_GET['q'] ?? ''));
$estado = (string) ($_GET['estado'] ?? '');
$categoria = (string) ($_GET['categoria'] ?? '');
$items = visible_expenses();
if ($q !== '') {
    $needle = normalize_text($q);
    $items = array_values(array_filter($items, function ($expense) use ($needle) {
        $blob = normalize_text($expense['concepto'] . ' ' . $expense['proveedor'] . ' ' . $expense['id_fiscal'] . ' ' . $expense['num_doc'] . ' ' . $expense['codigo_fiscal']);
        return strpos($blob, $needle) !== false;
    }));
}
if ($estado !== '') {
    $items = array_values(array_filter($items, function ($expense) use ($estado) {
        return $expense['estado'] === $estado;
    }));
}
if ($categoria !== '') {
    $items = array_values(array_filter($items, function ($expense) use ($categoria) {
        return $expense['category_id'] === $categoria;
    }));
}

app_open('Gastos', 'gastos');
render('app/gastos', [
    'items' => $items,
    'q' => $q,
    'estado' => $estado,
    'categoria' => $categoria,
    'categorias' => company_categories(null, false),
]);
app_close();
