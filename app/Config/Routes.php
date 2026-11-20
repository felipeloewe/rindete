<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout');
$routes->get('registro', 'Registro::index');
$routes->post('registro', 'Registro::store');

$routes->group('api/v1', static function (RouteCollection $routes): void {
    $routes->get('preasientos', 'Erp::preasientos');
    $routes->post('preasientos/(:num)/consumir', 'Erp::consumirPreasiento/$1');
    $routes->get('nominas', 'Erp::nominas');
    $routes->post('nominas/(:num)/consumir', 'Erp::consumirNomina/$1');
});

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('panel', 'Home::panel');
    $routes->get('cuenta', 'Cuenta::index');
    $routes->post('cuenta', 'Cuenta::update');
    $routes->get('empresa', 'Empresa::index');
    $routes->post('empresa', 'Empresa::update');
    $routes->get('personas', 'Personas::index');
    $routes->post('personas', 'Personas::crear');
    $routes->post('personas/(:num)', 'Personas::guardar/$1');

    $routes->get('gastos', 'Gastos::index');
    $routes->get('gastos/nuevo', 'Gastos::nuevo');
    $routes->post('gastos', 'Gastos::crear');
    $routes->get('gastos/(:num)/editar', 'Gastos::editar/$1');
    $routes->get('gastos/(:num)/comprobante', 'Gastos::comprobante/$1');
    $routes->post('gastos/(:num)/eliminar', 'Gastos::eliminar/$1');
    $routes->get('gastos/(:num)', 'Gastos::ver/$1');
    $routes->post('gastos/(:num)', 'Gastos::actualizar/$1');

    $routes->get('fondos', 'Fondos::index');
    $routes->get('fondos/pagos', 'Fondos::pagos');
    $routes->get('fondos/nomina', 'Fondos::nomina');
    $routes->post('fondos/nomina', 'Fondos::cerrarNomina');
    $routes->get('fondos/nomina/(:num)/archivo', 'Fondos::archivoNomina/$1');
    $routes->get('fondos/nueva', 'Fondos::nueva');
    $routes->post('fondos', 'Fondos::crear');
    $routes->post('fondos/(:num)/aceptar', 'Fondos::aceptar/$1');
    $routes->post('fondos/(:num)/rechazar', 'Fondos::rechazar/$1');
    $routes->post('fondos/(:num)/entregar', 'Fondos::entregar/$1');
    $routes->get('fondos/(:num)', 'Fondos::ver/$1');

    $routes->get('modulos', 'Modulos::index');
    $routes->post('modulos/contratar', 'Modulos::contratar');
    $routes->post('modulos/dejar', 'Modulos::dejar');

    $routes->get('informes', 'Informes::index');
    $routes->get('informes/preasientos', 'Informes::preasientos');
    $routes->post('informes/preasientos', 'Informes::armarPreasientos');
    $routes->post('informes/preasientos/cerrar', 'Informes::cerrarPreasientos');
    $routes->post('informes/preasientos/contabilizar', 'Informes::contabilizar');
    $routes->get('informes/contabilizados', 'Informes::contabilizados');
    $routes->get('informes/(:segment)', 'Informes::ver/$1');

    $routes->get('rendiciones', 'Rendiciones::index');
    $routes->get('rendiciones/nueva', 'Rendiciones::nueva');
    $routes->post('rendiciones', 'Rendiciones::crear');
    $routes->post('rendiciones/(:num)/agregar', 'Rendiciones::agregar/$1');
    $routes->post('rendiciones/(:num)/quitar/(:num)', 'Rendiciones::quitar/$1/$2');
    $routes->post('rendiciones/(:num)/enviar', 'Rendiciones::enviar/$1');
    $routes->post('rendiciones/(:num)/aceptar', 'Rendiciones::aceptar/$1');
    $routes->post('rendiciones/(:num)/rechazar', 'Rendiciones::rechazar/$1');
    $routes->post('rendiciones/(:num)/sumar', 'Rendiciones::sumar/$1');
    $routes->post('rendiciones/(:num)/eliminar', 'Rendiciones::eliminar/$1');
    $routes->get('rendiciones/(:num)', 'Rendiciones::ver/$1');

    $routes->get('parametros', 'Parametros::index');
    $routes->get('parametros/cuentas', 'Parametros::ver/cuentas');
    $routes->get('parametros/clasificacion', 'Parametros::ver/clasificacion');
    $routes->get('parametros/conexiones', 'Parametros::ver/conexiones');
    $routes->get('parametros/flujo', 'Parametros::ver/flujo');
    $routes->get('parametros/tarjetas', 'Parametros::ver/tarjetas');
    $routes->get('parametros/aceptados', 'Parametros::ver/aceptados');
    $routes->get('parametros/tipos', 'Parametros::ver/tipos');
    $routes->get('parametros/preasientos', 'Parametros::ver/preasientos');
    $routes->get('parametros/lugares', 'Parametros::ver/lugares');
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
    $routes->post('parametros/preasientos', 'Parametros::preasiento');
    $routes->post('parametros/lugares', 'Parametros::lugar');
    $routes->post('parametros/lugares/quitar', 'Parametros::quitarLugar');
    $routes->post('parametros/tipos/(:num)', 'Parametros::actualizarTipo/$1');

    $routes->get('categorias', 'Categorias::index');
    $routes->post('categorias', 'Categorias::crear');
    $routes->post('categorias/(:num)/eliminar', 'Categorias::eliminar/$1');
    $routes->post('categorias/(:num)', 'Categorias::actualizar/$1');
});
