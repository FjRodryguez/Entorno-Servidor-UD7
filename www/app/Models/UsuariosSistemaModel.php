<?php

declare(strict_types=1);

namespace Com\Daw2\Models;

use Com\Daw2\Core\BaseDbModel;

class UsuariosSistemaModel extends BaseDbModel
{

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuario_sistema WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
}