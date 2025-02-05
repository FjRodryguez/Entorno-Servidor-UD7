<?php

declare(strict_types=1);

namespace Com\Daw2\Controllers;

use Com\Daw2\Core\BaseController;
use Com\Daw2\Libraries\Respuesta;
use Com\Daw2\Models\ProveedorModel;
use Com\Daw2\Traits\RestController;
use Google\Service\CloudSearch\RpcOptions;

class ProveedoresController extends BaseController
{
    use RestController;

    private const ORDER_DEFECTO = 1;

    private const SENTIDO_DEFECTO = 'asc';

    private const PAGE_DEFECTO = 1;

    private const COLUMNS = ['cif', 'codigo', 'nombre', 'direccion', 'website', 'pais', 'email', 'telefono'];

    public function list()
    {
        $filtros = $this->getFiltros($_GET);

        $order = $this->getOrder();
        $sentido = $this->getSentido();
        $page = $this->getPage();

        $model = new ProveedorModel();
        $proveedores = $model->getProveedores($filtros, $order, $sentido, $page);
        $respuesta = new Respuesta(200, $proveedores);
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function getById($cif)
    {
        $model = new ProveedorModel();
        $proveedor = $model->getProveedor($cif);
        if ($proveedor != null) {
            $respuesta = new Respuesta(200, $proveedor);
        } else {
            $respuesta = new Respuesta(404, ['mensaje' => 'Proveedor no encontrado']);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function insert()
    {
        $errors = $this->checkErrors($_POST);
        if (empty($errors)) {
            $model = new ProveedorModel();
            if ($_POST['telefono'] === '') {
                $_POST['telefono'] = null;
            }
            $res = $model->insert($_POST);
            if ($res !== false) {
                $respuesta = new Respuesta(200, ['url' => 'http://localhost:8085/proveedores/' . $res]);
            } else {
                $respuesta = new Respuesta(500, ['mensaje' => 'Error al crear el proveedor']);
            }
        } else {
            $respuesta = new Respuesta(400, $errors);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function delete($cif)
    {
        $model = new ProveedorModel();
        $proveedor = $model->getByCif($cif);
        if ($proveedor !== false) {
            $res = $model->delete($cif);
            if ($res !== false) {
                $respuesta = new Respuesta(200, ['mensaje' => 'Proveedor eliminado']);
            } else {
                $respuesta = new Respuesta(500, ['mensaje' => 'Error al eliminar el proveedor']);
            }
        } else {
            $respuesta = new Respuesta(404, ['mensaje' => 'Proveedor no encontrado']);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    public function patch($cif)
    {
        $model = new ProveedorModel();
        $proveedor = $model->getByCif($cif);
        if ($proveedor !== false) {
            $_put = $this->getParams();
            $errors = $this->checkErrors($_put);
            if (empty($errors)) {
                if (isset($_put['telefono']) && $_put['telefono'] === '') {
                    $_put['telefono'] = null;
                }
                $res = $model->patch($_put, $cif);
                if ($res !== false) {
                    $respuesta = new Respuesta(200, ['url' => 'http://localhost:8085/proveedores/' . $res]);
                } else {
                    $respuesta = new Respuesta(500, ['mensaje' => 'Error al actualizar el proveedor']);
                }
            } else {
                $respuesta = new Respuesta(400, $errors);
            }
        } else {
            $respuesta = new Respuesta(404, ['mensaje' => 'Proveedor no encontrado']);
        }
        $this->view->show('json.view.php', ['respuesta' => $respuesta]);
    }

    private function checkErrors(array $data): array
    {
        $errors = [];
        $model = new ProveedorModel();
        if (empty($data)) {
            $errors['patch'] = "Rellena al menos un campo";
        }
        if (isset($data['cif'])) {
            if (!preg_match('/^[A-Z]\d{7}[A-Z]$/', $data['cif'])) {
                $errors['cif'] = "El formato del CIF debe ser L1234567M";
            } else {
                $proveedor = $model->getByCif($data['cif']);
                if ($proveedor !== false) {
                    $errors['cif'] = "El CIF ya exite";
                }
            }
        }

        if (isset($data['codigo'])) {
            if (empty($data['codigo'])) {
                $errors['codigo'] = "El código es obligtorio";
            } elseif (mb_strlen($data['codigo']) > 10) {
                $errors['codigo'] = "El código no puede tener más de 10 caracteres";
            } else {
                $proveedor = $model->getByCodigo($data['codigo']);
                if ($proveedor !== false) {
                    $errors['codigo'] = "El codigo ya exite";
                }
            }
        }

        if (isset($data['nombre_proveedor'])) {
            if (empty($data['nombre_proveedor'])) {
                $errors['nombre_proveedor'] = "El nombre del proveedor es obligatorio";
            } elseif (mb_strlen($data['nombre_proveedor']) > 255) {
                $errors['nombre_proveedor'] = "El nombre del proveedor no puede tener más de 255 caracteres";
            }
        }

        if (isset($data['direccion'])) {
            if (empty($data['direccion'])) {
                $errors['direccion'] = "La dirección es obligatorio";
            } elseif (mb_strlen($data['direccion']) > 255) {
                $errors['direccion'] = "La dirección no puede tener más de 255 caracteres";
            }
        }

        if (isset($data['website'])) {
            if (!filter_var($data['website'], FILTER_VALIDATE_URL)) {
                $errors['website'] = "La website no tiene una URL válida";
            } elseif (mb_strlen($data['website']) > 255) {
                $errors['website'] = "La url no puede tener más de 255 caracteres";
            }
        }

        if (isset($data['pais'])) {
            if (empty($data['pais'])) {
                $errors['pais'] = "El país es obligatorio";
            } elseif (mb_strlen($data['pais']) > 100) {
                $errors['pais'] = "El país no puede tener más de 100 caracteres";
            }
        }

        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "El email no es válido";
            } elseif (mb_strlen($data['email']) > 255) {
                $errors['email'] = "El email no puede tener más de 2055 caracteres";
            }
        }

        if (isset($data['telefono'])) {
            if (!empty($data['telefono']) && !preg_match('/^\d{8,12}$/', $data['telefono'])) {
                $errors['telefono'] = "El teléfono debe tener entre 8 y 12 dígitos";
            }
        }

        return $errors;
    }

    private function getFiltros($data)
    {
        $filtros = [];

        if (isset($data['cif'])) {
            $filtros['cif'] = '%' . $data['cif'] . '%';
        }
        if (isset($data['nombre'])) {
            $filtros['nombre'] = '%' . $data['nombre'] . '%';
        }
        if (isset($data['codigo'])) {
            $filtros['codigo'] = '%' . $data['codigo'] . '%';
        }
        if (isset($data['email'])) {
            $filtros['email'] = '%' . $data['email'] . '%';
        }
        if (isset($data['pais'])) {
            $filtros['pais'] = $data['pais'];
        }
        if (isset($data['total_productos_min']) && filter_var($data['total_productos_min'], FILTER_VALIDATE_INT)) {
            $filtros['total_productos_min'] = (int)$data['total_productos_min'];
        }
        if (isset($data['total_productos_max']) && filter_var($data['total_productos_max'], FILTER_VALIDATE_INT)) {
            $filtros['total_productos_max'] = (int)$data['total_productos_max'];
        }

        return $filtros;
    }

    private function getOrder()
    {
        if (isset($_GET['order']) && filter_var($_GET['order'], FILTER_VALIDATE_INT)) {
            if (abs((int)$_GET['order']) <= count(ProveedorModel::ORDER_COLUMNS)) {
                return abs((int)$_GET['order']);
            }
        }
        return self::ORDER_DEFECTO;
    }

    private function getSentido()
    {
        if (isset($_GET['sentido'])) {
            if (mb_strtolower(trim($_GET['sentido'])) == 'desc') {
                return 'desc';
            }
        }
        return self::SENTIDO_DEFECTO;
    }

    private function getPage()
    {
        if (isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT)) {
            return abs((int)$_GET['page']);
        }
        return self::PAGE_DEFECTO;
    }
}
