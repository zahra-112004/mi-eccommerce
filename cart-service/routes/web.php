<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () {
    return response()->json(['message' => 'Cart Service Aktif']);
});

$router->get('/cart', 'CartController@index');
$router->post('/cart', 'CartController@store');
$router->get('/cart/{id}', 'CartController@show');
$router->put('/cart/{id}', 'CartController@update');
$router->delete('/cart/{id}', 'CartController@destroy');
