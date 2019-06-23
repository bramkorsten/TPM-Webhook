<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix'=>'api/v1'], function() use($router){
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });

    // Routing for all routes related to packages
    $router->group(['prefix'=>'packages'], function() use($router){
        $router->get('/', 'PackageController@index');
        $router->post('/', 'PackageController@create');
        $router->get('/{id}', 'PackageController@show');
        $router->put('/{id}', 'PackageController@update');
        $router->delete('/{id}', 'PackageController@destroy');

        // Routing for versions
        $router->get('/{id}/versions', 'versionController@index');
        $router->get('/{id}/versions/latest', 'versionController@latest');
        $router->post('/{id}/versions', 'versionController@create');
    });

});
