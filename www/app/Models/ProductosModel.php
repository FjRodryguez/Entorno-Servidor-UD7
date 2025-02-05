<?php

declare(strict_types=1);

namespace Com\Daw2\Models;

use Com\Daw2\Core\BaseDbModel;

class ProductosModel extends BaseDbModel
{
    private const SELECT_FROM = "SELECT p.*, pr.nombre as nombre_proveedor, c.nombre_categoria FROM producto p join proveedor pr on p.proveedor = pr.cif join categoria c using(id_categoria)";

    public function getProducts($filtros)
    {
        if (!empty($filtros)) {
            $condiciones = $this->getCondiciones($filtros);
            $stmt = $this->pdo->prepare(self::SELECT_FROM . " WHERE " . implode(" AND ", $condiciones));
            $stmt->execute($filtros);
            return $stmt->fetchAll();
        } else {
            $stmt = $this->pdo->prepare(self::SELECT_FROM);
            $stmt->execute();
            return $stmt->fetchAll();
        }
    }

    public function find($codigo)
    {
        $stmt = $this->pdo->prepare(self::SELECT_FROM . " WHERE p.codigo = :codigo");
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->fetch();
    }

    public function delete($codigo)
    {
        $stmt = $this->pdo->prepare("DELETE FROM producto WHERE codigo = :codigo");
        $stmt->execute(['codigo' => $codigo]);
        return $stmt->rowCount() === 1;
    }

    public function insert($data)
    {
        $stmt = $this->pdo->prepare("INSERT INTO producto (codigo, nombre, descripcion, proveedor, coste, margen, stock, iva, id_categoria) VALUES (:codigo, :nombre, :descripcion, :proveedor, :coste, :margen, :stock, :iva, :categoria)");
        $row = $stmt->execute($data);
        if ($row !== false) {
            return $data['codigo'];
        }
        return false;
    }

    public function update($data, $oldCodigo)
    {
        $stmt = $this->pdo->prepare("UPDATE producto set codigo = :codigo, nombre = :nombre, descripcion = :descripcion, proveedor = :proveedor, coste = :coste, margen=:margen, stock = :stock, iva = :iva, id_categoria = :categoria WHERE codigo = :oldCodigo");
        $data['oldCodigo'] = $oldCodigo;
        $row = $stmt->execute($data);
        if ($row !== false) {
            return $data['codigo'];
        }
        return false;
    }


    private function getCondiciones($filtros)
    {
        $condiciones = [];
        if (isset($filtros['codigo'])) {
            $condiciones[] = 'p.codigo LIKE :codigo';
        }
        if (isset($filtros['nombre'])) {
            $condiciones[] = 'p.nombre LIKE :nombre';
        }
        if (isset($filtros['categoria'])) {
            $condiciones[] = 'c.id_categoria = :categoria';
        }
        if (isset($filtros['proveedor'])) {
            $condiciones[] = 'pr.cif = :proveedor';
        }
        if (isset($filtros['stock_min'])) {
            $condiciones[] = 'p.stock >= :stock_min';
        }
        if (isset($filtros['stock_max'])) {
            $condiciones[] = 'p.stock <= :stock_max';
        }
        return $condiciones;
    }
}
