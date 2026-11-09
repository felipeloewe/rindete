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
});
