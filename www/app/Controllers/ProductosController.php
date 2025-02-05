<?php

declare(strict_types=1);

namespace Com\Daw2\Controllers;

use Com\Daw2\Core\BaseController;
use Com\Daw2\Libraries\Respuesta;
use Com\Daw2\Models\CategoriaModel;
use Com\Daw2\Models\ProductosModel;
use Com\Daw2\Models\ProveedorModel;
use Com\Daw2\Traits\RestController;
use Decimal\Decimal;

class ProductosController extends BaseController
{
    use RestController;

    public function list(): void
    {
        $filtros = [];
        if (isset($_GET['codigo'])) {
            $filtros['codigo'] = '%' . $_GET['codigo'] . '%';
        }
        if (isset($_GET['nombre'])) {
            $filtros['nombre'] = '%' . $_GET['nombre'] . '%';
        }
        if (isset($_GET['categoria'])) {
            $filtros['categoria'] = $_GET['categoria'];
        }
        if (isset($_GET['proveedor'])) {
            $filtros['proveedor'] = $_GET['proveedor'];
        }
        if (isset($_GET['stock_min']) && filter_var($filtros['stock_min'], FILTER_VALIDATE_FLOAT)) {
            $filtros['stock_min'] = new Decimal($_GET['stock_min']);
        }
        if (isset($_GET['stock_max']) && filter_var($filtros['stock_max'], FILTER_VALIDATE_FLOAT)) {
            $filtros['stock_max'] = new Decimal($_GET['stock_max']);
        }

        $model = new ProductosModel();
        $data = $model->getProducts($filtros);
        $respuesta = new Respuesta(200, $data);

        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function get($codigo)
    {
        $model = new ProductosModel();
        $producto = $model->find($codigo);
        if ($producto === false) {
            $repuesta = new Respuesta(404, ['Mensaje' => 'Producto no encontrado']);
        } else {
            $repuesta = new Respuesta(200, $producto);
        }
        $this->view->show('json.view.php', ['respuesta' => $repuesta]);
    }

    public function delete($codigo)
    {
        $model = new ProductosModel();
        $row = $model->delete($codigo);
        if ($row === false) {
            $repuesta = new Respuesta(404, ['Mensaje' => 'No se ha podido eliminar el producto']);
        } else {
            $repuesta = new Respuesta(200, ['Mensaje' => 'Producto eliminado correctamente']);
        }
        $this->view->show('json.view.php', ['respuesta' => $repuesta]);
    }

    public function insert()
    {
        $errors = $this->checkErrors($_POST);
        if (empty($errors)) {
            $model = new ProductosModel();
            $res = $model->insert($_POST);
            if ($res === false) {
                $respuesta = new Respuesta(500, ['Mensaje' => 'No se ha podido agregar el producto']);
            } else {
                $respuesta = new Respuesta(200, ['url' => $_ENV['base.url'] . '/productos/' . $res]);
            }
        } else {
            $respuesta = new Respuesta(400, $errors);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function update($codigo)
    {
        $model = new ProductosModel();
        $row = $model->find($codigo);
        if ($row === false) {
            $respuesta = new Respuesta(404, ['Mensaje' => 'Producto no encontrado']);
        } else {
            $_put = $this->getParams();
            $errors = $this->checkErrors($_put, $codigo);
            if (empty($errors)) {
                $res = $model->update($_put, $codigo);
                if ($res === false) {
                    $respuesta = new Respuesta(500, ['Mensaje' => 'No se ha podido actualizar el producto']);
                } else {
                    $respuesta = new Respuesta(200, ['url' => $_ENV['base.url'] . '/productos/' . $res]);
                }
            } else {
                $respuesta = new Respuesta(400, $errors);
            }
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    private function checkErrors($data, $oldcode = ''): array
    {
        $errors = [];

        //Si el codigo antiguo no exite, o si el código antiguo es distinto del nuevo código se hacen las comprobaciones
        //sino no, pues se supone que ese código ya fue validado en su momento
        if ($oldcode === '' || $oldcode != $data['codigo']) {
            if (empty($data['codigo'])) {
                $errors['codigo'] = "El codigo es obligatorio";
            } elseif (!preg_match("/^[a-zA-Z]{3}\d{7}$/", $data['codigo'])) {
                $errors['codigo'] = "El codigo no es válido, debe tener 3 letras y despues 7 digitos";
            } else {
                $model = new ProductosModel();
                $producto = $model->find($data['codigo']);
                if ($producto !== false) {
                    $errors['codigo'] = "El código ya existe";
                }
            }
        }

        if (empty($data['nombre'])) {
            $errors['nombre'] = "El nombre es obligatorio";
        } elseif (mb_strlen($data['nombre']) > 255) {
            $errors['nombre'] = "El nombre debe tener 255 caracteres como máximo";
        } elseif (!preg_match("/^\w+ - \w+$/", $data['nombre'])) {
            $errors['nombre'] = "El nombre debe estar formado por abc - abc, donde abc pueden ser letras o números";
        }

        if (empty($data['descripcion'])) {
            $errors['descripcion'] = "La descripcion es obligatoria";
        } elseif (mb_strlen($data['descripcion']) > 255) {
            $errors['descripcion'] = "La descripción no puede tener más de 255 caracteres";
        }

        if (!filter_var($data['categoria'], FILTER_VALIDATE_INT)) {
            $errors['categoria'] = "La categoria no es válida";
        } else {
            $categoriaModel = new CategoriaModel();
            $categoria = $categoriaModel->find((int)$data['categoria']);
            if ($categoria === false) {
                $errors['categoria'] = "La categoria no es válida";
            }
        }

        if (!preg_match("/^[A-Z][0-9]{7}[A-Z]$/", $data['proveedor'])) {
            $errors['proveedor'] = "El proveedor no es válido";
        } else {
            $proveedorModel = new ProveedorModel();
            $proveedor = $proveedorModel->getByCif($data['proveedor']);
            if ($proveedor === false) {
                $errors['proveedor'] = "El proveedor no es válido";
            }
        }

        if (!filter_var($data['coste'], FILTER_VALIDATE_FLOAT)) {
            $errors['coste'] = "El coste no es válido";
        }

        if (!filter_var($data['margen'], FILTER_VALIDATE_FLOAT)) {
            $errors['margen'] = "El margen no es válido";
        }

        if (!filter_var($data['stock'], FILTER_VALIDATE_INT)) {
            $errors['stock'] = "El stock debe ser un número entero";
        }

        if (!filter_var($data['iva'], FILTER_VALIDATE_INT)) {
            $errors['iva'] = "El iva debe ser un número entero";
        }
        return $errors;
    }
}
