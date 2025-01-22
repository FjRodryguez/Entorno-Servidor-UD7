<?php

namespace Com\Daw2\Core;

use Com\Daw2\Controllers\CategoriaController;
use Com\Daw2\Models\CategoriaModel;
use Steampixel\Route;

class FrontController
{
    public static function main()
    {
        //Rutas de API
        Route::add(
            '/categorias',
            function () {
                $controlador = new CategoriaController();
                $controlador->listadoAPI();
            },
            'get'
        );

        Route::add(
            '/categorias/(\p{N}+)',
            fn($id) => (new CategoriaController())->get((int)$id),
            'get'
        );

        Route::add(
            '/categorias/new',
            function () {
                $controlador = new CategoriaController();
                $controlador->insert();
            },
            'post'
        );

        Route::add(
            '/categorias/(\p{N}+)',
            function ($id) {
                $controlador = new CategoriaController();
                $controlador->update((int)$id);
            },
            'put'
        );

        Route::add(
            '/categorias/(\p{N}+)',
            fn($id) => (new CategoriaController())->delete((int)$id),
            'delete'
        );

        Route::pathNotFound(
            function () {
            }
        );

        Route::methodNotAllowed(
            function () {
            }
        );

        Route::run();
    }
}
