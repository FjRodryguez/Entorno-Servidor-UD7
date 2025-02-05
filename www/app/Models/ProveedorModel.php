<?php

declare(strict_types=1);

namespace Com\Daw2\Models;

use Com\Daw2\Core\BaseDbModel;
use phpseclib3\Exception\FileNotFoundException;

class ProveedorModel extends BaseDbModel
{
    public const ORDER_COLUMNS = ['cif', 'codigo', 'nombre', 'pais'];
    private const SELECT_FROM = "SELECT pr.*, count(p.codigo) as total_productos_proveedor FROM proveedor pr left join producto p on pr.cif = p.proveedor";
    private const GROUP_BY = "GROUP BY pr.cif";

    public function getProveedores($filtros, $order, $sentido, $page): array
    {
        $registros = $this->getCount($filtros);
        $page = $this->checkPage($page, $registros);
        $inicio = ($page - 1) * $_ENV['data.per.page'];

        if (!empty($filtros)) {
            $condiciones = $this->getCondiciones($filtros);
            $where = empty($condiciones['where']) ? '' : ' WHERE ' . implode(" AND ", $condiciones['where']);
            $having = empty($condiciones['having']) ? '' : ' HAVING ' . implode(" AND ", $condiciones['having']);
            $sql = self::SELECT_FROM . $where . " " . self::GROUP_BY . $having .
                " order by " . self::ORDER_COLUMNS[$order - 1] . " " . $sentido .
                " LIMIT " . $inicio . "," . $_ENV['data.per.page'];
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filtros);
            return $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->prepare(self::SELECT_FROM . " " . self::GROUP_BY .
                " order by " . self::ORDER_COLUMNS[$order - 1] . " " . $sentido .
                " LIMIT " . $inicio . "," . $_ENV['data.per.page']);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
    }

    public function insert($data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO proveedor (cif, codigo, nombre, direccion, website, pais, email, telefono) VALUES (:cif, :codigo, :nombre_proveedor, :direccion, :website, :pais, :email, :telefono)");
        $row = $stmt->execute($data);
        if ($row !== false) {
            return $data['cif'];
        }
        return false;
    }

    public function delete($cif)
    {
        $stmt = $this->pdo->prepare("DELETE FROM proveedor WHERE cif = :cif");
        $stmt->execute(['cif' => $cif]);
        return $stmt->rowCount() === 1;
    }

    public function patch($data, $oldCif)
    {
        $cambios = $this->getCambios($data);
        $data['oldCif'] = $oldCif;
        $stmt = $this->pdo->prepare("UPDATE proveedor set " . implode(", ", $cambios) . " WHERE cif = :oldCif");
        $row = $stmt->execute($data);
        if ($row !== false) {
            return isset($data['cif']) ? $data['cif'] : $oldCif;
        }
        return false;
    }

    public function getProveedor(string $cif)
    {
        $proveedor = $this->getByCif($cif);
        if ($proveedor === false) {
            return null;
        }
        $stmt = $this->pdo->prepare("SELECT codigo, nombre, descripcion, coste, margen, iva, stock FROM producto WHERE proveedor = :cif");
        $stmt->execute(['cif' => $cif]);
        $proveedor['productos'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $proveedor;
    }

    public function getByCif(string $cif)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM proveedor WHERE cif = :cif");
        $stmt->execute(['cif' => $cif]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getByCodigo($codigo)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM proveedor WHERE codigo = :codigo");
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    private function getCount($filtros)
    {
        if (!empty($filtros)) {
            $condiciones = $this->getCondiciones($filtros);
            $where = empty($condiciones['where']) ? '' : ' WHERE ' . implode(" AND ", $condiciones['where']);
            $having = empty($condiciones['having']) ? '' : ' HAVING ' . implode(" AND ", $condiciones['having']);
            $sql = "SELECT COUNT(*) FROM (" . self::SELECT_FROM . $where . " " . self::GROUP_BY . " " . $having . ") AS subconsulta";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($filtros);
            return $stmt->fetchColumn(0);
        } else {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM (" . self::SELECT_FROM . " " . self::GROUP_BY . ") AS subconsulta");
            $stmt->execute();
            return $stmt->fetchColumn(0);
        }
    }

    private function getCondiciones($filtros): array
    {
        $condiciones = [
            'where' => [],
            'having' => []
        ];

        if (isset($filtros['cif'])) {
            $condiciones['where'][] = "pr.cif like :cif";
        }
        if (isset($filtros['nombre'])) {
            $condiciones['where'][] = "pr.nombre like :nombre";
        }
        if (isset($filtros['codigo'])) {
            $condiciones['where'][] = "pr.codigo like :codigo";
        }
        if (isset($filtros['email'])) {
            $condiciones['where'][] = "pr.email like :email";
        }
        if (isset($filtros['pais'])) {
            $condiciones['where'][] = "pr.pais = :pais";
        }
        if (isset($filtros['total_productos_min'])) {
            $condiciones['having'][] = "total_productos_proveedor >= :total_productos_min";
        }
        if (isset($filtros['total_productos_max'])) {
            $condiciones['having'][] = "total_productos_proveedor <= :total_productos_max";
        }

        return $condiciones;
    }

    private function getCambios($data)
    {
        $cambios = [];
        if (isset($data['cif'])) {
            $cambios[] = 'cif = :cif';
        }
        if (isset($data['codigo'])) {
            $cambios[] = 'codigo = :codigo';
        }
        if (isset($data['nombre_proveedor'])) {
            $cambios[] = 'nombre = :nombre_proveedor';
        }
        if (isset($data['direccion'])) {
            $cambios[] = 'direccion = :direccion';
        }
        if (isset($data['website'])) {
            $cambios[] = 'website = :website';
        }
        if (isset($data['pais'])) {
            $cambios[] = 'pais = :pais';
        }
        if (isset($data['email'])) {
            $cambios[] = 'email = :email';
        }
        if (isset($data['telefono']) || $data['telefono'] === null) {
            $cambios[] = 'telefono = :telefono';
        }
        return $cambios;
    }

    private function checkPage($page, $registros)
    {
        $paginas = ceil($registros / $_ENV['data.per.page']);
        if ($page > $paginas) {
            return 1;
        } else {
            return $page;
        }
    }
}
