<?php

declare(strict_types=1);

namespace Com\Daw2\Controllers;

use Ahc\Jwt\JWT;
use Com\Daw2\Core\BaseController;
use Com\Daw2\Libraries\Respuesta;
use Com\Daw2\Models\UsuariosSistemaModel;

class UsuariosSistemaController extends BaseController
{
    private const ROLES = ['rol_admin' => 1, 'rol_encargado' => 2, 'rol_staff' => 3];
    private const TABLAS = ['categorias', 'productos'];

    public function login()
    {
        $errors = $this->checkErrors($_POST);
        if (empty($errors)) {
            $model = new UsuariosSistemaModel();
            $user = $model->findByEmail($_POST['email']);
            if ($user !== false) {
                if (password_verify($_POST['pass'], $user['pass'])) {
                    //Hacemos playload con los datos del usuario
                    $playload = [
                        'id_usuario' => $user['id_usuario'],
                        'nombre' => $user['nombre'],
                        'id_rol' => $user['id_rol'],
                        'idioma' => $user['idioma'],
                    ];
                    //Creamos el JWT correspondiente, le pasamos la clave secreta, el algoritmo de encriptacion
                    // y la duración del token
                    //El secret lo creamos nosotros, como una cadena aleatoria
                    $jwt = new JWT($_ENV['service.secret'], 'HS256', 1800);
                    //Codificamos el playload
                    $token = $jwt->encode($playload);
                    //Devolvemos el token
                    $respuesta = new Respuesta(200, ['token' => $token]);
                } else {
                    $respuesta = new Respuesta(403, ['Mensaje' => 'Datos incorrectos']);
                }
            } else {
                $respuesta = new Respuesta(403, ['Mensaje' => 'Datos incorrectos']);
            }
        } else {
            $respuesta = new Respuesta(400, $errors);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public static function getPermisos(int $id_rol = -1)
    {
        $permisos = [];

        foreach (self::TABLAS as $tabla) {
            $permisos[$tabla] = match ($id_rol) {
                self::ROLES['rol_admin'] => 'rwd',
                self::ROLES['rol_encargado'] => 'r',
                self::ROLES['rol_staff'] => '',
                default => '',
            };
        }

        return $permisos;
    }


    public function checkErrors($data)
    {
        $errors = [];

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "El email no es válido";
        }
        if (empty($data['pass'])) {
            $errors['pass'] = "La contraseña es requerida";
        }

        return $errors;
    }
}
