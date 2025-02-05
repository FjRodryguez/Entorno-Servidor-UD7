<?php

namespace Com\Daw2\Core;

use Ahc\Jwt\JWT;
use Ahc\Jwt\JWTException;
use Com\Daw2\Controllers\CategoriaController;
use Com\Daw2\Controllers\ErrorController;
use Com\Daw2\Controllers\ProductosController;
use Com\Daw2\Controllers\ProveedoresController;
use Com\Daw2\Controllers\UsuariosSistemaController;
use Com\Daw2\Helpers\JwtTool;
use Com\Daw2\Libraries\Permisos;
use Com\Daw2\Libraries\Respuesta;
use Com\Daw2\Models\RolModel;
use Com\Daw2\Models\UsuariosSistemaModel;
use Google\Service\Compute\Router;
use Steampixel\Route;

class FrontController
{
    private static ?array $jwtData = null;
    private static array $permisos = [];

    public static function main()
    {
        if (JwtTool::getBearerToken()) {
            try {
                $bearer = JwtTool::getBearerToken();
                $jwt = new JWT($_ENV['service.secret']);
                self::$jwtData = $jwt->decode($bearer);
                self::$permisos = UsuariosSistemaController::getPermisos((int)self::$jwtData['id_rol']);
            } catch (JWTException $e) {
                $controller = new ErrorController();
                $controller->errorWithBody(403, ['mensaje' => $e->getMessage()]);
                die;
            }
        } else {
            self::$permisos = UsuariosSistemaController::getPermisos();
        }

        Route::add(
            '/categorias',
            function () {
                if (str_contains(self::$permisos['categorias'], 'r')) {
                    (new CategoriaController())->listadoAPI();
                } else {
                    http_response_code(403);
                }
            },
            'get'
        );
        Route::add(
            '/categorias/(\p{N}+)',
            function ($id) {
                if (str_contains(self::$permisos['categorias'], 'r')) {
                    (new CategoriaController())->get((int)$id);
                } else {
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/categorias/new',
            function () {
                if (str_contains(self::$permisos['categorias'], 'w')) {
                    (new CategoriaController())->insert();
                } else {
                    http_response_code(403);
                }
            },
            'post'
        );
        Route::add(
            '/categorias/(\p{N}+)',
            function ($id) {
                if (str_contains(self::$permisos['categorias'], 'w')) {
                    (new CategoriaController())->update($id);
                } else {
                    http_response_code(403);
                }
            },
            'put'
        );

        Route::add(
            '/categorias/(\p{N}+)',
            function ($id) {
                if (str_contains(self::$permisos['categorias'], 'd')) {
                    (new CategoriaController())->delete((int)$id);
                } else {
                    http_response_code(403);
                }
            },
            'delete'
        );

        Route::add(
            '/productos',
            function () {
                if (str_contains(self::$permisos['productos'], 'r')) {
                    (new ProductosController())->list();
                } else {
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/productos/([a-zA-Z]{3}\d{7})',
            function ($codigo) {
                if (str_contains(self::$permisos['productos'], 'r')) {
                    (new ProductosController())->get($codigo);
                } else {
                    http_response_code(403);
                }
            },
            'get'
        );

        Route::add(
            '/productos/new',
            function () {
                if (str_contains(self::$permisos['productos'], 'w')) {
                    (new ProductosController())->insert();
                } else {
                    http_response_code(403);
                }
            },
            'post'
        );

        Route::add(
            '/productos/([a-zA-Z]{3}\d{7})',
            function ($codigo) {
                if (str_contains(self::$permisos['productos'], 'w')) {
                    (new ProductosController())->update($codigo);
                } else {
                    http_response_code(403);
                }
            },
            'put'
        );

        Route::add(
            '/productos/([a-zA-Z]{3}\d{7})',
            function ($codigo) {
                if (str_contains(self::$permisos['productos'], 'd')) {
                    (new ProductosController())->delete($codigo);
                } else {
                    http_response_code(403);
                }
            },
            'delete'
        );

        Route::add(
            '/proveedores',
            function () {
                    (new ProveedoresController())->list();
            },
            'get'
        );

        Route::add(
            '/proveedores/([A-Z]\d{7}[A-Z])',
            function ($cif) {
                (new ProveedoresController())->getById($cif);
            },
            'get'
        );

        Route::add(
            '/proveedores/new',
            function () {
                (new ProveedoresController())->insert();
            },
            'post'
        );

        Route::add(
            '/proveedores/([A-Z]\d{7}[A-Z])',
            function ($cif) {
                (new ProveedoresController())->delete($cif);
            },
            'delete'
        );

        Route::add(
            '/proveedores/([A-Z]\d{7}[A-Z])',
            function ($cif) {
                (new ProveedoresController())->patch($cif);
            },
            'patch'
        );

        Route::add(
            '/test',
            fn() => throw new \Exception(),
            'get'
        );

        Route::add(
            '/login',
            fn() => (new UsuariosSistemaController())->login(),
            'post'
        );

        Route::pathNotFound(
            function () {
                http_response_code(404);
            }
        );

        Route::methodNotAllowed(
            function () {
                http_response_code(405);
            }
        );

        Route::run();
    }
}
