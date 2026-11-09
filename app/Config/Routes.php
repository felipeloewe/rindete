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

    $routes->get('categorias', 'Categorias::index');
    $routes->post('categorias', 'Categorias::crear');
    $routes->post('categorias/(:num)/eliminar', 'Categorias::eliminar/$1');
    $routes->post('categorias/(:num)', 'Categorias::actualizar/$1');
});
