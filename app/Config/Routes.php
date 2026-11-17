<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');
$routes->get('registro', 'Registro::index');
$routes->post('registro', 'Registro::store');

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('panel', 'Home::panel');
    $routes->get('cuenta', 'Cuenta::index');
    $routes->post('cuenta', 'Cuenta::update');
    $routes->get('empresa', 'Empresa::index');
    $routes->post('empresa', 'Empresa::update');

    $routes->get('gastos', 'Gastos::index');
    $routes->get('gastos/nuevo', 'Gastos::nuevo');
    $routes->post('gastos', 'Gastos::crear');
    $routes->get('gastos/(:num)/editar', 'Gastos::editar/$1');
    $routes->get('gastos/(:num)/comprobante', 'Gastos::comprobante/$1');
    $routes->post('gastos/(:num)/eliminar', 'Gastos::eliminar/$1');
    $routes->get('gastos/(:num)', 'Gastos::ver/$1');
    $routes->post('gastos/(:num)', 'Gastos::actualizar/$1');

    $routes->get('rendiciones', 'Rendiciones::index');
    $routes->get('rendiciones/nueva', 'Rendiciones::nueva');
    $routes->post('rendiciones', 'Rendiciones::crear');
    $routes->post('rendiciones/(:num)/agregar', 'Rendiciones::agregar/$1');
    $routes->post('rendiciones/(:num)/quitar/(:num)', 'Rendiciones::quitar/$1/$2');
    $routes->post('rendiciones/(:num)/enviar', 'Rendiciones::enviar/$1');
    $routes->post('rendiciones/(:num)/eliminar', 'Rendiciones::eliminar/$1');
    $routes->get('rendiciones/(:num)', 'Rendiciones::ver/$1');

    $routes->get('parametros', 'Parametros::index');
    $routes->post('parametros/cuentas', 'Parametros::cuenta');
    $routes->post('parametros/cuentas/(:num)/eliminar', 'Parametros::eliminarCuenta/$1');
    $routes->post('parametros/clasificacion', 'Parametros::clasificacion');
    $routes->post('parametros/conexiones', 'Parametros::conexion');
    $routes->post('parametros/conexiones/(:num)/eliminar', 'Parametros::eliminarConexion/$1');
    $routes->post('parametros/flujo', 'Parametros::flujo');
    $routes->post('parametros/aceptadores', 'Parametros::aceptador');
    $routes->post('parametros/aceptadores/(:num)/eliminar', 'Parametros::eliminarAceptador/$1');
    $routes->post('parametros/tarjetas', 'Parametros::tarjeta');
    $routes->post('parametros/tarjetas/(:num)/eliminar', 'Parametros::eliminarTarjeta/$1');
    $routes->post('parametros/aceptados', 'Parametros::aceptados');
    $routes->post('parametros/tipos', 'Parametros::tipo');
    $routes->post('parametros/tipos/(:num)', 'Parametros::actualizarTipo/$1');

    $routes->get('categorias', 'Categorias::index');
    $routes->post('categorias', 'Categorias::crear');
    $routes->post('categorias/(:num)/eliminar', 'Categorias::eliminar/$1');
    $routes->post('categorias/(:num)', 'Categorias::actualizar/$1');
});
