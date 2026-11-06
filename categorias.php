<?php
require __DIR__ . '/includes/init.php';
require_role('administrador');

$company = company_of();
$editId = (string) ($_GET['id'] ?? '');
$edit = $editId !== '' ? find_by_id('categories', $editId) : null;
if ($edit && $edit['company_id'] !== $company['id']) {
    $edit = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $accion = (string) ($_POST['accion'] ?? '');
    $id = (string) ($_POST['id'] ?? '');
    $cat = $id !== '' ? find_by_id('categories', $id) : null;
    if ($cat && $cat['company_id'] !== $company['id']) {
        $cat = null;
    }
    if ($accion === 'eliminar' && $cat) {
        $usada = false;
        foreach (company_expenses($company['id']) as $expense) {
            if ($expense['category_id'] === $cat['id']) {
                $usada = true;
                break;
            }
        }
        if ($usada) {
            flash('error', 'Esa categoría ya tiene gastos. Cámbiale el nombre o el tope, no la borres.');
        } else {
            remove_where('categories', $cat['id']);
            flash('ok', 'Categoría eliminada.');
        }
        redirect('categorias.php');
    }
    $nombre = field('nombre', 80);
    $detalle = field('detalle', 180);
    $tope = parse_amount($_POST['tope'] ?? '0');
    if ($nombre === '' || $tope === null) {
        flash('error', 'Escribe el nombre y un tope válido. Usa 0 si no hay límite.');
        redirect($cat ? 'categorias.php?id=' . rawurlencode($cat['id']) : 'categorias.php');
    }
    if ($accion === 'guardar' && $cat) {
        update_where('categories', $cat['id'], [
            'nombre' => $nombre,
            'detalle' => $detalle,
            'tope' => $tope,
            'activa' => !empty($_POST['activa']),
        ]);
        reevaluar_empresa($company['id']);
        flash('ok', 'Categoría actualizada. Volvimos a leer los gastos con el nuevo tope.');
    } elseif ($accion === 'crear') {
        insert_item('categories', [
            'id' => next_id('cat'),
            'company_id' => $company['id'],
            'nombre' => $nombre,
            'detalle' => $detalle,
            'tope' => $tope,
            'activa' => true,
        ]);
        flash('ok', 'Categoría creada.');
    } else {
        flash('error', 'No se pudo guardar la categoría.');
    }
    redirect('categorias.php');
}

$conteo = [];
foreach (company_expenses($company['id']) as $expense) {
    $conteo[$expense['category_id']] = ($conteo[$expense['category_id']] ?? 0) + 1;
}

app_open('Categorías', 'categorias');
render('app/categorias', [
    'items' => company_categories(),
    'edit' => $edit,
    'conteo' => $conteo,
    'moneda' => pais($company['pais'])['moneda'],
]);
app_close();
