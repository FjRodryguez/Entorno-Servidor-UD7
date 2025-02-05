<?php

declare(strict_types=1);

namespace Com\Daw2\Controllers;

use Com\Daw2\Core\BaseController;
use Com\Daw2\Libraries\Respuesta;
use Com\Daw2\Models\CategoriaModel;
use Com\Daw2\Traits\RestController;

class CategoriaController extends BaseController
{
    use RestController;

    public function listadoAPI(): void
    {
        $model = new CategoriaModel();
        $data = $model->get(['nombre_categoria' => $_GET['nombre_categoria'] ?? '']);
        $respuesta = new Respuesta(200, $data);
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function get(int $id): void
    {
        $model = new CategoriaModel();
        $row = $model->find($id);
        if ($row === false) {
            $respuesta = new Respuesta(404, ['mensaje' => 'Registro no encontrado']);
        } else {
            $respuesta = new Respuesta(200, $row);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function delete(int $id): void
    {
        $model = new CategoriaModel();
        try {
            $row = $model->delete($id);
            if ($row === false) {
                //Poner el mensaje es optativo
                $respuesta = new Respuesta(404, ['mensaje' => 'No se pudo eliminar el registro']);
            } else {
                $respuesta = new Respuesta(200, ['mensaje' => 'Registro eliminado']);
            }
        } catch (\PDOException $e) {
            //Si queremos desacoplar de base de datos entonces comprobamos si existen categorías que tienen como padre
            //actual antes de borrar
            if (isset($e->errorInfo[0]) && $e->errorInfo[0] == '23000') {
                $respuesta = new Respuesta(422, ['Mensaje' => 'No se puede eliminar una categoría que tenga categorías hijas']);
            } else {
                throw $e;
            }
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function insert()
    {
        $errors = $this->checkErrors($_POST);
        if ($errors === []) {
            $model = new CategoriaModel();
            $res = $model->insert(
                [
                    'id_padre' => (empty($_POST['id_padre']) ? null : (int)$_POST['id_padre']),
                    'nombre_categoria' => $_POST['nombre_categoria']
                ]
            );
            if ($res !== false) {
                $respuesta = new Respuesta(201, ['url' => $_ENV['base.url'] . '/categorias/' . $res]);
            } else {
                $respuesta = new Respuesta(500, ['mensaje' => 'Ha ocurrido un error']);
            }
        } else {
            $respuesta = new Respuesta(400, $errors);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function update(int $id)
    {
        $model = new CategoriaModel();
        $_put = $this->getParams();
        $errors = $this->checkErrors($_put);
        $categoria = $model->find($id);
        if ($categoria === false) {
            $respuesta = new Respuesta(404, ['mensaje' => 'La categoria no existe']);
        } else {
            if ($errors === []) {
                if (
                    $model->update(
                        $id,
                        [
                            'id_padre' => $_put['id_padre'] ?? null,
                            'nombre_categoria' => $_put['nombre_categoria']
                        ]
                    )
                ) {
                    $respuesta = new Respuesta(200);
                } else {
                    $respuesta = new Respuesta(500, ['mensaje' => 'Ha ocurrido un error']);
                }
            } else {
                $respuesta = new Respuesta(400, $errors);
            }
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    private function checkErrors(array $data)
    {
        $errors = [];
        if (empty($data['nombre_categoria'])) {
            $errors['nombre_categoria'] = 'Campo requerido';
        } elseif (!preg_match('/^\p{L}[\p{L}\p{N} ]*[\p{L}\p{N}]$/iu', $data['nombre_categoria'])) {
            $errors['nombre_categoria'] = 'El nombre de la categorías sólo puede empezar y acabar con letra y contener letras, espacios y números';
        }

        if (!empty($data['id_padre'])) {
            if (filter_var($data['id_padre'], FILTER_VALIDATE_INT) === false) {
                $errors['id_padre'] = 'Debe ser un valor entero o null';
            } else {
                $model = new CategoriaModel();
                $padre = $model->find((int)$data['id_padre']);
                if ($padre === false) {
                    $errors['id_padre'] = 'Categoria no encontrada';
                }
            }
        }
        return $errors;
    }
}
