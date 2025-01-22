<?php

declare(strict_types=1);

namespace Com\Daw2\Models;

use Com\Daw2\Core\BaseDbModel;
use PDO;

class CategoriaModel extends BaseDbModel
{
    private const ORDER_STRING = ' ORDER BY nombre_categoria ASC';

    public function get(array $filtros = []): array
    {
        $sql = 'SELECT * FROM categoria';
        $condiciones = [];
        $variables = [];
        if (!empty($filtros['nombre_categoria'])) {
            $condiciones[] = 'nombre_categoria LIKE :nombre_categoria';
            $variables['nombre_categoria'] = "%" . $filtros['nombre_categoria'] . "%";
        }
        if ($condiciones === []) {
            $stmt = $this->pdo->query($sql . self::ORDER_STRING);
        } else {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones) . self::ORDER_STRING;
            $stmt = $this->pdo->prepare($sql);
        }
        $stmt->execute($variables);
        return $stmt->fetchAll();
    }

    public function find(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM categoria WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }


    public function insert(array $datos): false|int
    {
        $stmt = $this->pdo->prepare('INSERT INTO categoria (nombre_categoria, id_padre) VALUES (:nombre_categoria, :id_padre)');
        $stmt->execute($datos);
        $id = $this->pdo->lastInsertId();
        if ($id !== false)
        {
            return (int)$id;
        }
        return false;
    }

    public function update(int $id, array $datos): bool
    {
        $stmt = $this->pdo->prepare('UPDATE categoria SET nombre_categoria = :nombre_categoria, id_padre = :id_padre WHERE id_categoria = :id');
        $datos['id'] = $id;
        return $stmt->execute($datos);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM categoria WHERE id_categoria = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1;
    }
}
